<?php

namespace app\admin\model;

use think\Model;


class Ordersdetail extends Model
{

    

    

    // 表名
    protected $name = 'ordersdetail';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'delivertime_text'
    ];
    

    



    public function getDelivertimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delivertime']) ? $data['delivertime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setDelivertimeAttr($value)
    {
        return $value === '' ? null : ($value && is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value);
    }

    public function orders()
    {
        return $this->belongsTo('Orders', 'orders_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
	
	

}
