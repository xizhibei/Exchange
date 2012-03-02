<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/1/25
 * ***************************************************************************** */

function js_alert($msg, $goto = null, $delay = null) {
    if ($delay != null) {
        $delay = "setTimeout(\"location.href='$goto'\",$delay);";
    } else if ($goto != null) {
        $delay = "window.top.location='$goto';";
    }
    $msg = "<script>alert(\"$msg\");$delay</script>";
    echo $msg;
}

function cutstr($string, $beginIndex, $length) {
    if (strlen($string) < $length) {
        return substr($string, $beginIndex);
    }

    $char = ord($string[$beginIndex + $length - 1]);
    if ($char >= 224 && $char <= 239) {
        $str = substr($string, $beginIndex, $length - 1);
        return $str;
    }

    $char = ord($string[$beginIndex + $length - 2]);
    if ($char >= 224 && $char <= 239) {
        $str = substr($string, $beginIndex, $length - 2);
        return $str;
    }

    return substr($string, $beginIndex, $length);
}

function getIp() {
    if (getenv('HTTP_CLIENT_IP')) {
        $onlineip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $onlineip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR')) {
        $onlineip = getenv('REMOTE_ADDR');
    } else {
        $onlineip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
    }
    return $onlineip;
}

function redirect($url, $msg) {

    if ($msg == "PleaseLogin") {
        $tmp = urlencode($_SERVER['REQUEST_URI']);
        $url = urlencode($url . "?return_url=" . $tmp);
    }
    else
        $url = urlencode($url);
    header("Location:/redirect?url=$url&msg=$msg");
}

function filter_bad_html($str) {
    $str = preg_replace("/\s+/", " ", $str); //过滤多余回车
    $str = preg_replace("/<[ ]+/si", "<", $str); //过滤<__("<"号后面带空格)
    $str = preg_replace("/<\!–.*?–>/si", "", $str); //注释
    $str = preg_replace("/<(\!.*?)>/si", "", $str); //过滤DOCTYPE
    $str = preg_replace("/<(\/?html.*?)>/si", "", $str); //过滤html标签
    $str = preg_replace("/<(\/?head.*?)>/si", "", $str); //过滤head标签
    $str = preg_replace("/<(\/?meta.*?)>/si", "", $str); //过滤meta标签
    $str = preg_replace("/<(\/?body.*?)>/si", "", $str); //过滤body标签
    $str = preg_replace("/<(\/?link.*?)>/si", "", $str); //过滤link标签
    $str = preg_replace("/<(\/?form.*?)>/si", "", $str); //过滤form标签
    $str = preg_replace("/cookie/si", "COOKIE", $str); //过滤COOKIE标签
    $str = preg_replace("/<(applet.*?)>(.*?)<(\/applet.*?)>/si", "", $str); //过滤applet标签
    $str = preg_replace("/<(\/?applet.*?)>/si", "", $str); //过滤applet标签
    $str = preg_replace("/<(style.*?)>(.*?)<(\/style.*?)>/si", "", $str); //过滤style标签
    $str = preg_replace("/<(\/?style.*?)>/si", "", $str); //过滤style标签
    $str = preg_replace("/<(title.*?)>(.*?)<(\/title.*?)>/si", "", $str); //过滤title标签
    $str = preg_replace("/<(\/?title.*?)>/si", "", $str); //过滤title标签
    $str = preg_replace("/<(object.*?)>(.*?)<(\/object.*?)>/si", "", $str); //过滤object标签
    $str = preg_replace("/<(\/?objec.*?)>/si", "", $str); //过滤object标签
    $str = preg_replace("/<(noframes.*?)>(.*?)<(\/noframes.*?)>/si", "", $str); //过滤noframes标签
    $str = preg_replace("/<(\/?noframes.*?)>/si", "", $str); //过滤noframes标签
    $str = preg_replace("/<(i?frame.*?)>(.*?)<(\/i?frame.*?)>/si", "", $str); //过滤frame标签
    $str = preg_replace("/<(\/?i?frame.*?)>/si", "", $str); //过滤frame标签
    $str = preg_replace("/<(script.*?)>(.*?)<(\/script.*?)>/si", "", $str); //过滤script标签
    $str = preg_replace("/<(\/?script.*?)>/si", "", $str); //过滤script标签
    $str = preg_replace("/javascript/si", "Javascript", $str); //过滤script标签
    $str = preg_replace("/vbscript/si", "Vbscript", $str); //过滤script标签
    $str = preg_replace("/on([a-z]+)\s*=/si", "On\\1=", $str); //过滤script标签
    $str = preg_replace("/&#/si", "&＃", $str); //过滤script标签，如javAsCript:alert(
    return $str;
}

function _filter_bad_html($str) {

    $str = preg_replace("/\s+/", " ", $str); //过滤多余回车
    $str = preg_replace("/<[ ]+/si", "<", $str); //过滤<__("<"号后面带空格)
    $str = preg_replace("/cookie/si", "COOKIE", $str); //过滤COOKIE标签
    $str = preg_replace("/javascript/si", "Javascript", $str); //过滤script标签
    $str = preg_replace("/vbscript/si", "Vbscript", $str); //过滤script标签
    $str = preg_replace("/on([a-z]+)\s*=/si", "On\\1=", $str); //过滤script标签
    $str = preg_replace("/&#/si", "&＃", $str); //过滤script标签，如javAsCript:alert(
    $patterns = array("/<\!–.*?–>/si",
        "/<(\!.*?)>/si", "/<(\/?html.*?)>/si",
        "/<(\/?head.*?)>/si",
        "/<(\/?meta.*?)>/si",
        "/<(\/?body.*?)>/si",
        "/<(\/?link.*?)>/si",
        "/<(\/?form.*?)>/si",
        "/<(applet.*?)>(.*?)<(\/applet.*?)>/si",
        "/<(\/?applet.*?)>/si",
        "/<(style.*?)>(.*?)<(\/style.*?)>/si",
        "/<(\/?style.*?)>/si",
        "/<(title.*?)>(.*?)<(\/title.*?)>/si",
        "/<(\/?title.*?)>/si",
        "/<(object.*?)>(.*?)<(\/object.*?)>/si",
        "/<(\/?objec.*?)>/si", "",
        "/<(noframes.*?)>(.*?)<(\/noframes.*?)>/si",
        "/<(\/?noframes.*?)>/si",
        "/<(i?frame.*?)>(.*?)<(\/i?frame.*?)>/si",
        "/<(\/?i?frame.*?)>/si",
        "/<(script.*?)>(.*?)<(\/script.*?)>/si",
        "/<(\/?script.*?)>/si",);
    $str = preg_replace($patterns, "", $str);
    return $str;
}

function image_resize($src_file, $dst_width = 32, $dst_height = 32) {
    if ($dst_width < 1 || $dst_height < 1) {
        return null;
    }
    if (!file_exists($src_file)) {
        return null;
    }

    $type = exif_imagetype($src_file);
    $support_type = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);

    if (!in_array($type, $support_type, true)) {
        return null;
    }

    switch ($type) {
        case IMAGETYPE_JPEG :
            $src_img = imagecreatefromjpeg($src_file);
            header("Content-Type: image/jpeg");
            break;
        case IMAGETYPE_PNG :
            $src_img = imagecreatefrompng($src_file);
            header("Content-Type: image/png");
            break;
        case IMAGETYPE_GIF :
            $src_img = imagecreatefromgif($src_file);
            header("Content-Type: image/gif");
            break;
        default:
            return null;
    }
    $src_w = imagesx($src_img);
    $src_h = imagesy($src_img);
    $ratio_w = 1.0 * $dst_width / $src_w;
    $ratio_h = 1.0 * $dst_height / $src_h;
    if ($src_w <= $dst_width && $src_h <= $dst_height) {
        $x = ($dst_width - $src_w) / 2;
        $y = ($dst_height - $src_h) / 2;
        $new_img = imagecreatetruecolor($dst_width, $dst_height);
        imagecopy($new_img, $src_img, $x, $y, 0, 0, $dst_width, $dst_height);
        switch ($type) {
            case IMAGETYPE_JPEG :
                imagejpeg($new_img, null, 100);
                break;
            case IMAGETYPE_PNG :
                imagepng($new_img);
                break;
            case IMAGETYPE_GIF :
                imagegif($new_img);
                break;
            default:
                break;
        }
    } else {
        if ($ratio_w <= $ratio_h) {
            $zoom_w = $dst_width;
            $zoom_h = $zoom_w * ($src_h / $src_w);
        } else {
            $zoom_h = $dst_height;
            $zoom_w = $zoom_h * ($src_w / $src_h);
        }

        $zoom_img = imagecreatetruecolor($zoom_w, $zoom_h);
        imagecopyresampled($zoom_img, $src_img, 0, 0, 0, 0, $zoom_w, $zoom_h, $src_w, $src_h);
        $new_img = imagecreatetruecolor($dst_width, $dst_height);
        $x = ($dst_width - $zoom_w) / 2;
        $y = ($dst_height - $zoom_h) / 2 + 1;
        imagecopy($new_img, $zoom_img, $x, $y, 0, 0, $dst_width, $dst_height);
        switch ($type) {
            case IMAGETYPE_JPEG :
                imagejpeg($new_img, null, 100);
                break;
            case IMAGETYPE_PNG :
                imagepng($new_img);
                break;
            case IMAGETYPE_GIF :
                imagegif($new_img);
                break;
            default:
                break;
        }
    }
}

?>
