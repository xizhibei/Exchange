<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */

class FriendModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'friend';
        parent::_setup();
    }

    public function getAllMy($uid) {
        $rows = $this->getAdapter()->fetchAll("select app_uid,rec_uid from friend where ($uid = app_uid or $uid = rec_uid) and status = " . self::Accepted);
        foreach ($rows as &$tmp) {
            if ($tmp['app_uid'] == $uid)
                $tmp = $this->getAdapter()->fetchRow("select * from user where uid = " . $tmp['rec_uid']);
            else
                $tmp = $this->getAdapter()->fetchRow("select * from user where uid = " . $tmp['app_uid']);
        }
        return $rows;
    }

    public function search($q, $uid) {
        $rows = $this->_db->fetchAll("select * from user where name like '%$q%' and uid <> $uid"); // and (((app_uid = $uid or rec_uid = $uid) and friend.status <> 0 and user.status = 1) or user.status = 1)
        foreach ($rows as &$tmp) {
            $tmp['friend_status'] = self::friend_status($uid, $tmp['uid']);
        }
        return $rows;
    }

    /**
     *
     * @param type $uid1
     * @param type $uid2 当前登录用户
     * @return type int,-1 is no relashipship
     */
    public function friend_status($uid1, $uid2) {
        $row = $this->_db->fetchRow("select friend.status as fs,user.status as us from friend,user where $uid1 = uid and (app_uid = $uid1 and rec_uid= $uid2) or (rec_uid = $uid2 and app_uid= $uid1)");
        if (!empty($row)) {
            if ($row['us'] == 1)
                return $row['fs'];
            else
                return self::UserUnnormal; //user status unnormal
        }
        else
            return self::NoRelated;
    }
    
    /**
     * make two user to be friend
     * @param type $uid1
     * @param type $uid2 当前登录用户
     * @return type bool whether success
     */
    public function makeFriend($uid1, $uid2) {
        $status = $this->friend_status($uid1, $uid2);
        if ($status == self::Accepted)
            return true;
        if ($status == self::Deleted || $status == self::Sended) {
            $this->update(array('status' => self::Accepted), "(app_uid = $uid1 and rec_uid= $uid2) or (rec_uid = $uid2 and app_uid= $uid1)");
            return true;
        }
        else if ($status == self::NoRelated) {
            $this->insert(array(
                'app_uid' => $uid1,
                'rec_uid' => $uid2,
                'msg' => "Auto Make Friend by System",
                'status' => FriendModel::Accepted,
                'date' => time(),
            ));
            return true;
        }else if ($status == self::BLACK_LIST) {
            return false;
        }       
    }

    const UserUnnormal = -2;
    const NoRelated = -1;

    const BLACK_LIST = 0;
    const Sended = 1;
    const Accepted = 2;
    const Rejected = 3;
    const Deleted = 4;

    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "黑名单";
            case 1:return "已发送";
            case 2:return "已接受";
            case 3:return "已拒绝";
        }
    }

    public function getStatusId($status) {
        switch ($status) {
            case "黑名单":return 0;
            case "已发送":return 1;
            case "已接受":return 2;
            case "已拒绝":return 3;
        }
    }

}

?>
