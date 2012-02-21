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
    $url = urlencode($url);
    $msg = urlencode($msg);
    header("Location:/redirect?url=$url&msg=$msg");
}

?>
