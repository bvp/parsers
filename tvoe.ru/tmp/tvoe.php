<?php
  ini_set("display_errors","1");
  ini_set("display_startup_errors","1");
  ini_set('error_reporting', E_ALL);
  $url = "http://tvoe.ru/collection/";
  require_once "parsencat.php";
  require_once "phpquery.php";

  //$db = JFactory::getDBO();
  //$db->setQuery("SELECT nc_crc FROM #__ncatalogues_object3");
  //$crcs = $db->loadResultArray();
	$brand_name = 'ТВОЕ';
	$brand = get_brand($brand_name, false);
	$content = send_get($url);
    $content = utf($content);
    
	preg_match_all('|<ul class="list">.*</ul>|isU',$content,$tmp); // ,PREG_SET_ORDER
	$ttypes = $tmp[0];
	foreach($ttypes as $ttype){
		preg_match_all('|<li><a href="(.*)">(.*)</a></li>|isU',$ttype,$ctypes,PREG_SET_ORDER);
		if(preg_match('|^woman/|',$ctypes[0][1],$w)){
			$type = 80;	// 0 - Все, 80 - Женский отдел, 81 - Мужской отдел, 82 - Детский отдел
		}
		if(preg_match('|^man/|',$ctypes[0][1],$w)){
			$type = 81;
		}
		foreach($ctypes as $ctype){
			$content = send_get($url.$ctype[1].'?view=all');
            $content = utf($content);
			preg_match_all('|<dl>\s*<dt><a href="(.*)" title="(.*)" original="(.*)".*></a></dt>\s*<dd>.*</dd>\s*<dd>(.*)<br/>\s*<span class="item-price">(.*)</span></dd>\s*</dl>|isU',$content,$titems,PREG_SET_ORDER);
			foreach($titems as $titem){
                $src = $url.$ctype[1].$titem[1];
                $crc = substr(crc32($src),-8);
                if(in_array($crc,$crcs)) continue;
				$content = send_get($src);
                $content = utf($content);
                $doc = phpQuery::newDocumentHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8" />'.$content);
                $desc = $doc->find('.info-block.additional');
                $desc->find('*')->removeAttr('class');
                preg_match('|<p id="share_info".*>(.*)</p>|isU',$content,$tdesc);
				preg_match('|<div class="right"><b>Артикул:</b>\s(.*)</div>|isU',$content,$tsku);
                $vidv = $doc->find('#left li:not(:has(a))')->text();
                $vidv = trim(str_replace('СКИДКИ!','',$vidv));
                $vidv = getDictId($vidv, 6, true);
                $item = new stdClass();
                $item->title = $titem[2];
				$item->alias = strtolower(translit($item->title));
				$item->nc_sku = $tsku[1];
				$item->nc_name = $titem[2];
				$item->nc_photo = $domain.str_replace('/st/i/pv','/st/i/full',$titem[3]);
				$item->nc_description = $titem[4];
				$item->nc_info = $tdesc[1];
				$item->nc_brend = $brand;
				$item->nc_pol = $type;
                $item->nc_vidv = $vidv;
                $item->nc_description = '<div>'.trim($desc->html()).'</div>';
                $item->nc_src = $src;
                $item->nc_crc = $crc;
				setObject($item, true);
                echo "<div>{$item->title} #{$item->nc_sku}</div>";
//print_r($item);exit;
			}
		}
	}
	file_put_contents($log, "Started: ".strftime('%Y.%m.%d %H:%M')."\n");
	mysql_close();
?>
