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

    public function setReaded($uid) {
        $this->_db->query("update sale set status = status + 1 where buyerid = $uid and (status =  " . self::Rejecting . " or status = " . self::Accepting . ")");
    }

    /**
     *
     * @param type $uid 取得他人对用户的交易请求数
     * @return type int
     */
    public function getUnreadReqNum($uid) {
        return $this->_db->fetchOne("select count(*) from sale where sellerid = $uid and status = " . self::Sended);
    }

    public function getUnreadReqOutcomeNum($uid) {
        return $this->_db->fetchOne("select count(*) from sale where buyerid = $uid and (status =  " . self::Rejecting . " or status = " . self::Accepting . ")");
    }

    /**
     *
     * @param type $uid
     * @param type $set_readed where run this function,we can set all the unreaded to readed
     * @return type 
     */
    public function getUnreadReqOutcome($uid, $set_readed = true) {
        $all = $this->_db->fetchAll("select *,sale.status as status from sale,user where uid = sellerid and buyerid = $uid and (sale.status =  " . self::Rejecting . " or sale.status = " . self::Accepting . ")");
        foreach ($all as &$tmp) {
//            if ($set_readed)
//                $this->_db->query("update sale set status = status + 1 where sid = " . $tmp['sid']);
            $tmp['req_time'] = date("Y-m-d H:i:s", $tmp['req_time']);
            if (isset($tmp['finish_time']) && $tmp['finish_time'] != "")
                $tmp['finish_time'] = $date("Y-m-d H:i:s", $tmp['finish_time']);
            $tmp['status'] = $this->getStatus($tmp['status']);
        }
        if ($set_readed) {
            $this->setReaded($uid);
        }
        return $all;
    }

    /**
     * 取得用户的所有交易请求(卖家)
     * @param type $uid
     * @return type 
     */
    public function getAllMy($uid) {
        $all = $this->_db->fetchAll("select *,sale.status as status from sale,user where uid = buyerid and sellerid = $uid and sale.status <> " . self::Deleted);
        foreach ($all as &$tmp) {
            $tmp['req_time'] = date("Y-m-d H:i:s", $tmp['req_time']);
            if (isset($tmp['finish_time']) && $tmp['finish_time'] != "")
                $tmp['finish_time'] = $date("Y-m-d H:i:s", $tmp['finish_time']);
//            if ($tmp['status'] == self::Accepting || $tmp['status'] == self::Rejecting)
//                $tmp['is_read'] = false;
//            else {
//                $tmp['is_read'] = true;
//            }
            $tmp['status'] = $this->getStatus($tmp['status']);
        }
        return $all;
    }

    /**
     * 取得用户向他人提交的请求处理结果（买家）
     * 这里如果有未读消息的话，会将交易标记为已读
     * 另外，用is_read标记是否已读
     * @param type $uid 
     */
    public function getReqOutcome($uid) {
        $all = $this->_db->fetchAll("select *,sale.status as status from sale,user where uid = sellerid and buyerid = $uid and sale.status >  " . self::Sended);
        $need_mark_readed = false;
        foreach ($all as &$tmp) {
            $tmp['req_time'] = date("Y-m-d H:i:s", $tmp['req_time']);
            if (isset($tmp['finish_time']) && $tmp['finish_time'] != "")
                $tmp['finish_time'] = $date("Y-m-d H:i:s", $tmp['finish_time']);
            if ($tmp['status'] == self::Accepting || $tmp['status'] == self::Rejecting) {
                $need_mark_readed = true;
                $tmp['is_read'] = false;
            }
            else
                $tmp['is_read'] = true;
            $tmp['status'] = $this->getStatus($tmp['status']);
        }

        if ($need_mark_readed) {
            $this->setReaded($uid);
        }
        return $all;
    }

    /**
     *
     * @param type $uid
     * @param type $sid默认为空，非空的话，取得所有sid所决定的交易中的被请求物品，因为其他用户也在请求
     * @return type 
     */
    public function getAllReq($uid, $sid = null) {
        if ($sid == null) {
            $all = $this->_db->fetchAll("select *,sale.status as status from sale,user where uid = buyerid and sellerid = $uid and sale.status = " . self::Sended);
        } else {
            $tmp = $this->fetchRow("sid = $sid");
            $gids = $this->goodsToArray($tmp['ask_goods']);
            $all = array();
            foreach ($gids as $gid) {
                $tmp = $this->_db->fetchAll("select *,sale.status as status from sale,user where uid = buyerid and sellerid = $uid and ask_goods like '%$gid%' and sale.status = " . self::Sended);
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
            return array_keys($total);
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
        $all = $this->_db->fetchRow("select *,sale.status as status from sale,user where sid = $sid and uid = sellerid and buyerid = $uid and sale.status <> " . self::Deleted);
        if (empty($all))
            $all = $this->_db->fetchRow("select *,sale.status as status from sale,user where sid = $sid and uid = buyerid and sellerid = $uid and sale.status <> " . self::Deleted);
        if ($all['use_goods'] != "money") {
            $all['use_names'] = $this->getGoods($all['use_goods'], false);
        }
        $all['ask_names'] = $this->getGoods($all['ask_goods'], false);
        return $all;
    }

    /**
     * 在用户同意交易，或者完成交易时，将所有交易涉及货物锁定，标记为交易中
     */
    public function setSaled($sid) {
        $tmp = $this->_db->fetchRow("select use_goods,ask_goods from sale where sid = $sid");
        foreach ($tmp as $goods) {
            $gids = $this->goodsToArray($goods);
            foreach ($gids as $gid) {
                $gid = $this->_db->update("goods", array('status' => GoodsModel::Saled), "gid = $gid");
            }
        }
    }

    /**
     * 设置交易失败，操作前，需检查交易的一些信息
     * @param type $sid
     * @param type $uid
     * @return boolean 
     */
    public function setFailed($sid, $uid) {
        $tmp = $this->_db->fetchRow("select * from sale where sid = $sid");
        if ($uid != $tmp['buyerid'])
            return false;
        if ($tmp['status'] != SaleModel::Accepted || $tmp['status'] != SaleModel::Accepting)
            return false;
        $this->update(array('status' => SaleModel::Failed, 'finish_time' => time()), "sid = $sid");
        return true;
    }

    /**
     * 设置交易成功，操作前，需检查交易的一些信息
     * 最后还需将所有参与交易的物品状态设置为已交易
     * @param type $sid
     * @param type $uid
     * @return boolean 
     */
    public function setSuccess($sid, $uid) {
        $tmp = $this->_db->fetchRow("select * from sale where sid = $sid");
        if ($uid != $tmp['buyerid'])
            return false;
        if ($tmp['status'] != SaleModel::Accepted || $tmp['status'] != SaleModel::Accepting)
            return false;
        $this->update(array('status' => SaleModel::Success, 'finish_time' => time()), "sid = $sid");

        $gids = $this->goodsToArray($tmp['ask_goods']);
        if ($tmp['use_goods'] != "money") {
            $gids2 = $this->goodsToArray($tmp['use_goods']);
            $gids = array_merge($gids, $gids2);
        }

        foreach ($gids as &$gid) {
            $gids = "id = $gid";
        }

        $this->_db->update("goods", array('status' => GoodsModel::Saled), implode(" or ", $gids));
        return true;
    }

    const Deleted = 0;
    const Sended = 1;
    const Accepting = 2;
    const Accepted = 3;
    const Success = 4;
    const Rejecting = 5;
    const Rejected = 6;
    const Failed = 7;

    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "已删除";
            case 1:return "已发送";
            case 2:return "已同意"; //已读的话，转为3
            case 3:return "已同意";
            case 4:return "交易成功";
            case 5:return "已拒绝"; //已读。转为6
            case 6:return "已拒绝";
            case 7:return "交易失败";
        }
    }

}

?>
