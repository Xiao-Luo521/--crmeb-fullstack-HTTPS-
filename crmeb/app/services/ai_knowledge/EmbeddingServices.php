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
 * 向量化服务（调用嵌入式模型）
 * Class EmbeddingServices
 * @package app\services\ai_knowledge
 */
class EmbeddingServices
{
    /**
     * 是否已配置可用的嵌入式模型
     * @return bool
     */
    public function hasModel(): bool
    {
        return (bool)Db::name('ai_model')->where('type', 3)->where('statue', 1)->count();
    }

    /**
     * 获取嵌入式模型配置
     * @return array
     * @throws AdminException
     */
    public function getModel(): array
    {
        $model = Db::name('ai_model')->where('type', 3)->where('statue', 1)->order('id asc')->find();
        if (!$model) {
            throw new AdminException('请先到「模型」中添加并启用嵌入式模型（类型选择：嵌入式模型）');
        }
        if (empty($model['base_url']) || empty($model['mode'])) {
            throw new AdminException('嵌入式模型的接口地址或模型标识为空，请先完善模型配置');
        }
        return $model;
    }

    /**
     * 批量获取文本向量
     * @param array $texts
     * @param array $model
     * @return array
     * @throws AdminException
     */
    public function embed(array $texts, array $model): array
    {
        $texts = array_values($texts);
        if (!$texts) {
            return [];
        }
        $url = rtrim($model['base_url'], '/');
        if (substr($url, -11) !== '/embeddings') {
            $url .= '/embeddings';
        }
        $body = json_encode(['model' => $model['mode'], 'input' => $texts], JSON_UNESCAPED_UNICODE);
        $headers = ['Content-Type: application/json'];
        if (!empty($model['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $model['api_key'];
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            throw new AdminException('调用嵌入式模型失败：' . $error);
        }
        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new AdminException('嵌入式模型返回数据格式错误');
        }
        if (isset($data['error'])) {
            $msg = is_array($data['error']) ? ($data['error']['message'] ?? json_encode($data['error'])) : $data['error'];
            throw new AdminException('嵌入式模型返回错误：' . $msg);
        }
        if (empty($data['data']) || !is_array($data['data'])) {
            throw new AdminException('嵌入式模型未返回向量数据');
        }
        $result = [];
        foreach ($data['data'] as $item) {
            if (!isset($item['embedding'])) {
                continue;
            }
            $result[(int)($item['index'] ?? count($result))] = $item['embedding'];
        }
        ksort($result);
        return array_values($result);
    }
}
