<?php

namespace addons\third\library;

use fast\Http;
use think\Config;
use think\Session;
// use EasyWeChat\Foundation\Application;
use EasyWeChat\Factory;

/**
 * 微信
 */
class Wechatmini
{

    /**
     * 配置信息
     * @var array
     */
    private $config = [];

    public function __construct($options = [])
    {
        if ($config = Config::get('third.wechatmini'))
        {
            $this->config = array_merge($this->config, $config);
        }
        $this->config = array_merge($this->config, is_array($options) ? $options : []);
    }



    /**
     * 获取用户信息
     * @param array $params
     * @return array
     */
    public function getUserInfo($params = [])
    {
        $params = $params ? $params : $_GET;
        if (isset($params['code']))
        {
            if ($params['code'])
            {
                $config = $this->config;
                $app = Factory::miniProgram($config);
                $sns = $app->auth->session($params['code']);
                
                if (isset($sns['openid'])){
                    \think\Log::error($sns);
                    if($sns['openid']){
                        // $userinfo = $sns['rawData'] ? json_decode(stripslashes(html_entity_decode($sns['rawData'])),true) : [];
                        // $userinfo['avatar'] = isset($userinfo['avatarUrl']) ? $userinfo['avatarUrl'] : '';
                        // $userinfo['nickname'] = isset($userinfo['nickName']) ? $userinfo['nickName'] : '';
                        $result = [
                            'access_token'  => '',
                            'refresh_token' => '',
                            'expires_in'    => 0,
                            'openid'        => $sns['openid'],
                            'unionid'       => '',
                            'userinfo'      => []
                        ];
                        return $result;
                    }
                }
            }
        }
        return [];
    }
}
