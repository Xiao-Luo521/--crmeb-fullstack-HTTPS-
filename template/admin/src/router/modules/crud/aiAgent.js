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

const pre = 'ai_agent_'

export default {
    path: `${routePre}`,
    name: 'crud_ai_agent',
    header: '',
    meta,
    component: LayoutMain,
    children: [
        {
            path: 'crud/ai_agent',
            name: `${pre}list`,
            meta: {
                auth: ['ai_agent-crud-list-index'],
                title: 'agent',
            },
            component: () => import('@/pages/crud/aiAgent/index'),
        },
    ],
}
