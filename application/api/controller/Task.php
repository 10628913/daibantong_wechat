<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Exception;

/**
 * 任务接口
 */
class Task extends Api
{
    protected $noNeedLogin = [''];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 获取任务列表
     * @ApiMethod (POST)
     * @param string $title 标题
     * @param string $status 任务状态
     * @ApiParams   (name="page", type="int", required=false, description="页码(默认1)")
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     */
    public function getUserTask()
    {
        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        $where = [
            'user_id' => $user['id']
        ];
        if (input('title')) $where['title'] = ['like', '%' . input('title') . '%'];
        if (input('status')) $where['status'] = input('status');
        $params = input();
        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;
        $rows = Db::name('task')->where($where)->order('createtime desc')->field('user_id', true)->limit((intval($page) - 1) * $page_size, $page_size)->select();
        if (!$rows) $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
        $this->success('获取成功!', $rows);
    }

    /**
     * 获取任务详情
     * @ApiMethod (POST)
     * @param string $tid 任务ID
     */
    public function getTaskInfo()
    {
        if (!$this->request->isPost()) return;
        if (!input('tid') || !is_numeric(input('tid'))) $this->error('参数错误');
        $user = $this->auth->getUserinfo();

        $where = [
            'user_id' => $user['id'],
            'id' => input('tid'),
        ];
        $row = Db::name('task')->where($where)->field('user_id', true)->find();
        if ($row) {
            $this->success('获取成功!', $row);
        } else {
            $this->error('获取失败!');
        }
    }

    /**
     * 创建任务
     * @param string $title 任务标题
     * @param string $client_name 客户名称
     * @param string $append 追加字段
     * @param string $content 内容
     * @param string $status 任务状态:1=待办,2=在途,3=已完成,4=无效单"
     */
    public function createTask()
    {
        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        $this->request->filter(['trim']);
        $params = input();
        $data = [
            'user_id' => $user['id'],
            'createtime' => time(),
            'title' => isset($params['title']) && $params['title'] != '' ? $params['title'] : '',
            'client_name' => isset($params['client_name']) && $params['client_name'] != '' ? $params['client_name'] : '',
            'append' => isset($params['append']) && $params['append'] != '' ? html_entity_decode($params['append']) : '',
            'content' => isset($params['content']) && $params['content'] != '' ? html_entity_decode($params['content']) : '',
        ];
        $rowId = Db::name('task')->insertGetId($data);
        if ($rowId) {
            $this->success('创建成功', ['id' => $rowId, 'createtime' => $data['createtime']]);
        } else {
            $this->error('创建失败');
        }
    }

    /**
     * 修改任务
     * @ApiMethod(POST)
     * @ApiParams  (name="id", type="string", required=true, description="任务id")
     * @ApiParams  (name="title", type="string", required=false, description="任务标题")
     * @ApiParams  (name="client_name", type="string", required=false, description="客户名称")
     * @ApiParams  (name="append", type="string", required=false, description="追加字段")
     * @ApiParams  (name="content", type="string", required=false, description="内容")
     * @ApiParams  (name="status", type="string", required=false, description="任务状态:1=待办,2=在途,3=已完成,4=无效单")
     */
    public function editTask()
    {
        if (!$this->request->isPost()) return;
        $this->request->filter(['trim']);
        $user = $this->auth->getUserinfo();
        $params = input();
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->error('任务id错误');
        }
        $taskDb = Db::name('task');
        $row = $taskDb->where(['id' => $params['id'], 'user_id' => $user['id']])->find();
        if (!$row) {
            $this->error('记录不存在');
        }
        $data = [
            'id' => $params['id'],
            'updatetime' => time()
        ];

        \think\Log::write($this->request->param('content'));
        if (isset($params['status']) && in_array($params['status'], ['1', '2', '3', '4'])) $data['status'] = $params['status'];
        if (isset($params['title'])) $data['title'] = $params['title'];
        if (isset($params['client_name'])) $data['client_name'] = $params['client_name'];
        if (isset($params['append'])) $data['append'] = html_entity_decode($params['append']);
        if (isset($params['content'])) $data['content'] = html_entity_decode($params['content']);
        if ($taskDb->update($data)) {
            $this->success('编辑成功', ['updatetime' => $data['updatetime']]);
        } else {
            $this->error('编辑失败');
        }
    }
    /**
     * 删除任务
     * @ApiMethod(POST)
     * @ApiParams  (name="id", type="string", required=true, description="任务id")
     */
    public function deleteTask()
    {
        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        $params = input();
        if (!isset($params['id']) || !is_numeric($params['id'])) {
            $this->error('任务id错误');
        }
        $taskDb = Db::name('task');
        $row = $taskDb->where(['id' => $params['id'], 'user_id' => $user['id']])->find();
        if (!$row) {
            $this->error('记录不存在');
        }
        if ($taskDb->where('id', $params['id'])->delete()) {
            $this->success('删除成功!');
        } else {
            $this->error('删除失败!');
        }
    }

    /**
     * 任务迁移(data = [{'title':'任务标题','client_name':'客户名称','append':'[{"name":"自定义字段名1","val":"字段值1"},{"name":"自定义字段名2","val":"字段值2"}]','content':'内容','createtime':'1699068272','updatetime':'1699068272','status':1}])
     * @ApiMethod(POST)
     * @ApiInternal
     * @ApiParams  (name="data", type="string", required=true, description="迁移内容",sample="")
     */
    public function migrationTask()
    {
        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        $data = html_entity_decode(input('data'));
        if (!$data) $this->error('参数不足');
        $dataArray = json_decode($data, true);
        if (!$dataArray || count($dataArray) == 0) $this->error('任务数据格式错误');

        Db::startTrans();
        try {
            $taskDb = Db::name('task');
            //删除用户旧任务数据
            $taskDb->where('user_id', $user['id'])->delete();
            $saveData = [];
            foreach ($dataArray as $v) {
                $saveData[] = [
                    'user_id' => $user['id'],
                    'title' => $v['title'],
                    'client_name' => $v['client_name'],
                    'append' => $v['append'],
                    'content' => $v['content'],
                    'createtime' => $v['createtime'],
                    'updatetime' => $v['updatetime'],
                    'status' => $v['status'],
                ];
            }
            $taskDb->insertAll($saveData);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error('数据迁移出错');
        }
        $this->success('任务迁移成功!');
    }

    /**
     * 创建/更新任务模板
     */
    public function upTaskTmp()
    {
        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        $data = html_entity_decode(input('data'));
        $task_tmp_db = Db::name('task_tmp');

        $row = $task_tmp_db->where('user_id', $user['id'])->find();
        $saveData = [
            'user_id' => $user['id'],
            'tmp' => $data,
            'updatetime' => time()
        ];
        if ($row) {
            $result = $task_tmp_db->update($saveData);
        } else {
            $result = $task_tmp_db->insert($saveData);
        }
        if (!$result) {
            $this->error('更新失败');
        }
        $this->success('更新成功');
    }

    /**
     * 获取任务模板
     */
    public function getTaskTmp()
    {
        if (!$this->request->isPost()) return;
        $user = $this->auth->getUserinfo();
        $task_tmp_db = Db::name('task_tmp');

        $row = $task_tmp_db->where('user_id', $user['id'])->field('user_id',true)->find();
        if (!$row) {
            $this->error('暂无任务模板');
        }
        $row['tmp'] = json_decode($row['tmp'],true);
        $this->success('获取成功',$row);
    }
}
