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

namespace app\model\crud;


use crmeb\basic\BaseModel;

/**
 * Class AiAgent
 * @date 2026/11/15
 * @package app\model\crud
 */
class AiAgent extends BaseModel
{

    /**
     * 表名
     * @var string
     */
    protected $name = 'ai_agent';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 关闭自动时间戳（时间由业务代码维护）
     * @var bool
     */
    protected $autoWriteTimestamp = false;

}
