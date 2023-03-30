<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Validate;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
     public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Orders;
       
    }
    
	public function order(){
		$orderid = $this->request->param('id');
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
					$order = \app\admin\model\Orders::where("name='".$orderid."'")->find();	
					$orderstr['query_id']=	$order['id'];
					//$orderstrt['query_id']=	$order['id'];
					//$orderstr['date']= $order['createtime'];	
					//$orderstr['query_number']=	$order['name'];
					$order['jilu']= \app\admin\model\Ordersdetail::where("orders_id",$order['id'])->order('id')->select();
					
					foreach ($order['jilu'] as $k=>$v){		
                       				
						$orderstr['jilu'][$k]['jilu_name']=$v['content'];
						$orderstr['jilu'][$k]['jilu_time']=date('Y-m-d H:i:s',strtotime($v['delivertime']));
					}
					
					foreach ($inlanddata as $kk=>$item)
					{						
						////$orderstr['jilu'][$k+1+$kk]['query_id']=$order['id'];
						$orderstr['jilu'][$k+1+$kk]['jilu_name']=$item->context;
						$orderstr['jilu'][$k+1+$kk]['jilu_time']=date('Y-m-d H:i:s',strtotime($item->ftime));
						
					}
					return json_encode($orderstr);
				}else{
					print_r($inlanderror);
				}
			}
			
			if(empty($errormessage)){
				
				// foreach ($details as $k=>$item){
					// echo $item->content;
				// }
				
			}else{
				print_r($errormessage);
			}
			
		}
		exit;
		
			$order = \app\admin\model\Orders::where("name='".$orderid."'")->find();	
			$orderstr['query_id']=	$order['id'];
			$orderstrt['query_id']=	$order['id'];
			//$orderstr['date']= $order['createtime'];	
			//$orderstr['query_number']=	$order['name'];
			$order['jilu']= \app\admin\model\Ordersdetail::where("orders_id",$order['id'])->order('id desc')->select();
			foreach ($order['jilu'] as $k=>$v){
				$orderstr['jilu'][$k]['jilu_id']=$v['id'];						
				$orderstr['jilu'][$k]['date']=$v['createtime'];			
				$orderstr['jilu'][$k]['query_id']=$order['id'];
				$orderstr['jilu'][$k]['jilu_name']=$v['content'];
				$orderstr['jilu'][$k]['jilu_time']=date('Y-m-d',strtotime($v['delivertime']));
			}
			foreach ($order['jilu'] as $k=>$v){
				$orderstrt['jilu'][$k]['jilu_id']=$v['id'];						
				$orderstrt['jilu'][$k]['date']=$v['createtime'];			
				$orderstrt['jilu'][$k]['query_id']=$order['id'];
				$orderstrt['jilu'][$k]['jilu_name']=$v['content'];
				$orderstrt['jilu'][$k]['jilu_time']=date('Y-m-d',strtotime($v['delivertime']));
				
				array_unshift($orderstr['jilu'],$orderstrt['jilu'][$k]);
			}
			var_dump($orderstr);
			exit;
			return json_encode($orderstr);
	   // }
		// foreach ($order['jilu'] as $k=>$v){
			 // $time = time();
			// print_r(unset($order['jilu'][$k]););
		// }
		//echo json_encode($orderd);
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
