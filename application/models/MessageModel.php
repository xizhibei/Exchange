<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */

class MessageModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'msg';
        $this->_primary = 'mid';
        parent::_setup();
    }

    public function getUnreadedNum($uid) {
        return $this->_db->fetchOne("select count(*) from msg where to_uid = $uid and to_del = 0 and status = " . self::Sended);
    }

    public function getInbox($uid, $with_sender = true) {
        $inbox = $this->fetchAll("to_uid = $uid and to_del = 0 and (status = " . self::Readed . " or status = " . self::Sended . ")")->toArray();
        foreach ($inbox as &$tmp) {
            $tmp['date'] = date("Y-m-d H:i:s", $tmp['date']);
            if ($with_sender) {
                $tmp['sender_name'] = $this->_db->fetchOne("select name from user where uid = " . $tmp['from_uid']);
            }
        }
        return $inbox;
    }

    public function getOutbox($uid, $with_receiver = true) {
        $outbox = $this->fetchAll("from_uid = $uid and from_del = 0 and status = " . self::Sended)->toArray();
        foreach ($outbox as &$tmp) {
            $tmp['date'] = date("Y-m-d H:i:s", $tmp['date']);
            if ($with_receiver) {
                $tmp['receiver_name'] = $this->_db->fetchOne("select name from user where uid = " . $tmp['to_uid']);
            }
        }
        return $outbox;
    }

    public function getDraft($uid, $with_receiver = true) {
        $outbox = $this->fetchAll("from_uid = $uid and from_del = 0 and status = " . self::Saved)->toArray();
        foreach ($outbox as &$tmp) {
            $tmp['date'] = date("Y-m-d H:i:s", $tmp['date']);
            if ($with_receiver) {
                $tmp['receiver_name'] = $this->_db->fetchOne("select name from user where uid = " . $tmp['to_uid']);
            }
        }
        return $inbox;
    }

    const Deleted = 0;
    const Sended = 1;
    const Saved = 2; //存草稿
    const Readed = 3;

    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "已删除";
            case 1:return "已发送";
            case 2:return "存草稿";
        }
    }

    public function getStatusId($status) {
        switch ($status) {
            case "已删除":return 0;
            case "已发送":return 1;
            case "存草稿":return 2;
        }
    }

}

?>