<?php

namespace app\admin\controller\posts;

use app\common\controller\Backend;
use think\Db;

/**
 * 帖子管理
 *
 * @icon fa fa-circle-o
 */
class Posts extends Backend
{

    /**
     * Posts模型对象
     * @var \app\admin\model\posts\Posts
     */
    protected $model = null;
    protected $siteWhere = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\posts\Posts;
        $this->view->assign("cidList", $this->model->getCidList());
        $this->view->assign("isRecommendList", $this->model->getIsRecommendList());
        $this->view->assign("isTopList", $this->model->getIsTopList());
        $this->view->assign("visibilityDataList", $this->model->getVisibilityDataList());

        $typeRows = Db::name('posts_type')->order('weigh desc')->select();
        $typeList = [];
        foreach ($typeRows as $key => $v) {
            $typeList[$v['id']] = $v['type_name'];
        }
        $this->view->assign('typelist', $typeList);
        $this->assignconfig('typelistObj',json_decode(json_encode($typeList)));

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
            ->with(['user'])
            ->where($this->siteWhere)
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


}
