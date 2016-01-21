<?php
    ini_set("display_errors","1");
    ini_set("display_startup_errors","1");
    ini_set('error_reporting', E_ALL);

    $url = "http://vessa.ru/catalog/";
    $host="http://vessa.ru";
    require_once "class.parsef.php";
    require_once "parsencat.php";
    require_once "jbdump.php";
    require_once "phpquery.php";
    
	mysql_connect($server,$user,$pass) or die("Don't can create connection!");
	mysql_select_db($db) or die(mysql_error());
	mysql_set_charset("utf8");

    $brand=20;
    $fcookie = "./cookie.sav";

    $cat[0] = get_category(0,'Весса',1);
    $cont = parsef::send_get($url,$fcookie);
    $doc = phpQuery::newDocumentHTML($cont,'utf-8');
    $a[0] = $doc->find('div.brands div.item div.logo a');
    foreach($a[0] as $tag){
        $el=pq($tag);
        $link=$host.$el->attr('href');
        $cat[1]=get_category($cat[0],trim($el->children('img')->attr('alt')),true);
        $cont=parsef::send_get($link,$fcookie);
        $doc=phpQuery::newDocumentHTML($cont,'utf-8');
        $a[1] = $doc->find('td.categories a.top-level');
        foreach($a[1] as $tag){
            $el=pq($tag);
            $link=$host.$el->attr('href').'?skip=1000000';
            $cat[2]=get_category($cat[1],trim($el->text()),true);
            $cont=parsef::send_get($link,$fcookie);
            $doc=phpQuery::newDocumentHTML($cont,'utf-8');
            $a[2] = $doc->find('div.pagination div.pages a');
            if($a[2]->length == 0) $a[2]=$el;
            foreach($a[2] as $tag){
                $el=pq($tag);
                $link=$host.$el->attr('href');
                $cont=parsef::send_get($link,$fcookie);
                $doc=phpQuery::newDocumentHTML($cont,'utf-8');
                $items=$doc->find('#products div.item');
                foreach($items as $item){
                    $item=pq($item);
                    $obj=array('type'=>3,'user_id'=>63,'object_user_id'=>3,'object_user_type'=>1,'published'=>1);
                    $obj['nc_src']=$host.$item->find('h2 a')->attr('href');
                    $path=explode('/',preg_replace('|/$|','',$obj['nc_src']));
                    $obj['nc_marking']='vessa-'.array_pop($path);
                    $obj['nc_name']=trim($item->find('h2 a')->text());
                    $obj['title']=$obj['nc_name'];
                    $obj['alias']=parsef::translit($obj['title']);
                    $obj['nc_briefdescription']=trim($item->find('div.note')->text());
                    $obj['nc_detaileddescription']='';
                    $desc=$item->find('div.properties div.row');
                    foreach($desc as $row) $obj['nc_detaileddescription'].='<p>'.trim(pq($row)->text()).'</p>';
                    $obj['nc_detaileddescription']=preg_replace('|\s\s+|',' ',$obj['nc_detaileddescription']);
                    $obj['nc_brandname']=$brand;                    
                    $obj['nc_type']=521;                    
                    $obj['nc_subtype']=527;                    
                    $obj['nc_photos']=array();
                    $obj['nc_photo']=$host.$item->find('div.photo a')->attr('href');
                    set_object($obj, $cat[2], true, true);
                    //jbdump($obj,0,"Объект \"{$obj['title']}\"");
                }
                //die();
            }
        }
    }

	mysql_close();
?>
