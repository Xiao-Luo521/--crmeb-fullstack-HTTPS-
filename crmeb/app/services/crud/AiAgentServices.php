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
 * @date 2026/11/15 18:40:48
 */

namespace app\services\crud;

use app\services\BaseServices;
use think\exception\ValidateException;
use think\facade\Db;
use app\dao\crud\AiAgentDao;
use crmeb\services\FormBuilder;

/**
 * Class CrudService
 * @date 2026/11/15
 * @package app\services\crud
 */
class AiAgentServices extends BaseServices
{

    /**
     * AiAgentServices constructor.
     * @param AiAgentDao $dao
     */
    public function __construct(AiAgentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 主页数据接口
     * @param array $where
     * @return array
     * @date 2026/11/15
     */
    public function getCrudListIndex(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $query = $this->dao->searchCrudModel($where, 'id,name,model_id,prompt,temperature,file_ids,status,create_time', 'id desc', []);
        $count = $query->count();
        $list = $query->page($page ?: 1, $limit ?: 10)->select()->toArray();

        $modelMap = Db::name('ai_model')->column('name', 'id');
        $fileMap = Db::name('ai_knowledge_file')->column('name', 'id');
        foreach ($list as &$item) {
            $item['model_name'] = $modelMap[$item['model_id']] ?? '未绑定';
            $item['file_names'] = $this->getFileNames($item['file_ids'] ?? '', $fileMap);
        }

        return ['count' => $count, 'list' => $list];
    }

    /**
     * 编辑和获取表单
     * @date 2026/11/15
     * @param int $id
     * @return array
     */
    public function getCrudForm(int $id = 0)
    {
        $url = '/crud/ai_agent';
        $info = [];
        if ($id) {
            $info = $this->dao->get($id);
            if (!$info) {
                throw new ValidateException('数据不存在');
            }
            $info = $info->toArray();
            $url .= '/' . $id;
        }

        $fileIds = [];
        if (!empty($info['file_ids'])) {
            $fileIds = array_values(array_filter(array_map('intval', explode(',', (string)$info['file_ids']))));
        }

        $rule = [];
        $rule[] = FormBuilder::input("name", "Agent名称", $info["name"] ?? '');
        $rule[] = FormBuilder::select("model_id", "聊天模型", isset($info['model_id']) ? (string)$info['model_id'] : '')
            ->options($this->getModelOptions())
            ->filterable(true)
            ->placeholder('请选择聊天模型');
        $rule[] = FormBuilder::textarea("prompt", "系统提示词", $info["prompt"] ?? '')
            ->placeholder('例如：你是一名专业的客服助手，请根据知识库内容用中文简洁回答');
        $rule[] = FormBuilder::input("temperature", "温度", isset($info['temperature']) ? (string)$info['temperature'] : '0.70')
            ->placeholder('0 ~ 2，值越大回答越发散，默认 0.70');
        $rule[] = FormBuilder::select("file_ids", "绑定知识库", $fileIds)
            ->options($this->getFileOptions())
            ->filterable(true)
            ->placeholder('请选择知识库文件（仅显示解析成功的文件，可多选）');
        $rule[] = FormBuilder::switches("status", "状态", isset($info['status']) ? (string)$info['status'] : '1');

        return create_form('Agent', $rule, $url, $id ? 'PUT' : 'POST');
    }

    /**
     * 新增
     * @date 2026/11/15
     * @param array $data
     * @return mixed
     */
    public function crudSave(array $data)
    {
        $data = $this->checkData($data);
        $data['create_time'] = $data['update_time'] = time();
        return $this->dao->save($data);
    }

    /**
     * 修改
     * @date 2026/11/15
     * @param int $id
     * @param array $data
     * @return \crmeb\basic\BaseModel
     */
    public function crudUpdate(int $id, array $data)
    {
        if (!$this->dao->get($id)) {
            throw new ValidateException('数据不存在');
        }
        $data = $this->checkData($data);
        $data['update_time'] = time();
        return $this->dao->update($id, $data);
    }

    /**
     * 下拉选项（聊天模型、知识库文件）
     * @return array
     */
    public function getOptions(): array
    {
        return [
            'models' => $this->getModelOptions(),
            'files' => $this->getFileOptions(),
        ];
    }

    /**
     * 详情（带模型名称、文件名称）
     * @param int $id
     * @return array
     */
    public function getDetail(int $id): array
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('数据不存在');
        }
        $info = $info->toArray();
        $model = Db::name('ai_model')->where('id', $info['model_id'])->find();
        $info['model_name'] = $model ? $model['name'] . '（' . $model['mode'] . '）' : '未绑定';
        $fileMap = Db::name('ai_knowledge_file')->column('name', 'id');
        $info['file_names'] = $this->getFileNames($info['file_ids'] ?? '', $fileMap);
        return $info;
    }

    /**
     * 聊天模型下拉
     * @return array
     */
    private function getModelOptions(): array
    {
        $models = Db::name('ai_model')
            ->whereIn('type', [1, 2])
            ->where('statue', 1)
            ->field('id,name,mode')
            ->order('id asc')
            ->select()
            ->toArray();
        $options = [];
        foreach ($models as $model) {
            $options[] = [
                'label' => $model['name'] . '（' . $model['mode'] . '）',
                'value' => (int)$model['id'],
            ];
        }
        return $options;
    }

    /**
     * 知识库文件下拉（仅解析成功的文件）
     * @return array
     */
    private function getFileOptions(): array
    {
        $cats = Db::name('ai_knowledge_category')->column('name', 'id');
        $files = Db::name('ai_knowledge_file')
            ->where('parse_status', 2)
            ->field('id,name,cat_id')
            ->order('id desc')
            ->limit(1000)
            ->select()
            ->toArray();
        $options = [];
        foreach ($files as $file) {
            $catName = $cats[$file['cat_id']] ?? '未分类';
            $options[] = [
                'label' => $catName . ' / ' . $file['name'],
                'value' => (int)$file['id'],
            ];
        }
        return $options;
    }

    /**
     * 文件ID转名称
     * @param string $fileIds
     * @param array $fileMap
     * @return string
     */
    private function getFileNames($fileIds, array $fileMap): string
    {
        $ids = array_filter(array_map('intval', explode(',', (string)$fileIds)));
        $names = [];
        foreach ($ids as $id) {
            if (isset($fileMap[$id])) {
                $names[] = $fileMap[$id];
            }
        }
        return implode('、', $names);
    }

    /**
     * 数据校验与格式化
     * @param array $data
     * @return array
     */
    private function checkData(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            throw new ValidateException('请输入Agent名称');
        }

        $modelId = (int)($data['model_id'] ?? 0);
        $model = $modelId ? Db::name('ai_model')->where('id', $modelId)->where('statue', 1)->find() : null;
        if (!$model) {
            throw new ValidateException('请选择有效的聊天模型');
        }

        $temperature = $data['temperature'] ?? '0.70';
        if (!is_numeric($temperature)) {
            throw new ValidateException('温度必须是 0 ~ 2 之间的数字');
        }
        $temperature = (float)$temperature;
        if ($temperature < 0 || $temperature > 2) {
            throw new ValidateException('温度范围应为 0 ~ 2');
        }

        $fileIds = $data['file_ids'] ?? [];
        if (is_string($fileIds)) {
            $fileIds = explode(',', $fileIds);
        }
        $fileIds = array_values(array_unique(array_filter(array_map('intval', (array)$fileIds))));
        if ($fileIds) {
            $validCount = Db::name('ai_knowledge_file')
                ->whereIn('id', $fileIds)
                ->where('parse_status', 2)
                ->count();
            if ($validCount != count($fileIds)) {
                throw new ValidateException('存在未解析成功或已删除的知识库文件，请重新选择');
            }
        }

        return [
            'name' => $name,
            'model_id' => $modelId,
            'prompt' => trim((string)($data['prompt'] ?? '')),
            'temperature' => $temperature,
            'file_ids' => implode(',', $fileIds),
            'status' => (int)($data['status'] ?? 1) ? 1 : 0,
        ];
    }
}
