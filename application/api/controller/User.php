<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Db;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }
    }


    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->post('mobile');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $siteConfig = Config::get("site");
            $searchMoney = $siteConfig['shesu_money'];
            $data['userinfo']['residue_search_count'] = 0;
            if ($searchMoney && $searchMoney > 0) {
                $data['userinfo']['residue_search_count'] = floor($data['userinfo']['amount'] / $searchMoney) ?: 0;
            }
            $data['userinfo']['has_search_count'] = Db::name('record_search')->where('user_id', $data['userinfo']['user_id'])->count();
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $siteConfig = Config::get("site");
            $searchMoney = $siteConfig['shesu_money'];
            $data['userinfo']['residue_search_count'] = 0;
            if ($searchMoney && $searchMoney > 0) {
                $data['userinfo']['residue_search_count'] = floor($data['userinfo']['amount'] / $searchMoney) ?: 0;
            }
            $data['userinfo']['has_search_count'] = Db::name('record_search')->where('user_id', $data['userinfo']['user_id'])->count();
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @param string $password 密码
     * @param string $mobile   手机号
     * @param string $code     验证码
     * @param string $nickname 昵称
     */
    public function register()
    {
        $password = $this->request->post('password');
        $mobile = $this->request->post('mobile');
        $code = $this->request->post('code');
        $nickname = trim($this->request->post('nickname'));
        if (!$password || !$nickname) {
            $this->error(__('Invalid parameters'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        // $ret = Sms::check($mobile, $code, 'register');
        // if (!$ret) {
        //     $this->error(__('Captcha is incorrect'));
        // }
        $ret = $this->auth->register('', $password, '', $mobile, ['nickname' => $nickname]);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $data['userinfo']['residue_search_count'] = 0;
            $data['userinfo']['has_search_count'] = 0;
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @param string $avatar   头像地址
     * @param string $nickname 昵称
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $nickname = $this->request->post('nickname');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        if ($avatar) {
            $user->avatar = $avatar;
        }
        $user->save();
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }



    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 获取用户最新信息
     * @ApiMethod (POST)
     */
    public function getUserInfo()
    {

        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        $siteConfig = Config::get("site");
        $searchMoney = $siteConfig['shesu_money'];
        $user['residue_search_count'] = 0;
        if ($searchMoney && $searchMoney > 0) {
            $user['residue_search_count'] = floor($user['amount'] / $searchMoney) ?: 0;
        }
        $user['has_search_count'] = Db::name('record_search')->where('user_id', $user['user_id'])->count();
        $this->success('获取成功', $user);
    }


    /**
     * 获取用户充值记录
     * @ApiMethod(POST)
     * @ApiParams   (name="page", type="int", required=false, description="页码(默认1)")
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     */
    public function getSearchRecord()
    {
        if (!$this->request->isPost()) return;

        $params = input();

        $user = $this->auth->getUserinfo();
        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;

        //涉诉记录
        $rows = Db::name('record_recharge')
            ->where(['user_id' => $user['id'], 'pay_result' => 1])
            ->order('createtime desc')
            ->limit((intval($page) - 1) * $page_size, $page_size)
            ->field('admin_id,remark,user_id', true)
            ->select();
        if (!$rows) $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
        $this->success('获取成功', $rows);
    }
}
