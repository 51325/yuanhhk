<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Validate;

/**
 * 站内留言
 *
 * @icon fa fa-circle-o
 */
class CmsMessage extends Frontend
{
    
    /**
     * CmsMessage模型对象
     * @var \app\admin\model\CmsMessage
     */
    protected $model = null;

    protected $noNeedLogin = ['add'];
    protected $noNeedRight = ['*'];
	
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\CmsMessage;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    
	

    /**
     * 添加
     */
    public function add()
    {
		
        if ($this->request->isPost()) {
            $name = $this->request->post('name');
            $telephone = $this->request->post('telephone');
            $email = $this->request->post('email');
            $wx = $this->request->post('wx', '');
            $content = $this->request->post('content');
            $address = $this->request->post('address');
			
            $rule = [
                'name'  => 'require|length:1,30',
                'email'     => 'email',
                'telephone'    => 'regex:/^1\d{10}$/'
            ];

            $msg = [
                'name.require' => 'Username can not be empty',
                'name.length'  => 'Username must be 1 to 30 characters',
                'email'            => 'Email is incorrect',
                'telephone'           => 'Mobile is incorrect',
            ];
            $data = [
                'name'  => $name,
                'telephone'  => $telephone,
                'email'     => $email,
            ];
            
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()));
            }
			$result = $this->model->addData($name, $telephone, $wx, $email, $address, $content);
            if ($result) {
                $this->success(__('提交成功'));
            } else {
                $this->error($result);
            }
        }
        return $this->view->fetch();
    }

}
