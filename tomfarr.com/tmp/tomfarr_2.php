<?php
  $url = "http://www.tomfarr.ru/catalog/s12/index.html";
  require_once "parsencat.php";
  require_once "phpquery.php";
/*
  $urls = array (
	'Коллекция ОСЕНЬ-ЗИМА 2012' => $domain.'/catalog/w12/index.html',
	'Коллекция ВЕСНА-ЛЕТО 2011' => $domain.'/catalog/s11/index.html',
	'Коллекция ОСЕНЬ-ЗИМА 2011' => $domain.'/catalog/w11/index.html',
	'Коллекция ВЕСНА-ЛЕТО 2010' => $domain.'/catalog/s10/start.htm',
  );
*/
  if(file_exists($log)) unlink($log);
  file_put_contents($log, "Started: ".strftime('%Y.%m.%d %H:%M')."\n");

	mysql_connect($server,$user,$pass) or die("Don't can create connection!");
	mysql_select_db($db) or die(mysql_error());
	mysql_set_charset("cp1251");
	//mysql_set_charset("utf-8");

	$brand_name = 'Tom Farr';
	$brand = get_brand($brand_name, true);
//echo $brand; exit;
	//$parent = get_category(0, $brand_name, true);
		$cont = send_get($url);
		preg_match_all('|<li><a href="(\S\S\S).html">(.*)</a></li>|isU', $cont, $tmp);
		$subcats = $tmp[1];
		$u = pathinfo($url);
		$u = $u['dirname'].'/';
		foreach($subcats as $subcat) {
			//if($f2++) exit;
			$cont = send_get($u.$subcat.'.html');
			//prn($cont);
			preg_match_all('|<li><a href="(?='.$subcat.')(.*)">(.*)</a></li>|isU', $cont, $tmp);
			$objects = $tmp[1];
			//prn($objects);
			foreach($objects as $object) {
				//if($f3++) exit;
				$cont = send_get($u.$object);
                $doc = phpQuery::newDocumentHTML($cont);
				echo $u.$object;
				//$cont = cyr_utf($cont);
				//prn($cont);
				preg_match('|<title>(.*)</title>|', $cont, $tmp);
				$cats = explode(' - ', $tmp[1]);
				//prn($cats);
				$category = $parent;
				//for($c=count($cats)-1; $c>0; $c--) $category = get_category($category, cyr_win($cats[$c]), true);
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
				preg_match('|<p><b>Артикул</b>: (.*)</p>|isU', $cont, $tmp);
				$item['nc_marking'] = cyr_win($tmp[1]);
				$item['alias'] = translit($cats[0].' '.$tmp[1]);
				preg_match('|<p><b>Описание</b>: (.*)</p>|isU', $cont, $tmp);
				$item['nc_name'] = cyr_win($tmp[1]);
				$item['nc_briefdescription'] = $item['nc_name'];
				preg_match('|<p><b>Цвет</b>: (.*)</p>|isU', $cont, $tmp);	
				$item['nc_detaileddescription'] = cyr_win($tmp[0]);
				preg_match('|<p><b>Материал</b>: (.*)</p>|isU', $cont, $tmp);	
				$item['nc_detaileddescription'] .= cyr_win($tmp[0]);
				preg_match('|<p><b>Размеры</b>: (.*)</p>|isU', $cont, $tmp);	
				$item['nc_detaileddescription'] .= cyr_win($tmp[0]);
				//preg_match_all('|src="../static/photos/([^"]+)"|isU', $cont, $tmp);
				//prn($tmp);
				//$img = pathinfo($tmp[1][0]);
				//$imgs = preg_replace('|^|', 'http://www.tomfarr.ru/static/photos/', $tmp[1]);
				//prn($imgs);
                $img = 'http://www.tomfarr.ru/static/'.$doc->find('#img')->attr('src');
                $item['nc_photo'] = $img;
				if(count($imgs)>1) $item['nc_photos'] = $imgs;
				$item['nc_photos'] = $imgs;
				$item['nc_brandname'] = $brand;
				$item['nc_type'] = $type;
				$item['nc_subtype'] = $subtype;
				//prn($item);
				//set_object($item, $category);
				set_object($item);
			}
		}
	mysql_close();
?>
