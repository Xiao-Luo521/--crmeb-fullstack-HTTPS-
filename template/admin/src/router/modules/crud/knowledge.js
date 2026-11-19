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

export default {
    path: `${routePre}`,
    name: 'ai_knowledge',
    header: '',
    meta,
    component: LayoutMain,
    children: [
        {
            path: 'ai/knowledge',
            name: 'ai_knowledge_index',
            meta: {
                auth: ['ai-knowledge'],
                title: '知识库',
            },
            component: () => import('@/pages/ai/knowledge/index'),
        },
    ],
}
