<?php

class TasteModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'taste';
        parent::_setup();
    }

    public function like($uid, $gid) {
        $tmp = $this->_db->fetchOne("select status from taste where uid = $uid and gid = $gid");
        if ($tmp == null) {
            $this->insert(array(
                'uid' => $uid,
                'gid' => $gid,
                'time' => time(),
                'status' => self::Like,
            ));
        } else if ($tmp == self::Like) {
            $this->update(array('status' => self::Normal, 'time' => time()), "uid = $uid and gid = $gid");
        } else {
            $this->update(array('status' => self::Like, 'time' => time()), "uid = $uid and gid = $gid");
        }
    }

    public function hate($uid, $gid) {
        $tmp = $this->_db->fetchOne("select status from taste where uid = $uid and gid = $gid");
        if ($tmp == null) {
            $this->insert(array(
                'uid' => $uid,
                'gid' => $gid,
                'time' => time(),
                'status' => self::Hate,
            ));
        } else if ($tmp == self::Hate) {
            $this->update(array('status' => self::Normal, 'time' => time()), "uid = $uid and gid = $gid");
        } else {
            $this->update(array('status' => self::Hate, 'time' => time()), "uid = $uid and gid = $gid");
        }
    }

    public function addStatus(array $data,$uid) {
        foreach ($data as &$tmp) {
            $s = $this->_db->fetchOne("select status from taste where uid = $uid and gid = " . $tmp['id']);
            $tmp['taste_status'] = $this->getStatus($s);
        }
        return $data;
    }

    public function getStatus($status_id) {
        switch ($status_id) {
            case 1:return "正常";
            case 2:return "喜爱";
            case 3:return "讨厌";
        }
    }

    const Normal = 1;
    const Like = 2;
    const Hate = 3;
}

?>
