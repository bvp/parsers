<?php
  $url = "http://www.tomfarr.com/collection/view/muzhskaya-odezhda.html";
  require_once "parsencat.php";

	
	
	mysql_connect($server,$user,$pass) or die("Don't can create connection!");
	mysql_select_db($db) or die(mysql_error());
	
	

	
  if(file_exists($log)) unlink($log);
  file_put_contents($log, "Started: ".strftime('%Y.%m.%d %H:%M')."\n");

	
	mysql_set_charset("cp1251");
	//mysql_set_charset("utf-8");
	
	$brand_name = 'Tom Farr';
	$brand = get_brand($brand_name, true);

	$parent = get_category(0, $brand_name, true);
	{
		
		$cont = send_get($url);
		
			preg_match_all('|<li><a href="(\S\S\S).html">(.*)</a></li>|isU', $cont, $tmp);
			$subcats = $tmp[1];
			
	
		$u = pathinfo($url);
		$u = $u['dirname'].'/';
		foreach($subcats as $subcat) {
			
			$cont = send_get($u.$subcat.'.html');
			print $cont; die();
			preg_match_all('|<li><a href="(?='.$subcat.')(.*)">(.*)</a></li>|isU', $cont, $tmp);
			$objects = $tmp[1];
			foreach($objects as $object) {
				//if($f3++) exit;
				$cont = send_get($u.$object);
				preg_match('|<title>(.*)</title>|', $cont, $tmp);
				$cats = explode(' - ', $tmp[1]);
				$category = $parent;
				for($c=count($cats)-1; $c>0; $c--) $category = get_category($category, cyr_win($cats[$c]), true);
				if(strpos($tmp[1], 'Женск')!==false) {
					$type = 521;
					$subtype = 527;
				}
				if(strpos($tmp[1], 'Мужск')!==false) {
					$type = 523;
					$subtype = 530;
				}
				//prn($u.$object);
				//prn($cont);
				$item = array();
				$item['alias'] = translit($cats[0]);
				preg_match('|<p><b>Артикул</b>: (.*)</p>|isU', $cont, $tmp);
				$item['nc_marking'] = cyr_win($tmp[1]);
				preg_match('|<p><b>Описание</b>: (.*)</p>|isU', $cont, $tmp);
				$item['nc_name'] = cyr_win($tmp[1]);
				$item['nc_briefdescription'] = $item['nc_name'];
				preg_match('|<p><b>Цвет</b>: (.*)</p>|isU', $cont, $tmp);	
				$item['nc_detaileddescription'] = cyr_win($tmp[0]);
				preg_match('|<p><b>Материал</b>: (.*)</p>|isU', $cont, $tmp);	
				$item['nc_detaileddescription'] .= cyr_win($tmp[0]);
				preg_match('|<p><b>Размеры</b>: (.*)</p>|isU', $cont, $tmp);	
				$item['nc_detaileddescription'] .= cyr_win($tmp[0]);
				preg_match_all('|smallImage:\'(.*)\'|isU', $cont, $tmp);
				//prn($tmp);
				$img = pathinfo($tmp[1][0]);
				$imgs = preg_replace('|^|', $domain, $tmp[1]);
				$item['nc_photo'] = $imgs[0];
				if(count($imgs)>1) $item['nc_photos'] = $imgs;
				$item['nc_photos'] = $imgs;
				$item['nc_brandname'] = $brand;
				$item['nc_type'] = $type;
				$item['nc_subtype'] = $subtype;
				//prn($item);
				set_object($item, $category);
			}
		}
	}
	mysql_close();
?>
