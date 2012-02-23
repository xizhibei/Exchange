<?php

class NewsModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'news';
        parent::_setup();
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
    
    const News = 1;
    const Announcement = 2;

}

?>
