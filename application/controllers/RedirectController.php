<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */

class RedirectController extends Zend_Controller_Action {
    
    public function init() {
//        $this->view->userinfo = $this->user;
    }

    public function indexAction() {
        $msg = array(
            'WrongWay' => '走错地方了吧！？',
            'PublihsSuccess' => '发布成功！',
            'PublishFail' => '发布失败!',
            'PleaseLogin' => '请先登录!',
            'LoginSuccess' => '登录成功!',
            'DeleteSuccess' => '删除成功!',
            'UpdateSuccess' => '更新成功！',
            'SendSuccess' => '发送成功！',
            'RegSuccess' => '注册成功,请尽快通过邮箱激活',
            'ConfirmSuccess' => '确认成功！',
            'UnknowError' => '出现错误了...',
            'MsgNotExist' => '消息不存在！',
            'ExitSuccess' => '退出成功！',
            'NotLogin' => '您还没有登录！',
            'ActiveSuccess' => '激活成功',
            'ActiveSuccess1' => '激活成功,之前可能由于他人登录您账户所致，建议您登录之后修改密码!',
            'SendActiveMailSuccess' => '成功发送激活邮件！',
        );

        $this->view->headTitle("跳转中...");
        $this->view->url = $_GET['url'];
        $this->view->msg = isset($msg[$_GET['msg']]) ? $msg[$_GET['msg']] : "出现错误了...";
    }

}

?>
