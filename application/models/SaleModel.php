<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */

class SaleModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'sale';
        $this->_primary = 'sid';
        parent::_setup();
    }

    public function getUnreadedNum($uid) {
        return $this->_db->fetchOne("select count(*) from sale where sellerid = $uid and status = " . self::Sended);
    }

    public function getAllMy($uid) {
        $all = $this->_db->fetchAll("select * from sale,user where uid = sellerid and buyerid = $uid and sale.status <> " . self::Deleted);
        foreach ($all as &$tmp) {
            $tmp['req_time'] = date("Y-m-d H:i:s", $tmp['req_time']);
            if (isset($tmp['deal_time']) && $tmp['deal_time'] != "")
                $tmp['deal_time'] = $date("Y-m-d H:i:s", $tmp['deal_time_time']);
            $tmp['status'] = $this->getStatus($tmp['status']);
        }
        return $all;
    }

    private function getGoods($goods, $cut = true) {
        $gids = explode("\t", $goods);
        $names = array();
        foreach ($gids as $gid) {
            if ($gid != "") {
                $n = $this->_db->fetchOne("select name from goods where id = $gid");
                if ($cut)
                    $names[$gid] = strlen($n) < 30 ? $n : cutstr($n, 0, 30) . "...";
                else
                    $names[$gid] = $n;
            }
        }
        return $names;
    }

    private function getDetailInfo(&$all) {
        foreach ($all as &$tmp) {
            if ($tmp['use_goods'] != "money") {
                $tmp['use_names'] = $this->getGoods($tmp['use_goods']);
            }
            $tmp['ask_names'] = $this->getGoods($tmp['ask_goods']);
        }
        return $all;
    }

    public function getSingleDetail($sid, $uid) {
        $all = $this->_db->fetchRow("select * from sale,user where sid = $sid and uid = sellerid and buyerid = $uid and sale.status <> " . self::Deleted);
        if (empty($all))
            $all = $this->_db->fetchRow("select * from sale,user where sid = $sid and uid = buyerid and sellerid = $uid and sale.status <> " . self::Deleted);
        if ($all['use_goods'] != "money") {
            $all['use_names'] = $this->getGoods($all['use_goods'],false);
        }
        $all['ask_names'] = $this->getGoods($all['ask_goods'],false);
        return $all;
    }

    public function getAllReq($uid) {
        $all = $this->_db->fetchAll("select * from sale,user where uid = buyerid and sellerid = $uid and sale.status = " . self::Sended);
        return $this->getDetailInfo($all);
    }

    const Deleted = 0;
    const Sended = 1;
    const Agreed = 2;
    const Saled = 3;
    const OnDealing = 4;
    const Rejected = 5;

    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "已删除";
            case 1:return "已发送";
            case 2:return "已同意";
            case 3:return "已交易";
            case 4:return "交易中";
            case 5:return "已拒绝";
        }
    }

    public function getStatusId($status) {
        switch ($status) {
            case "已删除":return 0;
            case "已发送":return 1;
            case "已同意":return 2;
            case "已交易":return 3;
            case "交易中":return 4;
            case "已拒绝":return 5;
        }
    }

}

?>
