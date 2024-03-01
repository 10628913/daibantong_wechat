<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = ['getBaseConfig', 'checkUpdate'];
    protected $noNeedRight = '*';

    public function _initialize()
    {

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Expose-Headers: __token__'); //跨域让客户端获取到
        }
        //跨域检测
        check_cors_request();

        if (!isset($_COOKIE['PHPSESSID'])) {
            Config::set('session.id', $this->request->server("HTTP_SID"));
        }
        parent::_initialize();
    }

    /**
     * 获取基础配置
     */
    public function getBaseConfig()
    {
        $siteConfig = Config::get("site");

        $config = [
            'hosturl' => $siteConfig['hosturl'],
            'kf' => [
                'kf_phone' => $siteConfig['kf_phone'],
                'kf_wechat' => $siteConfig['kf_wechat'],
                'kf_wechat_qrcode' => $siteConfig['kf_wechat_qrcode'],
                'kf_ali' => $siteConfig['kf_ali'],
                'kf_ali_qrcode' => $siteConfig['kf_ali_qrcode'],
                'search_kf' => $siteConfig['search_kf'],
                'result_kf' => $siteConfig['result_kf'],
            ],
            'notice' => $siteConfig['notice'],
            'adv' => [
                'img' => $siteConfig['img'],
                'url' => $siteConfig['url'],
                'is_show' => $siteConfig['is_show'],
                'search_adv_img' => $siteConfig['search_adv_img'],
                'search_adv_link' => $siteConfig['search_adv_link'],
                'search_adv_isshow' => $siteConfig['search_adv_isshow'],
                'adv_content' => $siteConfig['adv_content'],
            ],
            'money' => [
                'shesu' => $siteConfig['shesu_money'],
            ],
            'privacy_policy' => $siteConfig['privacy_policy_content']
        ];
        $this->success('获取成功', $config);
    }

    /**
     * app版本更新
     * @ApiParams  (name="platform", type="int", required=false, description="平台(默认1):1=安卓,2=ios")
     * @ApiParams  (name="version", type="string", required=true, description="当前版本号")
     */
    public function checkUpdate()
    {
        $platform = input('platform') ?: 1;
        $version = input('version') ?: '';
        if (!in_array($platform, [1, 2]) || !$version) $this->error('参数错误');

        $siteConfig = Config::get("site");
        switch ($platform) {
            case 1:
                if ($siteConfig['android_version'] && $siteConfig['android_version'] > $version) {
                    $data = [
                        'version' => $siteConfig['android_version'],
                        'update_content' => $siteConfig['android_update_content'],
                        'url' => strpos($siteConfig['android_apk'], 'http') != -1 || strpos($siteConfig['android_apk'], 'https')  != -1 ? $siteConfig['android_apk'] : $siteConfig['hosturl'] . $siteConfig['android_apk'],
                        'forced_update' => $siteConfig['android_forced_update']
                    ];
                    $this->success('有新版本!', $data);
                } else {
                    $this->error('无新版本');
                }
                break;
            case 2:
                break;
        }
    }
}
