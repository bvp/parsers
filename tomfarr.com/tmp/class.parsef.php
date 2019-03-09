<?php
class parsef{
    static $root="..";
    
    static function win($str, $to="windows-1251", $from="utf-8"){
        return mb_convert_encoding($str,$to,$from);
    }
    
    static function utf($str, $to="utf-8", $from="windows-1251"){
        return mb_convert_encoding($str,$to,$from);
    }
    
    static function removeBOM($text="") {
        if(substr($text, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
            $text= substr($text, 3);
        }
        return $text;
    }

    static function tolower($str,$cp="utf-8"){
        return mb_convert_case($str,MB_CASE_LOWER,$cp);
    }
    
    static function toupper($str,$cp="utf-8"){
        return mb_convert_case($str,MB_CASE_UPPER,$cp);
    }

    static function toupfirst($str,$cp="utf-8"){
        return parsef::toupper(mb_substr($str,0,1,$cp),$cp).mb_substr($str,1,mb_strlen($str,$cp),$cp);
    }

    static function get($get_url, $cookie_file="", $cookie="", $proxy=false, $headers=false){
        $referer = str_replace(basename($get_url),'',$get_url); 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $get_url);
        curl_setopt($ch, CURLOPT_HEADER,$headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru-RU; rv:1.7.12) Gecko/20050919 Firefox/1.0.7");
        if($proxy!==false) curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_VERBOSE,1);
        $data = curl_exec($ch);
        curl_close($ch);
        if(preg_match('|Location:\s+(.*)\s|isU',$data,$tmp)) {
            $location=$tmp[1];
            $data.=send_get($location, $cookie_file, $cookie);
        }
        return $data;
    }

    static function cget($get_url, $cookie_file="", $cookie="", $proxy=false, $headers=false, $pause=false){
        if(defined('JPATH_CACHE')) $cdir = JPATH_CACHE;
        else $cdir = dirname(__FILE__)."/cache";
        $fcache = $cdir."/parser/".parse_url($get_url, PHP_URL_HOST)."/".md5($get_url).".html";
        if(file_exists($fcache)){
            $data = file_get_contents($fcache);
        }else{
            $data = parsef::get($get_url, $cookie_file, $cookie, $proxy, $headers);
            $dcache = dirname($fcache);
            if(!file_exists($dcache)) mkdir($dcache, 0755, true);
            file_put_contents($fcache, $data);
            if($pause) usleep($pause);
        }
        return $data;
    }

    static function post($post_url, $post_data, $cookie_file="", $cookie="") {
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
            $data.=parsef::get($location, $cookie_file, $cookie);
        }
        return $data;
    }

    static function prn($array){
        echo "<pre>";
        echo str_replace("<", "&lt;", str_replace(">", "&gt;", print_r($array, true)));
        echo "</pre>";
    }

    static function translit($text, $for_alias=true){
        if($for_alias) $space="-"; else $space=" ";
        $trans = array("а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ё" => "e", "ж" => "zh", "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "kh", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "shch", "ы" => "y", "э" => "e", "ю" => "yu", "я" => "ya", "А" => "A", "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D", "Е" => "E", "Ё" => "E", "Ж" => "Zh", "З" => "Z", "И" => "I", "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N", "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T", "У" => "U", "Ф" => "F", "Х" => "Kh", "Ц" => "Ts", "Ч" => "Ch", "Ш" => "Sh", "Щ" => "Shch", "Ы" => "Y", "Э" => "E", "Ю" => "Yu", "Я" => "Ya", "ь" => "", "Ь" => "", "ъ" => "", "Ъ" => "", "№"=>"N", " " => $space);
        if($for_alias) {
            $text=str_replace(array("{","}","(",")","\\","/",":","*","?",'"',"<",">","|",".",","),"",$text);
            return strtolower(strtr($text, $trans));
        }else{
            return strtr($text, $trans);
        }
    }

    static function translate($text, $lang="ru-en", $for_alias=true){
        $urltext=urlencode($text);
        $json=file_get_contents("http://translate.yandex.net/api/v1/tr.json/translate?lang={$lang}&text={$urltext}");
        $req=json_decode($json);
        if(is_object($req) && $req->code==200){
            $text=implode("\n",$req->text);
        }
        return parsef::translit($text,$for_alias);
    }
      
    static function img_copy($from, $to=false, $size=0, $side='w', $quality=90, $fill=0xffffff) {
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
        
	static function thumb($src, $width=0, $height=0, $params = array()){
        if(isset($this) && isset($this->params) && is_array($this->params) && isset($this->params['overflow'])) $overflow = $this->params['overflow'];
		$pathcache = JPATH_SITE.'/images/thumbs';
        $overflow='original';
        $scaling='fields';
		$quality = '90';
		$align = 'center';
		$valign = 'center';
		$fill = '#FFFFFF';
        extract($params, EXTR_OVERWRITE);
        $fill = hexdec(str_replace('#','',$fill));
		$url = parse_url($src);
        if(isset($url['query'])) return false;
		elseif(substr($url['path'],0,1)=='/') $file = JPATH_SITE.$url['path'];
		else $file = JPATH_SITE.'/'.$url['path'];
		
        if(!file_exists($file)) return false;
        if(strpos($file,'com_ncatalogues')){
			$orig = str_replace(array('max_','thumb_','thumb2_'),'',$file);
			if(file_exists($orig)) $file = $orig;
		}
        if(($fsize=@getimagesize($file))===false) return false;
        $type = preg_replace('|^.*/|isU','',$fsize['mime']);
        $exts = array('','gif','jpg','png','swf','psd','bmp','tif','tif','jpc','jp2','jpx');
        $ext = ($fsize[2]>3 ? 'jpg' : $exts[$fsize[2]]);
        $crc = md5($file.json_encode($params));
        $name = basename($file,'.'.$ext);
        $dest = "{$pathcache}/{$crc}_{$name}_{$width}_{$height}.{$ext}";
        if(file_exists($dest)) return str_replace(JPATH_SITE,'',$dest);
        if(!file_exists($pathcache)){
            $perms = fileperms(JPATH_SITE);
            if(!mkdir($pathcache,$perms,true)) return false;
        }
        $ow = $fsize[0];
        $oh = $fsize[1];
        $put_img = "image".$type;
        if(!function_exists($put_img)) return false;
        $get_img = "imagecreatefrom".$type;
        if(!function_exists($get_img)) return false;
        $from = $get_img($file);
        $nx = 0;
        $ny = 0;
        $ox = 0;
        $oy = 0;
        /* Вычисление размеров */
        if(!$width){
            if(!$height){
                $nh = $oh;
                $nw = $ow;
            }else{
                $nh = $height;
                $nw = round(1.0*$ow/$oh*$nh);
            }
        }else{
            if(!$height){
                $nw = $width;
                $nh = round(1.0*$oh/$ow*$nw);
            }else{
                $nw = $width;
                $nh = $height;
                /* Вычисление пропорций */
                if($scaling=='fields'){
                    if($oh/$ow < $nh/$nw){
                        $ny = round(($nh-1.0*$oh/$ow*$nw)/2);
                    }else{
                        $nx = round(($nw-1.0*$ow/$oh*$nh)/2);
                    }
                }elseif($scaling=='crop'){
                    if($oh/$ow < $nh/$nw){
                        $ox = round(($ow-1.0*$nw/$nh*$oh)/2);
                    }else{
                        $oy = round(($oh-1.0*$nh/$nw*$ow)/2);
                    }
                }
            }
        }

        $cw = $nw; // coverWidth
        $ch = $nh; // coverHeight
        /* Контроль превышения размеров */
        if($nw > $ow && $nh > $oh){
            if($overflow == 'original'){
                $cw = $nw = $ow;
                $ch = $nh = $oh;
            }
            if($overflow == 'fields'){
                if($nw/$ow > $nh/$oh){
                    $nh = $oh;
                    $nw = round(1.0*$ow/$oh*$nh);
                }else{
                    $nw = $ow;
                    $nh = round(1.0*$oh/$ow*$nw);
                }
            }
            $ny = round(($ch-$nh)/2);
            $nx = round(($cw-$nw)/2);
        }
        
        $img = imagecreatetruecolor($cw, $ch);
        imagefill($img, 0, 0, $fill);
        imagecolortransparent($img, $fill);
        if($ext=='png'){
			imagealphablending($img, false);
			$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
			imagefill($img, 0, 0, $transparent);
			imagesavealpha($img,true);
			imagealphablending($img, true);
		}
        
	   /* Вычисление смещений */
		$nw = $cw-$nx*2;
		$nh = $ch-$ny*2;
		$ow -= $ox*2;
		$oh -= $oy*2;
		if($align=='left'){
			$nx = 0;
			$ox = 0;
		}elseif($align=='right'){
			$nx *= 2;
			$ox *= 2;
		}
		if($valign=='top'){
			$ny = 0;
			$oy = 0;
		}elseif($valign=='bottom'){
			$ny *= 2;
			$oy *= 2;
		}

        imagecopyresampled($img, $from, $nx, $ny, $ox, $oy, $nw, $nh, $ow, $oh);
        if($ext=='jpg') $put_img($img, $dest, $quality);
        else $put_img($img, $dest);
        imagedestroy($img);
        imagedestroy($from);

		return str_replace(JPATH_SITE,'',$dest);
	}

    static function crop($str='', $substr='', $n=0) {
        $str=strip_tags($str);
        $words = explode($substr, $str);
        if(count($words)>$n) {
            $words = array_slice($words, 0, $n);
            $str = implode($substr, $words).'...';
        }
        return $str;
    }
    static function cp($src_file, $dest_file) {
        return file_put_contents($dest_file,file_get_contents($src_file));
    }
    static function array_similar($arr, $word) {
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
    static function quant($cnt=0,$one='',$two='',$five='',$withnum=false) {
        if(in_array($cnt%100,array(11,12,13,14))||in_array($cnt%10,array(0,5,6,7,8,9))) {
            return ($withnum ? $cnt : '').$five;
        } elseif($cnt%10==1) {
            return ($withnum ? $cnt : '').$one;
        } else {
            return ($withnum ? $cnt : '').$two;
        }
    }
    static function getVar($varName, $defaultValue=''){
        $VARS = array_merge($_GET,$_POST);
        if(isset($VARS[$varName])) return $VARS[$varName];
        else return $defaultValue;
    }

    static function geocode($address){
        $addr=urlencode($address);
        $req=file_get_contents("http://geocode-maps.yandex.ru/1.x/?origin=jsapi2Geocoder&geocode={$addr}&format=json&rspn=0&results=1&lang=ru_RU");
        $json=json_decode($req);
        if($json==null) return false;
        if($json->response->GeoObjectCollection->metaDataProperty->GeocoderResponseMetaData->found)
            $coord=$json->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos;
        else return false;
        $coords=explode(' ',$coord);
        $gcode=$coords[1].','.$coords[0];
        return $gcode;
    }

    static function dest_file_exists($filename){
        $headers=get_headers($filename);
        return (strpos($headers[0],' 200 ')!==false);
    }

	static function normalize($size) {
		if (preg_match('/^(-?[\d\.]+)(|[KMG])$/i', $size, $match)) {
			$pos = array_search($match[2], array("", "K", "M", "G"));
			$size = $match[1] * pow(1024, $pos);
		} else {
			throw new Exception("Failed to normalize memory size '{$size}' (unknown format)");
		}
		return $size;
	}
		
	static function detectMaxUploadFileSize()
	{
		/**
		 * Converts shorthands like "2M" or "512K" to bytes
		 *
		 * @param int $size
		 * @return int|float
		 * @throws Exception
		 */

		$limits = array();
		$limits[] = self::normalize(ini_get('upload_max_filesize'));
		if (($max_post = self::normalize(ini_get('post_max_size'))) != 0) {
			$limits[] = $max_post;
		}
		if (($memory_limit = self::normalize(ini_get('memory_limit'))) != -1) {
			$limits[] = $memory_limit;
		}
		$maxFileSize = min($limits);
		return $maxFileSize;
	}

    static function number_cursive($num,$ucfirst=false){
        # Все варианты написания чисел прописью от 0 до 999 скомпануем в один небольшой массив
        $m=array(
            array('ноль'),
            array('-','один','два','три','четыре','пять','шесть','семь','восемь','девять'),
            array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать','пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать'),
            array('-','-','двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят','восемьдесят','девяносто'),
            array('-','сто','двести','триста','четыреста','пятьсот','шестьсот','семьсот','восемьсот','девятьсот'),
            array('-','одна','две')
        );
        # Все варианты написания разрядов прописью скомпануем в один небольшой массив
        $r=array(
            array('...ллион','','а','ов'), // используется для всех неизвестно больших разрядов 
            array('тысяч','а','и',''),
            array('миллион','','а','ов'),
            array('миллиард','','а','ов'),
            array('триллион','','а','ов'),
            array('квадриллион','','а','ов'),
            array('квинтиллион','','а','ов')
            // ,array(... список можно продолжить
        );
        if($num==0)return$m[0][0]; # Если число ноль, сразу сообщить об этом и выйти
        $o=array(); # Сюда записываем все получаемые результаты преобразования
        # Разложим исходное число на несколько трехзначных чисел и каждое полученное такое число обработаем отдельно
        foreach(array_reverse(str_split(str_pad($num,ceil(strlen($num)/3)*3,'0',STR_PAD_LEFT),3))as$k=>$p){
            $o[$k]=array();
            # Алгоритм, преобразующий трехзначное число в строку прописью
            foreach($n=str_split($p)as$kk=>$pp)
            if(!$pp)continue;else
            switch($kk){
                case 0:$o[$k][]=$m[4][$pp];break;
                case 1:if($pp==1){$o[$k][]=$m[2][$n[2]];break 2;}else$o[$k][]=$m[3][$pp];break;
                case 2:if(($k==1)&&($pp<=2))$o[$k][]=$m[5][$pp];else$o[$k][]=$m[1][$pp];break;
            }$p*=1;if(!$r[$k])$r[$k]=reset($r);

            # Алгоритм, добавляющий разряд, учитывающий окончание руского языка
            if($p&&$k)switch(true){
                case preg_match("/^[1]$|^\\d*[0,2-9][1]$/",$p):$o[$k][]=$r[$k][0].$r[$k][1];break;
                case preg_match("/^[2-4]$|\\d*[0,2-9][2-4]$/",$p):$o[$k][]=$r[$k][0].$r[$k][2];break;
                default:$o[$k][]=$r[$k][0].$r[$k][3];break;
            }$o[$k]=implode(' ',$o[$k]);
        }
        $result = implode(' ',array_reverse($o));
        if($ucfirst){
            $words = explode(" ",$result);
            $words[0] = mb_convert_case($words[0],MB_CASE_TITLE);
            $result = implode(" ",$words);
        }
        return $result;
    }

    static function phone($tel){
		$tel = preg_replace("|\D|isU", "", $tel);
		$len = strlen($tel);
		$digits = str_split($tel);
		$num = "";
		if($len > 2){
			$num .= array_pop($digits);
			$num .= array_pop($digits);
			$num .= "-";
		}
		if($len > 4){
			$num .= array_pop($digits);
			$num .= array_pop($digits);
			$num .= "-";
		}
		if($len > 6){
			$num .= array_pop($digits);
			$num .= array_pop($digits);
		}
		if(isset($digits[1]) && $digits[1]==9){
			$num .= array_pop($digits);
			$num .= " )";
		}else{
			$num .= " )";
			$num .= array_pop($digits);
		}
		while(count($digits) > 1) $num .= array_pop($digits);
		if(strpos($num,")")!==false) $num .= "( ";
		if(count($digits) > 0){
			$num .= array_pop($digits);
			$num .= "+";
		}
		$tel = implode("", array_reverse(str_split($num)));
		return $tel;
	}
}
?>
