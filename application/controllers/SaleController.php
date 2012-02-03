<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("UserModel");
Zend_Loader::loadClass("SaleModel");
Zend_Loader::loadClass("GoodsModel");
require_once 'Utility.php';

class SaleController extends Zend_Controller_Action {

    private $user;
    private $page_num = 5;

    public function init() {
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array());
        $acl->allow('user', $res);//, array('sale', 'exchange', 'chooseway', 'request', 'accept', 'refuse', 'detail', 'otherreq')
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            header("Location:/redirect?url=/user/login&msg=" . urlencode("请先登录!"));
            exit;
        }
    }
    
    public function indexAction(){
        
    }
    
    public function sellerAction() {
        $this->view->headTitle("已完成交易（卖家）");

        $sale = new SaleModel();

        $all = $sale->getAllMy($this->user['uid']); //除了已删除
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function buyerAction() {
        $this->view->headTitle("已完成交易（买家）");

        $sale = new SaleModel();

        $all = $sale->getReqOutcome($this->user['uid']); //除了已删除
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function saleAction() {
        $this->view->headTitle("物品交易");
        if (isset($_POST['submit'])) {
            $sale = new SaleModel();
            $sale->insert(array(
                'req_time' => time(),
                'use_goods' => 'money',
                'ask_goods' => $_POST['gid'],
                'status' => $sale->getStatusId("已发送"),
                'buyerid' => $this->user['uid'],
                'sellerid' => $_POST['target_id'],
            ));
            header("Location:/redirect?url=/sale&msg=" . urlencode("请求已发送!"));
            return;
        }
        if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
            $this->view->headScript()->appendFile("/js/jquery.js");
            $goods = new GoodsModel();
            $gid = $_GET['gid'];
            $tmp = $goods->fetchRow("id = " . $gid);
            $this->view->exchange = $tmp;
            $target_id = $tmp['uid'];
            $user = new UserModel();
            $user_profile = $user->fetchRow("uid = " . $target_id);
            $this->view->user_profile = $user_profile;
            $this->view->form = "<input type='hidden' name='target_id' value='" . $target_id . "'/>" . "<input type='hidden' name='gid' value='" . $gid . "'/>";
        }else
            header("Location:/redirect?url=/sale&msg=" . urlencode("走错地方了吧!"));
    }

    public function exchangeAction() {
        $this->view->headTitle("物品交换");
        if (!empty($_POST['my_gids']) && !empty($_POST['other_gids'])) {
            $my_req = "";
            foreach ($_POST['my_gids'] as $gid) {
                $my_req .= $gid . "\t";
            }
            $other_req = "";
            foreach ($_POST['other_gids'] as $gid) {
                $other_req .= $gid . "\t";
            }
            $sale = new SaleModel();
            $sale->insert(array(
                'req_time' => time(),
                'use_goods' => $my_req,
                'ask_goods' => $other_req,
                'status' => $sale->getStatusId("已发送"),
                'buyerid' => $this->user['uid'],
                'sellerid' => $_POST['target_id'],
            ));
            header("Location:/redirect?url=/sale&msg=" . urlencode("请求已发送!"));
            return;
        }
        if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
            $this->view->headScript()->appendFile("/js/jquery.js");
            $goods = new GoodsModel();
            $gid = $_GET['gid'];
            $tmp = $goods->fetchRow("id = " . $gid);
            $this->view->exchange = $tmp;
            $target_id = $tmp['uid'];
            $user = new UserModel();
            $user_profile = $user->fetchRow("uid = " . $target_id);
            $this->view->user_profile = $user_profile;
            $all_goods = $goods->fetchAll("uid = " . $this->user['uid'] . " and status = " . $goods->getStatusId("已发布"));
            $list = "<ul>";
            foreach ($all_goods as $goods) {
                $tmp = strlen($goods['name']) < 60 ? $goods['name'] : cutstr($goods['name'], 0, 60);
                $list .= "<li><input type='checkbox' name='my_gids[]' class='my' value='" . $goods['id'] . "'/>" . $tmp . "</li>";
            }
            $list .= "</ul>";
            $this->view->my_goods_list = $list;

            //other
            $goods = new GoodsModel();
            $all_goods = $goods->fetchAll("uid = " . $target_id . " and status = " . $goods->getStatusId("已发布"));
            $list = "<form><ul>";
            foreach ($all_goods as $goods) {
                $tmp = strlen($goods['name']) < 60 ? $goods['name'] : cutstr($goods['name'], 0, 60);
                $list .= "<li><input type='checkbox' name='other_gids[]' class='other' value='" . $goods['id'] . "'/>" . $tmp . "</li>";
            }
            $list .= "</ul>";
            $this->view->other_goods_list = $list . "<input type='hidden' name='target_id' value='" . $target_id . "'/>";
        }else
            header("Location:/redirect?url=/sale&msg=" . urlencode("走错地方了吧!"));
    }

    public function choosewayAction() {
        if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
            $gid = $_GET['gid'];
            $goods = new GoodsModel();
            $tmp = $goods->fetchRow("id = " . $gid . " and uid <>" . $this->user['uid']);
            if (empty($tmp) || $tmp['status'] != $goods->getStatusId("已发布")) {
                js_alert("您所查找的物品不存在或者已经下架。。。", "/goods/search");
            }else
                $this->view->tmp = $tmp;
        } else {
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
        }
    }

    public function refuseAction() {
        if (isset($_GET['sid']) && is_numeric($_GET['sid'])) {
            $gid = $_GET['sid'];
            $sale = new SaleModel();
            $sale->update(array('status' => SaleModel::Rejecting), "sid = $sid");
        } else {
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
        }
    }

    public function acceptAction() {
        if (isset($_GET['sid']) && is_numeric($_GET['sid'])) {
            $sid = $_GET['sid'];
            $sale = new SaleModel();
            $friend = new FriendModel();
            $others = $sale->getOtherReqNum($sid, false);
            $this->view->other_req_num = count($others);
            $this->view->sid = $sid;
            $sale_info = $sale->fetchRow("sid = $sid");
            if (isset($_GET["confirm"]) && $_GET['confirm'] == "true") {
                $sale->update(array('status' => SaleModel::Accepting), "sid = $sid");
                //set all goods status saled
                $sale->setSaled($sid);
                //make friend
                $friend->makeFriend($sale['buyer'],$this->user['uid']);
                foreach ($others as $id) {//reject other request
                    if ($id != $sid)
                        $sale->update(array('status' => SaleModel::Rejecting), "sid = $id");
                }
                header("Location:/redirect?url=/sale/request&msg=" . urlencode("确认成功!"));
            }
        } else {
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
        }
    }

    public function requestAction() {
        $this->view->headTitle("交易请求");
        $sale = new SaleModel();
        $all = $sale->getAllReq($this->user['uid']);
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function otherreqAction() {
        $this->view->headTitle("其他交易详情");
        if (isset($_GET['sid']) && is_numeric($_GET['sid'])) {
            $sid = $_GET['sid'];
            $sale = new SaleModel();
            $this->view->other_req_num = $sale->getOtherReqNum($sid);

            $all = $sale->getAllReq($this->user['uid'], $sid);
            $page = $this->_getParam('page', 1); //高置默认页
            if (!is_numeric($page))
                $page = 1;
            $numPerPage = $this->page_num; //每页显示的条数
            $paginator = Zend_Paginator::factory($all);
            $paginator->setCurrentPageNumber($page)
                    ->setItemCountPerPage($numPerPage);
            $this->view->paginator = $paginator;
        } else {
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
        }
    }

    public function detailAction() {
        $this->view->headTitle("交易详情");
        if (isset($_GET['sid']) && is_numeric($_GET['sid'])) {
            $sid = $_GET['sid'];
            $sale = new SaleModel();
            $this->view->tmp = $sale->getSingleDetail($sid, $this->user['uid']);
        } else {
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
        }
    }
    //未读请求处理结果
    public function unreadAction(){
        $this->view->headTitle("未读交易请求处理结果");
        $sale = new SaleModel();

        $all = $sale->getUnreadReqOutcome($this->user['uid']); //除了已删除
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }


    public function failAction(){
        
    }

//    function __call($action, $arguments) {
//        $this->_redirect('./');
//        print_r($action);
//        print_r($arguments);
//    }
}

