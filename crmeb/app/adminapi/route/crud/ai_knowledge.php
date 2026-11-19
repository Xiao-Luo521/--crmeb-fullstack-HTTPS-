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

use think\facade\Route;

/**
 * AI知识库路由
 */
Route::group('ai_knowledge', function () {
    //分类
    Route::get('category', 'v1.ai_knowledge.KnowledgeCategory/index')->option(['real_name' => '知识库分类列表']);
    Route::post('category', 'v1.ai_knowledge.KnowledgeCategory/save')->option(['real_name' => '知识库分类添加']);
    Route::put('category/:id', 'v1.ai_knowledge.KnowledgeCategory/update')->option(['real_name' => '知识库分类修改']);
    Route::delete('category/:id', 'v1.ai_knowledge.KnowledgeCategory/delete')->option(['real_name' => '知识库分类删除']);
    //文件
    Route::get('file', 'v1.ai_knowledge.KnowledgeFile/index')->option(['real_name' => '知识库文件列表']);
    Route::post('file/upload', 'v1.ai_knowledge.KnowledgeFile/upload')->option(['real_name' => '知识库文件上传']);
    Route::post('file/parse/:id', 'v1.ai_knowledge.KnowledgeFile/parse')->option(['real_name' => '知识库文件解析']);
    Route::get('file/chunks/:id', 'v1.ai_knowledge.KnowledgeFile/chunks')->option(['real_name' => '知识库文件分块']);
    Route::delete('file/:id', 'v1.ai_knowledge.KnowledgeFile/delete')->option(['real_name' => '知识库文件删除']);
    //检索
    Route::post('search', 'v1.ai_knowledge.KnowledgeSearch/search')->option(['real_name' => '知识库检索']);
    Route::get('model', 'v1.ai_knowledge.KnowledgeSearch/model')->option(['real_name' => '知识库向量模型状态']);
})->option(['mark' => 'ai_knowledge', 'mark_name' => 'AI知识库']);
