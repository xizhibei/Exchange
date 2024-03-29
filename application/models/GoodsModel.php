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

    /*     * **********    for admin ************************ */

    public function getAllGoods() {
        $all = $this->_db->fetchAll("select goods.*,user.name as uname from goods,user where goods.uid = user.uid order by goods.publish_time desc");
        foreach ($all as &$tmp) {
            $tmp['name'] = strlen($tmp['name']) <= 30 ? $tmp['name'] : cutstr($tmp['name'], 0, 30);
            $tmp['detail'] = filter_var($tmp['detail'], FILTER_SANITIZE_STRING);
            $tmp['detail_cut'] = strlen($tmp['detail']) <= 100 ? $tmp['detail'] : cutstr($tmp['detail'], 0, 100);
            $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
            $tmp['status'] = self::getStatus($tmp['status']);
            if ($tmp['expire_date'] == self::NeverExpireDate)
                $tmp['expire_date'] = "永不过期";
            else
                $tmp['expire_date'] = date("Y-m-d H:i:s", $tmp['expire_date']);
        }
        return $all;
    }

    /*     * ************  end  ********************** */

    private function writeSearchCache(array $all) {
        Zend_Loader::loadClass("Custom_Controller_Plugin_CNLuceneAnalyzer");
        //Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8());
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Custom_Controller_Plugin_CNLuceneAnalyzer());
        $index = new Zend_Search_Lucene('../data/search_cache/goods', true);
        Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');

        foreach ($all as $tmp) {
            $query = Zend_Search_Lucene_Search_QueryParser::parse("key:" . md5($tmp['id']), 'utf-8');
            $hits = $index->find($query);
            foreach ($hits as $hit) {
                $index->delete($hit->id);
            }

            $doc = new Zend_Search_Lucene_Document();

            $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($tmp['id'])));
            $doc->addField(Zend_Search_Lucene_Field::UnIndexed('url', "/goods/detail/gid/" . $tmp['id'], 'utf-8'));
            $doc->addField(Zend_Search_Lucene_Field::Text('name', strtolower($tmp['name']), 'utf-8'));
            $doc->addField(Zend_Search_Lucene_Field::Text('tags', implode(" ", $tmp['tags']), 'utf-8'));
//            $doc->addField(Zend_Search_Lucene_Field::Text('author', strtolower($author), 'utf-8'));
            $tmp['detail'] = filter_var($tmp['detail'], FILTER_SANITIZE_STRING);
            $doc->addField(Zend_Search_Lucene_Field::UnStored('detail', strtolower($tmp['detail_notcut']), 'utf-8'));

            $index->addDocument($doc);
            $index->commit();
        }
    }

    public function getAllPublished() {
        $cache = Zend_Cache::factory('Core', 'File', $this->config->front->toArray(), $this->config->back->toArray());
        $cache->clean(); ///////////
        if (($all = $cache->load('all_published')) == false) {
            $color = array('#FF4D4D', '#469AE9', '#333333', '#05183e', '#B6B986', '#666699', '#ff8a00', '#de312b', '#63a716');
            $num = count($color) - 1;
            $all = $this->fetchAll("expire_date > " . time() . " and status =" . GoodsModel::Published, "publish_time desc")->toArray();

            foreach ($all as &$tmp) {
                $tmp['name_cut'] = strlen($tmp['name']) <= 30 ? $tmp['name'] : cutstr($tmp['name'], 0, 30);
                $tmp['detail'] = filter_var($tmp['detail'], FILTER_SANITIZE_STRING);
                $tmp['detail_notcut'] = $tmp['detail'];
                $tmp['detail'] = strlen($tmp['detail']) <= 400 ? $tmp['detail'] : cutstr($tmp['detail'], 0, 400);
                $tmp['publish_time'] = date("Y-m-d H:i:s", $tmp['publish_time']);
                if ($tmp['expire_date'] == self::NeverExpireDate)
                    $tmp['expire_date'] = "永不过期";
                else
                    $tmp['expire_date'] = date("Y-m-d H:i:s", $tmp['expire_date']);
                $tmp['status'] = self::getStatus($tmp['status']);
                $tmp['color'] = $color[mt_rand(0, $num - 1)];
                $tmp['click'] = $this->_db->fetchOne("select count(*) from click where gid = " . $tmp['id']);
                $tmp['like_num'] = $this->_db->fetchOne("select count(*) from taste where status = 2 and gid = " . $tmp['id']);
                $tmp['hate_num'] = $this->_db->fetchOne("select count(*) from taste where status = 3 and gid = " . $tmp['id']);
                $tags = $this->_db->fetchAll("select name from tag,goods_tag where tag.tid = goods_tag.tid and gid = " . $tmp['id']);
                $t = array();
                foreach ($tags as $tag) {
                    array_push($t, $tag['name']);
                }
                $tmp['tags'] = $t;
            }
            
            $this->writeSearchCache($all);
            
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
            $tmp['detail'] = filter_var($tmp['detail'], FILTER_SANITIZE_STRING);
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
            $tmp['detail'] = filter_var($tmp['detail'], FILTER_SANITIZE_STRING);
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
    const NeverExpireDate = 2145931200; //2038-01-01 12:00:00 PM

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
