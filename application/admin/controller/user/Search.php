<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 查询记录管理
 *
 * @icon fa fa-circle-o
 */
class Search extends Backend
{

    /**
     * Search模型对象
     * @var \app\admin\model\Search
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Search;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("searchTypeList", $this->model->getSearchTypeList());
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
            ->where('user_id', $ids)
            ->order($sort, $order)
            ->paginate($limit);
        foreach ($list as &$v) {
            $v['mobile'] = $v->user->mobile;
        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function tj($ids = null)
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
            ->with('user')
            ->where($where)
            ->where('site', $ids)
            ->order($sort, $order)
            ->paginate($limit);
        $sumData = $this->model->where($where)->where('site', $ids)->field('sum(commission) as commission_sum,sum(money) as money_sum')->find();
        $result = ['total' => $list->total(), 'sumData' => $sumData, 'rows' => $list->items()];
        return json($result);
    }
}
