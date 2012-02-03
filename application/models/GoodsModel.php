<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */

class GoodsModel extends Zend_Db_Table {

    private $config;

    protected function _setup() {
        $this->_name = 'goods';
        $this->_primary = 'id';
        $this->config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'cache');
        parent::_setup();
    }

    public function getAllPublished() {
        $cache = Zend_Cache::factory('Core', 'File', $this->config->front->toArray(), $this->config->back->toArray());
        //$cache->clean();///////////
        if (($all = $cache->load('all_published')) === false) {
            $color = array('#FF4D4D', '#469AE9', '#333333', '#05183e', '#B6B986', '#C0F0C0', '#666699');
            $all = $this->fetchAll("status =" . GoodsModel::Published, "publish_time desc")->toArray();
            foreach ($all as &$tmp) {
                $tmp['name'] = strlen($tmp['name']) <= 30 ? $tmp['name'] : cutstr($tmp['name'], 0, 30);
                $tmp['detail'] = strlen($tmp['detail']) <= 200 ? $tmp['detail'] : cutstr($tmp['detail'], 0, 200);
                $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
                $tmp['status'] = self::getStatus($tmp['status']);
                $tmp['color'] = $color[mt_rand(0, 6)];
            }
            $cache->save($all, 'all_published');
        }
        return $all;
    }

    public function getSinglePublished($gid) {
        $single = $this->fetchRow("status = " . GoodsModel::Published . " and id = $gid");
        $single['publish_time'] = date("Y-m-d H:i:s", $single['publish_time']);
        $single['status'] = self::getStatus($single['status']);
        return $single;
    }

    public function getAllMy($uid) {
        $all = $this->fetchAll("status <> 0 and uid = $uid", "publish_time desc")->toArray();
        foreach ($all as &$tmp) {
            $tmp['name'] = strlen($tmp['name']) <= 40 ? $tmp['name'] : cutstr($tmp['name'], 0, 30);
            $tmp['detail'] = strlen($tmp['detail']) <= 40 ? $tmp['detail'] : cutstr($tmp['detail'], 0, 40);
            $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
            $tmp['status'] = self::getStatus($tmp['status']);
        }
        return $all;
    }

    public function search($q) {
        $where = "";
        if ($q != "")
            $where = "(name like '%$q%' or detail like '%$q%') and ";
        $all = $this->fetchAll($where . " status =" . GoodsModel::Published, "publish_time desc")->toArray();
        foreach ($all as &$tmp) {
            $tmp['name'] = strlen($tmp['name']) <= 40 ? $tmp['name'] : cutstr($tmp['name'], 0, 30);
            $tmp['detail'] = strlen($tmp['detail']) <= 40 ? $tmp['detail'] : cutstr($tmp['detail'], 0, 40);
            $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
            $tmp['status'] = self::getStatus($tmp['status']);
        }
        return $all;
    }

    const Deleted = 0;
    const Published = 1;
    const Saved = 2;
    const Saled = 3;

    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "已删除";
            case 1:return "已发布";
            case 2:return "暂存中";
            case 3:return "已交易";
            case 4:return "交易中";
        }
    }

}

?>
