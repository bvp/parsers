<?php
header('Content-type: text/plain');

require_once __DIR__."/../class.parsef.php";

$fcat = __DIR__."/CatalogSmallRDEALER.catnames.json";
$fprop = __DIR__."/CatalogSmallRDEALER.props.json";
$fjscsv = __DIR__."/CatalogSmallRDEALER.csv";
$fcsv = __DIR__."/options_".date('Y-m-d_H-i-s').".csv";
$ficsv = __DIR__."/../../var/files/1/options_".date('Y-m-d_H-i-s').".csv";
$imgdir = realpath(__DIR__."/../../var/files/1/exim/backup/images");
$flog = __FILE__.".log";

$magazin = "Automag";

$jscsv = explode('%', file_get_contents($fjscsv));

$handle = fopen($ficsv, "w");
$header = array(
    0 => "Product code",
    1 => "Language",
    2 => "Options",
);
fputcsv($handle, $header, ';', '"');
foreach($jscsv as &$data){
    $data = explode(';', $data);
    if($data[5]=="") continue; // У товара нет размеров
    $sku = $data[0];
    $d19 = explode('/', str_replace(',','.',$data[19]));
    foreach($d19 as &$q) $q = floatval($q);
    $d20 = explode('/', str_replace(',','.',$data[20]));
    foreach($d20 as &$q) $q = floatval($q);
    $sizes = array_unique(array_merge($d19, $d20));
    sort($sizes);
    if(!$sizes[0]) unset($sizes[0]);
    
    $line = array(
        0 => $sku,
        1 => "ru",
        2 => "({$magazin}) Размер: S[".implode(",", $sizes)."]",
    );

    fputcsv($handle, $line, ';', '"');
}
fclose($handle);
?>
The End!
