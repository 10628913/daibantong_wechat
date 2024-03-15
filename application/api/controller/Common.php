<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\exception\UploadException;
use app\common\library\Upload;
use think\Config;
use think\Db;
use think\Validate;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = ['getBaseConfig', 'checkUpdate','getSites','getSiteNotice','getSiteAdv'];
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
            'identity' => Db::name('user_identity')->order('id asc')->select(),
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
            'money' => [
                'shesu' => $siteConfig['shesu_money'],
                'sijie' => $siteConfig['sijie_money'],
            ],
            'privacy_policy' => $siteConfig['privacy_policy_content']
        ];
        $this->success('获取成功', $config);
    }

    /**
     * 获取站点
     * @ApiMethod(POST)
     */
    public function getSites()
    {
        if (!$this->request->isPost()) return;
        $rows = Db::name('city_site')->where(['show_switch' => 1])->field('id,name')->order('weigh desc')->select();
        if (!$rows) {
            $this->error('暂无站点开放!');
        }
        $this->success('获取成功!', $rows);
    }

    /**
     * 获取站点公告
     * @ApiMethod(POST)
     * @ApiParams  (name="site", type="int", required=true, description="站点id")
     */
    public function getSiteNotice()
    {
        if (!$this->request->isPost()) return;

        $params = input();
        $rules = [
            'site' => 'require|number|max:10'
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $row = Db::name('notice')->where(['site' => $params['site']])->find();
        if (!$row || $row['show_switch'] == 0) {
            $this->error('公告不存在或已关闭!');
        }
        $this->success('获取成功', $row['content']);
    }
    /**
     * 获取站点广告(返回:pos:广告位置,1=任务页,2=查询页)
     * @ApiMethod(POST)
     * @ApiParams  (name="site", type="int", required=true, description="站点id")
     */
    public function getSiteAdv()
    {
        if (!$this->request->isPost()) return;

        $params = input();
        $rules = [
            'site' => 'require|number|max:10'
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $rows = Db::name('adv')->where(['site' => $params['site'],'show_switch'=>1])->field('id,pos,adv_image,adv_url')->select();
        if (!$rows) {
            $this->error('站点暂无广告!');
        }
        $this->success('获取成功', $rows);
    }


    /**
     * 多图上传
     * @ApiMethod(POST)
     * @ApiParams   (name="images", type="file", required=true, description="上传的图片")
     */
    public function uploadImages()
    {

        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $files = request()->file('images');
        if (!$files) {
            $this->error('无上传内容');
        }
        $filePath = DS . 'uploads';
        $arr = [];
        foreach ($files as $i => $file) {
            $info = $file->validate(['size' => 5242880, 'ext' => 'jpg,png,bmp,jpeg,gif,flv,mp4'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                $url = $filePath . '/' . $info->getSaveName();
                $arr[] = $url;
            } else {
                $this->error($file->getError());
            }
        }
        if (!count($arr)) {
            $this->error('上传的图片不符合条件');
        }
        $this->success('上传成功', $arr);
    }

    /**
     * 文件上传
     * @ApiMethod(POST)
     * @ApiParams   (name="attachments", type="file", required=true, description="上传的文件")
     */
    public function uploadFiles()
    {

        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $files = $this->request->file('attachments');
        if (!$files) {
            $this->error('无上传内容');
        }
        $arr = [];
        try {
            foreach ($files as $file) {
                $filename = $file->getInfo()['name'];
                $upload = new Upload($file);
                $attachment = $upload->upload(null, $filename);
                $arr[] = $attachment->url;
            }
        } catch (UploadException $e) {
            $this->error($e->getMessage());
        }
        if (!count($arr)) {
            $this->error('上传的文件不符合条件');
        }
        $this->success('上传成功', $arr);
    }


   
}
