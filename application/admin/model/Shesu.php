<?php

namespace app\admin\model;

use think\Model;


class Shesu extends Model
{

    

    

    // 表名
    protected $name = 'record_shesu';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'search_type_text',
        'result_text'
    ];
    

    
    public function getSearchTypeList()
    {
        return ['1' => __('Search_type 1'), '2' => __('Search_type 2')];
    }

    public function getResultList()
    {
        return ['1' => __('Result 1'), '2' => __('Result 2')];
    }


    public function getSearchTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['search_type']) ? $data['search_type'] : '');
        $list = $this->getSearchTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getResultTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['result']) ? $data['result'] : '');
        $list = $this->getResultList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
