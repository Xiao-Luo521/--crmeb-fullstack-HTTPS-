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
use think\facade\Db;

/**
 * 知识库文件服务
 * Class KnowledgeFileServices
 * @package app\services\ai_knowledge
 */
class KnowledgeFileServices
{
    /**
     * 分块大小（字符数）
     * @var int
     */
    protected $chunkSize = 600;

    /**
     * 向量化每批数量
     * @var int
     */
    protected $embedBatch = 10;

    /**
     * 文件列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getList(array $where, int $page, int $limit): array
    {
        $query = Db::name('ai_knowledge_file')->alias('f')
            ->leftJoin('ai_knowledge_category c', 'f.cat_id = c.id')
            ->field('f.id,f.cat_id,f.name,f.file_type,f.file_size,f.char_count,f.chunk_count,f.parse_status,f.parse_msg,f.embed_status,f.create_time,c.name as cat_name')
            ->when(!empty($where['cat_id']), function ($query) use ($where) {
                $query->where('f.cat_id', (int)$where['cat_id']);
            })->when(!empty($where['name']), function ($query) use ($where) {
                $query->whereLike('f.name', '%' . $where['name'] . '%');
            })->when(isset($where['parse_status']) && $where['parse_status'] !== '', function ($query) use ($where) {
                $query->where('f.parse_status', (int)$where['parse_status']);
            });
        $count = $query->count();
        $list = $query->order('f.id desc')->page($page ?: 1, $limit ?: 10)->select()->toArray();
        $statusMap = [0 => '待解析', 1 => '解析中', 2 => '解析成功', 3 => '解析失败'];
        $embedMap = [0 => '未向量化', 1 => '已向量化', 2 => '向量化失败'];
        foreach ($list as &$item) {
            $item['parse_status_label'] = $statusMap[(int)$item['parse_status']] ?? '未知';
            $item['embed_status_label'] = $embedMap[(int)$item['embed_status']] ?? '未知';
            $item['file_size_label'] = $this->formatSize((int)$item['file_size']);
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', is_numeric($item['create_time']) ? (int)$item['create_time'] : strtotime($item['create_time'])) : '';
        }
        unset($item);
        return ['count' => $count, 'list' => $list];
    }

    /**
     * 上传文件
     * @param mixed $file 上传文件对象
     * @param int $catId
     * @return array
     */
    public function upload($file, int $catId): array
    {
        if (!$file) {
            throw new AdminException('请选择要上传的文件');
        }
        $originName = $file->getOriginalName();
        $ext = strtolower(pathinfo($originName, PATHINFO_EXTENSION));
        if (!in_array($ext, DocumentParserServices::$allowExt)) {
            throw new AdminException('不支持的文件格式：' . $ext . '，支持 ' . implode('、', DocumentParserServices::$allowExt));
        }
        if ($catId && !Db::name('ai_knowledge_category')->where('id', $catId)->count()) {
            throw new AdminException('分类不存在');
        }
        $dir = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ai_knowledge' . DIRECTORY_SEPARATOR . date('Ymd');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new AdminException('无法创建上传目录，请检查 public/uploads 目录权限');
        }
        $saveName = date('His') . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
        $info = $file->move($dir, $saveName);
        if (!$info) {
            throw new AdminException('文件保存失败：' . $file->getError());
        }
        $path = '/uploads/ai_knowledge/' . date('Ymd') . '/' . $saveName;
        $id = (int)Db::name('ai_knowledge_file')->insertGetId([
            'cat_id' => $catId,
            'name' => $originName,
            'file_path' => $path,
            'file_type' => $ext,
            'file_size' => (int)$info->getSize(),
            'parse_status' => 0,
            'parse_msg' => '',
            'embed_status' => 0,
        ]);
        return ['id' => $id, 'name' => $originName];
    }

    /**
     * 解析文件（提取文本 + 分块 + 向量化）
     * @param int $id
     * @return array
     */
    public function parseFile(int $id): array
    {
        $file = Db::name('ai_knowledge_file')->where('id', $id)->find();
        if (!$file) {
            throw new AdminException('文件不存在');
        }
        @set_time_limit(0);
        Db::name('ai_knowledge_file')->where('id', $id)->update([
            'parse_status' => 1,
            'parse_msg' => '',
        ]);
        try {
            $absPath = app()->getRootPath() . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $file['file_path']);
            /** @var DocumentParserServices $parser */
            $parser = app()->make(DocumentParserServices::class);
            $text = $parser->parse($absPath, $file['file_type']);
            if (mb_strlen($text) < 1) {
                throw new \Exception('未提取到文本内容，可能是扫描版或加密文件');
            }
            $chunks = $this->splitChunks($text);
            Db::name('ai_knowledge_chunk')->where('file_id', $id)->delete();
            $rows = [];
            foreach ($chunks as $index => $content) {
                $rows[] = [
                    'file_id' => $id,
                    'cat_id' => (int)$file['cat_id'],
                    'chunk_index' => $index,
                    'content' => $content,
                    'char_count' => mb_strlen($content),
                    'embedding' => '',
                    'dim' => 0,
                ];
            }
            foreach (array_chunk($rows, 200) as $batch) {
                Db::name('ai_knowledge_chunk')->insertAll($batch);
            }
            $embedStatus = 0;
            $modelId = 0;
            $msg = '';
            /** @var EmbeddingServices $embeddingService */
            $embeddingService = app()->make(EmbeddingServices::class);
            if ($embeddingService->hasModel()) {
                try {
                    $model = $embeddingService->getModel();
                    $modelId = (int)$model['id'];
                    $this->embedChunks($id, $model, $embeddingService);
                    $embedStatus = 1;
                } catch (\Exception $e) {
                    $embedStatus = 2;
                    $msg = '向量化失败：' . $e->getMessage();
                }
            } else {
                $msg = '未配置嵌入式模型，已按关键词模式检索';
            }
            Db::name('ai_knowledge_file')->where('id', $id)->update([
                'content' => $text,
                'char_count' => mb_strlen($text),
                'chunk_count' => count($rows),
                'parse_status' => 2,
                'parse_msg' => mb_substr($msg, 0, 480),
                'embed_status' => $embedStatus,
                'model_id' => $modelId,
            ]);
        } catch (\Exception $e) {
            Db::name('ai_knowledge_file')->where('id', $id)->update([
                'parse_status' => 3,
                'parse_msg' => mb_substr($e->getMessage(), 0, 480),
            ]);
            throw new AdminException('解析失败：' . $e->getMessage());
        }
        return ['chunk_count' => count($rows), 'embed_status' => $embedStatus];
    }

    /**
     * 对文件已有分块做向量化
     * @param int $fileId
     * @param array $model
     * @param EmbeddingServices $embeddingService
     * @throws \Exception
     */
    protected function embedChunks(int $fileId, array $model, EmbeddingServices $embeddingService)
    {
        $chunks = Db::name('ai_knowledge_chunk')->where('file_id', $fileId)->order('chunk_index asc')->field('id,content')->select()->toArray();
        foreach (array_chunk($chunks, $this->embedBatch) as $batch) {
            $texts = array_column($batch, 'content');
            $vectors = $embeddingService->embed($texts, $model);
            if (count($vectors) !== count($batch)) {
                throw new \Exception('向量数量与文本数量不一致');
            }
            foreach ($batch as $k => $chunk) {
                $vector = $vectors[$k];
                Db::name('ai_knowledge_chunk')->where('id', $chunk['id'])->update([
                    'embedding' => json_encode($vector),
                    'dim' => count($vector),
                ]);
            }
        }
    }

    /**
     * 文本分块
     * @param string $text
     * @return array
     */
    protected function splitChunks(string $text): array
    {
        $paragraphs = preg_split('/\n{2,}/u', $text);
        $chunks = [];
        $current = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            if (mb_strlen($paragraph) > $this->chunkSize) {
                $sentences = preg_split('/(?<=[。！？!?；;])/u', $paragraph);
                foreach ($sentences as $sentence) {
                    $sentence = trim($sentence);
                    if ($sentence === '') {
                        continue;
                    }
                    while (mb_strlen($sentence) > $this->chunkSize) {
                        if (trim($current) !== '') {
                            $chunks[] = trim($current);
                            $current = '';
                        }
                        $chunks[] = mb_substr($sentence, 0, $this->chunkSize);
                        $sentence = mb_substr($sentence, $this->chunkSize);
                    }
                    if ($sentence === '') {
                        continue;
                    }
                    if (mb_strlen($current . $sentence) > $this->chunkSize && trim($current) !== '') {
                        $chunks[] = trim($current);
                        $current = '';
                    }
                    $current .= $sentence;
                }
                continue;
            }
            if (mb_strlen($current . "\n" . $paragraph) > $this->chunkSize && trim($current) !== '') {
                $chunks[] = trim($current);
                $current = $paragraph;
            } else {
                $current = $current === '' ? $paragraph : $current . "\n" . $paragraph;
            }
        }
        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }
        return array_values($chunks);
    }

    /**
     * 获取文件分块
     * @param int $id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getChunks(int $id, int $page, int $limit): array
    {
        $file = Db::name('ai_knowledge_file')->where('id', $id)->field('id,name,parse_status,chunk_count')->find();
        if (!$file) {
            throw new AdminException('文件不存在');
        }
        $query = Db::name('ai_knowledge_chunk')->where('file_id', $id);
        $count = $query->count();
        $list = $query->field('id,chunk_index,char_count,dim,content')->order('chunk_index asc')->page($page ?: 1, $limit ?: 10)->select()->toArray();
        return ['file' => $file, 'count' => $count, 'list' => $list];
    }

    /**
     * 删除文件
     * @param int $id
     * @return bool
     */
    public function deleteFile(int $id): bool
    {
        $file = Db::name('ai_knowledge_file')->where('id', $id)->find();
        if (!$file) {
            throw new AdminException('文件不存在');
        }
        Db::name('ai_knowledge_chunk')->where('file_id', $id)->delete();
        Db::name('ai_knowledge_file')->where('id', $id)->delete();
        $absPath = app()->getRootPath() . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $file['file_path']);
        if (is_file($absPath)) {
            @unlink($absPath);
        }
        return true;
    }

    /**
     * 文件大小格式化
     * @param int $size
     * @return string
     */
    protected function formatSize(int $size): string
    {
        if ($size >= 1048576) {
            return round($size / 1048576, 2) . ' MB';
        }
        if ($size >= 1024) {
            return round($size / 1024, 1) . ' KB';
        }
        return $size . ' B';
    }
}
