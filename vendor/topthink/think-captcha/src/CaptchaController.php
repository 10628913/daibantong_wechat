<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\captcha;

use think\Config;

class CaptchaController
{
    public function index($id = "")
    {
        if(ob_get_contents()) ob_clean();
        $captcha = new Captcha((array)Config::get('captcha'));
        return $captcha->entry($id);
    }
}