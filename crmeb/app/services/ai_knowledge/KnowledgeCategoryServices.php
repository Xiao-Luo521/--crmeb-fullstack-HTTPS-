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
 * 知识库分类服务
 * Class KnowledgeCategoryServices
 * @package app\services\ai_knowledge
 */
class KnowledgeCategoryServices
{
    /**
     * 获取分类树
     * @return array
     */
    public function getTree(): array
    {
        $list = Db::name('ai_knowledge_category')->order('sort desc, id asc')->select()->toArray();
        $fileCounts = Db::name('ai_knowledge_file')->field('cat_id, count(*) as num')->group('cat_id')->select()->toArray();
        $counts = array_column($fileCounts, 'num', 'cat_id');
        foreach ($list as &$item) {
            $item['file_count'] = (int)($counts[$item['id']] ?? 0);
        }
        unset($item);
        return $this->buildTree($list);
    }

    /**
     * 组装树形结构
     * @param array $list
     * @param int $pid
     * @return array
     */
    protected function buildTree(array $list, int $pid = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ((int)$item['pid'] === $pid) {
                $children = $this->buildTree($list, (int)$item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 保存分类
     * @param array $data
     * @return int
     */
    public function save(array $data): int
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw new AdminException('请输入分类名称');
        }
        return (int)Db::name('ai_knowledge_category')->insertGetId([
            'pid' => (int)($data['pid'] ?? 0),
            'name' => $name,
            'sort' => (int)($data['sort'] ?? 0),
        ]);
    }

    /**
     * 修改分类
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $info = Db::name('ai_knowledge_category')->where('id', $id)->find();
        if (!$info) {
            throw new AdminException('分类不存在');
        }
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw new AdminException('请输入分类名称');
        }
        $pid = (int)($data['pid'] ?? 0);
        if ($pid === $id) {
            throw new AdminException('上级分类不能是自己');
        }
        if ($pid && Db::name('ai_knowledge_category')->where('pid', $id)->count() && $pid !== (int)$info['pid']) {
            throw new AdminException('该分类有下级分类，无法修改上级');
        }
        Db::name('ai_knowledge_category')->where('id', $id)->update([
            'pid' => $pid,
            'name' => $name,
            'sort' => (int)($data['sort'] ?? $info['sort']),
        ]);
        return true;
    }

    /**
     * 删除分类
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        if (Db::name('ai_knowledge_category')->where('pid', $id)->count()) {
            throw new AdminException('请先删除下级分类');
        }
        if (Db::name('ai_knowledge_file')->where('cat_id', $id)->count()) {
            throw new AdminException('该分类下还有文件，无法删除');
        }
        Db::name('ai_knowledge_category')->where('id', $id)->delete();
        return true;
    }
}
