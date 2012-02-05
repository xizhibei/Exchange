<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */

class UserModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'user';
        $this->_primary = 'uid';
        parent::_setup();
    }

    public function getAvatar($uid) {
        return $this->_db->fetchRow("select big_avatar,small_avatar from user where uid = $uid");
    }

    public function authenticateValid(array $user, array $data) {
        if ($user['pwd'] == $data['password'])
            return true;
        else
            return false;
    }

    public function codeRequired($email) {
        $times = $this->_db->fetchOne("select login_times from user where email = '$email'");
        if ($times > 2)
            return true;
        else
            return false;
    }

    public function incLoginTimes($uid) {
        $this->_db->query("update user set login_times = login_times + 1 where uid = $uid");
        $this->_db->query("update user set status = " . self::Locked . " where uid = $uid and IF(login_times > 5,1,0)");
    }

    public function updateLoginDate($uid) {
        $this->update(array("login_date" => time()), "uid = $uid");
    }

    public function clearLoginTimes($uid) {
        $this->update(array('login_times' => 0), "uid = $uid");
    }

    public function emailExist($email) {
        $select = parent::select()->where('email = ?', $email);
        $result = $this->getAdapter()->fetchOne($select);

        if (!empty($result))
            return true;
        else
            return false;
    }

    public function getUser($email, $pwd) {
        $select = parent::select()->where("email = ?", $email)->where("pwd = ?", $pwd);
        return $this->getAdapter()->fetchRow($select);
    }
    
    
    public function getActivationCode() {
        return sha1(md5(rand()) . "xizhibei" . time());
    }
    
    public function activeOrUnlockUser(){
        
    }

    const Deleted = 0;
    const Normal = 1;
    const NotValid = 2;
    const Locked = 3;
    const LockedByAdmin = 4;

    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "已删除";
            case 1:return "正常";
            case 2:return "待验证";
            case 3:return "已锁定"; //登录失败被锁定，可自行解锁
            case 4:return "已锁定"; //此时被管理员锁定，不可自行解锁
        }
    }

}

?>
