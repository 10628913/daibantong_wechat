<?php

namespace app\admin\model;

use think\Model;


class Recharge extends Model
{

    

    

    // 表名
    protected $name = 'record_recharge';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
    ];

    public function admin()
    {
        return $this->hasOne('Admin','id','admin_id')->field('username,nickname');
    }

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3')];
    }
    public function getPayStatusList()
    {
        return ['0' => __('Pay_result 0'), '1' => __('Pay_result 1'), '2' => __('Pay_result 2')];
    }

}
