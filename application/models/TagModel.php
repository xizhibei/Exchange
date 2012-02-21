<?php

class TasteModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'tag';
        parent::_setup();
    }

    public function getMostFrequently($limit) {
        return $this->_db->fetchAll("select * from tag order by goods_count desc limit 0,$limit");
    }

    public function addTags($tags, $gid) {
        $tags = explode(',', $tags);
        foreach ($tags as $tag) {
            if ($tmp == "")
                continue;
            $tmp = $this->_db->fetchOne("select count(*) from tag where name = '$tag'");
            if ($tag > 0) {
                $this->_db->query("update tag set goods_count = goods_count + 1 where name = $tag");
            } else {
                $this->insert(array(
                    'name' => $tag,
                ));
            }
        }
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
