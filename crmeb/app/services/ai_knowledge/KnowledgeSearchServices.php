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
 * 知识库检索服务
 * Class KnowledgeSearchServices
 * @package app\services\ai_knowledge
 */
class KnowledgeSearchServices
{
    /**
     * 单次参与计算的最大分块数
     * @var int
     */
    protected $maxScan = 2000;

    /**
     * 检索知识库
     * @param string $question
     * @param int $catId
     * @param int $limit
     * @return array
     */
    public function search(string $question, int $catId, int $limit): array
    {
        $question = trim($question);
        if ($question === '') {
            throw new AdminException('请输入要检索的内容');
        }
        $limit = $limit > 0 && $limit <= 20 ? $limit : 5;

        /** @var EmbeddingServices $embeddingService */
        $embeddingService = app()->make(EmbeddingServices::class);
        if ($embeddingService->hasModel()) {
            try {
                $model = $embeddingService->getModel();
                $vectors = $embeddingService->embed([$question], $model);
                $vector = $vectors[0] ?? [];
                if ($vector) {
                    $list = $this->vectorSearch($vector, $catId, $limit);
                    if ($list) {
                        return $this->formatResult('vector', $question, $list);
                    }
                }
            } catch (\Exception $e) {
                // 向量检索失败时降级为关键词检索
            }
        }
        $list = $this->keywordSearch($question, $catId, $limit);
        return $this->formatResult('keyword', $question, $list);
    }

    /**
     * 按文件范围检索知识库（供 Agent 绑定文件使用）
     * @param string $question
     * @param array $fileIds
     * @param int $limit
     * @return array
     */
    public function searchFiles(string $question, array $fileIds, int $limit = 5): array
    {
        $question = trim($question);
        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if ($question === '' || !$fileIds) {
            return ['mode' => 'none', 'question' => $question, 'list' => [], 'context' => ''];
        }
        $limit = $limit > 0 && $limit <= 20 ? $limit : 5;

        /** @var EmbeddingServices $embeddingService */
        $embeddingService = app()->make(EmbeddingServices::class);
        if ($embeddingService->hasModel()) {
            try {
                $model = $embeddingService->getModel();
                $vectors = $embeddingService->embed([$question], $model);
                $vector = $vectors[0] ?? [];
                if ($vector) {
                    $list = $this->vectorSearch($vector, 0, $limit, $fileIds);
                    if ($list) {
                        return $this->formatResult('vector', $question, $list);
                    }
                }
            } catch (\Exception $e) {
                // 向量检索失败时降级为关键词检索
            }
        }
        $list = $this->keywordSearch($question, 0, $limit, $fileIds);
        return $this->formatResult('keyword', $question, $list);
    }

    /**
     * 向量检索
     * @param array $vector
     * @param int $catId
     * @param int $limit
     * @param array $fileIds
     * @return array
     */
    protected function vectorSearch(array $vector, int $catId, int $limit, array $fileIds = []): array
    {
        $query = Db::name('ai_knowledge_chunk')->where('embedding', '<>', '')->whereNotNull('embedding')
            ->when($catId, function ($query) use ($catId) {
                $query->where('cat_id', $catId);
            })
            ->when($fileIds, function ($query) use ($fileIds) {
                $query->whereIn('file_id', $fileIds);
            });
        $chunks = $query->field('id,file_id,cat_id,chunk_index,content,embedding')->limit($this->maxScan)->select()->toArray();
        if (!$chunks) {
            return [];
        }
        $scored = [];
        foreach ($chunks as $chunk) {
            $embedding = json_decode($chunk['embedding'], true);
            if (!is_array($embedding) || !$embedding) {
                continue;
            }
            $score = $this->cosine($vector, $embedding);
            $scored[] = [
                'chunk_id' => (int)$chunk['id'],
                'file_id' => (int)$chunk['file_id'],
                'cat_id' => (int)$chunk['cat_id'],
                'chunk_index' => (int)$chunk['chunk_index'],
                'content' => $chunk['content'],
                'score' => round($score, 4),
            ];
        }
        usort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        return array_slice($scored, 0, $limit);
    }

    /**
     * 关键词检索（未配置向量模型时使用）
     * @param string $question
     * @param int $catId
     * @param int $limit
     * @param array $fileIds
     * @return array
     */
    protected function keywordSearch(string $question, int $catId, int $limit, array $fileIds = []): array
    {
        $terms = $this->splitTerms($question);
        $query = Db::name('ai_knowledge_chunk')->when($catId, function ($query) use ($catId) {
            $query->where('cat_id', $catId);
        })->when($fileIds, function ($query) use ($fileIds) {
            $query->whereIn('file_id', $fileIds);
        })->when($terms, function ($query) use ($terms) {
            $query->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->whereOr('content', 'like', '%' . $term . '%');
                }
            });
        });
        $chunks = $query->field('id,file_id,cat_id,chunk_index,content')->limit($this->maxScan)->select()->toArray();
        $scored = [];
        foreach ($chunks as $chunk) {
            $score = 0;
            foreach ($terms as $term) {
                $score += substr_count(mb_strtolower($chunk['content']), mb_strtolower($term));
            }
            if ($score <= 0) {
                continue;
            }
            $scored[] = [
                'chunk_id' => (int)$chunk['id'],
                'file_id' => (int)$chunk['file_id'],
                'cat_id' => (int)$chunk['cat_id'],
                'chunk_index' => (int)$chunk['chunk_index'],
                'content' => $chunk['content'],
                'score' => $score,
            ];
        }
        usort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        return array_slice($scored, 0, $limit);
    }

    /**
     * 拆分检索词
     * @param string $question
     * @return array
     */
    protected function splitTerms(string $question): array
    {
        $terms = preg_split('/[\s,，。？?！!、;；:：()（）\[\]【】]+/u', $question, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($terms as $term) {
            $term = trim($term);
            if ($term === '') {
                continue;
            }
            $result[] = $term;
            // 中文长句补充二字词组，提高命中率
            if (mb_strlen($term) > 4) {
                $len = mb_strlen($term);
                for ($i = 0; $i < $len - 1 && count($result) < 12; $i += 2) {
                    $result[] = mb_substr($term, $i, 2);
                }
            }
        }
        return array_slice(array_values(array_unique($result)), 0, 12);
    }

    /**
     * 组装返回结果
     * @param string $mode
     * @param string $question
     * @param array $list
     * @return array
     */
    protected function formatResult(string $mode, string $question, array $list): array
    {
        $fileIds = array_unique(array_column($list, 'file_id'));
        $files = $fileIds ? Db::name('ai_knowledge_file')->whereIn('id', $fileIds)->column('name', 'id') : [];
        $contents = [];
        foreach ($list as &$item) {
            $item['file_name'] = $files[$item['file_id']] ?? '';
        }
        unset($item);
        foreach ($list as $item) {
            $contents[] = '【' . $item['file_name'] . '】' . $item['content'];
        }
        return [
            'mode' => $mode,
            'question' => $question,
            'list' => $list,
            'context' => implode("\n\n", $contents),
        ];
    }

    /**
     * 余弦相似度
     * @param array $a
     * @param array $b
     * @return float
     */
    protected function cosine(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }
        $dot = $normA = $normB = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
