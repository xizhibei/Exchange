<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("UserModel");
Zend_Loader::loadClass("FriendModel");
require_once 'Utility.php';

class FriendsController extends Zend_Controller_Action {

    private $user;
    private $page_num = 10;

    public function init() {
        //$this->_helper->layout->setLayout('layout');
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array('default'));
        $acl->allow('user', $res, array('search', 'add', 'my', 'delete'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login","请先登录!");
            exit;
        }
    }

    public function indexAction() {
        
    }

    public function myAction() {
        $this->view->headScript()->appendFile("/js/jquery.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.mousewheel-3.0.4.pack.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.fancybox-1.3.4.pack.js");
        $this->view->headLink()->appendStylesheet("/fancybox/jquery.fancybox-1.3.4.css");
        $user = new UserModel();
        $friend = new FriendModel();

        $all = $friend->getAllMy($this->user['uid']); //除了已删除
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function searchAction() {
        if (isset($_GET['q']) && $_GET['q'] != "") {
            $q = $_GET['q'];
            $friend = new FriendModel();

            $all = $friend->search($q,$this->user['uid']);
            $page = $this->_getParam('page', 1); //高置默认页
            if (!is_numeric($page))
                $page = 1;
            $numPerPage = $this->page_num; //每页显示的条数
            $paginator = Zend_Paginator::factory($all);
            $paginator->setCurrentPageNumber($page)
                    ->setItemCountPerPage($numPerPage);
            $this->view->paginator = $paginator;
        }
    }

    public function addAction() {
        if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {
            $this->_helper->layout->setLayout('clean');
            $uid = $_GET['uid'];
            $friend = new FriendModel();
            $outcome = "";
            $status = $friend->friend_status($uid, $this->user['uid']);
            if ($status == FriendModel::Accepted || $status == FriendModel::BLACK_LIST) {//I made it !!!
                $outcome = "你们已经是朋友了哦！";
            } else if ($status == FriendModel::Sended) {
                $outcome = "您已提交好友请求，对方还没有受理，请耐心等待，或给TA发送站内信";
            } else {
                $outcome = "<form method = 'post' action='dealadd'><input type='hidden' name='uid' value='$uid'/><input type='textarea' name='msg'/></form>";
            }
            $this->view->outcome = $outcome;
        }else
            redirect("/index","走错地方了吧!");
    }

    public function dealaddAction() {//ajax
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        if (!empty($_POST) && is_numeric($_POST['uid'])) {
            $friend = new FriendModel();
            if ($_POST['uid'] != $this->user['uid'])
                $status = $friend->friend_status($_POST['uid'], $this->user['uid']);
            else {
                echo "fail";
                return;
            }
            if ($status == -1) {
                $data = array(
                    'app_uid' => $this->user['uid'],
                    'rec_uid' => $_POST['uid'],
                    'msg' => htmlentities($_POST['msg'], ENT_QUOTES),
                    'status' => FriendModel::Sended,
                    'date' => time(),
                );
                $friend->insert($data);
                echo "success";
            } else if ($status == FriendModel::Rejected) {
                $data = array(
                    'msg' => htmlentities($_POST['msg'], ENT_QUOTES),
                    'status' => FriendModel::Sended,
                    'date' => time(),
                );
                $friend->update($data, "app_uid = " . $this->user['uid'] . " and rec_uid = " . $_POST['uid']);
                echo "success";
            }else
                echo "fail";
        }
    }

    public function deleteAction() {
        if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {
            $uid = $_GET['uid'];
            $user = new UserModel();
            $tmp = $user->fetchRow("uid = $uid");
            $this->view->question = "<a href='/user/profile?uid=$uid'><img src='" . $tmp['small_avater'] . "' alt='" . $tmp['name'] . "' class='avater'/></a>" . $tmp['name'] . "";
            $this->view->question .= "<a href='/user/delete?uid=$uid&confirm=true'>确认</a>";
            if (isset($_GET['confirm']) && $_GET['confirm'] == "true") {
                $friend = new FriendModel();
                $status = $friend->friend_status($uid, $this->user['uid']);
                if ($status == FriendModel::Accepted) {
                    $data = array(
                        'status' => FriendModel::Deleted,
                        'date' => time(),
                    );
                    $friend->update($data, "(app_uid = " . $this->user['uid'] . " and rec_uid = $uid) or (app_uid = $uid and rec_uid = " . $this->user['uid'] . ")");
                    redirect("my","删除成功!");
                }else
                    redirect("my","出现错误了...");
            }
        }
    }

}

?>