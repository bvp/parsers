<?php
    ini_set("display_errors","1");
    ini_set("display_startup_errors","1");
    ini_set('error_reporting', E_ALL);
	
	

    $host="http://www.tomfarr.com/";

    require_once "class.parsef.php";
    require_once "parsencat.php";
    require_once "jbdump.php";
    require_once "phpquery.php";

	for ($page=0; $page<37; $page++) { //всего 36 страницы в женском Tom Farr

		if(($page!=0)&&($page!=1)) {
			$pageur = '-'.$page;
			$pageur = 'http://www.tomfarr.com/collection/view/zhenskaya-odezhda'.$pageur.'.html';
		} 
		if($page==1){
			$pageur = 'http://www.tomfarr.com/collection/view/zhenskaya-odezhda.html';
		}
		$url = $pageur;
			
		$fcookie = "./cookie.sav";
		
		$cont = parsef::cget($url,$fcookie);
		$doc = phpQuery::newDocumentHTML($cont);
		$item = $doc->find('.product-meta-wrapper a');
	   // $subcatsdescs = $doc->find('div.product-meta-wrapper');
		
		for($i=0;$i<$item->length;$i++){
			//if($i>5) exit;
			$hva = file_get_contents('hvatit');
			if($hva==='1') {
				exit;
			}
			
			sleep(2);
			
			$itemurls = $item->eq($i)->attr('href');
			
		
			
			print $itemurls.'<br />';
			

			$contob = parsef::cget($itemurls,$fcookie);
			
			$docob = phpQuery::newDocumentHTML($contob);
			$itemob = $docob->find('.product-info');
			$typeo = $docob->find('.slogan .h2 a');
			
			$name = $docob->find('.product-info .span8 h3')->html(); // Название товара
			$image = $docob->find('.product-images img')->attr('src'); // большое изображение
			
			
			$sku = $docob->find('.product-info .span8 h6')->html();
			$skutxt = explode(':',$sku);
			$skutxt = $skutxt[1]; // Артикул товара
			for($ik=3;$ik<4;$ik++){
				$types = $typeo->eq($ik)->html();
			}
			$vidv = $types; //Вид вещи
			$vidv = getDictId($vidv, 6, true);
			//print $vidv;
			//exit;
			$pol = 80; //Коллекция м/ж/д
			$polmzhd = 1232; // Пол
			$brend = 10; // Бренд
			
			
			$crc = substr(crc32($itemurls),-8);
            //if(in_array($crc,$crcs)) continue;
			//print $crc;
			//continue;
			
			$obj = new stdClass;
			
			
			$obj->nc_name = $name;      // запись имя товара
            $obj->nc_brend = $brend;    // запись бренд
            $obj->nc_pol = $pol;        // запись коллекция
			$obj->nc_polmzhd = $polmzhd; // запись пол
			$obj->nc_vidv = $vidv;      // запись вид вещи
			$obj->title=$obj->nc_name;  // запись тайтл
			$obj->nc_sku=$skutxt;  // запись артикул
			
			
			
			
			
            $obj->alias=parsef::translit($obj->title); //запись алиас
			
			
			$obj->nc_photo = $image;
			
			$obj->nc_src = $itemurls;        // источник
			$obj->nc_crc = $crc;
			
			//echo $db->getQuery();
			
			//print_r($obj);
			
			setObject($obj, true);
			
			
			
            echo "<div>{$obj->title} #{$obj->nc_sku}</div>";
			//exit;
		}
		
		
	}
mysql_close();
//exit();
?>