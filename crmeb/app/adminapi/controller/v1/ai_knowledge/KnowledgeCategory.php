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

namespace app\adminapi\controller\v1\ai_knowledge;

use app\adminapi\controller\AuthController;
use app\services\ai_knowledge\KnowledgeCategoryServices;
use think\facade\App;

/**
 * 知识库分类
 * Class KnowledgeCategory
 * @package app\adminapi\controller\v1\ai_knowledge
 */
class KnowledgeCategory extends AuthController
{
    /**
     * @var KnowledgeCategoryServices
     */
    protected $service;

    /**
     * @param App $app
     * @param KnowledgeCategoryServices $service
     */
    public function __construct(App $app, KnowledgeCategoryServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 分类树
     * @return \think\Response
     */
    public function index()
    {
        return app('json')->success($this->service->getTree());
    }

    /**
     * 添加分类
     * @return \think\Response
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['name', ''],
            ['sort', 0],
        ]);
        $this->service->save($data);
        return app('json')->success('添加成功');
    }

    /**
     * 修改分类
     * @param int $id
     * @return \think\Response
     */
    public function update($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $data = $this->request->postMore([
            ['pid', 0],
            ['name', ''],
            ['sort', 0],
        ]);
        $this->service->update((int)$id, $data);
        return app('json')->success('修改成功');
    }

    /**
     * 删除分类
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $this->service->delete((int)$id);
        return app('json')->success('删除成功');
    }
}
