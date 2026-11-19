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
export function getAiAgentListApi(params) {
    return request({
        url: 'crud/ai_agent',
        method: 'get',
        params,
    });
}

/**
 * 获取添加表单数据
 * @return {*}
 */
export function getAiAgentCreateApi() {
    return request({
        url: 'crud/ai_agent/create',
        method: 'get',
    });
}

/**
 * 添加数据
 * @param data
 * @return {*}
 */
export function aiAgentSaveApi(data) {
    return request({
        url: 'crud/ai_agent',
        method: 'post',
        data
    });
}

/**
 * 获取编辑表单数据
 * @param id
 * @return {*}
 */
export function getAiAgentEditApi(id) {
    return request({
        url: `crud/ai_agent/${id}/edit`,
        method: 'get'
    });
}

/**
 * 修改数据
 * @param id
 * @return {*}
 */
export function aiAgentUpdateApi(id, data) {
    return request({
        url: `crud/ai_agent/${id}`,
        method: 'put',
        data
    });
}

/**
 * 修改状态
 * @param id
 * @return {*}
 */
export function aiAgentStatusApi(id, data) {
    return request({
        url: `crud/ai_agent/status/${id}`,
        method: 'put',
        data
    });
}

/**
 * 删除数据
 * @param id
 * @return {*}
 */
export function aiAgentDeleteApi(id) {
    return request({
        url: `crud/ai_agent/${id}`,
        method: 'delete'
    });
}

/**
 * 获取数据
 * @param id
 * @return {*}
 */
export function getAiAgentReadApi(id) {
    return request({
        url: `crud/ai_agent/${id}`,
        method: 'get'
    });
}

/**
 * 获取下拉选项（聊天模型、知识库文件）
 * @return {*}
 */
export function getAiAgentOptionsApi() {
    return request({
        url: 'crud/ai_agent/options',
        method: 'get'
    });
}


