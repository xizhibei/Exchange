<?php

class TagModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'tag';
        parent::_setup();
    }

    public function getMostFrequently($off,$limit) {
        return $this->_db->fetchAll("select * from tag order by goods_count desc limit $off,$limit");
    }

    public function addTags($tags, $gid) {
        $tags = explode(',', $tags);
        array_unique($tags);//移除相同的标签
        foreach ($tags as $tag) {
            if ($tag == "")
                continue;
            $tid = $this->_db->fetchOne("select tid from tag where name = :tag",array('tag' => $tag));
            if (!$tid)
                $tid = $this->insert(array(
                    'name' => $tag,
                        ));
            $this->_db->insert('goods_tag', array(
                'tid' => $tid,
                'gid' => $gid,
            ));
            $this->_db->query("update tag set goods_count = goods_count + 1 where tid = :tid",array('tid' => $tid));
        }
    }
    
    public function getTags($gid){
        return $this->_db->fetchAll("select name from tag,goods_tag where tag.tid = goods_tag.tid and goods_tag.gid = $gid");
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
