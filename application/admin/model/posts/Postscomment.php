<?php

namespace app\admin\model\posts;

use think\Model;

class Postscomment extends Model
{

    // 表名
    protected $name = 'posts_comment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    // 追加属性
    protected $append = [
    ];
    
    public function user(){
        return $this->hasOne('app\admin\model\User','id','user_id')->field('id,nickname,avatar,identity');
    }
    public function touser(){
        return $this->hasOne('app\admin\model\User','id','to_user_id')->field('id,nickname,avatar,identity');
    }

    public function children(){
        return $this->hasMany('Postscomment','id','pid');
    }

}
