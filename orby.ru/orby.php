<?php
	$url = "http://shop.orby.ru/index.php?page=shop.browse&option=com_virtuemart&category_id=0&limit=1000";
	$suffix = "&vmcchk=1&Itemid=1";
	require_once "parsencat.php";
	if(file_exists($log)) unlink($log);
	file_put_contents($log, "Started: ".strftime('%Y.%m.%d %H:%M')."\n");

	mysql_connect($server,$user,$pass) or die("Don't can create connection!");
	mysql_select_db($db) or die(mysql_error());
	mysql_set_charset("utf8");

	$brand_name = 'Orby';
	$brand = get_brand($brand_name, true);
	$catalog = get_category(0, 'ОРБИ', true);
	$cont = send_get($url.$suffix, $cookie_file);
	$cont = cyr_utf($cont);
	preg_match_all('|<a class="prod-details" href="(.*)".*</a>|isU', $cont, $tmp);
	$links = $tmp[1];
	foreach($links as $link) {
		//if($f++) exit;
		prn($link);
		$cont = send_get($link.$suffix, $cookie_file);
		$cont = cyr_utf($cont);
		preg_match('|<span class="pathway">(.*)</span>|isU', $cont, $tmp);
		preg_match_all('|>(.*)<|isU', $tmp[0], $tmp);
		$tmp = $tmp[1];
		unset($tmp[0]);
		$cats = array();
		foreach($tmp as $t) if(trim($t)) $cats[] = trim($t);
		array_pop($cats);
		//prn($cats);

		$type = 525;
		$subtype = 533;

		if(count($cats)) {
			$category = $catalog;
			foreach($cats as $cat) $category = get_category($category, $cat, true);
		}
		preg_match('|product_id=(.*)&|isU', $link, $tmp);
		$obj['nc_marking'] = $tmp[1];
		preg_match('|<h1>(.*)</h1>|isU', $cont, $tmp);
		$obj['nc_name'] = trim($tmp[1]);
		$obj['alias'] = translit($obj['nc_name'].' '.$obj['nc_marking']);
		$obj['nc_briefdescription'] = $obj['nc_name'];
		preg_match('|<table.*class="htmtableborders">(.*)</table>|isU', $cont, $tmp);
		preg_match_all('|<span style="font-style: italic;">(.*)</span>|isU', $tmp[1], $tmp);
		$info = $tmp[1];
		$obj['nc_detaileddescription'] = '';
		$finded = false;
		for($i=0; $i<count($info); $i++) {
			if($finded) $obj['nc_detaileddescription'] .= '<p>'.$info[$i].'</p>';
			if(stripos($info[$i], $obj['nc_name'])!==false) $finded = true;
		}
		preg_match('|<select.*name="Размер">(.*)</select>|isU', $cont, $tmp);
		preg_match_all('|<option.*>(.*)</option>|isU', $tmp[1], $tmp);
		$obj['nc_detaileddescription'] .= '<p>Размеры: '.implode(', ', $tmp[1]).'</p>';
		preg_match('|<select.*name="Цвет">(.*)</select>|isU', $cont, $tmp);
		preg_match_all('|<option.*>(.*)</option>|isU', $tmp[1], $tmp);
		$obj['nc_detaileddescription'] .= '<p>Цвета: '.implode(', ', $tmp[1]).'</p>';
		preg_match('|http://shop.orby.ru/components/com_virtuemart/shop_image/product/.*.jpg|isU', $cont, $tmp);
		$obj['nc_photo'] = $tmp[0];
		$obj['nc_brandname'] = $brand;
		$obj['nc_type'] = $type;
		$obj['nc_subtype'] = $subtype;
		set_object($obj, $category);
	}
	mysql_close();
?>
