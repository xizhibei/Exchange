<?php

class AdminModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'admin';
        $this->_primary = 'aid';
        parent::_setup();
    }

    public function authenticateValid(array $user, array $data) {
        if ($user['pwd'] == $data['password'])
            return true;
        else
            return false;
    }

    public function incLoginTimes($aid) {
        $this->_db->query("update admin set login_times = login_times + 1 where aid = $aid");
        $this->_db->query("update admin set status = " . self::Locked . " where aid = $aid and IF(login_times > 5,1,0)");
    }

    public function updateLoginInfo($uid, $ip) {
        $this->update(array("login_date" => time(), 'login_ip' => $ip), "aid = $uid");
    }

    public function clearLoginTimes($aid) {
        $this->update(array('login_times' => 0), "aid = $aid");
    }

    //Admin level
    const System = 0;
    const Other =1;

    //status
    const Deleted = 0;
    const Normal = 1;
    const Locked = 2;
    const LockedByAdmin = 3;
}

?>
