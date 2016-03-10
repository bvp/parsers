<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('error_reporting', E_ALL);
//header('Content-type: text/plain; charset=UTF-8');
//setlocale(LC_ALL, 'ru_RU.65001', 'rus_RUS.65001', 'Russian_Russia. 65001', 'russian');

require_once "../parsencat.php";
require_once "../jbdump.php";
require_once "../fw.joomla.php";

$brand = 1295;
$brandName = "incity";
$host = "http://www.incity.ru";

$pol_ar = array(
	'Мужская коллекция' => 81,
	'Женская коллекция' => 80,
	'Детская коллекция' => 82,

	'мужское' => 81,
	'женское' => 80,
	'девочки' => 82,
	'мальчики' => 82,
);

$polmzhd_ar = array(
	'Мужская одежда' => 1231,
	'Женская одежда' => 1232,
	'Детская одежда' => 1233,
);

$vidv = array(
	'Аксессуары' => 1390,
	'Брюки' => 91,
	'Блузки' => 122,
	'Верхняя одежда' => 1391,
	'Джемперы' => 123,
	'Джинсы' => 1264,
	'Домашняя одежда' => 144,
	'Жакеты и кофты' => 124,
	'Колготки и чулки' => 1392,
	'Нижнее белье' => 1393,
	'Носки' => 1353,
	'Обувь' => 1397,
	'Платья' => 126,
	'Рубашки' => 147,
	'Спорт и пляж' => 1394,
	'Туалетная вода' => 1395,
	'Футболки' => 127,
	'Шапки и шарфы' => 1396,
	'Шорты' => 105,
	'Юбки' => 128,
);

$counter = 0;
$catalog = json_decode(file_get_contents($brandName . ".json"));
//foreach ($catalog as $cat => &$items) {
foreach ($catalog as &$item) {
	//	foreach ($items as &$item) {
	//$pol = 0;
	$polmzhd = 0;

	$category = explode(" - ", $item->category);
	$pol = $pol_ar[$category[0]];
	// $pol = 0;

	$obj = new stdClass;
	$obj->nc_name = $item->name; // запись имя товара
	$obj->nc_brend = $brand; // запись бренд
	$obj->nc_pol = $pol; // запись коллекция
	// $obj->nc_polmzhd = $polmzhd; // запись пол
	$obj->nc_polmzhd = 0; // запись пол
	$obj->nc_vidv = $vidv[$category[1]]; // запись вид вещи
	// $obj->nc_vidv = 0; // запись вид вещи
	$obj->title = $obj->nc_name; // запись тайтл
	// $obj->nc_description = $item->desc; // запись описания
	$obj->nc_sku = $item->sku; // запись артикул
	$obj->alias = parsef::translit(trim($item->name)); // запись алиас
	$obj->nc_photo = $item->pict; // запись картинки
	$obj->nc_src = $item->link; // запись источник
	$obj->nc_crc = crc32($item->link); // запись id источника
	
	if ($item->desc) {
	  $obj->nc_description = "<ul>";
	  foreach ($item->desc as $dVal) {
	    $obj->nc_description .= "<li>{$dVal}</li>";
	  }
	  $obj->nc_description .= "</ul>";
	}

	// $obj->nc_description = ""; // запись описания

	setObject($obj, true);
	echo "<div>{$obj->title} - записано</div>";

	//echo ".";
	$counter++;
}
echo "<div>\nОбработано записей - {$counter}\n</div>";
echo "<h1>The End!</h1>";
?>

