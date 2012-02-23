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
        $acl->deny('guest', $res);
        $acl->allow('user', $res); //, array('sale', 'exchange', 'chooseway', 'request', 'accept', 'refuse', 'detail', 'otherreq')
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login","请先登录!");
            exit;
        }
    }

    public function indexAction() {
        
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
        $this->_helper->layout->setLayout('clean');
        $this->view->headTitle("物品交易");
        if (isset($_POST['submit'])) {
            $sale = new SaleModel();
            $sale->insert(array(
                'req_time' => time(),
                'use_goods' => 'money',
                'ask_goods' => $_POST['gid'],
                'status' => SaleModel::Sended,
                'buyerid' => $this->user['uid'],
                'sellerid' => $_POST['target_id'],
            ));
            redirect("/sale","请求已发送!");
            return;
        }
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $this->view->headScript()->appendFile("/js/jquery.js");
            $goods = new GoodsModel();
            $tmp = $goods->fetchRow("id = " . $gid);
            $this->view->exchange = $tmp;
            $target_id = $tmp['uid'];
            $user = new UserModel();
            $user_profile = $user->fetchRow("uid = " . $target_id);
            $this->view->user_profile = $user_profile;
            $this->view->form = "<input type='hidden' name='target_id' value='" . $target_id . "'/>" . "<input type='hidden' name='gid' value='" . $gid . "'/>";
        }else
            redirect("/sale","走错地方了吧!");
    }

    public function exchangeAction() {
        $this->_helper->layout->setLayout('clean');
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
                'status' => SaleModel::Sended,
                'buyerid' => $this->user['uid'],
                'sellerid' => $_POST['target_id'],
            ));
            redirect("/sale","请求已发送!");
            return;
        }
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $goods = new GoodsModel();
            $tmp = $goods->fetchRow("id = " . $gid);
            $this->view->exchange = $tmp;
            $target_id = $tmp['uid'];
            $user = new UserModel();
            $user_profile = $user->fetchRow("uid = " . $target_id);
            $this->view->user_profile = $user_profile;
            $all_goods = $goods->fetchAll("uid = " . $this->user['uid'] . " and status = " . GoodsModel::Published);
            if ($all_goods->count() > 0 ) {
                $all = $all_goods->toArray();
                foreach ($all as &$g) {
                    $g['name'] = strlen($g['name']) < 60 ? $g['name'] : cutstr($g['name'], 0, 60);
                }
            } else {
                $all = array();
            }
            $this->view->my_goods = $all;

            //other
            $all_goods = $goods->fetchAll("uid = " . $target_id . " and status = " . GoodsModel::Published);
            if ($all_goods->count() >0 ) {
                $all = $all_goods->toArray();
                foreach ($all as &$g) {
                    $g['name'] = strlen($g['name']) < 60 ? $g['name'] : cutstr($g['name'], 0, 60);
                }
            } else {
                redirect("/goods", "出现错误了。。。可能物品已售");
                return;
            }
            $this->view->other_goods = $all;
            $this->view->target_id = $target_id;
        }else
            redirect("/sale","走错地方了吧!");
    }

    public function choosewayAction() {
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $goods = new GoodsModel();
            $tmp = $goods->fetchRow("id = " . $gid . " and uid <>" . $this->user['uid']);
            if (empty($tmp) || $tmp['status'] != $goods->getStatusId("已发布")) {
                js_alert("您所查找的物品不存在或者已经下架。。。", "/goods/search");
            }else
                $this->view->tmp = $tmp;
        } else {
            redirect("/index","走错地方了吧!");
        }
    }

    public function refuseAction() {
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $sale = new SaleModel();
            $sale->update(array('status' => SaleModel::Rejecting), "sid = $sid");
        } else {
            redirect("/index","走错地方了吧!");
        }
    }

    public function acceptAction() {
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
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
                $friend->makeFriend($sale['buyer'], $this->user['uid']);
                foreach ($others as $id) {//reject other request
                    if ($id != $sid)
                        $sale->update(array('status' => SaleModel::Rejecting), "sid = $id");
                }
                redirect("/sale/request","确认成功!");
            }
        } else {
            redirect("/index","走错地方了吧!");
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
        $sid = $this->_getParam("sid");
        if (isset($sid) && is_numeric($sid)) {
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
            redirect("/index","走错地方了吧!");
        }
    }

    public function detailAction() {
        $this->view->headTitle("交易详情");
        $sid = $this->_getParam("sid");
        if (isset($sid) && is_numeric($sid)) {
            $sale = new SaleModel();
            $this->view->tmp = $sale->getSingleDetail($sid, $this->user['uid']);
        } else {
            redirect("/index","走错地方了吧!");
        }
    }

    //未读请求处理结果
    public function unreadAction() {
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

    public function failAction() {
        
    }

//    function __call($action, $arguments) {
//        $this->_redirect('./');
//        print_r($action);
//        print_r($arguments);
//    }
}

