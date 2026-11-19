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

namespace app\api\controller\v1\ai;

use app\Request;
use app\services\ai\AiChatServices;
use app\services\ai_knowledge\KnowledgeSearchServices;
use think\facade\Db;

/**
 * 微页面 AI 聊天
 * Class ChatController
 * @package app\api\controller\v1\ai
 */
class ChatController
{
    /**
     * Agent 基本信息（公开）
     * @param Request $request
     * @return mixed
     */
    public function info(Request $request)
    {
        $agentId = (int)$request->param('agent_id', 0);
        $agent = $this->getAgent($agentId);
        if (!$agent) {
            return app('json')->fail('AI助手不存在或已停用');
        }
        return app('json')->success([
            'id' => (int)$agent['id'],
            'name' => $agent['name'],
        ]);
    }

    /**
     * 当前会员的聊天记录
     * @param Request $request
     * @return mixed
     */
    public function history(Request $request)
    {
        $uid = (int)$request->uid();
        $agentId = (int)$request->param('agent_id', 0);
        if (!$uid || !$agentId) {
            return app('json')->success(['session_id' => 0, 'list' => []]);
        }
        $session = Db::name('ai_agent_session')
            ->where('uid', $uid)
            ->where('agent_id', $agentId)
            ->order('update_time desc,id desc')
            ->find();
        if (!$session) {
            return app('json')->success(['session_id' => 0, 'list' => []]);
        }
        return app('json')->success([
            'session_id' => (int)$session['id'],
            'list' => $this->sessionMessages((int)$session['id']),
        ]);
    }

    /**
     * 启用中的 Agent 列表（公开）
     * @param Request $request
     * @return mixed
     */
    public function agents(Request $request)
    {
        $rows = Db::name('ai_agent')
            ->where('status', 1)
            ->field('id,name')
            ->order('id asc')
            ->select()->toArray();
        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
            ];
        }
        return app('json')->success($list);
    }

    /**
     * 当前会员的会话列表
     * @param Request $request
     * @return mixed
     */
    public function sessions(Request $request)
    {
        $uid = (int)$request->uid();
        $agentId = (int)$request->param('agent_id', 0);
        if (!$uid) {
            return app('json')->success([]);
        }
        $query = Db::name('ai_agent_session')->where('uid', $uid);
        if ($agentId) {
            $query = $query->where('agent_id', $agentId);
        }
        $rows = $query->order('update_time desc,id desc')
            ->limit(50)
            ->field('id,agent_id,title,update_time')
            ->select()->toArray();
        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id' => (int)$row['id'],
                'agent_id' => (int)$row['agent_id'],
                'title' => trim((string)($row['title'] ?? '')) !== '' ? (string)$row['title'] : '新会话',
                'time' => date('m-d H:i', (int)$row['update_time']),
            ];
        }
        return app('json')->success($list);
    }

    /**
     * 指定会话的消息
     * @param Request $request
     * @return mixed
     */
    public function messages(Request $request)
    {
        $uid = (int)$request->uid();
        $sessionId = (int)$request->param('session_id', 0);
        if (!$uid || !$sessionId) {
            return app('json')->success(['session_id' => 0, 'agent_id' => 0, 'list' => []]);
        }
        $session = Db::name('ai_agent_session')
            ->where('id', $sessionId)
            ->where('uid', $uid)
            ->find();
        if (!$session) {
            return app('json')->fail('会话不存在');
        }
        return app('json')->success([
            'session_id' => (int)$session['id'],
            'agent_id' => (int)$session['agent_id'],
            'title' => (string)$session['title'],
            'list' => $this->sessionMessages((int)$session['id']),
        ]);
    }

    /**
     * 读取会话消息并转成前端结构
     * @param int $sessionId
     * @return array
     */
    protected function sessionMessages(int $sessionId): array
    {
        $rows = Db::name('ai_agent_message')
            ->where('session_id', $sessionId)
            ->order('id asc')
            ->limit(100)
            ->select()->toArray();
        $list = [];
        foreach ($rows as $row) {
            if (!in_array($row['role'], ['user', 'assistant'], true)) {
                continue;
            }
            $list[] = [
                'role' => $row['role'],
                'content' => $row['content'],
                'html' => $row['role'] === 'assistant' ? Markdown::toHtml((string)$row['content']) : '',
                'time' => date('m-d H:i', (int)$row['create_time']),
            ];
        }
        return $list;
    }

    /**
     * 发送消息
     * @param Request $request
     * @return mixed
     */
    public function send(Request $request)
    {
        $uid = (int)$request->uid();
        $agentId = (int)$request->param('agent_id', 0);
        $message = trim((string)$request->param('message', ''));
        $sessionId = (int)$request->param('session_id', 0);
        $clientMessages = $request->param('messages', []);
        if (!is_array($clientMessages)) {
            $clientMessages = [];
        }

        $agent = $this->getAgent($agentId);
        if (!$agent) {
            return app('json')->fail('AI助手不存在或已停用');
        }

        // 兼容只传 messages 数组的旧客户端
        if ($message === '') {
            for ($i = count($clientMessages) - 1; $i >= 0; $i--) {
                $item = $clientMessages[$i];
                if (is_array($item) && ($item['role'] ?? '') === 'user' && trim((string)($item['content'] ?? '')) !== '') {
                    $message = trim((string)$item['content']);
                    break;
                }
            }
        }
        if ($message === '') {
            return app('json')->fail('请输入您的问题');
        }
        if (mb_strlen($message) > 2000) {
            $message = mb_substr($message, 0, 2000);
        }

        // 会话（登录会员按会员维度保存）
        if ($uid) {
            if ($sessionId) {
                $session = Db::name('ai_agent_session')
                    ->where('id', $sessionId)
                    ->where('uid', $uid)
                    ->where('agent_id', $agentId)
                    ->find();
                if ($session) {
                    $sessionId = (int)$session['id'];
                }
            }
            if (!$sessionId) {
                $sessionId = (int)Db::name('ai_agent_session')->insertGetId([
                    'uid' => $uid,
                    'agent_id' => $agentId,
                    'title' => mb_substr($message, 0, 30),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
        } else {
            $sessionId = 0;
        }

        // 历史上下文
        $history = [];
        if ($uid && $sessionId) {
            $rows = Db::name('ai_agent_message')
                ->where('session_id', $sessionId)
                ->order('id desc')
                ->limit(10)
                ->select()->toArray();
            $rows = array_reverse($rows);
            foreach ($rows as $row) {
                if (in_array($row['role'], ['user', 'assistant'], true)) {
                    $history[] = ['role' => $row['role'], 'content' => $row['content']];
                }
            }
        } else {
            foreach (array_slice($clientMessages, -10) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $role = $item['role'] ?? '';
                $content = trim((string)($item['content'] ?? ''));
                if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                    $history[] = ['role' => $role, 'content' => $content];
                }
            }
        }
        // 去掉与本次问题重复的最后一条
        $last = end($history);
        if ($last && $last['role'] === 'user' && $last['content'] === $message) {
            array_pop($history);
        }

        // 知识库检索
        $context = '';
        $fileIds = array_values(array_filter(array_map('intval', explode(',', (string)($agent['file_ids'] ?? '')))));
        if ($fileIds) {
            try {
                /** @var KnowledgeSearchServices $search */
                $search = app()->make(KnowledgeSearchServices::class);
                $result = $search->searchFiles($message, $fileIds, 4);
                $context = trim((string)($result['context'] ?? ''));
            } catch (\Throwable $e) {
                $context = '';
            }
        }

        $system = trim((string)($agent['prompt'] ?? ''));
        if ($system === '') {
            $system = '你是一个专业、友好的智能客服助手，请用简洁的中文回答用户问题。';
        }
        if ($context !== '') {
            $system .= "\n\n以下是从知识库中检索到的资料，请优先依据这些资料回答；若资料中没有答案，请如实告知并给出建议：\n" . $context;
        }

        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($history as $item) {
            $messages[] = $item;
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            /** @var AiChatServices $chat */
            $chat = app()->make(AiChatServices::class);
            $model = $chat->getChatModel((int)$agent['model_id']);
            $result = $chat->chat($model, $messages, [
                'temperature' => (float)($agent['temperature'] ?? 0.7),
            ]);
        } catch (\Throwable $e) {
            return app('json')->fail($e->getMessage());
        }

        $content = trim((string)($result['content'] ?? ''));
        if ($content === '') {
            return app('json')->fail('模型未返回内容，请稍后再试');
        }

        if ($uid && $sessionId) {
            $now = time();
            Db::name('ai_agent_message')->insertAll([
                [
                    'session_id' => $sessionId,
                    'uid' => $uid,
                    'agent_id' => $agentId,
                    'role' => 'user',
                    'content' => $message,
                    'create_time' => $now,
                ],
                [
                    'session_id' => $sessionId,
                    'uid' => $uid,
                    'agent_id' => $agentId,
                    'role' => 'assistant',
                    'content' => $content,
                    'create_time' => $now,
                ],
            ]);
            Db::name('ai_agent_session')->where('id', $sessionId)->update(['update_time' => $now]);
        }

        return app('json')->success([
            'content' => $content,
            'html' => Markdown::toHtml($content),
            'session_id' => $sessionId,
        ]);
    }

    /**
     * 获取启用的 Agent
     * @param int $agentId
     * @return array|false|null
     */
    protected function getAgent(int $agentId)
    {
        if (!$agentId) {
            return null;
        }
        return Db::name('ai_agent')->where('id', $agentId)->where('status', 1)->find();
    }
}
