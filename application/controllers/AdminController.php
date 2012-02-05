<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
Zend_Loader::loadClass("LoginForm");
Zend_loader::loadClass("AdminModel");
class AdminController extends Zend_Controller_Action {

    private $user;

    public function init() {
        //$this->_helper->layout->setLayout('layout');
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array());
        $acl->allow('user', $res, array('login'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            header("Location:/redirect?url=/user/login&msg=" . urlencode("请先登录!"));
            exit;
        }
    }

    public function indexAction() {
        
    }

    public function loginAction() {
        $this->view->headTitle("管理员登录");

        $form = new LoginForm();
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $data = $form->getValues();
                $admin = new AdminModel();
                $authAdapter = new Zend_Auth_Adapter_DbTable(
                                $admin->getAdapter(),
                                'admin',
                                'email',
                                'pwd'
                );
                $authAdapter->setIdentity($data['email'])//认证的值
                        ->setCredential($data['password']);
                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter); // 执行认证查询，并保存结果
                if ($result->isValid() && $auth->hasIdentity()) {
                    $tmp = (array) $authAdapter->getResultRowObject();
                    $tmp['role'] = "admin"; //统一字段为role，表示身份
                    $auth->getStorage()->write((object) $tmp);
                    js_alert("登录成功！3秒后自动跳转至主界面！", "/admin/index", 3000);
                    echo '正在跳转，若是您的浏览器没有跳转，可以点击<a href="/index">这里</a>';
                } else {
                    js_alert("密码或者邮箱不正确！");
                }
            } else {
                js_alert("登录失败！请检查输入！");
            }
        }
        $this->view->form = $form;
    }

}

?>
