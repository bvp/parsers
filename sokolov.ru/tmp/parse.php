<?php
header('Content-type: text/plain');

require_once __DIR__."/../class.parsef.php";

$fjs = __DIR__."/CatalogSmallRDEALER.js";
$fjson = __DIR__."/CatalogSmallRDEALER.json";
$fjscsv = __DIR__."/CatalogSmallRDEALER.csv";
$fprop = __DIR__."/CatalogSmallRDEALER.props.json";
$foutcsv = __DIR__."/catalog.csv";
$flog = __FILE__.".log";
$imgdir = realpath(__DIR__."/../../var/files/1/exim/backup/images");

/*
$json = (array)json_decode(file_get_contents($fjson));
foreach($json as $key => &$val){
    $val = $val->ru;
}
file_put_contents(__DIR__."/CatalogSmallRDEALER.catnames.json", json_encode($json));
*/

$prop = file_exists($fprop) ? (array)json_decode(file_get_contents($fprop)) : array();

$jscsv = explode('%', file_get_contents($fjscsv));
//$jscsv = explode('%', mb_convert_encoding(file_get_contents($fjscsv), 'utf-8', 'windows-1251'));

foreach($jscsv as &$line){
    $line = explode(';', $line);
    $sku = $line[0];
    $picsrc1 = "https://sokolov.ru/upload/w/jewelry-pictures/{$line[12]}.jpg";
    $picsrc2 = "https://sokolov.ru/private/catalogjs/w/jewelry/{$line[12]}.jpg";
    $picdst = "{$imgdir}/{$sku}.jpg";
    if(!file_exists($picdst) || !filesize($picdst)) copy($picsrc1, $picdst) || copy($picsrc2, $picdst);
    if(!isset($prop[$sku])){
        $cont = parsef::cget("https://sokolov.ru/jewelry-catalog/product/{$sku}/", $cookie_file="", $cookie="", $proxy=false, $headers=true, $pause=false);
        if(strpos($cont, "HTTP/1.1 404 Not Found")!==false){
            $prop[$sku] = false;
        }elseif(preg_match('|<body.*</body>|isU', $cont, $preg)){
            if(preg_match('|<h1.*</h1>|isU', $cont, $preg)) $prop[$sku]['Название'] = trim(strip_tags($preg[0]));
            if(preg_match('|<table[^>]*id="itemProperties".*</table>|isU', $cont, $preg) && preg_match_all('|<tr[^>]*>\s*<td[^>]*>(.*)</td>\s*<td[^>]*>(.*)</td>\s*</tr>|isU', $preg[0], $preg, PREG_SET_ORDER)){
                foreach($preg as &$pr) $prop[$sku][trim(strip_tags($pr[1]))] = preg_replace('|\s++|isU'," ",trim(strip_tags($pr[2])));
            }
            print_r($prop[$sku]);
        }
        //break;
        file_put_contents($fprop, json_encode($prop));
    }
}
//print_r(substr(print_r($jscsv,1), 0, 10240));
//file_put_contents($flog.".csv", substr(print_r($jscsv,1), 0, 10240));
//echo count($jscsv);

/*
Array
(
    [0] => Артикул
    [1] => -
    [2] => ??? Какая-то дата в формате "04.01.2015"
    [3] => catid
    [4] => Приблизительный вес, г
    [5] => Размеры кольца
    [6] => Вставки
    [7] => Высота подвески, мм
    [8] => Категория цен
    [9] => Вариации изделия (ИДы)
    [10] => Описание (знак зодиака, название иконы, толщина проволки цеипи и т.д.)
    [11] => Комплектные изделия (ИДы)
    [12] => Часть имени изображения (и, возможно, дерево категорий): 01/00/01 => https://sokolov.ru/private/catalogjs/w/jewelry/01/00/01.jpg (в залогиненном варианте); https://sokolov.ru/upload/w/jewelry-pictures/01/00/01.jpg (в незалогиненном)
    [13] => -
    [14] => Приблизительная стоимость
    [15] => Название коллекции / группы
    [16] => -
    [17] => Дата и время (изделия? модели? изготовления? последнего поступления?)
    [18] => Металл (цвет золота / серебро)
    [19] => Наличие в магазине
    [20] => Наличие на складе
    [21] => Код вариации изделия
    [22] => Цена за гр.
    [23] => Отключен (1 - товар недоступен, 0 - доступен)
    [24] => Какая-то дата в формате "2012-01-01"
    [25] => Какие-то теги
)
*/

?>
The End!
