<?php

namespace app\admin\controller;

use think\Db;
use Exception;
use think\exception\PDOException;
use app\common\controller\Backend;


/**
 * 站点城市管理
 *
 * @icon fa fa-circle-o
 */
class Site extends Backend
{

    /**
     * Site模型对象
     * @var \app\admin\model\Site
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Site;

    }



    /**
     * 删除
     *
     * @param $ids
     * @return void
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        $admin_db = Db::name('admin');
        try {
            foreach ($list as $item) {
                if($admin_db->where('city',$item['id'])->find()){
                    $this->error('站点下绑定有管理员,无法删除,请检查后重试');
                }
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException | Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }


}
