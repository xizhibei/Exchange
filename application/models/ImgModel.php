<?php

class ImgModel extends Zend_Db_Table {

    protected function _setup() {
        $this->_name = 'img';
        parent::_setup();
    }

    public function addImg($file, $uid) {
        $key = $file;
//        $key = substr($file, 0, strpos($file, "."));
        $img_id = $this->_db->fetchOne("select img.id from img where uid = $uid and `key` = :key", array('key' => $key));
        if (!$img_id)
            $img_id = $this->insert(array(
                'key' => $key,
                'uid' => $uid,
                    ));
        return $img_id;
    }

    public function addImgFromHtml($str, $uid) {
        preg_match_all('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', $str, $matches);
        $normal_path = array("/upload/$uid/images","/upload/$uid/images/.thumbs/images");
        
        foreach ($matches[2] as $img_src) {
            $name = dirname($img_src);
            if(in_array($name,$normal_path)){
                preg_match("/([\d\w]+\.\w+)$/i", $img_src, $match);
                
                $id = $this->addImg($match[1], $uid);
                $str = str_replace($img_src, "/img/" . $id, $str);
            }
        }
        return $str;
    }

    public function existImg($uid, $key) {
        return $this->_db->fetchOne("select id from img where uid = $uid and key = :key", array('key' => $key));
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
