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

    /**
     *
     * @param type $uid 取得他人对用户的交易请求您数
     * @return type int
     */
    public function getUnreadedNum($uid) {
        return $this->_db->fetchOne("select count(*) from sale where sellerid = $uid and status = " . self::Sended);
    }

    /**
     *
     * @param type $uid
     * @param type $sid默认为空，非空的话，取得所有sid所决定的交易中的被请求物品，因为其他用户也在请求
     * @return type 
     */
    public function getAllReq($uid, $sid = null) {
        if ($sid == null) {
            $all = $this->_db->fetchAll("select * from sale,user where uid = buyerid and sellerid = $uid and sale.status = " . self::Sended);
        } else {
            $tmp = $this->fetchRow("sid = $sid");
            $gids = $this->goodsToArray($tmp['ask_goods']);
            $all = array();
            foreach ($gids as $gid) {
                $tmp = $this->_db->fetchAll("select * from sale,user where uid = buyerid and sellerid = $uid and ask_goods like '%$gid%' and sale.status = " . self::Sended);
                foreach ($tmp as $sale) {
                    $all[$sale['sid']] = $sale;
                }
            }
        }
        return $this->getDetailInfo($all);
    }

    /**
     * 对于一桩交易，检查是否交易的被交易物品有其他用户请求
     * @param type $sid
     * @return type int,即请求人数
     */
    public function getOtherReqNum($sid, $num = true) {
        $tmp = $this->fetchRow("sid = $sid");
        $gids = $this->goodsToArray($tmp['ask_goods']);
        $total = array();
        foreach ($gids as $gid) {
            $tmp = $this->_db->fetchAll("select sid from sale where ask_goods like '%$gid%' and status = " . self::Sended);
            foreach ($tmp as $sale) {
                $total[$sale['sid']] = 1;
            }
        }
        if ($num)
            return count($total);
        else
            return array_keys ($total);
    }

    /**
     * 由于请求和被请求物品的格式是是\t为分隔符的，这里将存储的字符串转为数组
     * @param type $goods
     * @return type 
     */
    public function goodsToArray($goods) {
        $gids = explode("\t", $goods);
        array_pop($gids); //最后一个为空
        return $gids;
    }

    /**
     * 取得用户的所有请求
     * @param type $uid
     * @return type 
     */
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
            $all['use_names'] = $this->getGoods($all['use_goods'], false);
        }
        $all['ask_names'] = $this->getGoods($all['ask_goods'], false);
        return $all;
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
