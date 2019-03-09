<?php
	/*$res = preg_replace("/^.*?<body(\s[^>]*>|>)/si", '', $cont);
		$res = preg_replace("/(.*?)<\/body(\s|>).*$/si", '$1', $res);*/

		//$res = mb_convert_encoding($res, "windows-1251", "auto");
		//$cont = mb_convert_encoding($cont, "utf-8", "auto");
	exit;
    ini_set("display_errors","1");
    ini_set("display_startup_errors","1");
    ini_set('error_reporting', E_ALL);
	
	

    $host="http://www.wildberries.ru";

    require_once "class.parsef.php";
    require_once "parsencat.php";
    require_once "jbdump.php";
    require_once "phpquery.php";
	
	
	
	for ($page=1; $page<15; $page++) {

		if($page==1) {
			$url = 'http://www.wildberries.ru/1.1998.Vis-a-vis';
		} else {
			$url = 'http://www.wildberries.ru/1.1998.'.$page.'.Vis-a-vis';
		}
		
		$fcookie = "./cookie.sav";
		
		$cont = parsef::cget($url,$fcookie);
		$cont = preg_replace("/^.*?<body(\s[^>]*>|>)/si", '', $cont);
		$cont = preg_replace("/(.*?)<\/body(\s|>).*$/si", '$1', $cont);
		
		$cont = mb_convert_encoding($cont, "utf-8", "windows-1251");
		$doc = phpQuery::newDocumentHTML($cont);

		$item = $doc->find('.catalog_main_table .dtList a.ref_goods_n_p');
		
		for($i=0;$i<$item->length;$i++){
			$hva = file_get_contents('hvatit');
			if($hva==='1') {
				exit;
			}
			
			sleep(1);

			$itemurls = $item->eq($i)->attr('href');
			//print $itemurls.'<br />';
		
			$contob = parsef::cget($itemurls,$fcookie);
			$contob = preg_replace("/^.*?<body(\s[^>]*>|>)/si", '', $contob);
			$contob = preg_replace("/(.*?)<\/body(\s|>).*$/si", '$1', $contob);
			
			$contob = mb_convert_encoding($contob, "utf-8", "windows-1251");
			$docob = phpQuery::newDocumentHTML($contob);


			$name = $docob->find('#tab-description h1')->html(); // Название товара
			$sku = $docob->find('#tab-description span.article')->html();
			$descript = $docob->find('#tab-description p#description')->html();  //Описаие товара
			$sostav = $docob->find('#ppAdditional table')->html(); //таблица с составом
			$alldescript = '<p>'.$descript.'</p>'.'<table>'.$sostav.'</table>'; //полное описание
			$photo = $docob->find('#photo img#preview-large ')->attr('src'); 
			$photo = 'http://'.substr($photo, 2); //фотография
			$typer = explode(',',$name);
			$typer = $typer[0];
			$vidv = getDictId($typer, 6, true); // Вид вещи
			
			$pol = 80; //Коллекция м/ж/д
			$polmzhd = 1232; // Пол
			$brend = 7; // Бренд
			$crc = substr(crc32($itemurls),-8);
			
			$obj = new stdClass;
			
			$obj->nc_name = $name;                      // запись имя товара
            $obj->nc_brend = $brend;                    // запись бренд
            $obj->nc_pol = $pol;                        // запись коллекция
			$obj->nc_polmzhd = $polmzhd;                // запись пол
			$obj->nc_vidv = $vidv;                      // запись вид вещи
			$obj->title=$obj->nc_name;                  // запись тайтл
			$obj->nc_description = $alldescript;        // запись описания
			$obj->nc_sku=$sku;                          // запись артикул
            $obj->alias=parsef::translit(trim($obj->title));       // запись алиас
			$obj->nc_photo = $photo;                    // запись картинки
			$obj->nc_src = $itemurls;                   // запись источник
			$obj->nc_crc = $crc;                        // запись id источника
			
			//echo $db->getQuery();
			//print_r($obj);
			
			setObject($obj, true);

            echo "<div>{$obj->title} - записано</div>";
		}

	}
mysql_close();
//exit();
?>
