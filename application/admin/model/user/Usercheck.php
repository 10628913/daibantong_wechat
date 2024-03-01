<?php

namespace app\admin\model\user;

use think\Model;


class Usercheck extends Model
{

    

    

    // 表名
    protected $name = 'user_check';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'identity_text',
        'auth_result_text',
        'audit_time_text'
    ];
    

    
    public function getIdentityList()
    {
        return ['1' => __('Identity 1'), '2' => __('Identity 2'), '3' => __('Identity 3'), '4' => __('Identity 4'), '5' => __('Identity 5')];
    }

    public function getAuthResultList()
    {
        return ['0' => __('Auth_result 0'), '1' => __('Auth_result 1'), '2' => __('Auth_result 2')];
    }


    public function getIdentityTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['identity']) ? $data['identity'] : '');
        $list = $this->getIdentityList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAuthResultTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['auth_result']) ? $data['auth_result'] : '');
        $list = $this->getAuthResultList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAuditTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['audit_time']) ? $data['audit_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAuditTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user(){
        return $this->hasOne('app\admin\model\User','id','user_id')->field('id,nickname,mobile');
    }
    public function admin(){
        return $this->hasOne('app\admin\model\Admin','id','admin_id')->field('id,nickname,username');
    }

}
