<?php

namespace addons\third\library;

use addons\third\model\Third;
use app\common\model\User;
use fast\Random;
use think\Db;
use think\Exception;

/**
 * 第三方登录服务类
 *
 */
class Service
{

    /**
     * 第三方登录
     * @param string $platform 平台
     * @param array  $params   参数
     * @param array  $extend   会员扩展信息
     * @param int    $keeptime 有效时长
     * @return boolean
     */
    public static function connect($platform, $params = [], $extend = [], $keeptime = 0)
    {
        $time = time();
        $nickname = $params['nickname'] ?? ($params['userinfo']['nickname'] ?? '');
        $avatar = $params['avatar'] ?? ($params['userinfo']['avatar'] ?? '');
        $expires_in = $params['expires_in'] ?? 0;
        $unionid = !empty($params['unionid']) ? $params['unionid'] : '';

        $values = [
            'platform'      => $platform,
            'openid'        => $params['openid'],
            'openname'      => $nickname,
            'access_token'  => $params['access_token'] ?? '',
            'refresh_token' => $params['refresh_token'] ?? '',
            'expires_in'    => $expires_in,
            'logintime'     => $time,
            'expiretime'    => $time + $expires_in,
        ];
        $values = array_merge($values, $params);

        $auth = \app\common\library\Auth::instance();

        $auth->keeptime($keeptime);
        //是否有自己的
        $third = Third::get(['platform' => $platform, 'openid' => $params['openid']], 'user');
        if ($third) {
            if (!$third->user) {
                $third->delete();
            } else {
                $third->allowField(true)->save($values);
                // 写入登录Cookies和Token
                return $auth->direct($third->user_id);
            }
        }

        //存在unionid就需要判断是否需要生成新记录
        if ($unionid) {
            $third = Third::get(['platform' => $platform, 'unionid' => $unionid], 'user');
            if ($third) {
                if (!$third->user) {
                    $third->delete();
                } else {
                    // 保存第三方信息
                    $values['user_id'] = $third->user_id;
                    $third = Third::create($values, true);

                    //优化头像存储
                    if ($third->user->nickname == '微信用户' || !$third->user->avatar) {
                        $data = [];
                        if ($nickname) {
                            $data['nickname'] = $nickname;
                        }
                        if ($avatar) {
                            $data['avatar'] = $avatar;
                        }
                        if ($data) {
                            $third->user->save($data);
                        }
                    }
                    // 写入登录Cookies和Token
                    return $auth->direct($third->user_id);
                }
            }
        }

        if ($auth->id) {
            if (!$third) {
                $values['user_id'] = $auth->id;
                Third::create($values, true);
            }
            $user = $auth->getUser();
        } else {
            // 先随机一个用户名,随后再变更为u+数字id
            $username = Random::alnum(6);
            $password = Random::alnum(6);
            $domain = request()->host();

            Db::startTrans();
            try {
                // 默认注册一个会员
                $result = $auth->register($username, $password, $username . '@' . $domain, '', $extend);
                if (!$result) {
                    throw new Exception($auth->getError());
                }
                $user = $auth->getUser();

                // $username = 'u' . $user->id;
                // $email = $username . '@' . $domain;
                $fields = [];

                //判断用户名和邮箱是否已存在
                // $exist = User::getByUsername($username);
                // if (!$exist) {
                //     $fields['username'] = $username;
                // }
                // $exist = User::getByEmail($email);
                // if (!$exist) {
                //     $fields['email'] = $email;
                // }

                //如果昵称为空或为微信用户则修改
                if (!$user['nickname'] || $user['nickname'] == '微信用户') {
                    $fields['nickname'] = $nickname = $fields['username'];
                }

                if ($nickname) {
                    $fields['nickname'] = function_exists("xss_clean") ? xss_clean(strip_tags($nickname)) : strip_tags($nickname);
                }
                if ($avatar) {
                    $fields['avatar'] = function_exists("xss_clean") ? xss_clean(strip_tags($avatar)) : strip_tags($avatar);
                }

                // 更新会员资料
                $user = User::get($user->id);
                $user->save($fields);

                // 保存第三方信息
                $values['user_id'] = $user->id;
                Third::create($values, true);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                \think\Log::record($e->getMessage());
                $auth->logout();
                return false;
            }
        }
        // 写入登录Cookies和Token
        return $auth->direct($user->id);
    }

    /**
     * 判断是否绑定第三方
     * @param string $platform 平台
     * @param string $openid   Openid
     * @param string $apptype  平台类型(web/miniapp/mp)
     * @param string $unionid  Unionid
     * @return bool
     */
    public static function isBindThird($platform, $openid, $apptype = '', $unionid = '')
    {
        $conddtions = [
            'platform' => $platform,
            'openid'   => $openid
        ];
        if ($apptype) {
            $conddtions['apptype'] = $apptype;
        }
        $third = Third::get($conddtions, 'user');
        //第三方存在
        if ($third) {
            //用户失效
            if (!$third->user) {
                $third->delete();
                return false;
            }
            return true;
        }
        if ($unionid) {
            $third = Third::get(['platform' => $platform, 'unionid' => $unionid], 'user');
            if ($third) {
                //
                if (!$third->user) {
                    $third->delete();
                    return false;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * 判断是否在微信内
     * @return bool
     */
    public static function isWechat()
    {
        return strpos(request()->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false;
    }

    /**
     * 获取平台类型
     * @return string
     */
    public static function getApptype()
    {
        //如果是公众号则为mp,网页为web,小程序为miniapp，单独判断
        return self::isWechat() ? 'mp' : 'web';
    }
}
