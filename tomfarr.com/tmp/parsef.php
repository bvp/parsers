<?php
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

if(!isset($root)) $root=$_SERVER['DOCUMENT_ROOT'];
if(!isset($ext)) $ext=array(1 => 'gif', 2 => 'jpg', 3 => 'png', 4 => 'swf', 5 => 'psd', 6 => 'bmp', 7 => 'tif', 8 => 'tiff', 9 => 'jpc', 10 => 'jp2', 11 => 'jpx');

function win($str) {
    return mb_convert_encoding($str, "windows-1251", "utf-8");
}
function cyr_win($str) {
	return win ($str);
}

function utf($str) {
    return mb_convert_encoding($str, "utf-8", "windows-1251");
}
function cyr_utf($str) {
	return utf($str);
}

function tolower($inputString) {
	return mb_convert_case($inputString, MB_CASE_LOWER, "UTF-8");
}

function toupper($inputString) {
	return mb_convert_case($inputString, MB_CASE_UPPER, "UTF-8");
}

function toucfirst($str)
{
    $str = cyr_win($str);
	$str = mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1);
    $str = cyr_utf($str);
    return $str;
}

function send_get($get_url, $cookie_file="cookie.sav", $cookie="", $proxy=false) {
	global $location;
    $cached = false;
    if(defined('JPATH_CACHE')){
        $fcache = JPATH_CACHE."/parser/".parse_url($get_url, PHP_URL_HOST)."/".md5($get_url).".html";
        if(file_exists($fcache)){
            $data = file_get_contents($fcache);
            $cached = true;
        }
    }
    if(!$cached){
        $referer = str_replace(basename($get_url),'',$get_url); 
        $ch = curl_init();
        //echo $get_url.'|'.$referer;
        //curl_setopt($ch, CURLOPT_PROXY, "http://200.199.229.90:3128");
        curl_setopt($ch, CURLOPT_URL, $get_url);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:14.0) Gecko/20100101 Firefox/14.0");
        if($proxy!==false) curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_VERBOSE,1);
        $data = curl_exec($ch);
        curl_close($ch);
        if(preg_match('|Location:\s+(.*)\s|isU',$data,$tmp)) {
            $location=$tmp[1];
            $data.=send_get($location, $cookie_file, $cookie);
        }
        if(defined('JPATH_CACHE')){
            $dcache = dirname($fcache);
            if(!file_exists($dcache)) mkdir($dcache, 0755, true);
            file_put_contents($fcache, $data);
        }
    }
    return $data;
}

function send_post($post_url, $post_data, $cookie_file="", $cookie="") {
    global $location;
    $ch = curl_init();
    //curl_setopt($ch, CURLOPT_PROXY, "http://111.133.11.17:8080");
    curl_setopt($ch, CURLOPT_URL, $post_url);
    curl_setopt($ch, CURLOPT_HEADER,1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_REFERER, $post_url);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU; rv:1.7.12) Gecko/20050919 Firefox/1.0.7");
    curl_setopt($ch, CURLOPT_VERBOSE,1);
    $data = curl_exec($ch);
    curl_close($ch);
    preg_match('|Location:\s+(.*)\s|isU',$data,$tmp);
	if($tmp) {
		$location=$tmp[1];
		$data.=send_get($location, $cookie_file, $cookie);
	}
    return $data;
}

function prn($array){
		echo "<pre>";
		echo str_replace("<", "&lt;", str_replace(">", "&gt;", print_r($array, true)));
		echo "</pre>";
}

function translit($text, $for_alias=true) {
	if($for_alias) $space="-"; else $space=" ";
	$trans = array("а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ё" => "e", "ж" => "zh", "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "kh", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "shch", "ы" => "y", "э" => "e", "ю" => "yu", "я" => "ya", "А" => "A", "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D", "Е" => "E", "Ё" => "E", "Ж" => "Zh", "З" => "Z", "И" => "I", "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N", "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T", "У" => "U", "Ф" => "F", "Х" => "Kh", "Ц" => "Ts", "Ч" => "Ch", "Ш" => "Sh", "Щ" => "Shch", "Ы" => "Y", "Э" => "E", "Ю" => "Yu", "Я" => "Ya", "ь" => "", "Ь" => "", "ъ" => "", "Ъ" => "", " " => $space);
	if($for_alias) {
        $text=str_replace(array("\\","/",":","*","?",'"',"<",">","|"),"",$text);
        return strtolower(strtr($text, $trans));
    }else{
        return strtr($text, $trans);
    }
}
  
function img_copy($from, $to=false, $size=0, $side='w', $quality=90, $fill=0xffffff) {
	if(gettype($from) != 'resource') {
		if(($fsize=getimagesize($from))===false) return false; 
		$ow = $fsize[0];
		$oh = $fsize[1];
        $type = strtolower(substr($fsize['mime'], strpos($fsize['mime'], '/')+1));
		$get_img = "imagecreatefrom".$type;
		if(!function_exists($get_img)) return false;
        $from = $get_img($from);
		$can_destroy = true;
	}
	else {
		$ow = imagesx($from); 
		$oh = imagesy($from);
		$can_destroy = false;
	}
	if($size>0) {
		if(substr($side, 0, 1)=='w') {
			$nw = $size;
			$nh = round(1.0*$oh/$ow*$nw);
		}
		elseif(substr($side, 0, 1)=='h') {
			$nh = $size;
			$nw = round(1.0*$ow/$oh*$nh);
		}
		else {
			$nw = $nh = $size;
		}
	}
	else {
		$nw = $ow;
		$nh = $oh;
	}
    if($nw>$ow || $nh>$oh){
		$nw = $ow;
		$nh = $oh;
    }
	$img = imagecreatetruecolor($nw, $nh);
	imagefill($img, 0, 0, $fill);
	imagecopyresampled($img, $from, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
	if($to){
		imagejpeg($img, $to, $quality);
		imagedestroy($img);
		if($can_destroy) imagedestroy($from);
		return $to;
	}
	else {
		if($can_destroy) imagedestroy($from);
			return $img;
	}
}
	
function crop($str='', $substr='', $n=0) {
    $str=strip_tags($str);
    $words = explode($substr, $str);
    if(count($words)>$n) {
        $words = array_slice($words, 0, $n);
        $str = implode($substr, $words).'...';
    }
	return $str;
}
function cp($src_file, $dest_file) {
    return file_put_contents($dest_file,file_get_contents($src_file));
}
function array_similar($arr, $word) {
    $max = 0;
    $mid = 0;
    foreach($arr as $id=>$el) {
        similar_text($el, $word, $sim);
        if($sim>$max) {
            $max = $sim;
            $mid = $id;
        }
    }
    return $mid;
}
function quant($cnt=0,$one='',$two='',$five='',$withnum=false) {
    if(in_array($cnt%100,array(11,12,13,14))||in_array($cnt%10,array(0,5,6,7,8,9))) {
        return ($withnum ? $cnt : '').$five;
    } elseif($cnt%10==1) {
        return ($withnum ? $cnt : '').$one;
    } else {
        return ($withnum ? $cnt : '').$two;
    }
}
function getVar($varName, $defaultValue=''){
    $VARS = array_merge($_GET,$_POST);
    if(isset($VARS[$varName])) return $VARS[$varName];
    else return $defaultValue;
}

?>
