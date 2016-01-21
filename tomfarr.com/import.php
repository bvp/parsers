<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('error_reporting', E_ALL);
//header('Content-type: text/plain; charset=UTF-8');
setlocale(LC_ALL, 'ru_RU.65001', 'rus_RUS.65001', 'Russian_Russia. 65001', 'russian');

require_once "../class.parsef.php";
require_once "../parsencat.php";
require_once "../jbdump.php";
require_once "../phpquery.php";
require_once "../fw.joomla.php";
require_once "config.php";

$pol_ar = array(
	'Мужская коллекция' => 81,
	'Женская коллекция' => 80,
	'Детская коллекция' => 82,
);

$polmzhd_ar = array(
	'Мужская одежда' => 1231,
	'Женская одежда' => 1232,
	'Детская одежда' => 1233,
);

$vidv = array(
	'Мужская коллекция - Пуховики' => 1337,
	'Мужская коллекция - Куртки' => 1252,
	'Мужская коллекция - Пальто' => 1243,
	'Мужская коллекция - Дубленки' => 1243,
	'Мужская коллекция - Ветровки' => 1235,
	'Мужская коллекция - Джемперы' => 1255,
	'Мужская коллекция - Свитеры' => 1238,
	'Мужская коллекция - Кардиганы' => 1237,
	'Мужская коллекция - Водолазки' => 1332,
	'Мужская коллекция - Толстовки' => 1259,
	'Мужская коллекция - Пиджаки' => 1376,
	'Мужская коллекция - Жакеты' => 1343,
	'Мужская коллекция - Жилеты' => 1343,
	'Мужская коллекция - Сорочки' => 1372,
	'Мужская коллекция - Майки и футболки' => 1341,
	'Мужская коллекция - Брюки' => 1336,
	'Мужская коллекция - Головные уборы' => 1239,
	'Мужская коллекция - Шарфы и платки' => 1246,
	'Мужская коллекция - Варежки и перчатки' => 1370,
	'Мужская коллекция - Сумки' => 1356,
	'Мужская коллекция - Ремни' => 1354,

	'Женская коллекция - Пуховики' => 1337,
	'Женская коллекция - Куртки' => 1252,
	'Женская коллекция - Пальто' => 1243,
	'Женская коллекция - Дубленки' => 1243,
	'Женская коллекция - Ветровки' => 1235,
	'Женская коллекция - Плащи' => 1244,
	'Женская коллекция - Джемперы' => 1255,
	'Женская коллекция - Свитеры' => 1238,
	'Женская коллекция - Кардиганы' => 1237,
	'Женская коллекция - Водолазки' => 1332,
	'Женская коллекция - Толстовки' => 1259,
	'Женская коллекция - Жакеты' => 1343,
	'Женская коллекция - Блузы и Туники' => 1333,
	'Женская коллекция - Майки и футболки' => 1341,
	'Женская коллекция - Топы' => 1362,
	'Женская коллекция - Брюки' => 1336,
	'Женская коллекция - Джеггинсы' => 1388,
	'Женская коллекция - Джинсовая одежда' => 1389,
	'Женская коллекция - Юбки' => 1342,
	'Женская коллекция - Платья' => 1338,
	'Женская коллекция - Головные уборы' => 1239,
	'Женская коллекция - Шарфы и платки' => 1246,
	'Женская коллекция - Варежки и перчатки' => 1370,
	'Женская коллекция - Сумки' => 1356,
	'Женская коллекция - Ремни' => 1354,
);
$type = 28;
$table = "#__ncatalogues_object{$type}";
$uid = 82;
$userId = 5;
$userType = 1;

$counter = 0;
// $imagesDir = __DIR__ . "/images/{$GLOBALS['brandName']}/";
$imagesDir = __DIR__ . "/images/";

//$db = JFactory::getDBO();

//$db->setQuery("SELECT id, nc_src FROM `{$table}` WHERE nc_src LIKE '{$GLOBALS['host']}%'");
//$hasSrc = $db->loadColumn(1);
//$hasId = $db->loadColumn(0);
//$has = array_combine($hasSrc, $hasId);

/* $db->setQuery("SELECT id, title FROM #__ncatalogues_field_dictionary_value WHERE parent={$GLOBALS['brand']}");
$catId = $db->loadColumn(0);
$catTitle = $db->loadColumn(1);
$catArr = array_combine($catTitle, $catId);
// var_dump($catArr);
// echo "\$catArr['Шорты'] - " . $catArr['Шорты'] . "\n";
 */
$catalog = json_decode(file_get_contents(__DIR__ . "/{$GLOBALS['brandName']}.json"));
//foreach ($catalog as $cat => &$items) {
foreach ($catalog as &$item) {
	//	foreach ($items as &$item) {
	$pol = 0;
	$polmzhd = 0;

	$category = explode(" - ", $item->category)[0];
	$pol = $pol_ar[$category];

	$obj = new stdClass;
	$obj->nc_name = $item->name; // запись имя товара
	$obj->nc_brend = $GLOBALS['brand']; // запись бренд
	$obj->nc_pol = $pol; // запись коллекция
	$obj->nc_polmzhd = $polmzhd; // запись пол
	$obj->nc_vidv = $vidv[$item->category]; // запись вид вещи
	$obj->title = $obj->nc_name; // запись тайтл
	$obj->nc_description = $item->desc; // запись описания
	$obj->nc_sku = $item->sku; // запись артикул
	$obj->alias = parsef::translit(trim($item->name)); // запись алиас
	$obj->nc_photo = $item->pict; // запись картинки
	$obj->nc_src = $item->link; // запись источник
	$obj->nc_crc = crc32($item->link); // запись id источника

	setObject($obj, true);
	echo "<div>{$obj->title} - записано</div>";

	// echo ".";
	$counter++;
}
echo "\nОбработано записей - {$counter}\n";
?>
The End!
