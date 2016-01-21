<?php
    ini_set("display_errors","1");
    ini_set("display_startup_errors","1");
    ini_set('error_reporting', E_ALL);

    $url = "http://shop.pelican-style.ru/";
    $host="http://shop.pelican-style.ru";
    require_once "class.parsef.php";
    require_once "parsencat.php";
    require_once "jbdump.php";
    require_once "phpquery.php";

    $sex = array('Женщинам'=>80,'Мужчинам'=>81,'Детям'=>82);
    $subsex = array('Девочкам'=>108,'Мальчикам'=>109,'Новорожденным'=>110);
    $cat = $a = array();
    
	$brand_name = 'Pelican';
    $brand = $cat[0] = get_brand($brand_name, false);

    $cont = parsef::cget($url,$fcookie);
    $doc = phpQuery::newDocumentHTML($cont,$charset);
    $menu = $doc->find('div.nav-menu');
    $a[0] = $menu->children('ul')->children('li')->children('a');
    foreach($a[0] as $tag){
        $el=pq($tag);
        $link=$host.$el->attr('href');
        $pol = $cat[1] = isset($sex[trim($el->text())]) ? $sex[trim($el->text())] : 0;
        $a[1] = $el->parent('li')->children('ul')->children('li')->children('a');
        foreach($a[1] as $tag){
            $el=pq($tag);
            $link=$host.$el->attr('href');
            $sub = trim($el->text());
            $subpol = 0;
            if($pol==82 && isset($subsex[$sub])){
                $subpol = $subsex[$sub];
            }
            $a[2] = $el->parent('li')->children('ul')->children('li')->children('a');
            foreach($a[2] as $tag){
                $el=pq($tag);
                $link=$host.$el->attr('href');
                $vidv = $cat[3] = getDictId(trim($el->text()), 6, true);
                $page=0;
                $maxpage=1;
                while($page++ < $maxpage){
                    $cont=parsef::cget("{$link}&PAGEN_1={$page}",$fcookie);
                    $doc=phpQuery::newDocumentHTML($cont,$charset);
                    $items=$doc->find('#main-catalog>li>a');
                    foreach($items as $item){
                        $item = pq($item);
                        $src = $host.$item->attr('href');
                        $crc = substr(crc32($src),-8);
                        $obj = new stdClass;
                        $obj->nc_src=$src;
                        $obj->nc_crc=$crc;
                        $path=explode('/',preg_replace('|/$|','',$src));
                        $obj->title=trim($item->find('span.name')->text());
                        $obj->nc_sku=preg_replace('|\s.*$|isU','',$obj->title);
                        $obj->nc_name=trim(str_replace($obj->nc_sku,'',$obj->title));
                        $obj->alias=parsef::translit($obj->title);

                        $cont=parsef::cget($src,$fcookie);
                        $doc=phpQuery::newDocumentHTML($cont,$charset);
                        $item=$doc->find('#content-tovar');
                        $desc = $item->find('div.description');
                        $desc->find('*')->removeAttr('class');
                        $obj->nc_description = '<div>'.trim($desc->html()).'</div>';
                        $obj->nc_brend = $brand;
                        $obj->nc_pol = $pol;
                        $obj->nc_subpol = $subpol;
                        $obj->nc_vidv = $vidv;
                        $obj->nc_photo=$host.$item->find('div.img-big a')->attr('href');
                        setObject($obj, false);
                        echo "<div>{$obj->title} #{$obj->nc_sku}</div>";
//print_r($obj); exit;
                        //jbdump($obj,1,"Объект \"{$obj['title']}\"");
                    }
                }
                //die();
            }
        }
    }

	mysql_close();
?>
