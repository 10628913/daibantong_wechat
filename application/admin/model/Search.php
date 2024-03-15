<?php

namespace app\admin\model;

use think\Model;


class Search extends Model
{

    

    

    // 表名
    protected $name = 'record_search';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'search_type_text'
    ];
    

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getSearchTypeList()
    {
        return ['1' => __('Search_type 1'), '2' => __('Search_type 2')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSearchTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['search_type']) ? $data['search_type'] : '');
        $list = $this->getSearchTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->hasOne('User','id','user_id')->field('id,mobile,nickname');
    }




}
