<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;
    protected $siteWhere = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
        $identityRows = Db::name('user_identity')->select();
        $identityList = [];
        foreach ($identityRows as $key => $v) {
            $identityList[$v['id']] = $v['name'];
        }
        $this->assignconfig('identityObj',json_decode(json_encode($identityList)));

        $siteRows = Db::name('city_site')->select();
        $siteList = ['-1'=>'全国','0'=>'未选择'];
        foreach ($siteRows as $key => $v) {
            $siteList[$v['id']] = $v['name'];
        }
        $this->view->assign('sites', $siteList);
        $this->assignconfig('siteObj',json_decode(json_encode($siteList)));

        if ($this->auth->site) {
            $this->siteWhere = ['site' => $this->auth->site];
        }
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with(['group'])
                ->where($this->siteWhere)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
                
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->where($this->siteWhere)->where('id',$ids)->find();
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 修改站点
     */
    public function edit_site($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->where($this->siteWhere)->where('id',$ids)->find();
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->where($this->siteWhere)->where('id',$ids)->find();
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

    /**
     * 充值
     */
    public function recharge($ids = null)
    {
        $row = $this->model->where($this->siteWhere)->where('id',$ids)->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        if(!is_numeric($params['money'])){
            $this->error('请输入正确的充值金额');
        }
        $data = [
            'amount'=> bcadd($row['amount'],$params['money'],2),
            'recharge_amount'=> bcadd($row['recharge_amount'],$params['money'],2),
        ];
        $result = false;
        Db::startTrans();
        try {
            $before = $row['amount'];
            $result = $row->allowField(true)->save($data);
            $recordData = [
                'title' => '管理员充值',
                'user_id' => $row['id'],
                'admin_id' => $this->auth->id,
                'money' => $params['money'],
                'before' => $before,
                'after' => $data['amount'],
                'createtime' => time(),
                'remark' => $params['remark'] ? : '',
                'pay_result' => 1,
                'site' => $row['site']
            ];
            Db::name('record_recharge')->insert($recordData);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

}
