<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("SaleModel");
Zend_Loader::loadClass("GoodsModel");
Zend_Loader::loadClass("MessageModel");
Zend_Loader::loadClass("NewsModel");
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
            redirect("/user/login", "PleaseLogin");
            exit;
        }
        $this->view->userinfo = $this->user;
    }

    public function indexAction() {
        if (isset($this->user['uid'])) {
            $this->view->content = "Hello Mr." . $this->user['name'] . "<br />Your ID is " . $this->user['uid'];
//            $message = new MessageModel();
//            $msg_count = $message->getUnreadedNum($this->user['uid']);
//            $this->view->msg = "<a href=\"/msg/index\">您有" . $msg_count . "条未读站内信</a>";
//            $sale = new SaleModel();
//            $msg_count = $sale->getUnreadReqNum($this->user['uid']);
//            $this->view->sale_msg = "<a href=\"/sale/request\">您有" . $msg_count . "条未处理交易请求</a>";
//
//            $msg_count = $sale->getUnreadReqOutcomeNum($this->user['uid']);
//            $this->view->sale_outcome_msg = "<a href=\"/sale/unread\">您有" . $msg_count . "条未读交易请求处理结果信息</a>";
        }
        else
            $this->view->content = "Hello " . $this->user['name'];
        $news = new NewsModel();
        $this->view->news = $news->getHotNewsTitle(10);
    }
    
    public function unreadnewAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        if (isset($this->user['uid'])) {
            $message = new MessageModel();
            echo $message->getUnreadedNum($this->user['uid']);
        }else
            echo "-1";
    }

    public function unreadreqAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        if (isset($this->user['uid'])) {
            $sale = new SaleModel();
            echo $sale->getUnreadReqNum($this->user['uid']);
        }else
            echo "-1";
    }

    public function unreadinfoAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        if (isset($this->user['uid'])) {
            $sale = new SaleModel();
            echo $sale->getUnreadReqOutcomeNum($this->user['uid']);
        }else
            echo "-1";
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

