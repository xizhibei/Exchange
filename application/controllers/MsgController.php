<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("FriendModel");
Zend_Loader::loadClass("UserModel");
Zend_Loader::loadClass("MessageModel");
Zend_Loader::loadClass("SendMsgForm");

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
            header("Location:/redirect?url=/user/login&msg=" . urlencode("请先登录!"));
            exit;
        }
    }

    public function indexAction() {
        self::inboxAction();
        $this->render("inbox");
    }

    public function showAction() {
        if (isset($_GET['mid']) && is_numeric($_GET['mid'])) {
            $mid = $_GET['mid'];
            $message = new MessageModel();
            $tmp = $message->fetchRow("mid = $mid");
            if ($tmp['to_uid'] == $this->user['uid'] || $tmp['from_uid'] == $this->user['uid']) {
                if ($tmp['status'] == MessageModel::Deleted) {
                    header("Location:/redirect?url=index&msg=" . urlencode("消息不存在!"));
                    return;
                }
                $user = new UserModel();
                $this->view->msg = $tmp;
                $this->view->user = $user->fetchRow("uid = " . $tmp['from_uid']);
                if ($tmp['status'] == MessageModel::Sended)
                    $message->update(array('status' => MessageModel::Readed), "mid = $mid");
            } else {
                header("Location:/redirect?url=/msg/index&msg=" . urlencode("有错误哦。。。"));
            }
        }else
            header("Location:/redirect?url=/msg/index&msg=" . urlencode("走错地方了吧!"));
    }

    public function quickmsgAction() {
        $this->view->headTitle("发送站内信");
        $this->_helper->layout->disableLayout(); //disable layout
//        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        if ($this->_request->isPost()) {
            
        }
        if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {
            $uid = $_GET['uid'];
            $user = new UserModel();
            $tmp = $user->fetchRow("uid = $uid");
            $this->view->user = $tmp->toArray();
        }else
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
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

        if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {
            $uid = $_GET['uid'];
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
                header("Location:/redirect?url=index&msg=" . urlencode("成功!"));
            }
        }else
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
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
        if (isset($_GET['mid']) && is_numeric($_GET['mid'])) {
            $mid = $_GET['mid'];
            $message = new MessageModel();
            $tmp = $message->fetchRow("mid = $mid");
            if ($tmp['to_uid'] == $this->user['uid']) {//收信者
                $this->view->confirm_link = "<a href='/msg/delete?mid=$mid&confirm=true'>确认</a>";
                if (isset($_GET['confirm']) && $_GET['confirm'] == "true") {
                    if ($tmp['to_del'] == 1) {
                        header("Location:/redirect?url=/msg/index&msg=" . urlencode("消息不存在!"));
                        return;
                    }else
                        $message->update(array('to_del' => 1), "mid = $mid");
                    header("Location:/redirect?url=/msg/index&msg=" . urlencode("删除成功"));
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
                        header("Location:/redirect?url=/msg/index&msg=" . urlencode("消息不存在!"));
                        return;
                    }else
                        $message->update(array('from_del' => 1), "mid = $mid");
                    header("Location:/redirect?url=/msg/index&msg=" . urlencode("删除成功"));
                }
            }
        }else
            header("Location:/redirect?url=/msg/index&msg=" . urlencode("走错地方了吧!"));
    }

}

?>