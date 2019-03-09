<?php
/*
	DELETE FROM `jos_ncatalogues_object_object_href` WHERE 1;
	DELETE FROM `jos_ncatalogues_object3` WHERE 1;
	DELETE FROM `jos_ncatalogues_media` WHERE 1;
*/
  set_time_limit(0);
  $root_dir=$_SERVER['DOCUMENT_ROOT'];
  $GLOBALS['root_dir'] = $root_dir;

  define('_JEXEC', 1);
  define('DS', '/');
  define('JPATH_BASE', realpath(dirname(__FILE__)."/.."));
  require_once(JPATH_BASE."/includes/defines.php");
  require_once(JPATH_SITE."/includes/framework.php");

  require_once($root_dir."/configuration.php");
  $cfg = new JConfig;
  $server = $cfg->host;
  $db = $cfg->db;
  $user = $cfg->user;
  $pass = $cfg->password;
  $prefix = $cfg->dbprefix;
  $ncp = $prefix."ncatalogues_";
  $GLOBALS['ncp'] = $ncp;
  $aurl = parse_url($url);
  $domain = $aurl['scheme']."://".$aurl['host'];
  $GLOBALS['domain'] = $domain;
  $log=$cfg->log_path.'/'.$aurl['host'].'.log';
  $tmp=$cfg->tmp_path;
  $cookie_file = $tmp."/".$aurl['host'].".cke";
  $csv=$tmp."/".$aurl['host'].".csv";
  require_once "parsef.php";
  require_once "class.parsef.php";

	function upload_photo($file,$src,$field,$id,$quality=90){
		//echo "<p>upload_photo: {$file}, {$src}, {$field}, {$id}, {$quality}</p>";
		$root_dir = defined('JPATH_SITE') ? JPATH_SITE : $GLOBALS['root_dir'];
		$lpath = "{$root_dir}/images/com_ncatalogues/{$field}/{$id}/";
		if(!file_exists($lpath.$file) && (file_exists($lpath) || mkdir($lpath, 0755, true))){
			img_copy(str_replace(' ', '%20', $src), $lpath.$file);
			if(strlen(file_get_contents($lpath.$file))){
				if(isset($GLOBALS[$field.'_params'])) $params = $GLOBALS[$field.'_params'];
				else {
					$db = JFactory::getDBO();
					$db->setQuery("SELECT * FROM #__ncatalogues_field WHERE engtitle = '{$field}'");
					$fphoto = $db->loadObject();
                    $params = unserialize($fphoto->parametrs);
					$GLOBALS[$field.'_params'] = $params;
				}
				if(!file_exists($lpath.'thumb_'.$file)) img_copy($lpath.$file, $lpath.'thumb_'.$file, $params['width_min']);
				if(!file_exists($lpath.'max_'.$file)) img_copy($lpath.$file, $lpath.'max_'.$file, $params['width_max']);
				if(isset($params['width_ico']) && !file_exists($lpath.'ico_'.$file)) img_copy($lpath.$file, $lpath.'ico_'.$file, $params['width_ico']);
			}
		}
		return 1;
	}

	function upload_photo_classic($file,$src,$field,$id,$quality=90){
		//echo "<p>upload_photo: {$file}, {$src}, {$field}, {$id}, {$quality}</p>";
		$root_dir=$GLOBALS['root_dir'];
		$lpath = $root_dir.'/images/com_ncatalogues/'.$field.'/'.$id.'/';
		$lpath2 = $root_dir.'/images/com_ncatalogues/'.$field.'/';
		if(file_exists($lpath.$file)){
            img_copy($lpath.$file,$lpath2.$file);
            $ncp = $GLOBALS['ncp'];
            if(isset($GLOBALS[$field.'_params'])) $params = $GLOBALS[$field.'_params'];
            else {
                $sql = "SELECT * FROM `{$ncp}field` WHERE engtitle = '".$field."'";
                $q = mysql_query($sql);
                $photo = mysql_fetch_assoc($q);
                $params = unserialize($photo['parametrs']);
                $GLOBALS[$field.'_params'] = $params;
            }
            if(!file_exists($lpath.'thumb_'.$file)){
                img_copy($lpath.$file, $lpath.'thumb_'.$file, $params['width_min']);
                img_copy($lpath.'thumb_'.$file,$lpath2.'thumb_'.$file);
            }
            if(!file_exists($lpath.'max_'.$file)){
                img_copy($lpath.$file, $lpath.'max_'.$file, $params['width_max']);
                img_copy($lpath.'max_'.$file,$lpath2.'max_'.$file);
            }
            if(isset($params['ico_min']) && !file_exists($lpath.'ico_'.$file)){
                img_copy($lpath.$file, $lpath.'ico_'.$file, $params['ico_min']);
                img_copy($lpath.'ico_'.$file,$lpath2.'ico_'.$file);
            }
		}
		return 1;
	}

	function upload_photos($src,$field,$id,$quality=90){
		$type = 3;
		$domain = $GLOBALS['domain'];
		$root_dir=$GLOBALS['root_dir'];
		$ncp = $GLOBALS['ncp'];
		$time = time();
		$lpath = $root_dir.'/images/com_ncatalogues/'.$field.'/'.$id.'/';
		if(!is_array($src)) $src = array($src);
        $sql = "SELECT * FROM `{$ncp}field` WHERE engtitle = '".$field."'";
        $q = mysql_query($sql);
        $photo = mysql_fetch_assoc($q);
        $params = unserialize($photo['parametrs']);
		$sql = "";
		for($i=0; $i<count($src); $i++) {
			$path = pathinfo($src[$i]);
			$file = $path['basename'];
			if(strlen($file)-strrpos($file,'.') != 4) $file = '_'.$time.'_'.$i.'.'.$path['extension'];
			$tofile = str_replace($path['extension'], 'jpg', $file);

			if(!file_exists($lpath.'max_'.$file) && (file_exists($lpath) || mkdir($lpath, 0755, true))){
				$src_img = $src[$i];
				if(!file_exists($lpath.$file)) copy(str_replace(' ', '%20', $src_img), $lpath.$file);
				if(strlen(file_get_contents($lpath.$file))){
					$fsize=getimagesize($lpath.$file);
					$ftype = strtolower(substr($fsize['mime'], strpos($fsize['mime'], '/')+1));
					$get_img = "imagecreatefrom".$ftype;
					$in=$get_img($lpath.$file);
					$oldw = imagesx($in); 
					$oldh = imagesy($in);
					if(!$oldw) $oldw=1;
					if(!$oldh) $oldh=1;
					if($ftype!='jpeg') {
						$neww = $oldw;
						$newh = $oldh;
						$out=imagecreatetruecolor($neww,$newh);
						imagefill($out, 0, 0, 0xffffff);
						imagecopyresampled($out,$in,0,0,0,0,$neww,$newh,$oldw,$oldh);
						imagejpeg($out,$lpath.''.$tofile,$quality);
						imagedestroy($out);
					}
					$neww = $params['width_max'];
					$neww = $params['width_min'];
					$newh = round(1.0*$oldh/$oldw*$neww);
					$out=imagecreatetruecolor($neww,$newh);
					imagefill($out, 0, 0, 0xffffff);
					imagecopyresampled($out,$in,0,0,0,0,$neww,$newh,$oldw,$oldh);
					imagejpeg($out,$lpath.'thumb_'.$tofile,$quality);
					imagedestroy($out);
					$neww = $params['width_max'];
					$newh = round(1.0*$oldh/$oldw*$neww);
					$out=imagecreatetruecolor($neww,$newh);
					imagefill($out, 0, 0, 0xffffff);
					imagecopyresampled($out,$in,0,0,0,0,$neww,$newh,$oldw,$oldh);
					imagejpeg($out,$lpath.'max_'.$tofile,$quality);
					imagedestroy($out);
					if(array_key_exists('width_ico', $params)) {
						$neww = $params['width_ico'];
						$pref = 'ico_';
					} else {
						$neww = $params['width_min2'];
						$pref = 'thumb2_';
					}
					$newh = round(1.0*$oldh/$oldw*$neww);
					$out=imagecreatetruecolor($neww,$newh);
					imagefill($out, 0, 0, 0xffffff);
					imagecopyresampled($out,$in,0,0,0,0,$neww,$newh,$oldw,$oldh);
					imagejpeg($out,$lpath.$pref.$tofile,$quality);
					imagedestroy($out);
					imagedestroy($in);
				}
			}
			if($i) $sql .= ", ";
			$sql .= "(".$type.", ".$id.", '".$tofile."', '', ".$time.", 'image', ".$photo['id'].", ".($i+1).", '')";
		}
		if($sql) {
			mysql_query("DELETE FROM `{$ncp}media` WHERE `object_type`=".$type." AND `object`=".$id." AND `fieldid`=".$photo['id']."");
			$q = mysql_query($fsql="INSERT INTO `{$ncp}media` (`object_type`, `object`, `file`, `text`, `cdate`, `file_type`, `fieldid`, `ordering`, `session_id`) VALUES ".$sql);
			if($q) {
			} else {
				echo "<p>{$fsql}</p>";
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error();
			}
		}
		return $i;
	}

	function get_brand($name, $force=false){
        $db = JFactory::getDBO();
		$db->setQuery("SELECT id FROM #__ncatalogues_field_dictionary_value WHERE published=1 AND dictionary=1 AND title LIKE '{$name}'");
		$brand = $db->loadResult();
        return $brand;
	}

	function get_category($parent, $title, $force=false, $shortdesc="", $description=""){
		$ncp = $GLOBALS['ncp'];
		$sql = "SELECT * FROM {$ncp}category WHERE parent={$parent} AND title LIKE '{$title}'";
		$q = mysql_query($sql);
		$category = mysql_fetch_assoc($q);
		//prn($sql);
		//prn($category);
		if(!$category && $force){
			$sql = "SELECT coalesce(max(ordering),0) as ord FROM {$ncp}category WHERE parent={$parent}";
			$q = mysql_query($sql);
			$ord = mysql_fetch_assoc($q);
			$ord = $ord['ord'];
			$sql = "INSERT INTO `{$ncp}category` (`title`, `alias`, `parent`, `published`, `ordering`, `shortdesc`, `description`) VALUES ('{$title}', '".mysql_real_escape_string(parsef::translit($title))."', {$parent}, 1, ".($ord+1).", '{$shortdesc}', '{$description}')";
			if(mysql_query($sql)) {
			} else {
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error()."\n<p>{$sql}</p>";
			}
			$category['id'] = mysql_insert_id();
			$sql = "INSERT INTO `{$ncp}category_href` (`category`, `object`, `object_type`, `type`) VALUES (".$category['id'].", 0, 3, 'object_type')";
			if(mysql_query($sql)) {
			} else {
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error()."\n<p>{$sql}</p>";
			}
		}
		if (isset($category['id']))
			return $category['id'];
		else return false;
	}

    function getDictId($title, $did=false, $force=false){
        $db = JFactory::getDBO();

        if(is_array($title)) $where = $title;
        else $where = array('title'=>$title);
        if($did) $where['dictionary'] = $did;
        $whereline = array();
        foreach($where as $key => &$val) $whereline[] = $db->nameQuote($key)."=".$db->Quote($val);
        $whereline = implode(" AND ",$whereline);
        
        $db->setQuery("SELECT id FROM #__ncatalogues_field_dictionary_value WHERE ".$whereline);
        $id = $db->loadResult();
        if(!$id && $force){
            $item = new stdClass;
            foreach($where as $key => &$val) $item->$key = $val;
            if(!isset($item->alias)) $item->alias = parsef::translit($item->title);
            if(!isset($item->published)) $item->published = 1;
            $db->insertObject("#__ncatalogues_field_dictionary_value",$item,"id");
            $item->value = $item->ordering = $item->id;
            $db->updateObject("#__ncatalogues_field_dictionary_value",$item,"id");
            $id = $item->id;
        }
        return $id;
    }

	function get_dictionary($dictname, $value, $force=false) {
		$ncp = $GLOBALS['ncp'];
        $sql = "SELECT * FROM `{$ncp}field_dictionary` WHERE title='{$dictname}' AND published=1";
        $q = mysql_query($sql);
        if($dict = mysql_fetch_assoc($q)) $did = $dict['id'];
        else return false;
		$sql = "
            SELECT *
            FROM `{$ncp}field_dictionary_value`
            WHERE title='{$value}' AND dictionary={$did}
        ";
		$q = mysql_query($sql);
		$dictv = mysql_fetch_assoc($q);
		if(!$dictv && $force){
            $q = mysql_query("SELECT max(convert(value,signed))+1 as val FROM `{$ncp}field_dictionary_value` WHERE dictionary={$did}");
            $val = mysql_fetch_assoc($q);
            $val = $val['val'];
            $q = mysql_query("SELECT max(ordering)+1 as ord FROM `{$ncp}field_dictionary_value` WHERE dictionary={$did}");
            $ord = mysql_fetch_assoc($q);
            $ord = $ord['ord'];
			$sql = "
                INSERT INTO `{$ncp}field_dictionary_value` (`title`, `description`, `value`, `parent`, `dictionary`, `published`, `ordering`)
                VALUES ('{$value}', '', {$val}, 0, {$did}, 1, {$ord})
            ";
			if(mysql_query($sql)) {
                return $val;
			} else {
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error();
			}
		}
		if(isset($dictv['value'])) return $dictv['value'];
		else return false;
	}

	function setObject(&$item, $update=false){
        $db = JFactory::getDBO();
        $src = $item->nc_photo;
        $path = pathinfo($src);
        $file = basename($src, $path['extension']).'jpg';
        if(strlen($src)-strrpos($src,'.') != 4) $file = microtime(true).'.jpg';
        $item->nc_photo = $file;

        if(!isset($item->type)) $item->type = 3;
        
        $db->setQuery("SELECT * FROM #__ncatalogues_object{$item->type} WHERE nc_crc='{$item->nc_crc}'");
        $object = $db->loadObject();
        if(is_object($object)){
            if($update){
                $item->id = $object->id;
                $item->mdate = time();
                $db->updateObject("#__ncatalogues_object{$item->type}", $item, "id");
                if($err = $db->getErrorMsg()) echo $err."\n<br/>\n";
            }                
        }else{
            $default=array(
                'type' => 3,
                'user_id' => 275,
                'object_user_id' => 1,
                'object_user_type' => 1,
                'cdate' => time(),
                'mdate' => time(),
                'published' => 1
            );
            foreach($default as $key => &$val) if(!isset($item->$key)) $item->$key = $val;
            $db->insertObject("#__ncatalogues_object{$item->type}", $item, "id");
            if($err = $db->getErrorMsg()) echo $err."\n<br/>\n";
            upload_photo($file, $src, 'nc_photo', $item->id);
            //if(isset($object['nc_photos'])) upload_photos($srcs, 'nc_photos', $obj['id']);
            return $item->id;
        }
//print_r($file);exit;
    }
    
	function set_object($object, $category=0, $update=false, $multicat=false){
		//var_dump($object);
		//die();
		//$log = 'vivid.txt';
		//die('xx');
		$ncp = $GLOBALS['ncp'];
		$domain = $GLOBALS['domain'];
		$src = $object['nc_photo'];
		$path = pathinfo($src);
		$fbrand = 212;
		$file = basename($src, $path['extension']).'jpg';
		if(strlen($src)-strrpos($src,'.') != 4) $file = time().'.jpg';
		$object['nc_photo'] = $file;
		if(isset($object['nc_photos'])) {
			$srcs = $object['nc_photos'];
			$object['nc_photos'] = '';
			//$files = preg_replace('|^.*/|', '', $srcs);
		}
		$sql = "
			SELECT o3.*, coalesce(ch.category, 0) as category
			FROM `{$ncp}object3` o3
			INNER JOIN `{$ncp}object_object_href` ooh ON ooh.child=o3.id
				AND ooh.child_type=3
				AND ooh.parent=".$object['nc_brandname']."
				AND ooh.parent_type=10
				AND ooh.fieldid={$fbrand}
			LEFT OUTER JOIN {$ncp}category_href ch ON ch.object=o3.id AND ch.object_type=3 AND ch.type='object'
			WHERE nc_src='".$object['nc_src']."'
		";
		//prn($sql);
		//echo $sql;
		$q = mysql_query($sql);
		
		$obj = mysql_fetch_assoc($q);
		if($obj){
            if($update){
                if($obj['nc_brandname']!=$object['nc_brandname']) {
                    $q = mysql_query("DELETE FROM `{$ncp}object_object_href` WHERE child=".$obj['id']." AND fieldid={$fbrand} AND parent_type=10 AND child_type=3");
                    if($q) {
                    } else {
                        echo '<font color="#d00">ОШИБКА:</font> '.mysql_error();
                    }
                    $q = mysql_query("INSERT INTO `{$ncp}object_object_href` (`parent`, `child`, `fieldid`, `parent_type`, `child_type`) VALUES (".$object['nc_brandname'].", ".$obj['id'].", {$fbrand}, 10, 3)");
                    if($q) {
                    } else {
                        echo '<font color="#d00">ОШИБКА:</font> '.mysql_error();
                    }
                }
                if($obj['category']!=$category) {
                    if(!$multicat) mysql_query("DELETE FROM {$ncp}category_href WHERE object=".$obj['id']." AND object_type=3 AND type='object'");
                    if($category) mysql_query("INSERT INTO `{$ncp}category_href` (`category`, `object`, `object_type`, `type`) VALUES (".$category.", ".$obj['id'].", 3, 'object')");
                }
                $sql = "UPDATE `{$ncp}object3` SET `mdate`=".time()."";
                foreach($object as $key => $val) $sql .= ", `".$key."`='".$val."'";
                $sql .= " WHERE id=".$obj['id'];
               // prn($sql);
                if(mysql_query($sql)) {
                    echo '<font color="#070">УСПЕШНО!</font>';
                } else {
                    echo '<font color="#d00">ОШИБКА:</font> '.mysql_error()."\n<p>{$sql}</p>";
                }
                echo '<hr />';
            }
		} else {
			$sql = "SELECT coalesce(max(o3.ordering),0) as ord FROM {$ncp}object3 o3";
			if($category) $sql .= " INNER JOIN {$ncp}category_href ch ON ch.object=o3.id AND ch.category=".$category." AND object_type=3 AND type='object'";
			if(isset($log)) file_put_contents($log, $sql."\n\n");
			if(($q=mysql_query($sql))){
                $ord = mysql_fetch_assoc($q);
                $ord = $ord['ord'];
            }else{
                $ord=0;
            }
            $default=array('type'=>3,'user_id'=>63,'object_user_id'=>3,'object_user_type'=>1,'cdate'=>time(),'mdate'=>time(),'published'=>1,'ordering'=>$ord+1);
            $object=array_merge($default,$object);
			$skey = array();
			$sval = array();
			foreach($object as $key => $val){
				$skey[] = "`{$key}`";
				$sval[] = "'".mysql_real_escape_string($val)."'";
			}
            $skey=implode(", ",$skey);
            $sval=implode(", ",$sval);
			$sql = "INSERT INTO `{$ncp}object3` ({$skey}) VALUES ({$sval})";
			//echo $sql;
			if(isset($log)) file_put_contents($log, $sql."\n\n");
			//prn($sql);
			if(mysql_query($sql)) {
				echo '<font color="#070">УСПЕШНО!</font>';
			} else {
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error()."\n<p>{$sql}</p>";
			}
			echo '<hr />';
			$obj['id'] = mysql_insert_id();
			$sql = "INSERT INTO `{$ncp}object_object_href` (`parent`, `child`, `fieldid`, `parent_type`, `child_type`) VALUES (".$object['nc_brandname'].", ".$obj['id'].", {$fbrand}, 10, 3)";
			mysql_query($sql);
			if(mysql_query($sql)) {
			} else {
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error()."\n<p>{$sql}</p>";
			}
			if($category){
				$sql = "INSERT INTO `{$ncp}category_href` (`category`, `object`, `object_type`, `type`) VALUES (".$category.", ".$obj['id'].", 3, 'object')";
				if(isset($log)) file_put_contents($log, $sql."\n\n");
				mysql_query($sql);
				if(mysql_query($sql)) {
				} else {
					echo '<font color="#d00">ОШИБКА:</font> '.mysql_error()."\n<p>{$sql}</p>";
				}
			}
		}
		$file = $object['nc_photo'];
		upload_photo($file, $src, 'nc_photo', $obj['id']);
		//if(isset($object['nc_photos'])) upload_photos($srcs, 'nc_photos', $obj['id']);
		return $obj['id'];
	}

	function set_object2($object){
		$ncp = $GLOBALS['ncp'];
		$domain = $GLOBALS['domain'];
		$sql = "
			SELECT *
			FROM `{$ncp}object{$object['type']}`
			WHERE nc_src='{$object['nc_src']}'
		";
		//prn($sql);
		$q = mysql_query($sql);
		if($obj = mysql_fetch_assoc($q)){
			$sql = "UPDATE `{$ncp}object{$object['type']}` SET `mdate`=".time()."";
            unset($object['cdate']);
			foreach($object as $key => $val) $sql .= ", `".$key."`='".$val."'";
			$sql .= " WHERE id={$obj['id']}";
			prn($sql);
			if(mysql_query($sql)) {
				echo '<font color="#070">УСПЕШНО!</font>';
			} else {
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error();
			}
			echo '<hr />';
		} else {
			$sql = "SELECT coalesce(max(ordering),0) as ord FROM {$ncp}object{$object['type']}";
			$q = mysql_query($sql);
			$ord = mysql_fetch_assoc($q);
			$ord = $ord['ord'];
			$skey = "";
			$sval = "";
            $default=array('type'=>3,'user_id'=>63,'object_user_id'=>3,'object_user_type'=>1,'cdate'=>time(),'mdate'=>time(),'published'=>1,'ordering'=>$ord+1);
            $object=array_merge($default,$object);
			foreach($object as $key => $val){
				$skey .= "`".$key."`";
				$sval .= "'".mysql_real_escape_string($val)."'";
			}
            $skey=implode(", ",$skey);
            $sval=implode(", ",$sval);
			$sql = "INSERT INTO `{$ncp}object{$object['type']}` ({$skey}) VALUES ({$sval})";
			prn($sql);
			if(mysql_query($sql)) {
				echo '<font color="#070">УСПЕШНО!</font>';
			} else {
				echo '<font color="#d00">ОШИБКА:</font> '.mysql_error();
			}
			echo '<hr />';
			$obj['id'] = mysql_insert_id();
		}

		//$file = $object['nc_photo'];
		//upload_photo($file, $src, 'nc_photo', $obj['id']);

		return $obj['id'];
	}
?>
