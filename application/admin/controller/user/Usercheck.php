<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\Session;

use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 用户认证管理
 *
 * @icon fa fa-circle-o
 */
class Usercheck extends Backend
{

    /**
     * Usercheck模型对象
     * @var \app\admin\model\user\Usercheck
     */
    protected $model = null;
    protected $siteWhere = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\user\Usercheck;
        $this->view->assign("identityList", $this->model->getIdentityList());
        $this->view->assign("authResultList", $this->model->getAuthResultList());

        if ($this->auth->site) {
            $this->siteWhere = ['site' => $this->auth->site];
        }
    }


    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
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
            ->with(['user', 'admin'])
            ->where($this->siteWhere)
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }



    public function audit($ids)
    {
        $row = $this->model
            ->alias('profile')
            ->where($this->siteWhere)
            ->where(['id' => $ids])->find();
        if (false === $this->request->isPost()) {
            if (!$row) {
                $this->error(__('No Results were found'));
            }
            $this->view->assign("row", $row);
            return $this->view->fetch();
        }
        if ($row['audit_result'] != 0)
            $this->error('无法重复审核');
        $params = $this->request->post('row/a');
        if (!in_array($params['audit_result'], ['1', '2'])) {
            $this->error('请选择审核结果');
        }
        if ($params['audit_result'] == 2 && !$params['audit_remark']) {
            $this->error('请输入审核失败原因');
        }
        $auditData = [
            'id' => $ids,
            'audit_result' => $params['audit_result'],
            'audit_remark' => $params['audit_remark'],
            'audit_time' => time(),
            'admin_id' => Session::get('admin')['id']
        ];

        Db::startTrans();
        try {
            if ($this->model->update($auditData)) {
                //生成用户通知
                $content = '';
                $identityName = Db::name('user_identity')->where('id', $row['identity'])->value('name');
                if ($auditData['audit_result'] == 1) {
                    $userDb = Db::name('user');
                    $user = $userDb->where('id', $row['user_id'])->find();
                    if (!$user) {
                        $this->error('用户信息错误');
                    } else {
                        $identity = explode(',', $user['identity']);
                        if (!$identity) {
                            $identity = $row['identity'];
                            $userDb->update(['id' => $user['id'], 'identity' => $identity]);
                        } elseif (!in_array($row['identity'], $identity)) {
                            $identity[] = $row['identity'];
                            $userDb->update(['id' => $user['id'], 'identity' => implode(',', $identity)]);
                        }
                    }
                    $content = '您好，您的【' . $identityName . '】身份认证已通过！请后续遵循平台规则发帖。';
                } else {
                    $content = '您好，您的【' . $identityName . '】身份认证未通过！原因: ' . $auditData['audit_remark'];
                }

                Db::name('user_message')->insert([
                    'user_id' => $row['user_id'],
                    'type' => 1,
                    'content' => $content,
                    'item_id' => $row['id'],
                    'createtime' => time()
                ]);
                Db::commit();
                $this->success();
            } else {
                $this->error();
            }
        } catch (\think\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }
}
