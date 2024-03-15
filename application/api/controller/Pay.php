<?php

namespace app\api\controller;

use addons\epay\library\Service;
use think\addons\Controller;
use app\common\controller\Api;
use think\Db;
use think\Exception;
use think\Log;
use think\Request;
use think\Response;
use think\exception\HttpResponseException;

class Pay extends Api
{
    protected $noNeedLogin = ['notifyx'];
    protected $noNeedRight = '*';

    protected $layout = 'default';

    protected $config = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 充值下单
     * @ApiMethod(POST)
     * @ApiParams  (name="amount", type="int", required=true, description="充值金额") 
     */
    public function submitOrder()
    {
        if (!$this->request->isPost()) return;
        $this->request->filter('trim');
        $amount = input('amount');

        if (!$amount || (!in_array($amount, [
            '0.01', '10', '100', '200', '500', '600', '800', '1000', '2000',
            '3000'
        ]) && ($amount != intval($amount) || $amount < 10))) {
            $this->error("请选择正确的充值金额!");
        }

        $user = $this->auth->getUserinfo();
        $orderNo = date("YmdHis") . mt_rand(100000, 999999);
        $orderData = [
            'user_id' => $user['id'],
            'money' => $amount,
            'createtime' => time(),
            'type' => 2,
            'order_no' => $orderNo
        ];
        $row = Db::name('record_recharge')->insertGetId($orderData);
        if ($row) {
            $params = [
                'openid' => Service::getWechatMiniOpenid(),
                'type' => 'wechat',
                'orderid' => $orderNo,
                'title' => '余额充值',
                'amount' => $amount,
                'method' => 'miniapp',
                'notifyurl' => request()->root(true) . '/api/pay/notifyx/paytype/wechat',
                'returnurl' => request()->root(true) . '/result.html?out_trade_no=' . $orderNo,
            ];
            Log::write($params);
            $reqResult = Service::submitOrder($params);
            Log::write('支付开始');
            Log::write($reqResult);
            Log::write('支付结束');
            $this->success('请求成功', ['order_no' => $orderNo, 'pay_content' => $reqResult]);
        }
        $this->error('下单失败!');
    }

    /**
     * 根据订单号获取充值结果
     * @ApiMethod(POST)
     * @ApiParams  (name="order_no", type="int", required=true, description="订单号") 
     */
    public function getRechargeResult()
    {
        if (!$this->request->isPost()) return;
        $orderNo = input('order_no');
        if (!$orderNo || strlen($orderNo) != 20) $this->error('订单号错误');

        $user = $this->auth->getUserinfo();
        $row = Db::name('record_recharge')->where(['user_id' => $user['id'], 'order_no' => $orderNo])->find();
        if (!$row) $this->error('订单号错误');
        $this->success('获取成功', ['pay_result' => $row['pay_result']]);
    }



    protected function returnJson($msg, $data = null, $code = 0)
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];

        //未设置状态码,根据code值判断
        $code = $code >= 1000 || $code < 200 ? 200 : $code;
        $response = Response::create($result, 'json', $code)->header([]);
        throw new HttpResponseException($response);
    }


    /**
     *@ApiInternal
     */
    public function notifyx()
    {
        $paytype = input('paytype');
        $pay = Service::checkNotify($paytype);
        if (!$pay) {
            Log::write('签名错误');
            return;
        }
        $data = Service::isVersionV3() ? $pay->callback() : $pay->verify();

        Db::startTrans();
        try {
            if ($paytype == 'wechat') {
                if (Service::isVersionV3()) {
                    $data = $data['resource']['ciphertext'];
                    $data['total_fee'] = $data['amount']['total'] / 100;
                }
                $out_trade_no = $data['out_trade_no'];
                $trade_state = $data['result_code'];
                $orderDb = Db::name('order');
                $user_db = Db::name('user');
                $record_recharge_db = Db::name('record_recharge');
                $order = $orderDb->where(['order_sn' => $out_trade_no])->find();
                if ($order && $order['pay_result'] == 0) {
                    $user = $user_db->where('id', $order['user_id'])->find();
                    if (!$user) return;
                    $userData = [
                        'amount' => bcadd($user['amount'], $order['money'], 2),
                        'recharge_amount' => bcadd($user['recharge_amount'], $order['money'], 2),
                    ];
                    $rechargeData = [
                        'id' => $order['id'],
                        'pay_no' => $data['transaction_id'],
                        'pay_result' => $trade_state == 'SUCCESS' ? '1' : '2',
                        'pay_time' => time(),
                        'before' => $user['amount'],
                        'after' => $userData['amount'],
                        'site' => $user['site']
                    ];

                    if ($record_recharge_db->update($rechargeData)) {
                        $user_db->where(['id' => $user['id']])->update($userData);
                    }
                    Db::commit();
                }
            }
            if ($paytype == 'alipay') {
                $out_trade_no = $data['out_trade_no'];
                $trade_state = $data['trade_status'];
                Log::write('$trade_state:' . $trade_state);
                $record_recharge_db = Db::name('record_recharge');
                $user_db = Db::name('user');
                $order = $record_recharge_db->where(['order_no' => $out_trade_no])->find();
                if ($order && $order['pay_result'] == 0) {
                    $user = $user_db->where('id', $order['user_id'])->find();
                    if (!$user) return;
                    $userData = [
                        'amount' => bcadd($user['amount'], $order['money'], 2),
                        'recharge_amount' => bcadd($user['recharge_amount'], $order['money'], 2),
                    ];
                    $rechargeData = [
                        'id' => $order['id'],
                        'pay_no' => $data['trade_no'],
                        'pay_result' => $trade_state == 'TRADE_SUCCESS' ? '1' : '2',
                        'pay_time' => strtotime($data['notify_time']),
                        'before' => $user['amount'],
                        'after' => $userData['amount'],
                        'site' => $user['site']
                    ];

                    if ($record_recharge_db->update($rechargeData)) {

                        $user_db->where(['id' => $user['id']])->update($userData);
                    }
                    Db::commit();
                }
            }
        } catch (Exception $e) {
            Db::rollback();
            Log::write('支付失败');
        }

        //下面这句必须要执行,且在此之前不能有任何输出
        return $pay->success()->send();
    }
}
