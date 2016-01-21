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
  'Спортивная одежда для мужчин - Спортивные костюмы' => 2609,
  'Спортивная одежда для мужчин - Спортивные брюки' => 2610,
  'Спортивная одежда для мужчин - Толстовки' => 31935,
  'Спортивная одежда для мужчин - Футболки и майки' => 2611,
  'Спортивная одежда для мужчин - Шорты' => 2614,
  'Спортивная одежда для мужчин - Поло' => 2613,
  'Спортивная одежда для мужчин - Cпортивные куртки и ветровки' => 2612,
  'Спортивная одежда для мужчин - Куртки утепленные' => 2604,
  'Спортивная одежда для мужчин - Брюки утепленные' => 2607,
  
  'Женская спортивная одежда - Спортивные костюмы' => 2618,
  'Женская спортивная одежда - Брюки' => 2615,
  'Женская спортивная одежда - Утепленные куртки' => 2608,
  'Женская спортивная одежда - Толстовки' => 31936,
  'Женская спортивная одежда - Футболки и майки' => 2616,
  'Женская спортивная одежда - Утепленные брюки' => 2606,
  'Женская спортивная одежда - Бриджи' => 2617,
  'Женская спортивная одежда - Шорты' => 2619,
  'Женская спортивная одежда - Ленггинсы' => 31934,

  'Детская спортивная одежда - Спортивные костюмы' => 2622,
  'Детская спортивная одежда - Толстовки' => 2621,
  'Детская спортивная одежда - Брюки' => 31933,
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
    foreach ($item->desc as $dVal) {
      $obj->nc_text .= "<li>{$dVal}</li>";
    }
    $obj->nc_text .= "</ul>";
  }
  $obj->nc_category = isset($vidv[$cat]) ? $vidv[$cat] : false;
  // $obj->nc_category = isset($catArr[$item->category]) ? $catArr[$item->category] : false;
  $obj->nc_sku = $item->sku;
  $pict = false;
  if($item->pict) {
    //$pict = $item->pict;
    $pict = parsef::translit(basename($item->pict), false);
    $pict_ext = pathinfo($pict, PATHINFO_EXTENSION);
    $pict = mb_strimwidth($pict, 0, 64, "." .$pict_ext);
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
      echo ".";
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
      echo ".";
    }
  }
  $counter++;
}
echo "Обработано записей - {$counter}\n";
?>
The End!
