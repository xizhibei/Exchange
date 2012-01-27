<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("RegForm");
Zend_Loader::loadClass("LoginForm");
Zend_Loader::loadClass("UserModifyForm");
Zend_Loader::loadClass("UserModel");
require_once 'Utility.php';

class UserController extends Zend_Controller_Action {

    private $user;

    public function init() {
        //$this->_helper->layout->setLayout('layout');
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array('reg', 'login', 'profile', 'default'));
        $acl->allow('user', $res, array('modify', 'logout'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            header("Location:/redirect?url=/user/login&msg=" . urlencode("请先登录!"));
            exit;
        }
    }

    public function indexAction() {
        
    }

    public function regAction() {
        $this->view->headTitle("注册");
        $form = new RegForm();

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $data = $form->getValues();
                $user = new UserModel();
                if ($user->emailExist($data['email'])) {
                    js_alert("此邮箱已被注册！");
                } else {
                    $insertData = array(
                        'name' => $data['username'],
                        'email' => $data['email'],
                        'pwd' => $data['password'],
                        'regdate' => time(),
                    );
                    $user->insert($insertData);
                    header("Location:/redirect?url=/index&msg=" . urlencode("注册成功"));
                }
            } else {
                echo js_alert("注册失败！请检查输入！");
            }
        }
        $this->view->form = $form;
    }

    public function loginAction() {
        $this->view->headTitle("登录");

        $form = new LoginForm();
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $data = $form->getValues();
                $user = new UserModel();
                $authAdapter = new Zend_Auth_Adapter_DbTable(
                                $user->getAdapter(),
                                'user',
                                'email',
                                'pwd'
                );
                $authAdapter->setIdentity($data['email'])//认证的值
                        ->setCredential($data['password']);
                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter); // 执行认证查询，并保存结果
                if ($result->isValid() && $auth->hasIdentity()) {
                    $tmp = (array) $authAdapter->getResultRowObject();
                    $tmp['role'] = "user"; //统一字段为role，表示身份
                    $auth->getStorage()->write((object) $tmp);
                    header("Location:/redirect?url=/index&msg=" . urlencode("登录成功"));
                } else {
                    js_alert("密码或者邮箱不正确！");
                }
            } else {
                js_alert("登录失败！请检查输入！");
            }
        }
        $this->view->form = $form;
    }

    public function logoutAction() {
        $this->view->headTitle("退出");
        $auth = Zend_Registry::get("auth");
        if (!$auth->getStorage()->isEmpty()) {
            $auth->getStorage()->clear();
            header("Location:/redirect?url=/index&msg=" . urlencode("退出成功"));
        } else {
            header("Location:/redirect?url=/index&msg=" . urlencode("您还没有登录"));
        }
    }

    public function modifyAction() {
        $this->view->headTitle("修改个人信息");
        $this->view->headScript()->appendFile("/js/jquery.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.mousewheel-3.0.4.pack.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.fancybox-1.3.4.pack.js");
        $this->view->headLink()->appendStylesheet("/fancybox/jquery.fancybox-1.3.4.css");
        
        $user = new UserModel();
        $form = new UserModifyForm();
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $data = $form->getValues();
                $updateData = array(
                    'name' => $data['username'],
                    'sex' => $data['sex'],
                    'qq' => $data['qq'],
                    'cellphone' => $data['cellphone'],
                    'big_avater' => $formData['avater'],
                    'small_avater' => $formData['avater'],
                );
                $user->update($updateData, "uid = " . $this->user['uid']);
                header("Location:/redirect?url=/user/modify&msg=" . urlencode("修改成功！"));
            } else {
                js_alert("更新失败！请检查输入！");
            }
        }
        $this->view->user = $user->fetchRow("uid = " . $this->user['uid'])->toArray();
    }

    public function profileAction() {
        if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {
            $uid = $_GET['uid'];
            $user = new UserModel();
            $profile = $user->fetchRow("uid = " . $uid);
            $dispaly = "";
            $dispaly .= "<div><img src='" . $profile['big_avater'] . "' /></div>";
            $dispaly .= "<div>用户名：" . $profile['name'] . "</div>";
            $dispaly .= "<div>性别：" . $profile['sex'] . "</div>";
            if (isset($this->user['uid'])) {
                $dispaly .= "<div>Email：" . $profile['email'] . "</div>";
            }
            $this->view->display = $dispaly;
        }
    }

    public function resetpwdAction() {
        
    }

    public function noRouteAction() {
        $this->_redirect('/');
    }

}

?>
