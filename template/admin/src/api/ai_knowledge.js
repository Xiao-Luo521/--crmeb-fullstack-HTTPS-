// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

import request from '@/libs/request';

/**
 * 知识库分类列表
 */
export function getKnowledgeCategoryApi() {
  return request({
    url: 'ai_knowledge/category',
    method: 'get',
  });
}

/**
 * 添加分类
 */
export function knowledgeCategorySaveApi(data) {
  return request({
    url: 'ai_knowledge/category',
    method: 'post',
    data,
  });
}

/**
 * 修改分类
 */
export function knowledgeCategoryUpdateApi(id, data) {
  return request({
    url: `ai_knowledge/category/${id}`,
    method: 'put',
    data,
  });
}

/**
 * 删除分类
 */
export function knowledgeCategoryDeleteApi(id) {
  return request({
    url: `ai_knowledge/category/${id}`,
    method: 'delete',
  });
}

/**
 * 知识库文件列表
 */
export function getKnowledgeFileListApi(params) {
  return request({
    url: 'ai_knowledge/file',
    method: 'get',
    params,
  });
}

/**
 * 上传知识库文件
 */
export function knowledgeFileUploadApi(data) {
  return request({
    url: 'ai_knowledge/file/upload',
    method: 'post',
    file: true,
    data,
    timeout: 600000,
  });
}

/**
 * 解析知识库文件
 */
export function knowledgeFileParseApi(id) {
  return request({
    url: `ai_knowledge/file/parse/${id}`,
    method: 'post',
    timeout: 600000,
  });
}

/**
 * 文件分块列表
 */
export function getKnowledgeFileChunksApi(id, params) {
  return request({
    url: `ai_knowledge/file/chunks/${id}`,
    method: 'get',
    params,
  });
}

/**
 * 删除知识库文件
 */
export function knowledgeFileDeleteApi(id) {
  return request({
    url: `ai_knowledge/file/${id}`,
    method: 'delete',
  });
}

/**
 * 知识库检索
 */
export function knowledgeSearchApi(data) {
  return request({
    url: 'ai_knowledge/search',
    method: 'post',
    data,
    timeout: 600000,
  });
}

/**
 * 向量模型状态
 */
export function getKnowledgeModelApi() {
  return request({
    url: 'ai_knowledge/model',
    method: 'get',
  });
}
