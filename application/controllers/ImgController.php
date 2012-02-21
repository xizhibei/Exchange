<?php

class ImgController extends Zend_Controller_Action {

    private $user;

    public function init() {
        //$this->_helper->layout->setLayout('layout');
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res); //, array('code', 'index','avatar')
        $acl->allow('user', $res);
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login","请先登录!");
            exit;
        }

        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
    }

    public function codeAction() {
        //调用我们的验证码类
        Zend_Loader::loadClass('Custom_Controller_Plugin_ImgCode');
        $imagecode = new Custom_Controller_Plugin_ImgCode();
        //返回验证码图片	
        $imagecode->image3();
    }

    public function indexAction() {
        $img = $this->_getParam('file', 'img_not_found.jpg');
        if (!file_exists("../img/$img"))
            $img = "img_not_found.jpg";
        header("Location:/img/$img");
    }

    public function avatarAction() {
        $uid = $this->_getParam("uid");
        if ($uid != null && is_numeric($uid)) {
            Zend_Loader::loadClass("UserModel");
            $user = new UserModel();
            $tmp = $user->getAvatar($uid);
            $img = fread(fopen($tmp['small_avatar'], "rb"), filesize($tmp['small_avatar']));
            $info = getimagesize($tmp['small_avatar']);
            $type = $info['mime'];
            header("content-type:$type");
            echo $img;
            return;
        }
    }

}

?>
