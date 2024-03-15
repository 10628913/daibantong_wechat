<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 充值记录管理
 *
 * @icon fa fa-circle-o
 */
class Recharge extends Backend
{

    /**
     * Recharge模型对象
     * @var \app\admin\model\user\Recharge
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Recharge;
    }



    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->where(['user_id' => $ids, 'pay_result' => 1, 'flag' => 1])
            ->order($sort, $order)
            ->paginate($limit);
        foreach ($list as &$v) {
            $v['admin_username'] = '';
            if ($v['admin_id']) {
                $v['admin_username'] = $v->admin->username;
            }
        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }
}
