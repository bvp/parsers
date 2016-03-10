<?php
    ini_set("display_errors","1");
    ini_set("display_startup_errors","1");
    ini_set('error_reporting', E_ALL);

    $url = "http://vis-a-vis-profesional.ru/shop/visavis/";
    require_once "parsef.php";
    require_once "parsencat.php";
    require_once "jbdump.php";
    require_once "phpquery.php";
    
	mysql_connect($server,$user,$pass) or die("Don't can create connection!");
	mysql_select_db($db) or die(mysql_error());
	mysql_set_charset("cp1251");
	//mysql_set_charset("utf-8");

    function parse($url,$cat=0,$page=1){
        $fcookie = "./cookie.sav";
        $host = parse_url($url,0).'://'.parse_url($url,1);
        $cont = send_get($url.($page>1 ? "page-{$page}/" : ""),$fcookie);
        $doc = phpQuery::newDocumentHTML($cont);
        $as = $doc->find('#Content div[style="margin-bottom: 15px;"] a');
        if($as->length){
            foreach($as as $a){
                if(trim(pq($a)->text())=='АРХИВ') continue;
                $subcat = get_category($cat, win(trim(pq($a)->text())), true);
                parse($host.pq($a)->attr('href'),$subcat);
            }
        }else{
            $as = $doc->find('#Content div.product a');
            if($as->length){
                foreach($as as $a){
                    $obj=array();
                    $cont = send_get($src=$host.pq($a)->attr('href'),$fcookie);
                    $doc = phpQuery::newDocumentHTML($cont);
                    $prod = $doc->find('div.products-wrap');

                    $obj['nc_marking']=md5($src);
                    $obj['nc_name']=win(trim($h2=$prod->find('div.prod-info h2')->text()));
                    $obj['title']=$obj['nc_name'];
                    $obj['alias']=translit($h2);
                    $obj['nc_briefdescription'] = '';
                    $info=$prod->find('div.prod-info');
                    $info->find('h2')->remove();
                    $info->find('a')->remove();
                    $obj['nc_detaileddescription'] = win($info->html());
                    $obj['nc_brandname'] = $GLOBALS['brand'];                    
                    $obj['nc_photos']=array();
                    $obj['nc_photo']=$host.$prod->find('div.product div.product img')->attr('src');
                    set_object($obj, $cat);
                    jbdump($obj,0,'Объект "'.$h2.'"');
                }
                parse($url,$cat,$page+1);
            }
        }
    }

    $brand=get_brand('VISAVIS', true);
    $GLOBALS['brand']=$brand;
    $cat = get_category(0, 'VISAVIS', true);
    
    parse($url,$cat);

	mysql_close();
?>
