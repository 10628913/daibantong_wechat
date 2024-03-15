<?php

namespace app\api\controller;

use app\admin\model\posts\Posts as PostsModel;
use app\admin\model\posts\Postscomment;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Db;
use think\Validate;

/**
 * 论坛接口
 */
class Posts extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取帖子类型
     * @ApiMethod(POST)
     */
    public function getPostsType()
    {

        if (!$this->request->isPost()) return;
        $rows = Db::name('posts_type')->field('id,type_name')->order('weigh desc')->select();
        if (!$rows) {
            $this->error('暂无内容!');
        }
        $this->success('获取成功', $rows);
    }

    /**
     * 发布帖子
     * @ApiMethod(POST)
     * @ApiParams   (name="content", type="string", required=true, description="帖子内容")
     * @ApiParams   (name="images", type="string", required=false, description="图片/视频")
     * @ApiParams   (name="files", type="string", required=false, description="文件")
     * @ApiParams   (name="is_recommend", type="int", required=false, description="是否推荐(默认0):1=是,0=否"),
     * @ApiParams   (name="is_top", type="int", required=false, description="是否置顶(默认0):1=是,0=否"),
     * @ApiParams   (name="is_show", type="int", required=false, description="是否显示(默认1):1=是,0=否"),
     * @ApiParams   (name="posts_type", type="string", required=false, description="帖子类型(多个用,分隔)"),
     * @ApiParams   (name="visibility", type="string", required=false, description="可见范围(多个用,分隔)"),
     * 
     */
    public function publishPost()
    {
        if (!$this->request->isPost()) return;
        $params = input('', null, ['trim', 'strip_tags', 'htmlspecialchars']);
        $rules = [
            'content' => 'require|min:10',
            'images' => '',
            'files' => '',
            'is_recommend' => 'in:0,1',
            'is_top' => 'in:0,1',
            'posts_type' => 'number',
            'visibility' => 'require|number|max:1'
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        //需求金额
        $needMoney = 0;
        //推荐金额
        $recommendMoney = 0;
        //置顶金额
        $topMoney = 0;

        $config = \think\Config::get('site');
        if (isset($params['is_recommend']) && $params['is_recommend']) {
            $recommendMoney =  $config['post_top_money'];
        }
        if (isset($params['is_recommend']) && $params['is_recommend']) {
            $topMoney = $config['post_top_money'];
        }

        $needMoney = bcadd($recommendMoney, $topMoney);

        $user = $this->auth->getUserinfo();
        if ($needMoney > 0 && $user['amount'] < $needMoney) {
            $this->error('余额不足,需求[' . $needMoney . '],剩余[' . $user['amount'] . ']!');
        }


        $data = [
            'user_id' => $user['id'],
            'cid' => 1,
            'post_type' => $params['posts_type'],
            'site' => $user['site'],
            'is_recommend' => input('is_recommend') ?: 0,
            'is_top' => input('is_top') ?: 0,
            'visibility_data' => $params['visibility'],
            'content' => $params['content']
        ];
        $currentTime = time();
        $endTime = $currentTime + 604800;
        if ($data['is_recommend']) {
            $data['recommend_start_time'] = $currentTime;
            $data['recommend_end_time'] = $endTime;
        }
        if ($data['is_top']) {
            $data['top_start_time'] = $currentTime;
            $data['top_end_time'] = $endTime;
        }

        if (isset($params['images']) && $params['images']) {
            $imageArr = explode(',', $params['images']);
            $params['images'] = [];
            $rootPath = ROOT_PATH . 'public';
            foreach ($imageArr as $i => $v) {
                if (!is_file($rootPath . $v)) {
                    continue;
                }
                $params['images'][] = $v;
            }
            $data['images'] = implode(',', $params['images']);
        }

        if (isset($params['files']) && $params['files']) {
            $filesArr = explode(',', $params['files']);
            $attach_files = [];
            $rootPath = ROOT_PATH . 'public';
            foreach ($filesArr as $i => $v) {
                if (!is_file($rootPath . $v)) {
                    continue;
                }
                $attach_files[] = $v;
            }
            $data['attach_files'] = implode(',', $attach_files);
        }

        $postsDb = new PostsModel();
        $result = $postsDb->insertGetId($data);
        if (!$result) {
            $this->error('发布失败');
        }
        if ($needMoney > 0) {
            Db::name('user')->where('id', $user['id'])->setDec('amount', $needMoney);
            $recordDb = Db::name('record_recharge');
            $recordData = [
                'user_id' => $user['id'],
                'flag' => 2,
                'createtime' => time(),
                'pay_result' => 1,
                'site' => $user['site'],
                'admin_id' => 0
            ];

            if ($recommendMoney > 0) {
                $recordData['title'] = '帖子推荐';
                $recordData['money'] = $recommendMoney;
                $recordDb->insert($recordData);
            }
            if ($topMoney > 0) {
                $recordData['title'] = '帖子置顶';
                $recordData['money'] = $topMoney;
                $recordDb->insert($recordData);
            }
        }

        $this->success('发布成功');
    }



    /**
     * 获取论坛帖子
     * @ApiMethod(POST)
     * @ApiParams  (name="user_id", type="int", required=false, description="发帖人id")
     * @ApiParams   (name="page", type="int", required=false, description="页码(默认1)")
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     * @ApiParams   (name="cid", type="int", required=false, description="帖子分类(默认0):0=普通,1=教程,2=工具,3=文件,4=全国")
    //  * @ApiParams   (name="post_type", type="string", required=false, description="帖子类型(多个用,分隔)")
     * @ApiParams   (name="keywords", type="string", required=false, description="搜索关键字"),
     * @ApiParams   (name="is_recommend", type="int", required=false, description="获取推荐帖子"),
     * 
     */
    public function getPosts()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'user_id' => 'number|max:10',
            'page' => 'number|max:10',
            'page_size' => 'number|max:10',
            'cid' => 'number|in:0,1,2,3,4',
            // 'post_type' => 'max:100',
            'keywords' => 'max:100',
            'is_recommend' => 'eq:1',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $postsDb = new PostsModel();
        //将过期置顶/推荐帖子下架
        $postsDb->where(['is_recommend' => 1, 'recommend_end_time' => ['lt', time()]])->update(['is_recommend' => 0, 'recommend_start_time' => 0, 'recommend_end_time' => 0]);
        $postsDb->where(['is_top' => 1, 'top_end_time' => ['lt', time()]])->update(['is_top' => 0, 'top_start_time' => 0, 'top_end_time' => 0]);

        $where = ['show_switch' => 1];
        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;

        $user = $this->auth->getUserinfo();
        if ($user['site'] != 0) {
            $where['site'] = $user['site'];
        }
        $userId = input('user_id');
        if ($userId) {
            $where['user_id'] = $userId;
        }
        $cid = input('cid');
        if ($cid) {
            $where['cid'] = $cid;
        }
        $keywords = input('keywords');
        if ($keywords) {
            $where['title|content'] = ['like', "%$keywords%"];
        }

        $is_recommend = input('is_recommend');
        if ($is_recommend) {
            $where['is_recommend'] = 1;
        }

        $likeDb = Db::name('posts_like');
        $rows = $postsDb
            ->with(['user'])
            ->where($where)
            ->field('id,user_id,post_type,content,is_recommend,is_top,images,attach_files,createtime,comment_num')
            ->order('is_top desc,is_recommend desc,createtime desc')
            ->limit((intval($page) - 1) * $page_size, $page_size)
            ->select();
        if (!$rows) {
            $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
        }
        foreach ($rows as &$v) {
            $hasLike = $likeDb->where(['posts_id' => $v['id'], 'user_id' => $user['id']])->find();
            $v['is_like'] = $hasLike ? 1 : 0;
            unset($v);
        }
        $this->success('获取成功', $rows);
    }

    /**
     * 获取我的帖子
     * @ApiMethod(POST)
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     * @ApiParams   (name="cid", type="int", required=false, description="帖子分类(默认0):0=普通,1=教程,2=工具,3=文件,4=全国")
     * @ApiParams   (name="is_show", type="int", required=true, description="帖子状态:1=显示中,0=隐藏中")
     * 
     */
    public function getUserPosts()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'page' => 'number|max:10',
            'page_size' => 'number|max:10',
            'is_show' => 'require|in:0,1'
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $postsDb = new PostsModel();
        $user = $this->auth->getUserinfo();
        $where = ['user_id' => $user['id'], 'show_switch' => $params['is_show']];

        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;

        $rows = $postsDb
            ->where($where)
            ->field('id,post_type,content,is_recommend,is_top,images,attach_files,createtime,like_num,comment_num')
            ->order('is_top desc,is_recommend desc,createtime desc')
            ->limit((intval($page) - 1) * $page_size, $page_size)
            ->select();
        if (!$rows) {
            $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
        }
        $this->success('获取成功', $rows);
    }


    /**
     * 获取帖子详情
     * @ApiMethod(POST)
     * @ApiParams  (name="post_id", type="int", required=true, description="帖子")
     */
    public function getPostDetail()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'post_id' => 'require|number|max:10',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $postsDb = new PostsModel();
        $row = $postsDb
            ->with(['user'])
            ->where('id', input('post_id'))
            ->field('id,user_id,content,post_type,title,is_recommend,is_top,images,attach_files,createtime,comment_num')
            ->find();
        if (!$row) {
            $this->error('帖子不存在');
        }

        $this->success('获取成功', $row);
    }

    /**
     * 隐藏/显示帖子
     * @ApiMethod(POST)
     * @ApiParams  (name="post_id", type="int", required=true, description="帖子")
     */
    public function postShowSwitch()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'post_id' => 'require|number|max:10',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());
        $user = $this->auth->getUserinfo();

        $postsDb = new PostsModel();
        $row = $postsDb
            ->with(['user'])
            ->where('id', input('post_id'))
            ->field('id,show_switch,user_id')
            ->find();
        if (!$row) {
            $this->error('帖子不存在');
        }
        if ($row['user_id'] != $user['id']) {
            $this->error('无权访问');
        }
        if ($row['show_switch'] == 1) {
            $postsDb->where('id', $row['id'])->update(['show_switch' => 0]);
            $this->success('帖子已隐藏', ['flag' => 0]);
        } else {
            $postsDb->where('id', $row['id'])->update(['show_switch' => 1]);
            $this->success('帖子已显示', ['flag' => 1]);
        }
    }


    /**
     * 帖子点赞/取消点赞
     * @ApiMethod(POST)
     * @ApiParams  (name="post_id", type="int", required=true, description="帖子id")
     */
    public function postLike()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'post_id' => 'require|number|max:10',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $postsDb = new PostsModel();
        $post = $postsDb->where(['id' => $params['post_id']])->find();
        if (!$post || $post['show_switch'] != 1) {
            $this->error('帖子不存在或已隐藏');
        }

        $user = $this->auth->getUserinfo();
        $likeDb = Db::name('posts_like');
        $hasLike = $likeDb->where(['posts_id' => $post['id'], 'user_id' => $user['id']])->find();
        if ($hasLike) {
            $likeDb->where('id', $hasLike['id'])->delete();
            $postsDb->where(['id' => $params['post_id']])->setDec('like_num', 1);
            $this->success('取消点赞成功', ['flag' => 2]);
        } else {
            $likeDb->insert(['posts_id' => $post['id'], 'user_id' => $user['id'], 'createtime' => time()]);
            $postsDb->where(['id' => $params['post_id']])->setInc('like_num', 1);
            $title = mb_strlen($post['content']) > 20 ? substr($post['content'], 0, 19) + '...' : $post['content'];
            Db::name('user_message')->insert([
                'user_id' => $post['user_id'],
                'type' => 3,
                'content' => $user['nickname'] . '点赞了您的帖子【' . $title . '】',
                'item_id' => $post['id'],
                'createtime' => time()
            ]);
            $this->success('点赞成功', ['flag' => 1]);
        }
    }

    /**
     * 帖子评论
     * @ApiMethod(POST)
     * @ApiParams  (name="post_id", type="int", required=true, description="帖子id")
     * @ApiParams  (name="comment_id", type="int", required=false, description="评论id")
     * @ApiParams  (name="user_id", type="int", required=false, description="评论目标用户")
     * @ApiParams  (name="content", type="string", required=true, description="评论内容")
     */
    public function postComment()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'post_id' => 'require|number|max:10',
            'comment_id' => 'number|max:10',
            'user_id' => 'number|max:10',
            'content' => 'require|max:200',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $postsDb = new PostsModel();
        $post = $postsDb->where(['id' => $params['post_id']])->find();
        if (!$post || $post['show_switch'] != 1) {
            $this->error('帖子不存在或已隐藏');
        }

        $user = $this->auth->getUserinfo();
        $commentDb = Db::name('posts_comment');

        $data = [
            'posts_id' => $post['id'],
            'user_id' => $user['id'],
            'comment_content' => $params['content'],
            'createtime' => time()
        ];

        $commentId = input('comment_id');
        if ($commentId) {
            $comment = $commentDb->where(['id' => $commentId])->find();
            if (!$comment || $comment['posts_id'] != $post['id']) {
                $this->error('评论信息错误!');
            }
            $data['pid'] = $commentId;
        }

        $toUserId = input('user_id');
        if ($toUserId) {
            $toUser = Db::name('user')->where(['id' => $toUserId])->find();
            if (!$toUser || $toUser['status'] != 'normal') {
                $this->error('目标用户不存在或已冻结!');
            }
            $data['to_user_id'] = $toUserId;
        }

        $result = $commentDb->insertGetId($data);
        if (!$result) {
            $this->error('评论失败!');
        }
        $title = mb_strlen($post['content']) > 20 ? substr($post['content'], 0, 19) + '...' : $post['content'];
        Db::name('user_message')->insert([
            'user_id' => $toUserId ? : $post['user_id'],
            'type' => 2,
            'content' => $user['nickname'] . '评论了你:“'.$params['content'].'”<br/>【' . $title . '】',
            'item_id' => $post['id'],
            'createtime' => time()
        ]);
        $this->success('评论成功!');
    }

    /**
     * 获取帖子评论信息
     * @ApiMethod(POST)
     * @ApiParams  (name="post_id", type="int", required=true, description="帖子id")
     * @ApiParams   (name="page", type="int", required=false, description="页码(默认1)")
     * @ApiParams   (name="page_size", type="int", required=false, description="每页数据(默认10)")
     */
    public function getPostsComment()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'post_id' => 'require|number|max:10',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());

        $postsDb = new PostsModel();
        $post = $postsDb->where(['id' => $params['post_id']])->find();
        if (!$post || $post['show_switch'] != 1) {
            $this->error('帖子不存在或已隐藏');
        }

        $page = isset($params['page']) && is_numeric($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) && is_numeric($params['page_size']) ? $params['page_size'] : 10;
        $commentDb = new Postscomment();
        $where = ['posts_id' => $post['id'], 'pid' => 0];
        $totalCount = $commentDb->where(['posts_id' => $post['id']])->count();
        $rows = $commentDb
            ->with(['user'])
            ->where($where)
            ->limit((intval($page) - 1) * $page_size, $page_size)
            ->select();
        if (!$rows) {
            $this->error($page == 1 ? '暂无记录' : '已加载全部记录');
        }
        foreach ($rows as &$v) {
            $v['reply_count'] = $commentDb->where(['posts_id' => $v['posts_id'], 'pid' => $v['id']])->count();
        }
        $this->success('获取成功', ['total_count' => $totalCount, 'comments' => $rows]);
    }

    /**
     * 获取评论回复信息
     * @ApiMethod(POST)
     * @ApiParams  (name="comment_id", type="int", required=true, description="评论id")
     */
    public function getCommentReply()
    {
        if (!$this->request->isPost()) return;
        $params = input();
        $rules = [
            'comment_id' => 'require|number|max:10',
        ];
        $validate = new Validate($rules);
        $result = $validate->check($params);
        if (!$result) $this->error($validate->getError());


        $commentDb = new Postscomment();
        $where = ['id' => $params['comment_id'], 'pid' => 0];
        $row = $commentDb->where($where)->find();
        if (!$row) {
            $this->error('评论信息不存在!');
        }
        $rows = $commentDb
            ->with(['user', 'touser'])
            ->where(['pid' => $row['id']])
            ->order('createtime desc')
            ->select();
        if (!$rows) {
            $this->error('暂无记录');
        }
        $this->success('获取成功', $rows);
    }
}
