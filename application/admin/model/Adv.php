<?php

namespace app\admin\model;

use think\Model;


class Adv extends Model
{

    

    

    // 表名
    protected $name = 'adv';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pos_text'
    ];
    

    
    public function getPosList()
    {
        return ['1' => __('Pos 1'), '2' => __('Pos 2')];
    }


    public function getPosTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pos']) ? $data['pos'] : '');
        $list = $this->getPosList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
