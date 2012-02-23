<?php

class ClickModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'click';
        parent::_setup();
    }

    public function updateClick($uid, $ip, $gid, $tid = null) {
        if ($tid != null) {
            if ($uid != null)
                $tmp = $this->_db->fetchOne("select max(time) from click where uid = $uid and tid = $tid");
            else
                $tmp = $this->_db->fetchOne("select max(time) from click where ip = '$ip' and tid = $tid");
        }else {
            if ($uid != null)
                $tmp = $this->_db->fetchOne("select max(time) from click where uid = $uid and gid = $gid");
            else
                $tmp = $this->_db->fetchOne("select max(time) from click where ip = '$ip' and gid = $gid");
        }
        if (isset($tmp) && time() - $tmp < 60) {
            return false;
        }
        $this->insert(array(
            'uid' => $uid,
            'gid' => $gid,
            'tid' => $tid,
            'time' => time(),
            'ip' => $ip,
        ));
        return true;
    }

}

?>
