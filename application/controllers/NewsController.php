<?php

class NewsController extends Zend_Controller_Action {

    private $user;
    private $page_num = 5;

    public function init() {
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array('index', 'search', 'detail', 'default', 'ajaxdetail', 'moretag'));
        $acl->allow('user', $res, array('add', 'delete', 'modify', 'manage', 'like', 'hate'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login", "请先登录!");
            exit;
        }
    }

    public function indexAction() {
        
    }
    

}

?>
