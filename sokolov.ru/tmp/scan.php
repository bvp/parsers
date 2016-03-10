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
$all = array();
foreach($prop as &$pr) foreach($pr as $p => &$r) $all[$p] = null;
print_r($all);

/*
$jscsv = explode('%', file_get_contents($fjscsv));
//$jscsv = explode('%', mb_convert_encoding(file_get_contents($fjscsv), 'utf-8', 'windows-1251'));
$sizes = array();
foreach($jscsv as &$line){
    $line = explode(';', $line);
    $szs = explode(',', $line[5]);
    foreach($szs as &$sz) $sizes[$sz] = null;
}
ksort($sizes);
print_r($sizes);
*/
//print_r(substr(print_r($jscsv,1), 0, 10240));
//file_put_contents($flog.".csv", substr(print_r($jscsv,1), 0, 10240));
//echo count($jscsv);

/*
Array
(
    [Название] => 
    [Для кого] => 
    [Металл] => 
    [Приблизительный вес] => 
    [Вставка] => 
    [Форма вставки] => 
    [Технология] => 
    [Знак зодиака] => 
    [Вид замка] => 
    [Вид плетения] => 
    [Лик святого на иконе] => 
)
*/

?>
The End!
jQuery('label[for^=feature_]').each(function(){
  jQuery('#'+jQuery(this).attr('for')).val('!'+jQuery(this).text()+'!');
});
