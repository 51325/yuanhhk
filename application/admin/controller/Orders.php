<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Orders extends Backend
{
    
    /**
     * Orders模型对象
     * @var \app\admin\model\Orders
     */
    protected $model = null;

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
     * 导入
     */
    public function import()
    {
		$errorMsg="";
		//\think\Log::write("begin import", 'notice');
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }
		//\think\Log::write("begin import2", 'notice');
        //加载文件
        $insert = [];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
			
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
			
            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
					if($maxColumnNumber <= 6){
						if(trim($val) == "姓名"){
							$val = "收件人姓名";
						}
						if(trim($val) == "国内单号"){
							$val = "国内快递单号";
						}
						if(trim($val) == "日期" || trim($val) == "时间"){
							$val = "快递时间";
						}
						if(trim($val) == "快件状态"){
							$val = "内容";
						}
					}
					
					$fields[] = $val;
					
                }
            }
			//\think\Log::write("begin import3", 'notice');
			$details = [];
			$importorderid = "";
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
				$detailindex = 1;
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
				$detail = [];
                $temp = array_combine($fields, $values);
                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
					if(isset($fieldArr[$k]) && $fieldArr[$k] == 'name' && $v !==''){
						$importorderid = $v;
						$count = count($insert);
						if($count > 0){
							$insert[$count - 1]["details"] = $details;
						}
						$details = [];
					}
					if($k == '快递时间' && $v !==''){
						$v = str_replace(".", "", trim($v));
						$is_date=strtotime($v)?strtotime($v):false;
						if($is_date===false){
							$errorMsg= $errorMsg."单号：".$importorderid."的快递时间：".$v."不是正确的时间格式，请修改后重新导入。";
						}else{
							$detail["delivertime"] = $v;
						}
					}else if($k == '内容' && $v !==''){
						$detail["content"] = $v;
						if($maxColumnNumber <= 6){
							if(strpos($v, "国内") !== false){
								$company = substr(strstr($v, "国内"), 6 , strpos(strstr($v, "国内"), "，") - 6);
								$count = count($insert);
								if($count > 0){
									$insert[$count - 1]["inlandcomp"] = $company;
								}
							}else if(strpos($v, "國內") !== false){
								$company = substr(strstr($v, "國內"), 6 , strpos(strstr($v, "國內"), "，") - 6);
								$count = count($insert);
								if($count > 0){
									$insert[$count - 1]["inlandcomp"] = $company;
								}
							}
							if(strpos($v, "单号：") !== false){
								$inlandorderid = substr(strstr($v, "单号："), 9);
								$count = count($insert);
								if($count > 0){
									$insert[$count - 1]["inlandorderid"] = $inlandorderid;
								}
							}else if(strpos($v, "單號：") !== false){
								$inlandorderid = substr(strstr($v, "單號："), 9);
								$count = count($insert);
								if($count > 0){
									$insert[$count - 1]["inlandorderid"] = $inlandorderid;
								}
							}
						}
					}
                }
				if(isset($detail["delivertime"]) && !empty(trim($detail["delivertime"])) && isset($detail["content"]) && !empty(trim($detail["content"]))){
					$detail['sortid'] = $detailindex;
					$detailindex = $detailindex + 1;
					$details[] = $detail;
				}
                if ($row && !empty($row["name"])) {
                    $insert[] = $row;
                }
				
            }
			//\think\Log::write("begin import4", 'notice');
			$count = count($insert);
			if($count > 0){
				$insert[$count - 1]["details"] = $details;
			}
			$details = [];
        } catch (Exception $exception) {
			//\think\Log::write("error", 'notice');
            $this->error($exception->getMessage());
        }
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }
		if($errorMsg){
			$this->error($errorMsg);
		}
        try {
			//\think\Log::write($insert, 'notice');
			//更新
			foreach ($insert as $k => $v) {
				$details = $v["details"];
				unset($v["details"]);
				$isExists = $this->model->where("name", $v['name'])->count();
				if($isExists > 0){
					$this->model->where("name", $v['name'])->update($v);
					$resultid = $this->model->where("name", $v['name'])->find()["id"];
				}else{
					$this->model->save($v);
					$resultid = $this->model->id;
				}
				if($resultid > 0){
					//save details
					foreach($details as $k => $v){
						$detailmodel = new \app\admin\model\Ordersdetail;
						$v["orders_id"] = $resultid;
						$v["createtime"] = time();
						$v["updatetime"] = time();
						$transtime = $v["delivertime"];
						$isExists = $detailmodel->where(["delivertime" => $transtime, "orders_id" => $resultid])->count();
						if($isExists > 0){
						}else{
							$detailmodel->save($v);
						}
						\think\Log::write($isExists, 'orderdetail');
					}
				}
				$this->model = new \app\admin\model\Orders;
			}
			
        } catch (PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            $this->error($msg);
        } catch (Exception $e) {
            $this->error($e->getMessage());
		}
        $this->success();
    }

	
	public function buildqrcode($ids = NULL){
		$row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
		
		$code = "http://www.huanqiuabc.com/index/search_kuai_di/search.html?orderid=".$row["name"];
		$this->view->assign("compcode", $code); 
		return $this->view->fetch();
	}
}
