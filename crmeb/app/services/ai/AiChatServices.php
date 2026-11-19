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

namespace app\services\ai;

use crmeb\exceptions\AdminException;
use think\facade\Db;

/**
 * 大模型对话服务
 * 兼容 OpenAI 协议（智谱、DeepSeek、通义、OpenAI 等均适用）
 * Class AiChatServices
 * @package app\services\ai
 */
class AiChatServices
{
    /**
     * 获取聊天模型配置
     * @param int $id 模型ID，为0时取第一个启用的聊天/多模态模型
     * @return array
     * @throws AdminException
     */
    public function getChatModel(int $id = 0): array
    {
        $query = Db::name('ai_model')->whereIn('type', [1, 2])->where('statue', 1);
        if ($id) {
            $query->where('id', $id);
        }
        $model = $query->order('id asc')->find();
        if (!$model) {
            throw new AdminException($id ? '模型不存在或未启用' : '请先在「模型」中添加并启用聊天模型');
        }
        if (empty($model['base_url']) || empty($model['mode'])) {
            throw new AdminException('模型的接口地址或模型标识为空，请先完善配置');
        }
        return $model;
    }

    /**
     * 发送对话请求
     * @param array $model 模型配置
     * @param array $messages 消息列表，如 [['role' => 'user', 'content' => '你好']]
     * @param array $options 可选参数：temperature / max_tokens
     * @return array ['content' => 回复内容, 'usage' => token用量, 'raw' => 原始返回]
     * @throws AdminException
     */
    public function chat(array $model, array $messages, array $options = []): array
    {
        $url = rtrim($model['base_url'], '/');
        if (substr($url, -17) !== '/chat/completions') {
            $url .= '/chat/completions';
        }

        $body = [
            'model' => $model['mode'],
            'messages' => array_values($messages),
            'stream' => false,
        ];
        if (isset($options['temperature'])) {
            $body['temperature'] = (float)$options['temperature'];
        }
        if (!empty($options['max_tokens'])) {
            $body['max_tokens'] = (int)$options['max_tokens'];
        }

        $headers = ['Content-Type: application/json'];
        if (!empty($model['api_key'])) {
            $headers[] = 'Authorization: Bearer ' . $model['api_key'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
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

        if ($error) {
            throw new AdminException('模型请求失败：' . $error);
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new AdminException('模型返回数据异常：' . mb_substr((string)$response, 0, 200));
        }
        if (!empty($result['error'])) {
            $err = $result['error'];
            $msg = is_array($err) ? ($err['message'] ?? json_encode($err, JSON_UNESCAPED_UNICODE)) : $err;
            throw new AdminException('模型调用失败：' . $msg);
        }

        $content = $result['choices'][0]['message']['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        }
        $content = trim((string)$content);
        if ($content === '') {
            throw new AdminException('模型未返回内容，请检查模型标识是否正确');
        }

        return [
            'content' => $content,
            'usage' => $result['usage'] ?? [],
            'raw' => $result,
        ];
    }
}
