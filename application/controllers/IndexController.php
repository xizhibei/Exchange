<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("SaleModel");
Zend_Loader::loadClass("GoodsModel");
Zend_Loader::loadClass("MessageModel");

class IndexController extends Zend_Controller_Action {

    private $user;

    public function init() {
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array('index', 'default'));
        $acl->allow('user', $res);
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            header("Location:/redirect?url=/user/login&msg=" . urlencode("请先登录!"));
            exit;
        }
    }

    public function indexAction() {
        if (isset($this->user['uid'])) {
            $this->view->content = "Hello Mr." . $this->user['name'] . "<br />Your ID is " . $this->user['uid'] . "<br /><a href='/user/logout'>退出</a>";
            $message = new MessageModel();
            $msg_count = $message->getUnreadedNum($this->user['uid']);
            $this->view->msg = "<a href=\"/msg/index\">您有" . $msg_count . "条未读站内信</a>";
            $sale = new SaleModel();
            $msg_count = $sale->getUnreadedNum($this->user['uid']);
            $this->view->sale_msg = "<a href=\"/sale/request\">您有" . $msg_count . "条未处理交易请求</a>";
        }
        else
            $this->view->content = "Hello " . $this->user['name'];
    }

    public function imgcodeAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        //调用我们的验证码类
        Zend_Loader::loadClass('Custom_Controller_Plugin_ImgCode');
        $imagecode = new Custom_Controller_Plugin_ImgCode();
        //返回验证码图片	
        $imagecode->image3();
    }

    public function captchaAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        $this->codeSession = new Zend_Session_Namespace('code'); //在默认构造函数里实例化

        $captcha = new Zend_Captcha_Image(array('font' => '../public/code/fpnf.ttf', //字体文件路径
                    'fontsize' => 24, //字号
                    'imgdir' => '../public/image/', //验证码图片存放位置
                    'session' => $this->codeSession, //验证码session值
                    'width' => 120, //图片宽
                    'height' => 50, //图片高
                    'wordlen' => 5)); //字母数

        $captcha->setGcFreq(3); //设置删除生成的旧的验证码图片的随机几率
        $captcha->generate(); //生成图片
        $this->view->ImgDir = $captcha->getImgDir();
        $this->view->captchaId = $captcha->getId(); //获取文件名，md5编码
        $this->view->code = $captcha->getWord(); //获取当前生成的验证字符串

        echo $this->codeSession->code;
    }

//    function __call($action, $arguments) {
//        $this->_redirect('./');
//        print_r($action);
//        print_r($arguments);
//    }
}

