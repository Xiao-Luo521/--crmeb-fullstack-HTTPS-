<?php


use think\facade\Route;

Route::get('crud/ai_agent', 'crud.AiAgent/index')->option(['real_name' => 'agent列表接口']);

Route::get('crud/ai_agent/create', 'crud.AiAgent/create')->option(['real_name' => 'agent获取创建表单接口']);

Route::get('crud/ai_agent/options', 'crud.AiAgent/options')->option(['real_name' => 'agent下拉选项接口']);

Route::post('crud/ai_agent', 'crud.AiAgent/save')->option(['real_name' => 'agent保存接口']);

Route::get('crud/ai_agent/:id/edit', 'crud.AiAgent/edit')->option(['real_name' => 'agent获取修改表单接口']);

Route::put('crud/ai_agent/:id', 'crud.AiAgent/update')->option(['real_name' => 'agent修改接口']);

Route::put('crud/ai_agent/status/:id', 'crud.AiAgent/status')->option(['real_name' => 'agent修改状态接口']);

Route::delete('crud/ai_agent/:id', 'crud.AiAgent/delete')->option(['real_name' => 'agent删除接口']);

Route::get('crud/ai_agent/:id', 'crud.AiAgent/read')->option(['real_name' => 'agent查看接口']);



