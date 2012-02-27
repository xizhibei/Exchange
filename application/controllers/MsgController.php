<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("FriendModel");
Zend_Loader::loadClass("UserModel");
Zend_Loader::loadClass("MessageModel");
Zend_Loader::loadClass("SendMsgForm");
require_once 'Utility.php';

class MsgController extends Zend_Controller_Action {

    private $user;
    private $page_num = 5;

    public function init() {
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
//        $acl->allow('guest', $res);
        $acl->allow('user', $res, array('index', 'message', 'send', 'inbox', 'outbox', 'show', 'quickmsg', 'draft', 'delete'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login","PleaseLogin");
            exit;
        }
        $this->view->userinfo = $this->user;
    }

    public function indexAction() {
        self::inboxAction();
        $this->render("inbox");
    }

    public function showAction() {
        $mid = $this->_getParam("mid");
        if (isset($mid) && is_numeric($mid)) {
            $message = new MessageModel();
            $tmp = $message->fetchRow("mid = $mid");
            if ($tmp['to_uid'] == $this->user['uid'] || $tmp['from_uid'] == $this->user['uid']) {
                if ($tmp['status'] == MessageModel::Deleted) {
                    redirect("index","MsgNotExist");
                    return;
                }
                $user = new UserModel();
                $this->view->msg = $tmp;
                $this->view->user = $user->fetchRow("uid = " . $tmp['from_uid']);
                if ($tmp['status'] == MessageModel::Sended)
                    $message->update(array('status' => MessageModel::Readed), "mid = $mid");
            } else {
                redirect("/msg/index","UnknowError");
            }
        }else
            redirect("/msg/index","WrongWay");
    }

    public function quickmsgAction() {
        $this->view->headTitle("发送站内信");
        $this->_helper->layout->disableLayout(); //disable layout
//        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        if ($this->_request->isPost()) {
            
        }
        $uid = $this->_getParam("uid");
        if (isset($uid) && is_numeric($uid)) {
            $user = new UserModel();
            $tmp = $user->fetchRow("uid = $uid");
            $this->view->user = $tmp->toArray();
        }else
            redirect("/index","WrongWay");
    }

    public function sendAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering

        $form = new SendMsgForm();
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $message = new MessageModel();
                $message->insert(array(
                    'title' => $formData['title'],
                    'message' => $formData['msg'],
                    'from_uid' => $this->user['uid'],
                    'to_uid' => $formData['to_user'],
                    'reply_mid' => 0,
                    'status' => MessageModel::Sended,
                    'date' => time(),
                ));
                echo 'success';
            }else
                echo 'fail';
        } else {
            echo 'fail';
        }
    }

    public function messageAction() {
        $this->view->headTitle("发送站内信");
        $this->view->headScript()->appendFile("/ckeditor/ckeditor.js");

        $uid = $this->_getParam("uid");
        if (isset($uid) && is_numeric($uid)) {
            $user = new UserModel();
            $tmp = $user->fetchRow("uid = $uid");
            $this->view->form = "<div>收件人：<a href='/user/profile?uid=$uid'>" . $tmp['name'] . "</a></div>";
            $form = new SendMsgForm();
//            $form->setAction("send");
            $form->setDefault("to_user", $uid);
            $this->view->form .= $form;

            if ($this->getRequest()->isPost()) {
                $formData = $this->getRequest()->getPost();
                if ($form->isValid($formData)) {
                    $message = new MessageModel();
                    $message->insert(array(
                        'title' => $formData['title'],
                        'message' => $formData['msg'],
                        'from_uid' => $this->user['uid'],
                        'to_uid' => $formData['to_user'],
                        'reply_mid' => 0,
                        'status' => MessageModel::Sended,
                        'date' => time(),
                    ));
                }
                redirect("index","SendSuccess");
            }
        }else
            redirect("/index","WrongWay");
    }

    public function inboxAction() {
        $this->view->headTitle("收件箱");
        $message = new MessageModel();

        $all = $message->getInbox($this->user['uid']); //除了已删除
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function outboxAction() {
        $this->view->headTitle("发件箱");
        $message = new MessageModel();

        $all = $message->getOutbox($this->user['uid']); //除了已删除
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function draftAction() {
        $this->view->headTitle("草稿箱");
        $message = new MessageModel();

        $all = $message->getOutbox($this->user['uid']); //除了已删除
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    //这里不会真删除站内信，只会标记为删除，以后可能需要该下
    public function deleteAction() {
        $mid = $this->_getParam("mid");
        if (isset($mid) && is_numeric($mid)) {
            $message = new MessageModel();
            $tmp = $message->fetchRow("mid = $mid");
            if ($tmp['to_uid'] == $this->user['uid']) {//收信者
                $this->view->confirm_link = "<a href='/msg/delete?mid=$mid&confirm=true'>确认</a>";
                if (isset($_GET['confirm']) && $_GET['confirm'] == "true") {
                    if ($tmp['to_del'] == 1) {
                        redirect("/msg/index","MsgNotExist");
                        return;
                    }else
                        $message->update(array('to_del' => 1), "mid = $mid");
                    redirect("/msg/index","DeleteSuccess");
                }
            } else if ($tmp['from_uid'] == $this->user['uid']) {//发信者
                $this->view->confirm_link = "<a href='/msg/delete?mid=$mid&confirm=true'>确认</a>";
                if (isset($_GET['confirm']) && $_GET['confirm'] == "true") {
                    if ($tmp['status'] == MessageModel::Saved) //草稿直接删除
                        $message->update(array(
                            'from_del' => 1,
                            'to_del' => 1,
                            'status' => MessageModel::Deleted
                                ), "mid = $mid");
                    else if ($tmp['from_del'] == 1) {
                        redirect("/msg/index","MsgNotExist");
                        return;
                    }else
                        $message->update(array('from_del' => 1), "mid = $mid");
                    redirect("/msg/index","DeleteSuccess");
                }
            }
        }else
            redirect("/msg/index","WrongWay");
    }

}

?>