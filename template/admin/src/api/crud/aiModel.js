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
 * 获取列表数据
 * @param params
 * @return {*}
 */
export function getAiModelListApi(params) {
    return request({
        url: 'crud/ai_model',
        method: 'get',
        params,
    });
}

/**
 * 获取添加表单数据
 * @return {*}
 */
export function getAiModelCreateApi() {
    return request({
        url: 'crud/ai_model/create',
        method: 'get',
    });
}

/**
 * 添加数据
 * @param data
 * @return {*}
 */
export function aiModelSaveApi(data) {
    return request({
        url: 'crud/ai_model',
        method: 'post',
        data
    });
}

/**
 * 获取编辑表单数据
 * @param id
 * @return {*}
 */
export function getAiModelEditApi(id) {
    return request({
        url: `crud/ai_model/${id}/edit`,
        method: 'get'
    });
}

/**
 * 修改数据
 * @param id
 * @return {*}
 */
export function aiModelUpdateApi(id, data) {
    return request({
        url: `crud/ai_model/${id}`,
        method: 'put',
        data
    });
}

/**
 * 修改状态
 * @param id
 * @return {*}
 */
export function aiModelStatusApi(id, data) {
    return request({
        url: `crud/ai_model/status/${id}`,
        method: 'put',
        data
    });
}

/**
 * 删除数据
 * @param id
 * @return {*}
 */
export function aiModelDeleteApi(id) {
    return request({
        url: `crud/ai_model/${id}`,
        method: 'delete'
    });
}

/**
 * 模型测试
 * @param id
 * @param data
 * @return {*}
 */
export function aiModelTestApi(id, data) {
    return request({
        url: `crud/ai_model/test/${id}`,
        method: 'post',
        data,
        timeout: 120000
    });
}

/**
 * 获取数据
 * @param id
 * @return {*}
 */
export function getAiModelReadApi(id) {
    return request({
        url: `crud/ai_model/${id}`,
        method: 'get'
    });
}


