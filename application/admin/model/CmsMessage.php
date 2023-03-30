<?php

namespace app\admin\model;

use think\Model;
use think\Db;


class CmsMessage extends Model
{

    

    

    // 表名
    protected $name = 'cms_message';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    public function addData($name, $telephone, $wx, $email, $address, $content){
		$params["name"] = $name;
		$params["telephone"] = $telephone;
		$params["wx"] = $wx;
		$params["email"] = $email;
		$params["address"] = $address;
		$params["content"] = $content;
		Db::startTrans();
		try {
			$result = $this->save($params);
			Db::commit();
		} catch (ValidateException $e) {
			Db::rollback();
			return $e->getMessage();
		} catch (PDOException $e) {
			Db::rollback();
			return $e->getMessage();
		} catch (Exception $e) {
			Db::rollback();
			return $e->getMessage();
		}
		if ($result !== false) {
			return true;
		} else {
			return 'No rows were inserted';
		}
	}



}
