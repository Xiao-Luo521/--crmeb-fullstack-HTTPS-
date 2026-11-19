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

namespace app\adminapi\validate\crud;


use think\Validate;

/**
 * Class CrudValidate
 * @date 2026/11/15
 * @package app\adminapi\validate\crud
 */
class AiAgentValidate extends Validate
{

    /**
     * @var array
     */
    protected $rule = [
        'name'=> 'require',
        'model_id'=> 'require',
    ];

    /**
     * @var array
     */
    protected $message = [
        'name.require'=> 'agent名称必须填写',
        'model_id.require'=> '聊天模型必须填写',
    ];

    /**
     * @var array
     */
    protected $scene = [

    ];
}
