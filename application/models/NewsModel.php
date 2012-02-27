<?php

class NewsModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'news';
        parent::_setup();
    }

    public function getAllNews() {
        $all = $this->_db->fetchAll("select news.*,user.name from news,user where user.uid = news.aid order by news.update_time desc");
        foreach ($all as &$tmp) {
            $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
            $tmp['update_time'] = date("Y-m-d H:i:s", $tmp['update_time']);
            $tmp['content'] = filter_var($tmp['content'], FILTER_SANITIZE_STRING);
            $tmp['content_cut'] = strlen($tmp['content']) <= 200 ? $tmp['content'] : cutstr($tmp['content'], 0, 100) . "...";
            $tmp['type'] = $this->getType($tmp['type']);
            $tmp['status'] = $this->getStatus($tmp['status']);
        }
        return $all;
    }
    
    public function getHotNewsTitle($num) {
        $all = $this->_db->fetchAll("select nid,title from news where status = " . self::Published . " order by click desc limit 0,$num");
//        foreach ($all as &$tmp) {
//            $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
//            $tmp['update_time'] = date("Y-m-d H:i:s", $tmp['update_time']);
//            $tmp['content'] = filter_var($tmp['content'], FILTER_SANITIZE_STRING);
//            $tmp['content_cut'] = strlen($tmp['content']) <= 200 ? $tmp['content'] : cutstr($tmp['content'], 0, 100) . "...";
//            $tmp['type'] = $this->getType($tmp['type']);
//            $tmp['status'] = $this->getStatus($tmp['status']);
//        }
        return $all;
    }

    public function getSingleNews($nid) {
        $tmp = $this->fetchRow("nid = $nid")->toArray();

        $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
        $tmp['update_time'] = date("Y-m-d H:i:s", $tmp['update_time']);
        $tmp['content'] = filter_var($tmp['content'], FILTER_SANITIZE_STRING);
        $tmp['content_cut'] = strlen($tmp['content']) <= 200 ? $tmp['content'] : cutstr($tmp['content'], 0, 100) . "...";
        $tmp['type'] = $this->getType($tmp['type']);
        $tmp['status'] = $this->getStatus($tmp['status']);
        $tmp['author'] = $this->_db->fetchOne("select name from user where uid = " . $tmp['aid']);
        return $tmp;
    }
    
    public function updateReadTimes($nid){
        $this->_db->query("update news set click = click + 1 where nid = $nid");
    }

    public function getStatus($status_id) {
        switch ($status_id) {
            case 0:return "已删除";
            case 1:return "已发布";
            case 2:return "已保存";
        }
    }

    const Deleted = 0;
    const Published = 1;
    const Saved = 2;

    public function getType($id) {
        switch ($id) {
            case 0:return "新闻";
            case 1:return "公告";
        }
    }

    const News = 1;
    const Announcement = 2;

}

?>
