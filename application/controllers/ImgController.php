<?php

require_once 'Utility.php';

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
            redirect("/user/login", "PleaseLogin");
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
        $id = $this->_getParam("imgid");
        if ($id != null && is_numeric($id)) {
            Zend_Loader::loadClass("ImgModel");
            $img = new ImgModel();
            $img_info = $img->fetchRow("id = $id");
            $file = "./upload/" . $img_info['uid'] . "/.thumbs/images/medium_" . $img_info['key'];
            if (!file_exists($file))
                $file = "./img/img_not_found.jpg";

            $extend = pathinfo($file);
            $extend = strtolower($extend["extension"]);

            header("Content-Type: image/$extend");
            $file = file_get_contents($file);
            echo $file;
        }
    }

    public function avatarAction() {
        $uid = $this->_getParam("uid");
//        $type = $this->_getParam("type", "small");
        if ($uid != null && is_numeric($uid)) {
            Zend_Loader::loadClass("UserModel");
            $user = new UserModel();
            $tmp = $user->getAvatar($uid);
            $file = "./upload/$uid/.thumbs/images/small_$tmp";

            if (!file_exists($file))
                $file = "./img/boy.jpg";

            $extend = pathinfo($file);
            $extend = strtolower($extend["extension"]);

            header("Content-Type: image/$extend");
            $file = file_get_contents($file);
            echo $file;
        }
    }

}

?>
