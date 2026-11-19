// +---------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +---------------------------------------------------------------------
// | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
// +---------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +---------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +---------------------------------------------------------------------

import LayoutMain from '@/layout';
import setting from '@/setting'

let routePre = setting.routePre

const meta = {
    auth: true,
}

const pre = 'ai_model_'

export default {
    path: `${routePre}`,
    name: 'crud_ai_model',
    header: '',
    meta,
    component: LayoutMain,
    children: [
        {
            path: 'crud/ai_model',
            name: `${pre}list`,
            meta: {
                auth: ['ai_model-crud-list-index'],
                title: '模型',
            },
            component: () => import('@/pages/crud/aiModel/index'),
        },
    ],
}
