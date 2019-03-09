<?php
header('Content-type: text/plain');

require_once __DIR__."/../class.parsef.php";

$fcat = __DIR__."/CatalogSmallRDEALER.catnames.json";
$fprop = __DIR__."/CatalogSmallRDEALER.props.json";
$fjscsv = __DIR__."/CatalogSmallRDEALER.csv";
$fcsv = __DIR__."/import_short_".date('Y-m-d_H-i-s').".csv";
$ficsv = __DIR__."/../../var/files/1/import_".date('Y-m-d_H-i-s').".csv";
$imgdir = realpath(__DIR__."/../../var/files/1/exim/backup/images");
$flog = __FILE__.".log";

$cats = json_decode(file_get_contents($fcat), true);

$props = json_decode(file_get_contents($fprop), true);

$jscsv = explode('%', file_get_contents($fjscsv));

$i = 0;
$handle = fopen($fcsv, "w");
$header = array(
    0 => "Product code",
    1 => "Language",
    //2 => "Status", // A / D / H
    3 => "Category",
    //4 => "Price",
    //5 => "Weight",
    //6 => "Quantity",
    //7 => "Date added",
    //8 => "Detailed image",
    //9 => "Product name",
    10 => "SEO name",
    11 => "Short description",
    //12 => "Features",
);
fputcsv($handle, $header, ';', '"');
foreach($jscsv as &$data){
    $data = explode(';', $data);
    //print_r($data);
    $sku = $data[0];
    $prop = isset($props[$sku]) ? $props[$sku] : array();
    if(!$data[4] && isset($prop['Приблизительный вес'])) $data[4] = floatval(str_replace(',','.',$prop['Приблизительный вес']));
    $qty = 0;
    if(strpos($data[19],':')!==false || strpos($data[20],':')!==false){ // кол-во по размерам
        $d19 = explode('/', $data[19]);
        foreach($d19 as &$q){
            list($size, $qt) = explode(':', $q.":");
            $qty += (int)$qt;
        }
        $d20 = explode('/', $data[20]);
        foreach($d20 as &$q){
            list($size, $qt) = explode(':', $q.":");
            $qty += (int)$qt;
        }
    }else{
        $qty += (int)$data[19];
        $qty += (int)$data[20];
    }

    $frepl = array(
        '|&.*;|isU' => "",
        '|[\(\)]|isU' => "/",
    );
    $features = array();
    foreach($prop as $pr => &$op){
        $op = preg_replace(array_keys($frepl),array_values($frepl),$op);
        //$op = _replace('|&.*;|isU',"",$op);
        $features[] = "(Поля с сайта) {$pr}: T[{$op}]";
    }
    $features[] = "(Поля из файла) Артикул: T[{$data[0]}]";
    $features[] = "(Поля из файла) Дата: T[{$data[2]}]";
    $features[] = "(Поля из файла) Категория: O[{$data[3]}]";
    $features[] = "(Поля из файла) Приблизительный вес: T[{$data[4]}]";
    $features[] = "(Поля из файла) Все размеры: T[{$data[5]}]";
    $features[] = "(Поля из файла) Вставки: T[{$data[6]}]";
    $features[] = "(Поля из файла) Высота подвески: O[{$data[7]}]";
    $features[] = "(Поля из файла) Категория цен: O[{$data[8]}]";
    $features[] = "(Поля из файла) Вариации: T[{$data[9]}]";
    $features[] = "(Поля из файла) Описание: T[{$data[10]}]";
    $features[] = "(Поля из файла) Комплект: T[{$data[11]}]";
    $features[] = "(Поля из файла) Приблизительная стоимость: O[{$data[14]}]";
    $features[] = "(Поля из файла) Название коллекции / группы: T[{$data[15]}]";
    $features[] = "(Поля из файла) Дата и время: T[{$data[17]}]";
    $features[] = "(Поля из файла) Металл: T[{$data[18]}]";
    $features[] = "(Поля из файла) Наличие в магазине: T[{$data[19]}]";
    $features[] = "(Поля из файла) Наличие на складе: T[{$data[20]}]";
    $features[] = "(Поля из файла) Код вариации: T[{$data[21]}]";
    $features[] = "(Поля из файла) Цена за гр.: O[{$data[22]}]";
    $features[] = "(Поля из файла) Отключен: C[".($data[23]==1 ? "Y" : "N")."]";
    $features[] = "(Поля из файла) Дата другая: T[{$data[24]}]";
    $features[] = "(Поля из файла) Теги: T[{$data[25]}]";
    $features = implode("; ", $features);

    $cat = isset($cats[$data[3]]) ? $cats[$data[3]] : "|";
    $cat = str_replace("||","|",$cat);
    $cat = explode('|', $cat);
    $type = array_pop($cat);
    $cat = implode('///',$cat);
    
    $line = array(
        0 => $sku,
        1 => "ru",
        //2 => isset($prop['Название']) ? 'A' : 'D',
        3 => $cat,
        //4 => $data[14] ? $data[14] : $data[22]*$data[4],
        //5 => $data[4],
        //6 => $qty,
        //7 => date('d M Y H:i:s', strtotime($data[24])),
        //8 => file_exists("{$imgdir}/{$sku}.jpg") && filesize("{$imgdir}/{$sku}.jpg")>0 ? "exim/backup/images/{$sku}.jpg#{[ru]:;}" : "",
        //9 => isset($prop['Название']) ? $prop['Название'] : "",
        10 => preg_replace('|[^A-Za-z\-]++|isU', "", parsef::translit($type))."-{$sku}",
        11 => $type,
        //12 => $features,
    );
    // if(!$line[3] && isset($prop[''])) $line[3] = 
    fputcsv($handle, $line, ';', '"');
    if($i++ > 100000) break;
}
fclose($handle);
copy($fcsv, $ficsv);
?>
The End!
