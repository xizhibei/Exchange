<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/2/3
 * ***************************************************************************** */

class Custom_Controller_Plugin_FormValidate extends Zend_Controller_Plugin_Abstract {

    public $msg;

    public function isValid($func, $data) {
        return $this->$func($data);
    }

    public function getMsg() {
        return $this->msg;
    }

    public function login($data) {
        $validater = new Zend_Validate_EmailAddress();
        if (!$validater->isValid($data['email'])) {
            $this->msg = "邮箱不正确";
//            $this->msg = $validater->getMessages();
            return false;
        }
        if ($data['password'] == "")
            return false;
        //pwd can be any char
        return true;
    }

    public function sendCode($data) {
        $validater = new Zend_Validate_EmailAddress();
        if (!$validater->isValid($data['email'])) {
            $this->msg = "邮箱不正确";
//            $this->msg = $validater->getMessages();
            return false;
        }
        return $this->authCode($data['code']);
    }

    public function authCode($code) {
        $authCode = new Zend_Session_Namespace('Auth_Code');
        if ($authCode->imagecode == "") {
            $this->msg = "请刷新验证码！";
            return false;
        } else if (!isset($code)) {
            $authCode->imagecode = "";
            $this->msg = "请输入验证码！";
            return false;
        } else if (strtolower($code) != strtolower($authCode->imagecode)) {
            $authCode->imagecode = "";
            $this->msg = "验证码不正确！";
            return false;
        }
        return true;
    }

}

?>
