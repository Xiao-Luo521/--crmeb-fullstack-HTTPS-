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
use app\services\ai_knowledge\KnowledgeFileServices;
use think\facade\App;

/**
 * 知识库文件
 * Class KnowledgeFile
 * @package app\adminapi\controller\v1\ai_knowledge
 */
class KnowledgeFile extends AuthController
{
    /**
     * @var KnowledgeFileServices
     */
    protected $service;

    /**
     * @param App $app
     * @param KnowledgeFileServices $service
     */
    public function __construct(App $app, KnowledgeFileServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 文件列表
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['cat_id', 0],
            ['name', ''],
            ['parse_status', ''],
        ]);
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        return app('json')->success($this->service->getList($where, $page ?: 1, $limit ?: 15));
    }

    /**
     * 上传文件
     * @return \think\Response
     */
    public function upload()
    {
        $catId = (int)$this->request->param('cat_id', 0);
        $file = $this->request->file('file');
        if (!$file) {
            return app('json')->fail('请选择要上传的文件');
        }
        return app('json')->success('上传成功', $this->service->upload($file, $catId));
    }

    /**
     * 解析文件
     * @param int $id
     * @return \think\Response
     */
    public function parse($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        return app('json')->success('解析成功', $this->service->parseFile((int)$id));
    }

    /**
     * 文件分块列表
     * @param int $id
     * @return \think\Response
     */
    public function chunks($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        return app('json')->success($this->service->getChunks((int)$id, $page ?: 1, $limit ?: 10));
    }

    /**
     * 删除文件
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $this->service->deleteFile((int)$id);
        return app('json')->success('删除成功');
    }
}
