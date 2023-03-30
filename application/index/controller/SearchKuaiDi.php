<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Validate;

/**
 * 站内留言
 *
 * @icon fa fa-circle-o
 */
class SearchKuaiDi extends Frontend
{
    
    protected $model = null;
	protected $layout = 'default';
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
	
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Orders;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    
	

    /**
     * 添加
     */
    public function search()
    {
		$orderid = $this->request->param('orderid');
		if(!empty($orderid)){
			$inlandorderid = "";
			$inlanddata = [];
			$errormessage = "";
			$inlanderror = "";
			$details = $this->searchCommon($orderid, $inlandorderid, $inlanddata, $errormessage, $inlanderror);
			$total = 0;
			if(is_array($details)){
				$total = count($details);
			}
			if($total > 0){
				if(empty($inlanderror)){
					$this->view->assign('inland', $inlanddata);
				}else{
					$this->view->assign('inlanderror', $inlanderror);
				}
			}
			if(empty($errormessage)){
				$this->view->assign('details', $details);
			}else{
				$this->error($errormessage);
			}
			
			$this->view->assign('inlandorderid', $inlandorderid);
			$this->view->assign('orderid', $orderid);
			$this->view->assign('details', $details);
			$this->view->assign('total', $total);
			
			return $this->view->fetch();
		}else{
			return $this->error("请输入订单号查询。");
		}
    }
	public static function searchCommon($orderid, &$inlandorderid, &$inlanddata, &$errormessage, &$inlanderror){
		if(!empty($orderid)){
			$orderid = preg_replace( '/[\W]/', '', $orderid);
            $details = [];
			$order = \app\admin\model\Orders::where("name='".$orderid."'")->find();
			if(!empty($order) && $order->id > 0){
				$where["orders_id"] = array("eq", $order->id);
				$where["delivertime"] = array("lt", date("Y-m-d H:i:s"));
				$ordersdetail = new \app\admin\model\Ordersdetail();
				$details = $ordersdetail->where($where)->order("delivertime desc")->select();
				
				$total = count($details);
				foreach($details as $k=>$ordersdata){
					$ordersdata["delivertimestr"] = $ordersdata["delivertime"];
				}
				
					//\think\Log::write($order, "test0");
					//\think\Log::write($details, "test0");
				//查国内快递
				if($total > 0 && !empty($order->inlandorderid) && (strstr($details[0]["content"], "国内") > -1 || strstr($details[0]["content"], "國內") > -1)){
					$inlandorderid = $order->inlandorderid;
					//\think\Log::write($inlandorderid, "test1");
					$inlanddata = SearchKuaiDi::searchKuaiDi100($order, $inlanderror);
				}
				if($total > 0){
					return $details;
				}else{
					$errormessage = "未查询到物流信息，请稍后再查。";
				}
            } else {
                $errormessage = "未查询到对应订单，请确认您输入的单号无误。";
            }
		}
	}
	
	public static function searchKuaiDi100($order, &$errormessage){
		try{
			//查快递公司编码
			$codedata = \app\admin\model\Company::where("name='".$order->inlandcomp."'")->find();
			//\think\Log::write($codedata, "test2");
			if(!empty($codedata) && !empty($codedata->code)){
				//参数设置
				$key = 'dIWdxQXZ901';						//客户授权key
				$customer = '37589BCFF1BA98EE95647E08C41A3C16';					//查询公司编号
				$param = array (
					'com' => $codedata->code,			//快递公司编码
					'num' => $order->inlandorderid,	//快递单号
					'phone' => $order->phone,				//手机号
					'from' => '',				//出发地城市
					'to' => '',					//目的地城市
					'resultv2' => '0'			//开启行政区域解析
				);
				
				//请求参数
				$post_data = array();
				$post_data["customer"] = $customer;
				$post_data["param"] = json_encode($param);
				$sign = md5($post_data["param"].$key.$post_data["customer"]);
				$post_data["sign"] = strtoupper($sign);
				
				$url = 'http://poll.kuaidi100.com/poll/query.do';	//实时查询请求地址
				
				$params = "";
				foreach ($post_data as $k=>$v) {
					$params .= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
				}
				$post_data = substr($params, 0, -1);
				
				//发送post请求
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$result = curl_exec($ch);
				$data = str_replace("\"", '"', $result );
				$data = json_decode($data);
				if(!empty($data)){
					if($data->message == "ok"){
						if(count($data->data) >0){
							return $data->data;
						}else{
							$errormessage = "国内快递查询暂无物流信息，请等待物流更新。";
						}
					}else{
						$errormessage = "国内快递查询失败: ".$data->message;
					}
				}else{
					$errormessage = "国内快递查询结果为空。";
				}
				
				//开启订阅
				/*
					//参数设置
					$param = array (
						'company' => $codedata->code,			//快递公司编码
						'number' => $order->inlandorderid,	//快递单号
						'from' => '',					//出发地城市
						'to' => '',						//目的地城市
						'key' => $key,					//客户授权key
						'parameters' => array (
							'callbackurl' => 'http://huanqiuabc.com/kuaidi100callback.php',		//回调地址
							'salt' => '',				//加密串
							'resultv2' => '0',			//行政区域解析
							'autoCom' => '0',			//单号智能识别
							'interCom' => '0',			//开启国际版
							'departureCountry' => '',	//出发国
							'departureCom' => '',		//出发国快递公司编码
							'destinationCountry' => '',	//目的国
							'destinationCom' => '',		//目的国快递公司编码
							'phone' => $order->phone				//手机号
						)
					);
					
					//请求参数
					$post_data = array();
					$post_data["schema"] = 'json';
					$post_data["param"] = json_encode($param);
					
					$url = 'http://poll.kuaidi100.com/poll';	//订阅请求地址
					
					$params = "";
					foreach ($post_data as $k=>$v) {
						$params .= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
					}
					$post_data = substr($params, 0, -1);
					
					//发送post请求
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$result = curl_exec($ch);
					$data = str_replace("\"", '"', $result );
					$data = json_decode($data);
					
					//写入订阅信息
					if($data->result == "true"){
						$order->isbooked = 1;
						$order->save();
					}
					*/
				
			}else{
				$errormessage = "未查询到对应快递公司编码，请联系管理员。";
			}
			return null;
		}catch (Exception $exception){
			$errormessage = $exception->getMessage();
		}
	}
	
	public function searchfor100(){
		$result["message"] = "ok";
		$resultdata = array();
		$orderid = $this->request->param('nu');
		if(!empty($orderid)){
			$inlandorderid = "";
			$inlanddata = [];
			$errormessage = "";
			$inlanderror = "";
			$details = $this->searchCommon($orderid, $inlandorderid, $inlanddata, $errormessage, $inlanderror);
			$total = count($details);
			\think\Log::write($inlanddata, "test0");
			if(empty($errormessage)){
				$result["status"] = "1";
				if($total > 0){
					if(empty($inlanderror)){
						foreach($inlanddata as $k=>$indata){
							$temp["time"] = $indata->time;
							$temp["context"] = $indata->context;
							array_push($resultdata, $temp);
						}
					}
					foreach($details as $j=>$detaildata){
						$temp["time"] = $detaildata["delivertimestr"];
						$temp["context"] = $detaildata["content"];
						array_push($resultdata, $temp);
					}
				}
				$result["data"] = $resultdata;
			}else{
				$result["status"] = "0";
				$result["message"] = $errormessage;
				$result["data"] = "";
				
			}
			echo json_encode($result ,JSON_UNESCAPED_UNICODE);
		}
	}

}
