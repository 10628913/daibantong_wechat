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
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'loginByMiniApp'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }
    }

    /**
     * 小程序登录
     *
     * @ApiMethod (POST)
     * @param string $code     Code码
     */
    public function loginByMiniApp()
    {
        $platform = 'wechatmini';
        $code = $this->request->post("code");

        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);

        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'));
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
     * 退出登录
     * @ApiInternal
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
     * @ApiMethod (POST)
     * @ApiParams  (name="avatar", type="string", required=false, description="头像地址") 
     * @ApiParams   (name="nickname", type="string", required=false, description="昵称")
     * @ApiParams   (name="mobile", type="string", required=false, description="手机号")
     * @ApiParams   (name="company_name", type="string", required=false, description="企业名称")
     * @ApiParams   (name="company_position", type="string", required=false, description="职位")
     * @ApiParams   (name="company_business", type="string", required=false, description="主营业务")
     * @ApiParams   (name="company_card", type="string", required=false, description="名片地址")
     */
    public function profile()
    {
        $user = $this->auth->getUser();

        $params = input('', null, 'trim,strip_tags,htmlspecialchars');


        if (!$this->request->isPost()) return;
        $params = input('', null, ['trim', 'strip_tags', 'htmlspecialchars']);
        $rules = [
            'avatar' => 'max:200',
            'nickname' => 'min:2|max:10',
            'mobile' => 'length:11',
            'company_name' => 'min:4|max:20',
            'company_position' => 'min:2|max:20',
            'company_business' => 'min:4|max:100',
            'company_card' => 'max:200',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());


        if (isset($params['nickname']) && $params['nickname']) {
            $nickname = $params['nickname'];
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }

        if (isset($params['avatar']) && $params['avatar']) {
            $avatar = $params['avatar'];
            if (strpos($avatar, 'uploads/') !== false) {
                $imageUrl = ROOT_PATH . 'public' . $avatar;
                if (is_file($imageUrl)) {
                    $thumb = thumb($imageUrl, 300, 300);
                    $thumbUrl = str_replace(ROOT_PATH . 'public', '', $thumb);
                    $user->avatar = $thumbUrl;
                }
            }
        }

        if (isset($params['mobile']) && $params['mobile']) {
            $mobile =  $params['mobile'];
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user->mobile = $mobile;
        }
        if (isset($params['company_name']) && $params['company_name']) {
            $user->company_name = $params['company_name'];
        }
        if (isset($params['company_position']) && $params['company_position']) {
            $user->company_position = $params['company_position'];
        }
        if (isset($params['company_business']) && $params['company_business']) {
            $user->company_business = $params['company_business'];
        }
        if (isset($params['company_card']) && $params['company_card']) {
            $user->company_card = $params['company_card'];
        }

        $user->save();
        $this->success('操作成功');
    }


    /**
     * 获取自己的最新信息
     * @ApiMethod (POST)
     */
    public function getMyInfo()
    {
        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        // $siteConfig = Config::get("site");
        // $searchMoney = $siteConfig['shesu_money'];
        // $user['residue_search_count'] = 0;
        // if ($searchMoney && $searchMoney > 0) {
        //     $user['residue_search_count'] = floor($user['amount'] / $searchMoney) ?: 0;
        // }
        $user['has_search_count'] = Db::name('record_search')->where('user_id', $user['user_id'])->count();
        $postsDb = Db::name('posts');
        $user['posts_count'] = $postsDb->where('user_id', $user['user_id'])->count();
        $user['like_count'] = $postsDb->where('user_id', $user['user_id'])->sum('like_num');
        $user['message_count'] = Db::name('user_message')->where(['user_id' => $user['id'], 'is_read' => 0])->count();
        $this->success('获取成功', $user);
    }

    /**
     * 获取用户的资料
     * @ApiMethod (POST)
     * @ApiParams  (name="user_id", type="int", required=true, description="用户ID")
     */
    public function getPersonInfo()
    {
        if (!$this->request->isPost()) return;

        $params = input();
        $rules = [
            'user_id' => 'require|number|max:10'
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $user = Db::name('user')->where('id', $params['user_id'])->field('id,nickname,mobile,avatar,site,identity,company_name,company_position,company_business,company_card,status')->find();
        if (!$user || $user['status'] == 'hidden') {
            $this->error('用户不存在或已冻结!');
        }
        if ($user['mobile']) {
            $user['mobile'] = substr_replace($user['mobile'], '****', 3, 4);
        }
        unset($user['status']);
        $this->success('获取成功', $user);
    }

    /**
     * 绑定用户站点
     * @ApiMethod (POST)
     * @ApiParams  (name="site", type="int", required=true, description="站点id")
     */
    public function userBindSite()
    {
        if (!$this->request->isPost()) return;

        $params = input();
        $rules = [
            'site' => 'require|number|max:10'
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $user = $this->auth->getUserinfo();
        if ($user['site'] != -1) {
            $this->error('用户已绑定过站点,操作失败!');
        }

        $site = Db::name('city_site')->where(['id' => $params['site'], 'show_switch' => 1])->find();
        if (!$site) {
            $this->error('站点不存在或已关闭!');
        }
        if (Db::name('user')->where(['id' => $user['id']])->update(['site' => $site['id']])) {
            $this->success('操作成功!');
        } else {
            $this->error('操作失败!');
        }
    }


    /**
     * 获取用户钱包记录(flag:1=加,2=减)
     * @ApiMethod(POST)
     * @ApiParams   (name="page", type="int", required=false, description="页码(默认1)")
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     */
    public function getSearchRecord()
    {
        if (!$this->request->isPost()) return;

        $params = input();
        $rules = [
            'page' => 'number|max:10',
            'page_size' => 'number|max:2',

        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $user = $this->auth->getUserinfo();
        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;

        $rows = Db::name('record_recharge')
            ->where(['user_id' => $user['id'], 'pay_result' => 1])
            ->order('createtime desc')
            ->limit((intval($page) - 1) * $page_size, $page_size)
            ->field('admin_id,remark,user_id,before,after,pay_result,pay_time,pay_no,order_no', true)
            ->select();
        if (!$rows) $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
        $this->success('获取成功', $rows);
    }


    /**
     * 获取消息记录(type:1=认证审核,2=评论,3=点赞)
     * @ApiMethod(POST)
     * @ApiParams   (name="page", type="int", required=false, description="页码(默认1)")
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     */
    public function getMessageRecord()
    {
        if (!$this->request->isPost()) return;

        $params = input();
        $rules = [
            'page' => 'number|max:10',
            'page_size' => 'number|max:2',

        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $user = $this->auth->getUserinfo();
        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;

        $rows = Db::name('user_message')
            ->where(['user_id' => $user['id']])
            ->order('is_read asc,createtime desc')
            ->limit((intval($page) - 1) * $page_size, $page_size)
            ->select();
        if (!$rows) $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
        $this->success('获取成功', $rows);
    }

    /**
     * 将消息设为已读
     * @ApiMethod(POST)
     * @ApiParams   (name="message_id", type="int", required=true, description="消息id")
     */
    public function setMessageRead()
    {
        if (!$this->request->isPost()) return;
        $params = input();

        $user = $this->auth->getUserinfo();
        $messageDb = Db::name('user_message');
        $row = $messageDb
            ->where(['user_id' => $user['id'], 'id' => $params['message_id']])
            ->find();
        if (!$row) $this->error('消息不存在');
        if ($row['is_read'] == 0) {
            $messageDb->where('id', $row['id'])->update(['is_read' => 1]);
        }
        $this->success('操作成功');
    }


    /**
     * 身份认证
     * @ApiMethod(POST)
     * @ApiParams(name="identity", type="int", required=true, description="认证身份:1=过桥资方,2=私借资方,3=典当资方,4=资深代办人,5=银行人员")
     * @ApiParams(name="name", type="string", required=true, description="姓名")
     * @ApiParams(name="company_name", type="string", required=true, description="企业名称")
     * @ApiParams(name="phone", type="string", required=true, description="手机号")
     * @ApiParams(name="position", type="string", required=true, description="职位")
     * @ApiParams(name="main_business", type="string", required=true, description="主营业务")
     */
    public function identityAuth()
    {
        if (!$this->request->isPost())
            return;
        $user = $this->auth->getUser();

        $params = input();
        $rules = [
            'identity' => 'require|number|in:1,2,3,4,5',
            'name' => 'require|min:2|max:10',
            'company_name' => 'require|min:4|max:20',
            'phone' => 'require|number|length:11',
            'position' => 'require|min:2|max:20',
            'main_business' => 'require|max:100',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) {
            $this->error($validate->getError());
        }
        $userCheckDb = Db::name('user_check');
        $saveData = [
            'user_id' => $user['id'],
            'site' => $user['site'],
            'name' => $params['name'],
            'identity' => $params['identity'],
            'company_name' => $params['company_name'],
            'phone' => $params['phone'],
            'position' => $params['position'],
            'main_business' => $params['main_business'],
            'createtime' => time()
        ];
        $row = $userCheckDb->where(['user_id' => $user['id'], 'identity' => $params['identity']])->find();
        if ($row) {
            if ($row['audit_result'] == 0) {
                $this->error('您已申请过该身份,请等待工作人员进行审核!');
            }
            if ($row['audit_result'] == 1) {
                $this->error('您的身份认证已审核通过,请勿重复申请!');
            }
            $saveData['id'] = $row['id'];
            $result = $userCheckDb->update($saveData);
        } else {
            $result = $userCheckDb->insert($saveData);
        }

        if ($result) {
            $this->success('申请成功,请等待工作人员进行审核!');
        }
        $this->error('申请失败!');
    }

    /**
     * 获取身份认证进度(audit_result:-1=未申请,0=已申请,1=审核通过,2=审核不通过)
     * @ApiMethod(POST)
     */
    public function getAuditProgress()
    {
        if (!$this->request->isPost()) {
            return;
        }
        $user = $this->auth->getUserinfo();
        $rows = Db::name('user_identity')
            ->alias('i')
            ->join('__USER_CHECK__ c', 'c.identity = i.id and c.user_id=' . $user['id'], 'left')
            ->field('i.*,c.audit_result')
            ->order('i.id asc')->select();
            foreach ($rows as &$v) {
                $v['audit_result'] = is_numeric($v['audit_result']) ? $v['audit_result'] : -1;
            }
        $this->success('获取成功', $rows);
    }
}
