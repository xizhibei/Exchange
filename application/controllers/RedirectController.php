<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
class RedirectController extends Zend_Controller_Action {

    public function indexAction() {
        $this->view->headTitle("跳转中...");
        $this->view->url = $_GET['url'];
        $this->view->msg = $_GET['msg'];
    }

}

?>
