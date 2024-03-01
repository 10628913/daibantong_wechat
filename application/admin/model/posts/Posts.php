<?php

namespace app\admin\model\posts;

use think\Model;
use traits\model\SoftDelete;

class Posts extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'posts';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'cid_text',
        'is_recommend_text',
        'recommend_start_time_text',
        'recommend_end_time_text',
        'is_top_text',
        'top_start_time_text',
        'top_end_time_text',
        'visibility_data_text'
    ];
    

    
    public function getCidList()
    {
        return ['0' => __('Cid 0'), '1' => __('Cid 1'), '2' => __('Cid 2'), '3' => __('Cid 3'), '4' => __('Cid 4')];
    }

    public function getIsRecommendList()
    {
        return ['1' => __('Is_recommend 1'), '0' => __('Is_recommend 0')];
    }

    public function getIsTopList()
    {
        return ['1' => __('Is_top 1'), '0' => __('Is_top 0')];
    }

    public function getVisibilityDataList()
    {
        return ['0' => __('Visibility_data 0'), '1' => __('Visibility_data 1'), '2' => __('Visibility_data 2'), '3' => __('Visibility_data 3'), '4' => __('Visibility_data 4'), '5' => __('Visibility_data 5')];
    }


    public function getCidTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['cid']) ? $data['cid'] : '');
        $list = $this->getCidList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsRecommendTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_recommend']) ? $data['is_recommend'] : '');
        $list = $this->getIsRecommendList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getRecommendStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['recommend_start_time']) ? $data['recommend_start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRecommendEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['recommend_end_time']) ? $data['recommend_end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsTopTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_top']) ? $data['is_top'] : '');
        $list = $this->getIsTopList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTopStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['top_start_time']) ? $data['top_start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getTopEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['top_end_time']) ? $data['top_end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getVisibilityDataTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['visibility_data']) ? $data['visibility_data'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getVisibilityDataList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    protected function setRecommendStartTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRecommendEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setTopStartTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setTopEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setVisibilityDataAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
    protected function setPostTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    public function user(){
        return $this->hasOne('app\admin\model\User','id','user_id')->field('id,nickname');
    }

}
