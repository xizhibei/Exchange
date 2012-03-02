<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */
Zend_Loader::loadClass("GoodsPublishForm");
Zend_Loader::loadClass("GoodsModel");
Zend_Loader::loadClass("SaleModel");
Zend_Loader::loadClass("UserModel");
Zend_Loader::loadClass("TagModel");
Zend_Loader::loadClass("ImgModel");
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
        $acl->allow('guest', $res, array('index', 'search', 'detail', 'default', 'ajaxdetail', 'moretag'));
        $acl->allow('user', $res, array('add', 'delete', 'modify', 'manage', 'like', 'hate'));
        $acl->allow('admin');
        if (!$acl->isAllowed($this->user['role'], $res, $this->getRequest()->getActionName())) {
            redirect("/user/login", "PleaseLogin");
            exit;
        }
        $this->view->userinfo = $this->user;
    }

    public function indexAction() {
        $this->view->headTitle("最新货物");
        $this->view->headScript()->appendFile("/js/jquery.js");
        $this->view->headScript()->appendFile("/js/jquery.scrollTo-min.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.mousewheel-3.0.4.pack.js");
        $this->view->headScript()->appendFile("/fancybox/jquery.fancybox-1.3.4.pack.js");
        $this->view->headLink()->appendStylesheet("/fancybox/jquery.fancybox-1.3.4.css");
        $goods = new GoodsModel();

        $all = $goods->getAllPublished();
        if (isset($this->user['uid'])) {
            $this->view->uid = $this->user['uid'];
            Zend_Loader::loadClass("TasteModel");
            $t = new TasteModel();
            $all = $t->addStatus($all, $this->user['uid']);
        }

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
        $this->view->headScript()->appendFile("/js/anytimec.js");
        $this->view->headLink()->appendStylesheet("/css/validationEngine.jquery.css");
        $this->view->headLink()->appendStylesheet("/css/anytimec.css");
        $this->view->headScript()->appendFile("/js/jquery.scrollTo-min.js");

        $tag = new TagModel();
        $this->view->tags = $tag->getMostFrequently(0, 10);

        if ($this->getRequest()->isPost()) {
            Zend_Loader::loadClass("Custom_Controller_Plugin_FormValidate");
            $validater = new Custom_Controller_Plugin_FormValidate();
            $data = $this->getRequest()->getPost();
            if ($validater->isValid("goodsPublish", $data)) {
                $goods = new GoodsModel();

                if ($data['date'] == "")
                    $expire_date = GoodsModel::NeverExpireDate;
                else {
                    $date = new DateTime($data['date']);
                    $expire_date = $date->getTimestamp();
                }

                $data['detail'] = filter_bad_html($data['detail']);

                $img = new ImgModel();
                $img_id = $img->addImg($data['pic_url'], $this->user['uid']);
                $data['detail'] = $img->addImgFromHtml($data['detail'], $this->user['uid']);

                $insertData = array(
                    'name' => $data['name'],
                    'pic_id' => $img_id,
                    'price' => $data['price'],
                    'detail' => $data['detail'],
                    'ex_cond' => $data['ex_cond'],
                    'sale_ways' => $data['sale_ways'],
                    'publish_time' => time(),
                    'uid' => $this->user['uid'],
                    'status' => GoodsModel::Published,
                    'expire_date' => $expire_date,
                );
                $gid = $goods->insert($insertData);

                $tag = new TagModel();
                $tag->addTags($data['tags'], $gid);

                redirect("/goods/manage", "PublihsSuccess");
            } else {
                $this->view->note = "发布失败！请检查输入！";
            }
        }
    }

    public function moretagAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $tag = new TagModel();

        $off = $this->_getParam('off', 10);
        if (!is_numeric($off))
            $off = 10;
        $limit = $this->_getParam('limit', 10);
        if (!is_numeric($limit))
            $limit = 10;
        $all = $tag->getMostFrequently($off, $limit);
        $json = array();
        $i = 0;
        foreach ($all as $tmp) {
            $json[$i++] = $tmp['name'];
        }
        echo json_encode($json);
    }

    /**
     * @ToDo:待完善，包括删除确认，可能有人正在请求交换这个货物，此时确认的话还需将所有请求拒绝
     */
    public function deleteAction() {
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $goods = new GoodsModel();
            $goods->update(array('status' => $goods->getStatusId("已删除")), "id = " . $gid);
            redirect("/goods/manage", "DeleteSuccess");
        } else {
            redirect("/index", "WrongWay");
        }
    }

    public function modifyAction() {
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $this->view->headScript()->appendFile("/js/jquery.js");
            $this->view->headScript()->appendFile("/fancybox/jquery.mousewheel-3.0.4.pack.js");
            $this->view->headScript()->appendFile("/fancybox/jquery.fancybox-1.3.4.pack.js");
            $this->view->headLink()->appendStylesheet("/fancybox/jquery.fancybox-1.3.4.css");
            $this->view->headScript()->appendFile("/ckeditor/ckeditor.js");
            $this->view->headScript()->appendFile("/js/languages/jquery.validationEngine-zh_CN.js");
            $this->view->headScript()->appendFile("/js/jquery.validationEngine.js");
            $this->view->headScript()->appendFile("/js/anytimec.js");
            $this->view->headLink()->appendStylesheet("/css/validationEngine.jquery.css");
            $this->view->headLink()->appendStylesheet("/css/anytimec.css");
            $this->view->headScript()->appendFile("/js/jquery.scrollTo-min.js");

            $this->view->headTitle("物品信息修改");
            $tag = new TagModel();
            $this->view->tags = $tag->getMostFrequently(0, 10);
            $goods = new GoodsModel();

//load goods info
            $tmp = $goods->fetchRow("id = " . $gid)->toArray();
            if ($tmp['uid'] != $this->user['uid']) {//不是物品的主人
                redirect("/goods/manage", "WrongWay");
                return;
            }
            $tmp['date'] = date("Y-m-d h:i:s A", $tmp['expire_date']);
            $tags_array = $tag->getTags($gid);
            $tags = array();
            foreach ($tags_array as $t) {
                array_push($tags, $t['name']);
            }
//            if ($tags != "")
//                $tags = substr($tags, 0, strlen($tags) - 1);
            $tmp['tags'] = implode(",", $tags);
            $this->view->goods = $tmp;
            $this->render("add"); //用add的文件来渲染，能保持和发布的时候一致

            if ($this->getRequest()->isPost()) {
                Zend_Loader::loadClass("Custom_Controller_Plugin_FormValidate");
                $validater = new Custom_Controller_Plugin_FormValidate();
                $data = $this->getRequest()->getPost();
                if ($validater->isValid("goodsPublish", $data)) {


                    if ($data['date'] == "")
                        $expire_date = GoodsModel::NeverExpireDate;
                    else {
                        $date = new DateTime($data['date']);
                        $expire_date = $date->getTimestamp();
                    }

                    $data['detail'] = filter_bad_html($data['detail']);

                    $img = new ImgModel();
                    $img_id = $img->addImg($data['pic_url'], $this->user['uid']);

                    $data['detail'] = $img->addImgFromHtml($data['detail'], $this->user['uid']);

                    $insertData = array(
                        'name' => $data['name'],
                        'pic_id' => $img_id,
                        'pic_url' => $data['pic_url'],
                        'price' => $data['price'],
                        'detail' => $data['detail'],
                        'ex_cond' => $data['ex_cond'],
                        'sale_ways' => $data['sale_ways'],
                        'expire_date' => $expire_date,
                    );
                    $gid = $goods->update($insertData, "id = " . $tmp['id']);

                    $tag = new TagModel();
                    $tag->addTags($data['tags'], $gid);

                    redirect("/goods/manage", "UpdateSuccess");
                } else {
                    $this->view->note = "更新失败！请检查输入！" . $validater->getMsg();
                }
            }
        } else {
            redirect("/index", "WrongWay");
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
        

        Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8());

        $index = new Zend_Search_Lucene('../data/search_cache/goods');
        $keywords = $this->_getParam("q", "");

        $stopWords = array('a', 'an', 'at', 'the', 'and', 'or', 'is', 'am');
//        $cnStopWords = array('的');
        $stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords($stopWords);

        if (!empty($keywords)) {
            $analyzer = Zend_Search_Lucene_Analysis_Analyzer::getDefault();

            //$analyzer->setCnStopWords($cnStopWords);
            $analyzer->addFilter($stopWordsFilter);

            $analyzer->setInput($keywords, 'utf-8');

            $tokenCounter = 0;
            while (($token = $analyzer->nextToken() ) !== null) {
                $tokens[$tokenCounter++] = $token;
            }
//print_r( $tokens ); 

            $count = 0;
            $all_search_outcome = array();
            foreach ($tokens as $tokenObject) {
                $keyword = $tokenObject->getTermText();

                $query = Zend_Search_Lucene_Search_QueryParser::parse($keyword, 'utf-8');
                $hits = $index->find($query);

                foreach ($hits as $hit) {
                    $tmp = array();
                    $tmp['url'] = $hit->url;
                    $tmp['name'] = $hit->name;
                    $all_search_outcome[$count++] = $tmp;
                }
            }
        }

        $this->view->count = $count;
        if ($count) {
            $page = $this->_getParam('page', 1); //高置默认页
            if (!is_numeric($page))
                $page = 1;
            $numPerPage = $this->page_num; //每页显示的条数
            $paginator = Zend_Paginator::factory($all_search_outcome);
            $paginator->setCurrentPageNumber($page)
                    ->setItemCountPerPage($numPerPage);
            $this->view->paginator = $paginator;
        }
    }

    public function detailAction() {
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $goods = new GoodsModel();
            $this->view->detail = $goods->fetchRow("id = " . $gid);
            $this->view->status = $goods->getStatus($this->view->detail['status']);
        } else
            redirect("/index", "WrongWay");
    }

    public function ajaxdetailAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid)) {
            $goods = new GoodsModel();
            $this->view->detail = $goods->getSinglePublished($gid);
            $this->render("ajaxdetail");

            Zend_Loader::loadClass("ClickModel");
            $c = new ClickModel();
            $c->updateClick(isset($this->user['uid']) ? $this->user['uid'] : null, getIp(), $gid);
        } else
            echo 'fail';
    }

    public function likeAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid) && isset($this->user['uid'])) {
            Zend_Loader::loadClass("TasteModel");
            $c = new TasteModel();
            $c->like($this->user['uid'], $gid);
            echo 'success';
        }else
            echo 'fail';
    }

    public function hateAction() {
        $this->_helper->layout->disableLayout(); //disable layout
        $this->_helper->viewRenderer->setNoRender(); //suppress auto-rendering
        $gid = $this->_getParam("gid");
        if (isset($gid) && is_numeric($gid) && isset($this->user['uid'])) {
            Zend_Loader::loadClass("TasteModel");
            $c = new TasteModel();
            $c->hate($this->user['uid'], $gid);
            echo 'success';
        }else
            echo 'fail';
    }

}

