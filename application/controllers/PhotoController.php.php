<?php
Zend_Loader::loadClass("ImgModel");
require_once 'Utility.php';
class PhotoController extends Zend_Controller_Action {

    private $user;
    private $page_num = 5;

    public function init() {
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array('index'));
        $acl->allow('user', $res, array('add', 'delete', 'modify', 'manage', 'like', 'hate'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login", "PleaseLogin");
            exit;
        }
        $this->view->userinfo = $this->user;
    }

    public function indexAction() {
        
    }
    
    public function manageAction(){
        $nid = $this->_getParam("nid");
        if (isset($nid) && is_numeric($nid)) {
            $news = new NewsModel();
            $news->updateReadTimes($nid);
            $this->view->news = $news->getSingleNews($nid);
        }else
            redirect ("/news/all", "WrongWay");
    }

}

?>
