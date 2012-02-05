<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
Zend_Loader::loadClass("GoodsPublishForm");
Zend_Loader::loadClass("GoodsModel");
Zend_Loader::loadClass("SaleModel");
Zend_Loader::loadClass("UserModel");
require_once 'Utility.php';

class GoodsController extends Zend_Controller_Action {

    private $user;
    private $page_num = 10;

    public function init() {
        $auth = Zend_Registry::get("auth");
        $acl = Zend_Registry::get("acl");
        $this->user = (array) $auth->getStorage()->read();
        $res = $this->getRequest()->getControllerName();
        $acl->add(new Zend_Acl_Resource($res));
        $acl->allow('guest', $res, array('index', 'search', 'detail', 'default','ajaxdetail'));
        $acl->allow('user', $res, array('add', 'delete', 'modify', 'manage'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            header("Location:/redirect?url=/user/login&msg=" . urlencode("请先登录!"));
            exit;
        }
    }

    public function indexAction() {
        $this->view->headTitle("最新货物");
        $this->view->headScript()->appendFile("/js/jquery.js");
        $this->view->headScript()->appendFile("/js/jquery.scrollTo-min.js");
        $goods = new GoodsModel();

        $all = $goods->getAllPublished();

        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function addAction() {
        $this->view->headTitle("货物发布");
        $this->view->headScript()->appendFile("/js/jquery.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.mousewheel-3.0.4.pack.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.fancybox-1.3.4.pack.js");
        $this->view->headLink()->appendStylesheet("/fancybox/jquery.fancybox-1.3.4.css");
        $this->view->headScript()->appendFile("/ckeditor/ckeditor.js");
        $this->view->headScript()->appendFile("/js/languages/jquery.validationEngine-zh_CN.js");
        $this->view->headScript()->appendFile("/js/jquery.validationEngine.js");
        $this->view->headLink()->appendStylesheet("/css/validationEngine.jquery.css");
        $form = new GoodsPublishForm();

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $data = $form->getValues();
                $goods = new GoodsModel();

                $insertData = array(
                    'name' => $data['name'],
                    'pic_url' => $data['pic'],
                    'price' => $data['price'],
                    'detail' => $data['detail'],
                    'ex_cond' => $data['ex_cond'],
                    'sale_ways' => $data['sale_ways'],
                    'publish_time' => time(),
                    'uid' => $this->user['uid'],
                    'status' => $goods->getStatusId("已发布"),
                );
                $goods->insert($insertData);
                header("Location:/redirect?url=/goods/manage&msg=" . urlencode("发布成功!"));
            } else {
                echo '<script>alert("发布失败！请检查输入！");</script>';
            }
        }
        $this->view->form = $form;
    }

    /**
     * @ToDo:待完善，包括删除确认，可能有人正在请求交换这个货物，此时确认的话还需将所有请求拒绝
     */
    public function deleteAction() {
        if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
            $gid = $_GET['gid'];
            $goods = new GoodsModel();
            $goods->update(array('status' => $goods->getStatusId("已删除")), "id = " . $gid);
            header("Location:/redirect?url=/goods/manage&msg=" . urlencode("删除成功!"));
        } else {
            $this->_redirect("/index");
        }
    }

    public function modifyAction() {
        if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
            $this->view->headScript()->appendFile("/js/jquery.js");
            $this->view->headScript()->appendFile("/fancybox/jquery.mousewheel-3.0.4.pack.js");
            $this->view->headScript()->appendFile("/fancybox/jquery.fancybox-1.3.4.pack.js");
            $this->view->headLink()->appendStylesheet("/fancybox/jquery.fancybox-1.3.4.css");
            $this->view->headScript()->appendFile("/ckeditor/ckeditor.js");
            $gid = $_GET['gid'];
            $this->view->headTitle("货物信息修改");
            $form = new GoodsPublishForm();
            $goods = new GoodsModel();

            if ($this->getRequest()->isPost()) {
                $formData = $this->getRequest()->getPost();
                if ($form->isValid($formData)) {
                    $data = $form->getValues();
                    $insertData = array(
                        'name' => $data['name'],
                        'price' => $data['price'],
                        'pic_url' => $data['pic'],
                        'ex_cond' => $data['ex_cond'],
                        'detail' => $data['detail'],
                        'sale_ways' => $data['sale_ways'],
                        'publish_time' => time(),
                        'uid' => $this->user['uid'],
                        'status' => $goods->getStatusId("已发布"),
                    );
                    $goods->update($insertData, "id = " . $gid);
                    header("Location:/redirect?url=/goods/manage&msg=" . urlencode("更新成功!"));
                } else {
                    echo '<script>alert("更新失败！请检查输入！");</script>';
                }
            }

            $tmp = $goods->fetchRow("id = " . $gid);
            $this->view->goods = $tmp;
//            $form->setDefaults(array(
//                'name' => $tmp['name'],
//                'price' => $tmp['price'],
//                'ex_cond' => $tmp['ex_cond'],
//                'detail' => $tmp['detail'],
//                'sale_ways' => $tmp['sale_ways'],
//            ));
//            $this->view->form = $form;
        } else {
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
        }
    }

    public function manageAction() {
        $goods = new GoodsModel();
        $all = $goods->getAllMy($this->user['uid']); //除了已删除
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
        $goods = new GoodsModel();
        $q = isset($_GET['q']) ? $_GET['q'] : "";
        $all = $goods->search($q);
        $page = $this->_getParam('page', 1); //高置默认页
        if (!is_numeric($page))
            $page = 1;
        $numPerPage = $this->page_num; //每页显示的条数
        $paginator = Zend_Paginator::factory($all);
        $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
    }

    public function detailAction() {
        if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
            $gid = $_GET['gid'];
            $goods = new GoodsModel();
            $this->view->detail = $goods->fetchRow("id = " . $gid);
            $this->view->status = $goods->getStatus($this->view->detail['status']);
        } else
            header("Location:/redirect?url=/index&msg=" . urlencode("走错地方了吧!"));
    }
    
    public function ajaxdetailAction(){
        if (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
            $this->_helper->layout->disableLayout(); //disable layout
            $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
            $gid = $_GET['gid'];
            $goods = new GoodsModel();
            $this->view->detail = $goods->getSinglePublished($gid);
            $this->render("ajaxdetail");
        } else
            echo 'fail';
    }

//    function __call($action, $arguments) {
//        $this->_redirect('./');
//        print_r($action);
//        print_r($arguments);
//    }
}

