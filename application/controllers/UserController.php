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
        $acl->allow('guest', $res, array('reg', 'login', 'profile', 'default', 'active', 'sendactivecode', 'coderequired', 'resetpwd'));
        $acl->allow('user', $res, array('modify', 'logout'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login", "PleaseLogin");
            exit;
        }
        $this->view->userinfo = $this->user;
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
                    $this->view->note = "此邮箱已被注册！";
                } else {
                    $insertData = array(
                        'name' => $data['username'],
                        'email' => $data['email'],
                        'pwd' => $data['password'],
                        'status' => UserModel::NotValid,
                        'code' => $user->getActivationCode(),
                        'code_date' => time(),
                        'regdate' => time(),
                    );
                    $id = $user->insert($insertData);
                    //send activation mail
                    $insertData['uid'] = $id;
                    Zend_Loader::loadClass('Custom_Controller_Plugin_SendMail');
                    $mail = new Custom_Controller_Plugin_SendMail();
                    $mail->send($insertData, "您已经成功注册，请尽快通过邮箱激活", "reg.phtml");

                    redirect("/index", "RegSuccess");
                }
            } else {
                $this->view->note = "注册失败！请检查输入！";
            }
        }
        $this->view->form = $form;
    }

    public function loginAction() {
        $this->view->headTitle("登录");
        $this->view->headScript()->appendFile("/js/jquery.js");
        $this->view->headScript()->appendFile("/js/languages/jquery.validationEngine-zh_CN.js");
        $this->view->headScript()->appendFile("/js/jquery.validationEngine.js");
        $this->view->headLink()->appendStylesheet("/css/validationEngine.jquery.css");

        $auth = Zend_Registry::get("auth");
        if (isset($this->user['code_required']) && $this->user['code_required'] == true)
            $this->view->code_required = true;

        if ($this->getRequest()->isPost()) {
            Zend_Loader::loadClass("Custom_Controller_Plugin_FormValidate");
            $validater = new Custom_Controller_Plugin_FormValidate();
            $data = $this->getRequest()->getPost();
            if ($validater->isValid('login', $data)) {
                $user = new UserModel();
                $result = $user->fetchRow("email = '" . $data['email'] . "'")->toArray();
                if (empty($result)) {
                    $this->view->note = "邮箱不正确！";
                    return;
                }

                //update login date
                $user->updateLoginDate($result['uid']);

                if ($result['login_times'] > 2) {
                    $this->view->code_required = true;
                    $this->user['code_required'] = true;
                    $auth->getStorage()->write((object) $this->user); //update cache
                    if (!isset($data['code']))//code didnot add to form,reload it
                        return;
                    if (!$validater->isValid("authCode", $data['code'])) {
                        $this->view->note = $validater->getMsg();
                        return;
                    }
                }
                if ($result['status'] != UserModel::Normal) {
                    if ($result['status'] == UserModel::Locked) {
                        $this->view->note = "您已被锁定，请点击<a href='/user/sendactivecode'>这里</a>解锁！";
                        return;
                    } else if ($result['status'] == UserModel::NotValid) {
                        $this->view->note = "您还没有激活您的邮箱，激活链接已发送至您的邮箱，请点击激活！";
                        return;
                    }
                }

                if ($user->authenticateValid($result, $data)) {
                    if ($result['login_times'] > 0)
                        $user->clearLoginTimes($result['uid']); //清除登录失败次数，归零
                    $result['role'] = "user"; //统一字段为role，表示身份
//                    $auth = Zend_Registry::get("auth");
                    $auth->getStorage()->write((object) $result);
                    $url = $this->_getParam("return_url");
                    if (!isset($url))
                        $url = "/index";
                    redirect($url, "LoginSuccess");
                } else {
                    $user->incLoginTimes($result['uid']);
                    $this->view->note = "LoginSuccess";
                }
            } else {
                $this->view->note = $validater->getMsg();
            }
        }
    }

    //used in login ,for ajax judge require code or not
    public function coderequiredAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        $email = $this->_getParam("email");

        $validater = new Zend_Validate_EmailAddress();
        if ($validater->isValid($email)) {
            $user = new UserModel();
            if ($user->codeRequired($email)) {
                $this->user['code_required'] = true;
                $auth = Zend_Registry::get("auth");
                $auth->getStorage()->write((object) $this->user); //update cache
                echo "true";
            }
            else
                echo 'false';
        }else
            echo "false";
    }

    public function logoutAction() {
        $this->view->headTitle("退出");
        $auth = Zend_Registry::get("auth");
        if (!$auth->getStorage()->isEmpty()) {
            $auth->getStorage()->clear();
            redirect("/index", "ExitSuccess");
        } else {
            redirect("/index", "NotLogin");
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

                Zend_Loader::loadClass("ImgModel");
                $img = new ImgModel();
                $img_id = $img->addImg($formData['avatar'], $this->user['uid']);

                $updateData = array(
                    'name' => $data['username'],
                    'sex' => $data['sex'],
                    'qq' => $data['qq'],
                    'cellphone' => $data['cellphone'],
                    'avatar_id' => $img_id,
                );
                $user->update($updateData, "uid = " . $this->user['uid']);
                //更新当前认证信息
                $tmp = $user->fetchRow($this->user['uid'])->toArray();
                $tmp['role'] = $this->user['role'];
                $auth = Zend_Registry::get("auth");
                $auth->getStorage()->write((object) $tmp);
                redirect("/user/modify", "UpdateSuccess");
            } else {
                js_alert("更新失败！请检查输入！");
            }
        }
        $this->view->user = $user->getUserWithAvavtar($this->user['uid']);
    }

    public function profileAction() {
        $uid = $this->_getParam("uid");
        if ($uid == null && isset($this->user['uid'])){
            $uid = $this->user['uid'];
        }
        if ($uid != null && is_numeric($uid)) {
            $user = new UserModel();
            $this->view->profile = $user->getUser($uid);
            if (isset($this->user['uid']))
                $this->view->display = true;
            else
                $this->view->display = false;
        }else
            redirect("/index", "WrongWay");
    }

    public function resetpwdAction() {
        $this->view->headTitle("重置密码");
        if ($this->user['role'] == "user")
            $this->view->old_required = true;
        else if (isset($this->user['temp']) && $this->user['temp'] == true)
            $this->view->old_required = false;
        $this->view->headScript()->appendFile("/js/jquery.js");
        $this->view->headScript()->appendFile("/js/languages/jquery.validationEngine-zh_CN.js");
        $this->view->headScript()->appendFile("/js/jquery.validationEngine.js");
        $this->view->headLink()->appendStylesheet("/css/validationEngine.jquery.css");
        if ($this->getRequest()->isPost()) {
            
        }
    }

    public function activeAction() {
        if (isset($this->user['uid'])) {
            $this->view->msg = "请不要在登录时间操作，请先<a href='/user/logout'>退出<a>";
            return;
        }
        $key = $this->_getParam("key");
        $uid = $this->_getParam("uid");
        $type = $this->_getParam("type", "active");
        if ($uid != null && $key != null && is_numeric($uid)) {
            $user = new UserModel();
            $tmp = $user->fetchRow("uid = $uid");

            if (time() - $tmp['code_date'] > 86400) {//24 * 3600
                $this->view->msg = "激活码已过期，请重新<a href='/user/sendactivecode/type/$type'>发送</a>";
                return;
            }

            if ($key != $tmp['code']) {
                $this->view->msg = "激活码不正确";
                return;
            }

            if ($type == "active" && $tmp['status'] == UserModel::NotValid) {
                $user->update(array('status' => UserModel::Normal), "uid = $uid");
                redirect("/user/login", "ActiveSuccess");
            } else if ($type == "unlock" && $tmp['status'] == UserModel::Locked) {
                $user->update(array('status' => UserModel::Normal, 'login_times' => 0), "uid = $uid"); //clean the login times
                redirect("/user/login", "ActiveSuccess1");
            } else if ($type == "findpwd" && $tmp['status'] == UserModel::Normal) {
                $this->user['uid'] = $tmp['uid'];
                $this->user['temp'] = true;
                $auth = Zend_Registry::get("auth");
                $auth->getStorage()->write((object) $this->user); //update cache
                redirect("/user/resetpwd", "ActiveSuccess1");
            }
            else
                $this->view->msg = "您不可，或者，不用激活";
        } else
            $this->view->msg = "亲，走错地方了吧！";
    }

    public function sendactivecodeAction() {
        $this->view->headTitle("发送激活邮件");
        $type = $this->_getParam("type");
        if ($this->getRequest()->isPost()) {
            Zend_Loader::loadClass("Custom_Controller_Plugin_FormValidate");
            $validater = new Custom_Controller_Plugin_FormValidate();
            $data = $this->getRequest()->getPost();
            if ($validater->isValid('sendCode', $data)) {
                $user = new UserModel();
                $result = $user->fetchRow("email = '" . $data['email'] . "'")->toArray();
                if (empty($result)) {
                    $this->view->note = "邮箱不存在！";
                    return;
                }

                if ($result['status'] != UserModel::NotValid
                        || $result['status'] != UserModel::Locked
                        || !($type == "findpwd" && $result['status'] == UserModel::Normal)) {
                    redirect("/index", "您不需要，或者不可发送激活邮件！");
                    return;
                }

                Zend_Loader::loadClass('Custom_Controller_Plugin_SendMail');
                $mail = new Custom_Controller_Plugin_SendMail();
                //generare code and update user
                $result['code'] = $user->getActivationCode();
                $result['code_date'] = time();
                $user->update(array(
                    'code' => $result['code'],
                    'code_date' => $result['code_date']), "uid = " . $result['uid']);

                if ($result['status'] == UserModel::NotValid) {
                    $mail->send($result, "您已经成功注册，请尽快通过邮箱激活", "reg.phtml");
                } else if ($result['status'] == UserModel::Locked) {
                    $mail->send($result, "您由于登录失败次数过多已经被锁定，请尽快通过邮箱激活", "unlock.phtml");
                } else if ($type == "findpwd" && $result['status'] == UserModel::Normal) {
                    $mail->send($result, "请尽快通过邮箱激活并修改密码", "findpwd.phtml");
                } else {
                    redirect("/index", "您不需要，或者不可发送激活邮件！");
                    return;
                }

                redirect("/index", "SendActiveMailSuccess");
            }else
                $this->view->note = $validater->getMsg();
        }
    }

}

?>
