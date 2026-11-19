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
use app\services\ai_knowledge\EmbeddingServices;
use app\services\ai_knowledge\KnowledgeSearchServices;
use think\facade\App;

/**
 * 知识库检索
 * Class KnowledgeSearch
 * @package app\adminapi\controller\v1\ai_knowledge
 */
class KnowledgeSearch extends AuthController
{
    /**
     * @var KnowledgeSearchServices
     */
    protected $service;

    /**
     * @param App $app
     * @param KnowledgeSearchServices $service
     */
    public function __construct(App $app, KnowledgeSearchServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 检索知识库
     * @return \think\Response
     */
    public function search()
    {
        $data = $this->request->postMore([
            ['question', ''],
            ['cat_id', 0],
            ['limit', 5],
        ]);
        return app('json')->success($this->service->search((string)$data['question'], (int)$data['cat_id'], (int)$data['limit']));
    }

    /**
     * 获取嵌入式模型配置状态
     * @return \think\Response
     */
    public function model()
    {
        /** @var EmbeddingServices $embeddingService */
        $embeddingService = app()->make(EmbeddingServices::class);
        $hasModel = $embeddingService->hasModel();
        $model = [];
        if ($hasModel) {
            $info = $embeddingService->getModel();
            $model = ['id' => (int)$info['id'], 'name' => $info['name'], 'mode' => $info['mode']];
        }
        return app('json')->success(['has_model' => $hasModel, 'model' => $model]);
    }
}
