<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------
 */

/**
 * agent
 * @author crud自动生成代码
 * @date 2026/11/15
 */

namespace app\adminapi\controller\crud;

use app\adminapi\controller\AuthController;
use think\facade\App;
use app\services\crud\AiAgentServices;

/**
 * Class AiAgent
 * @date 2026/11/15
 * @package app\adminapi\controller\crud
 */
class AiAgent extends AuthController
{

    /**
     * @var AiAgentServices
     */
    protected $service;

    /**
     * AiAgentController constructor.
     * @param App $app
     * @param AiAgentServices $service
     */
    public function __construct(App $app, AiAgentServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }


    /**
     * 列表
     * @date 2026/11/15
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['model_id', ''],
            ['status', ''],
        ]);
        return app('json')->success($this->service->getCrudListIndex($where));
    }

    /**
     * 创建
     * @return \think\Response
     * @date 2026/11/15
     */
    public function create()
    {
        return app('json')->success($this->service->getCrudForm());
    }

    /**
     * 保存
     * @return \think\Response
     * @date 2026/11/15
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['model_id', ''],
            ['prompt', ''],
            ['temperature', ''],
            ['file_ids', []],
            ['status', ''],

        ]);

        validate(\app\adminapi\validate\crud\AiAgentValidate::class)->check($data);

        $this->service->crudSave($data);

        return app('json')->success('添加成功');
    }

    /**
     * 编辑获取数据
     * @param $id
     * @return \think\Response
     * @date 2026/11/15
     */
    public function edit($id)
    {
        return app('json')->success($this->service->getCrudForm((int)$id));
    }

    /**
     * 修改
     * @param $id
     * @return \think\Response
     * @date 2026/11/15
     */
    public function update($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }

        $data = $this->request->postMore([
            ['name', ''],
            ['model_id', ''],
            ['prompt', ''],
            ['temperature', ''],
            ['file_ids', []],
            ['status', ''],

        ]);

        validate(\app\adminapi\validate\crud\AiAgentValidate::class)->check($data);

        $this->service->crudUpdate((int)$id, $data);

        return app('json')->success('修改成功');
    }

    /**
     * 修改状态
     * @param $id
     * @return \think\Response
     * @date 2026/11/15
     */
    public function status($id)
    {
        if (!$id) {
            return app('json')->fail('ID不能为空'); 
        }

        $data = $this->request->postMore([
            ['field', ''],
            ['value', '']
        ]);

        $filedAll = ['status'];

        if (!in_array($data['field'], $filedAll)) {
            return app('json')->fail('字段不存在');
        }

        if ($this->service->update(['id'=> $id], [$data['field']=> $data['value']])) {
            return app('json')->success('修改成功');
        } else {
             return app('json')->fail('修改失败');
        }
    }

    /**
     * 删除
     * @param $id
     * @return \think\Response
     * @date 2026/11/15
     */
    public function delete($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }

        if ($this->service->destroy((int)$id)) {
            return app('json')->success('删除成功');
        } else {
            return app('json')->fail('删除失败');
        }
    }

    /**
     * 查看
     * @param $id
     * @return \think\Response
     * @date 2026/11/15
     */
    public function read($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }

        return app('json')->success($this->service->getDetail((int)$id));
    }

    /**
     * 下拉选项（聊天模型、知识库文件）
     * @return \think\Response
     */
    public function options()
    {
        return app('json')->success($this->service->getOptions());
    }



}
