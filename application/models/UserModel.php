<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
class UserModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'user';
        $this->_primary = 'uid';
        parent::_setup();
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
    
    const Deleted = 0;
    const Normal = 1;
    const NotValid = 2;
    const Locked = 3;
    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "已删除";
            case 1:return "待验证";
            case 2:return "已锁定";
        }
    }

    public function getStatusId($status) {
        switch ($status) {
            case "已删除":return 0;
            case "待验证":return 1;
            case "已锁定":return 2;
        }
    }

    public function getSalt() {
        return sha1(md5(rand()) . "xizhibei" . time());
    }

}

?>
