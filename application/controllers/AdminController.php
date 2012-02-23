<?php

/* * ***************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * *************************************************************************** */
Zend_loader::loadClass("AdminModel");
Zend_Loader::loadClass("UserModel");
Zend_Loader::loadClass("GoodsModel");
Zend_Loader::loadClass("NewsModel");
require_once 'Utility.php';

class AdminController extends Zend_Controller_Action {

    private $user;

    public function init() {
//$this->_helper->layout->setLayout('layout');
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->deny('guest', $res);
        $acl->allow('user', $res, array('login', 'logincheck'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login", "请先登录!");
            exit;
        }
        $this->_helper->layout->setLayout('admin');
    }

    public function indexAction() {
        
    }

    public function logincheckAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        $ret_info = array();
        $ret_info[0] = "fail";
        $ret_info[1] = "错误";
        $ret_info[2] = "出错了哦";
        $auth = Zend_Registry::get("auth");
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $admin = new AdminModel();
            //update login date
            $result = $admin->fetchRow("aid = " . $this->user['uid'])->toArray();
            $admin->updateLoginInfo($result['aid'], getIp());

            if ($result['login_times'] > 2) {
                $this->user['code_required'] = true;
                $auth->getStorage()->write((object) $this->user); //update cache
                if (!isset($data['code']))//code didnot add to form,reload it
                    return;
                if (!$validater->isValid("authCode", $data['code'])) {
                    $this->view->note = $validater->getMsg();
                    return;
                }
            }
            if ($result['status'] != AdminModel::Normal) {
                if ($result['status'] == AdminModel::Locked) {
                    $ret_info[2] = "您已被锁定，请联系管理员解锁！";
                    return;
                }
            }

            if ($admin->authenticateValid($result, $data)) {
                if ($result['login_times'] > 0)
                    $admin->clearLoginTimes($result['aid']); //清除登录失败次数，归零
                $result = array_merge($result, $this->user);
                $result['role'] = "admin"; //统一字段为role，表示身份
                $auth->getStorage()->write((object) $result);
                $ret_info[0] = "success";
                $ret_info[1] = "成功";
                $ret_info[2] = "登录成功！";
            } else {
                $admin->incLoginTimes($result['aid']);
                $ret_info[2] = "密码不正确！";
            }
        }
        echo json_encode($ret_info);
    }

    public function loginAction() {
        $this->view->headTitle("管理员登录");
        $this->_helper->layout->disableLayout(); //disable layout

        if (isset($this->user['code_required']) && $this->user['code_required'] == true)
            $this->view->code_required = true;

        $admin = new AdminModel();
        $temp = $admin->fetchRow("aid = " . $this->user['uid']);
        if (empty($temp))
            redirect("/index", "你不是管理员哈~");
    }

    public function logoutAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        $auth = Zend_Registry::get("auth");
        $uid = $this->user['uid'];
        $fully = $this->_getParam("fully");
        if (!$auth->getStorage()->isEmpty()) {
            if ($fully == "true") {
                $auth->getStorage()->clear();
            } else {
                $user = new UserModel();
                $tmp = $user->fetchRow("uid = $uid")->toArray();
                $tmp['role'] = "user";
                $auth->getStorage()->write((object) $tmp);
            }
            redirect("/index", "退出成功");
        } else {
            redirect("/index", "您还没有登录");
        }
    }

    public function usermanageAction() {
        $this->view->headTitle("用户列表");
        $user = new UserModel();
        $all = $user->getAllUser();

        $page = $this->_getParam('page', 1); //高置默认页
        $page_num = $this->_getParam("num", 10);
        if (!is_numeric($page))
            $page = 1;
        if (!is_numeric($page_num))
            $page_num = 10;
        $numPerPage = $page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function userlockAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->_getParam("uid");
        if (isset($uid) && is_numeric($uid)) {
            $user = new UserModel();
            $user->update(array('status' => UserModel::LockedByAdmin), "uid = $uid");
            echo 'success';
        }else
            echo 'fail';
    }

    public function userunlockAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->_getParam("uid");
        if (isset($uid) && is_numeric($uid)) {
            $user = new UserModel();
            $user->update(array('status' => UserModel::Normal), "uid = $uid");
            echo 'success';
        }else
            echo 'fail';
    }

    public function usereditAction() {
        $uid = $this->_getParam("uid");
        if (isset($uid) && is_numeric($uid)) {
            
        }
    }

    public function userdeleteAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->_getParam("uid");
        if (isset($uid) && is_numeric($uid)) {
            $user = new UserModel();
            $user->update(array('status' => UserModel::Deleted), "uid = $uid");
            echo 'success';
        }else
            echo 'fail';
    }

    public function userupgradeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $uid = $this->_getParam("uid");
        if (isset($uid) && is_numeric($uid)) {
            $user = new UserModel();
            $this->view->user = $user->fetchRow("uid = $uid");

            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost();
                $admin = new AdminModel();
                $insertData = array(
                    'aid' => $uid,
                    'pwd' => $data['pwd'],
                    'level' => $data['level']
                );
                $admin->insert($insertData);
            }
            echo 'success';
        } else {
            echo 'fail';
        }
    }

    public function goodsmanageAction() {
        $this->view->headTitle("货物列表");
        $user = new GoodsModel();
        $all = $user->getAllGoods();

        $page = $this->_getParam('page', 1); //高置默认页
        $page_num = $this->_getParam("num", 10);
        if (!is_numeric($page))
            $page = 1;
        if (!is_numeric($page_num))
            $page_num = 10;
        $numPerPage = $page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function goodseditAction() {
        
    }

    public function goodsdeleteAction() {
        
    }

    public function newspublishAction() {
        $this->view->headTitle("新闻发布");
        $this->view->headScript()->appendFile("/ckeditor/ckeditor.js");

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            $news = new NewsModel();
            $insertData = array(
                'title' => $data['title'],
                'content' => $data['content'],
                'publish_time' => time(),
                'update_time' => time(),
                'aid' => $this->user['aid'],
                'status' => NewsModel::Published,
                'type' => $data['type'] == "news" ? NewsModel::News : NewsModel::Announcement,
            );
            $news->insert($insertData);
            redirect("/admin/newmanage", "发布成功");
        }
    }

    public function newsmanageAction() {
        
    }

}

?>
