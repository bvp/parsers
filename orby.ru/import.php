<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('error_reporting', E_ALL);
header('Content-type: text/plain; charset=UTF-8');
setlocale(LC_ALL, 'ru_RU.65001', 'rus_RUS.65001', 'Russian_Russia. 65001', 'russian');

require_once __DIR__ . "/../class.parsef.php";
require_once __DIR__ . "/../fw.joomla.php";
require_once __DIR__ . "/config.php";

$vidv = array(
'Девочки - Все для девочек' => 2563,
'Девочки - Аксессуары' => 2600,
'Девочки - Белье, носки и пляжная мода' => 32060,
'Девочки - Блузки' => 2568,
'Девочки - Брюки и комбинезоны' => 2586,
'Девочки - Верхняя одежда' => 2589,
'Девочки - Головные уборы' => 32061,
'Девочки - Джинсы' => 2570,
'Девочки - Костюмы и форма' => 32062,
'Девочки - Платья' => 2587,
'Девочки - Рубашки' => 32063,
'Девочки - Обувь' => 32064,
'Девочки - Свитеры и кофты' => 2585,
'Девочки - Футболки' => 2571,
'Девочки - Юбки' => 32065,
'Мальчики - Все для мальчиков' => 2565,
'Мальчики - Аксессуары' => 32066,
'Мальчики - Белье, носки и пляжная мода' => 32067,
'Мальчики - Брюки и комбинезоны' => 2574,
'Мальчики - Верхняя одежда' => 2588,
'Мальчики - Головные уборы' => 2573,
'Мальчики - Джинсы' => 2576,
'Мальчики - Обувь' => 32068,
'Мальчики - Рубашки' => 2583,
'Мальчики - Свитеры и кофты' => 2582,
'Мальчики - Футболки' => 2580,
//'Школа - Все для школьниц' => 
//'Школа - Все для школьников' => 
'Школа - Аксессуары' => 2562,
'Школа - Блузки' => 2560,
'Школа - Брюки и комбинезоны' => 2556,
'Школа - Наборы комплектующих' => 32069,
'Школа - Костюмы и форма' => 2554,
'Школа - Платья' => 2555,
'Школа - Обувь' => 2559,
'Школа - Рубашки' => 2561,
'Школа - Свитеры и кофты' => 2557,
'Школа - Футболки' => 2569,
'Школа - Шорты' => 2567,
'Школа - Юбки' => 2558,
'Распродажа - Аксессуары' => 2596,
'Распродажа - Белье, носки и пляжная мода' => 32070,
'Распродажа - Блузки' => 2579,
'Распродажа - Брюки и комбинезоны' => 2593,
'Распродажа - Верхняя одежда' => 2590,
'Распродажа - Головные уборы' => 2594,
'Распродажа - Джинсы' => 2592,
'Распродажа - Костюмы и форма' => 2584,
'Распродажа - Платья' => 2599,
'Распродажа - Обувь' => 32071,
'Распродажа - Рубашки' => 2597,
'Распродажа - Свитеры и кофты' => 2598,
'Распродажа - Футболки' => 2595,
'Распродажа - Шорты' => 2591,
'Распродажа - Юбки' => 2581,
);
$type = 28;
$table = "#__ncatalogues_object{$type}";
$uid = 82;
$userId = 5;
$userType = 1;

$counter = 0;
// $imagesDir = __DIR__ . "/images/{$GLOBALS['brandName']}/";
$imagesDir = __DIR__ . "/images/";

$db = JFactory::getDBO();

$db->setQuery("SELECT id, nc_src FROM `{$table}` WHERE nc_src LIKE '{$GLOBALS['host']}%'");
$hasSrc = $db->loadColumn(1);
$hasId = $db->loadColumn(0);
$has = array_combine($hasSrc, $hasId);

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
  $obj = new stdClass;
  $obj->id = isset($has[$item->link]) ? $has[$item->link] : null;
  $obj->title = $item->name;
  $obj->type = $type;
  $obj->alias = parsef::translit($item->name);
  $obj->user_id = $uid;
  $obj->object_user_id = $userId;
  $obj->object_user_type = $userType;
  $obj->cdate = time();
  $obj->mdate = time();
  $obj->published = 1;
  $obj->nc_name = $item->name;
  if ($item->desc) {
    $obj->nc_text = "<ul class='nobullet'>";
    foreach ($item->desc as $dKey => &$dVal) {
      $obj->nc_text .= "<li><strong>{$dKey}:</strong> {$dVal}</li>";
    }
    $obj->nc_text .= "</ul>";
  }
  $obj->nc_category = isset($vidv[$item->category]) ? $vidv[$item->category] : false;
  // $obj->nc_category = isset($catArr[$item->category]) ? $catArr[$item->category] : false;
  $obj->nc_sku = $item->sku;
  $pict = false;
  if($item->pict) {
    //$pict = $item->pict;
    $parts = parse_url($item->pict);
    $newPict = $parts['scheme']."://".$parts['host'].$parts['path'];
    $pict = parsef::translit(basename($newPict), false);
    $pict_ext = pathinfo($newPict, PATHINFO_EXTENSION);
    $pict = mb_strimwidth($newPict, 0, 64, "." .$pict_ext);
  } else $pict = false;
  $obj->nc_image = $pict;
  $obj->nc_src = $item->link;
  $obj->nc_crc = crc32($item->link);
  $obj->nc_brid = $GLOBALS['brand'];
  if (empty($obj->id)) {
    if ($db->insertObject($table, $obj, "id")) {
      if ($item->pict) {
        $photoPath = JPATH_SITE . "/images/com_ncatalogues/nc_image/{$obj->id}/";
        if (!file_exists($photoPath)) {
          mkdir($photoPath, 0755, true);
        }
        copy($item->pict, $photoPath . $obj->nc_image);
      }

      $hrefBrid = new stdClass;
      $hrefBrid->object_type = $type;
      $hrefBrid->object = $obj->id;
      $hrefBrid->dictionary = 5;
      $hrefBrid->value = $obj->nc_brid;
      $hrefBrid->fieldid = 52;
      $db->insertObject("#__ncatalogues_field_dictionary_href", $hrefBrid);

      $hrefCat = new stdClass;
      $hrefCat->object_type = $type;
      $hrefCat->object = $obj->id;
      $hrefCat->dictionary = 5;
      $hrefCat->value = $obj->nc_category;
      $hrefCat->fieldid = 48;
      $db->insertObject("#__ncatalogues_field_dictionary_href", $hrefCat);
    }
  } else {
    if ($db->updateObject($table, $obj, "id")) {
      if ($item->pict) {
        $photoPath = JPATH_SITE . "/images/com_ncatalogues/nc_image/{$obj->id}/";
        if (!file_exists($photoPath)) {
          mkdir($photoPath, 0755, true);
        }
        // echo "pict - " . $imagesDir . basename($item->pict) . " => photoPath - " . $photoPath . $obj->nc_image . "\n";
        // copy($imagesDir . basename($item->pict), $photoPath . $obj->nc_image);
        copy($item->pict, $photoPath . $obj->nc_image);
      }
      $db->setQuery("DELETE FROM #__ncatalogues_field_dictionary_href WHERE object_type='{$type}' AND object='{$obj->id}'");
      $db->query();

      $hrefBrid = new stdClass;
      $hrefBrid->object_type = $type;
      $hrefBrid->object = $obj->id;
      $hrefBrid->dictionary = 5;
      $hrefBrid->value = $obj->nc_brid;
      $hrefBrid->fieldid = 52;
      $db->insertObject("#__ncatalogues_field_dictionary_href", $hrefBrid);

      $hrefCat = new stdClass;
      $hrefCat->object_type = $type;
      $hrefCat->object = $obj->id;
      $hrefCat->dictionary = 5;
      $hrefCat->value = $obj->nc_category;
      $hrefCat->fieldid = 48;
      $db->insertObject("#__ncatalogues_field_dictionary_href", $hrefCat);
    }
  }
  echo ".";
  $counter++;
}
echo "Обработано записей - {$counter}\n";
?>
The End!
