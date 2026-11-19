<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\ai_knowledge;

use crmeb\exceptions\AdminException;

/**
 * 知识库文档解析服务
 * Class DocumentParserServices
 * @package app\services\ai_knowledge
 */
class DocumentParserServices
{
    /**
     * 支持的文件格式
     * @var array
     */
    public static $allowExt = ['txt', 'md', 'markdown', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'pdf'];

    /**
     * 解析文件为纯文本
     * @param string $filePath 服务器绝对路径
     * @param string $ext 文件后缀
     * @return string
     * @throws \Exception
     */
    public function parse(string $filePath, string $ext): string
    {
        if (!is_file($filePath)) {
            throw new \Exception('文件不存在');
        }
        $ext = strtolower($ext);
        switch ($ext) {
            case 'txt':
            case 'md':
            case 'markdown':
            case 'csv':
                return $this->clean($this->readTextFile($filePath));
            case 'docx':
                return $this->clean($this->readDocx($filePath));
            case 'xlsx':
                return $this->clean($this->readXlsx($filePath));
            case 'doc':
                return $this->clean($this->runCommand('catdoc -w -d utf-8 ' . escapeshellarg($filePath)));
            case 'xls':
                return $this->clean($this->runCommand('xls2csv -d utf-8 -c " " ' . escapeshellarg($filePath)));
            case 'pdf':
                return $this->clean($this->runCommand('pdftotext -enc UTF-8 -layout ' . escapeshellarg($filePath) . ' -'));
            default:
                throw new \Exception('暂不支持该文件格式：' . $ext);
        }
    }

    /**
     * 读取纯文本文件（自动转 UTF-8）
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    protected function readTextFile(string $filePath): string
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            throw new \Exception('文件读取失败');
        }
        // 去除 BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'GBK,GB2312,BIG5,UTF-8');
        }
        return $content;
    }

    /**
     * 解析 docx
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    protected function readDocx(string $filePath): string
    {
        if (!class_exists('\ZipArchive')) {
            throw new \Exception('服务器未安装 zip 扩展，无法解析 docx');
        }
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('word 文件解析失败');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            throw new \Exception('word 文件内容为空或格式不正确');
        }
        $xml = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml);
        $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml);
        $xml = str_replace('</w:p>', "\n", $xml);
        $text = strip_tags($xml);
        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * 解析 xlsx
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    protected function readXlsx(string $filePath): string
    {
        if (!class_exists('\ZipArchive')) {
            throw new \Exception('服务器未安装 zip 扩展，无法解析 xlsx');
        }
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('excel 文件解析失败');
        }
        // 共享字符串
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $xml = @simplexml_load_string($sharedXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    $shared[] = $this->nodeText($si);
                }
            }
        }
        // 第一个工作表
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, 'xl/worksheets/sheet') === 0 && substr($name, -4) === '.xml') {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }
        $zip->close();
        if ($sheetXml === false || $sheetXml === '') {
            throw new \Exception('excel 文件内容为空或格式不正确');
        }
        $xml = @simplexml_load_string($sheetXml);
        if (!$xml) {
            throw new \Exception('excel 文件解析失败');
        }
        $lines = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $type = (string)$c['t'];
                $value = (string)$c->v;
                if ($type === 's') {
                    $value = $shared[(int)$value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = $this->nodeText($c->is);
                }
                $cells[] = trim($value);
            }
            if (implode('', $cells) !== '') {
                $lines[] = implode("\t", $cells);
            }
        }
        return implode("\n", $lines);
    }

    /**
     * 获取 xml 节点的纯文本
     * @param \SimpleXMLElement $node
     * @return string
     */
    protected function nodeText($node): string
    {
        $xml = $node->asXML();
        if ($xml === false) {
            return '';
        }
        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * 执行命令行提取文本
     * @param string $command
     * @return string
     * @throws \Exception
     */
    protected function runCommand(string $command): string
    {
        $output = [];
        $code = 0;
        @exec($command . ' 2>&1', $output, $code);
        $text = implode("\n", $output);
        if ($code !== 0 && trim($text) === '') {
            throw new \Exception('文档内容提取失败，请检查服务器是否安装对应解析工具');
        }
        return $text;
    }

    /**
     * 文本清洗
     * @param string $text
     * @return string
     */
    protected function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text);
        $text = preg_replace('/[ \t]+\n/u', "\n", $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        return trim($text);
    }
}
