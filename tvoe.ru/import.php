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
'Женщины - Одежда - Верхняя одежда' => 31939,
'Женщины - Одежда - Вязаный трикотаж' => 2004,
'Женщины - Одежда - Джемпер' => 2010,
'Женщины - Одежда - Кардиган' => 2006,
'Женщины - Одежда - Водолазка' => 31968,
'Женщины - Одежда - Толстовка/Свитшот' => 31969,
'Женщины - Одежда - Блузка/Рубашка' => 2009,
'Женщины - Одежда - Поло' => 2011,
'Женщины - Одежда - Футболка' => 2005,
'Женщины - Одежда - Футболка (без принта)' => 2005,
'Женщины - Одежда - Майка' => 2012,
'Женщины - Одежда - Майка (без принта)' => 2012,
'Женщины - Одежда - Топ' => 2013,
'Женщины - Одежда - Боди' => 31970,
'Женщины - Одежда - Платье' => 2003,
'Женщины - Одежда - Юбка' => 2002,
'Женщины - Одежда - Брюки' => 2015,
'Женщины - Одежда - Джинсы' => 31971,
'Женщины - Одежда - Леггинсы/Бриджи' => 2017,
'Женщины - Одежда - Шорты' => 2016,
'Женщины - Одежда - Пижама/Ночная сорочка' => 2014,
'Женщины - Одежда - Трусы' => 31972,
'Женщины - Одежда - Носки/Следки' => 31973,
'Женщины - Белье - Бюстгальтер' => 31974,
'Женщины - Белье - Трусы' => 31972,
'Женщины - Белье - Носки/Следки' => 31973,
'Женщины - Обувь - Кеды' => 31975,
'Женщины - Обувь - Сапоги' => 31976,
'Женщины - Аксессуары - Шапки/шарфы/перчатки' => 31977,
'Женщины - Аксессуары - Бейсболка' => 31977,
'Женщины - Аксессуары - Шарф/Платок' => 31977,
'Мужчины - Одежда - Верхняя одежда' => 31978,
'Мужчины - Одежда - Вязаный трикотаж' => 2021,
'Мужчины - Одежда - Джемпер' => 2019,
'Мужчины - Одежда - Водолазка' => 2008,
'Мужчины - Одежда - Толстовка' => 2018,
'Мужчины - Одежда - Рубашка/Сорочка' => 2020,
'Мужчины - Одежда - Поло' => 2022,
'Мужчины - Одежда - Футболка' => 2024,
'Мужчины - Одежда - Футболка (без принта)' => 2024,
'Мужчины - Одежда - Майка' => 31979,
'Мужчины - Одежда - Майка (без принта)' => 31979,
'Мужчины - Одежда - Брюки/Джинсы' => 2025,
'Мужчины - Одежда - Шорты' => 2023,
'Мужчины - Одежда - Трусы' => 31980,
'Мужчины - Одежда - Носки/Следки' => 31981,
'Мужчины - Одежда - TBOE + Batman' => 31983,
'Мужчины - Белье - Трусы' => 31980,
'Мужчины - Белье - Носки/Следки' => 31981,
'Мужчины - Аксессуары - Шапки/шарфы/перчатки' => 31982,
'Мужчины - Аксессуары - Бейсболка' => 31982,
'Дети - Девочки 0-2 - Верхняя одежда' => 31940,
'Дети - Девочки 0-2 - Куртка' => 31941,
'Дети - Девочки 0-2 - Шапки/шарфы/ варежки/перчатки' => 31942,
'Дети - Девочки 0-2 - Вязаный трикотаж' => 31943,
'Дети - Девочки 0-2 - Джемпер' => 31944,
'Дети - Девочки 0-2 - Блузка/Рубашка' => 31945,
'Дети - Девочки 0-2 - Футболка' => 31946,
'Дети - Девочки 0-2 - Боди' => 31947,
'Дети - Девочки 0-2 - Платье' => 31948,
'Дети - Девочки 0-2 - Леггинсы/Бриджи' => 31949,
'Дети - Девочки 0-2 - Колготки' => 31950,
'Дети - Девочки 0-2 - Носки/Следки' => 31951,
'Дети - Девочки 2-8 - Верхняя одежда' => 31940,
'Дети - Девочки 2-8 - Куртка' => 31941,
'Дети - Девочки 2-8 - Шапки/шарфы/ варежки/перчатки' => 31942,
'Дети - Девочки 2-8 - Вязаный трикотаж' => 31943,
'Дети - Девочки 2-8 - Джемпер' => 31944,
'Дети - Девочки 2-8 - Кардиган' => 31952,
'Дети - Девочки 2-8 - Водолазка' => 31953,
'Дети - Девочки 2-8 - Блузка/Рубашка' => 31945,
'Дети - Девочки 2-8 - Поло' => 31954,
'Дети - Девочки 2-8 - Футболка' => 31946,
'Дети - Девочки 2-8 - Майка' => 31964,
'Дети - Девочки 2-8 - Боди' => 31947,
'Дети - Девочки 2-8 - Платье' => 31948,
'Дети - Девочки 2-8 - Юбка' => 31956,
'Дети - Девочки 2-8 - Брюки' => 31955,
'Дети - Девочки 2-8 - Леггинсы/Бриджи' => 31949,
'Дети - Девочки 2-8 - Пижама' => 31957,
'Дети - Девочки 2-8 - Топ/Белье' => 31965,
'Дети - Девочки 2-8 - Трусы' => 31966,
'Дети - Девочки 2-8 - Колготки' => 31950,
'Дети - Девочки 2-8 - Гольфы' => 31958,
'Дети - Девочки 2-8 - Носки/Следки' => 31951,
'Дети - Девочки 2-8 - Сумка' => 31961,
'Дети - Девочки 8-13 - Верхняя одежда' => 31940,
'Дети - Девочки 8-13 - Куртка' => 31941,
'Дети - Девочки 8-13 - Шапки/шарфы/ варежки/перчатки' => 31942,
'Дети - Девочки 8-13 - Вязаный трикотаж' => 31943,
'Дети - Девочки 8-13 - Джемпер' => 31944,
'Дети - Девочки 8-13 - Кардиган' => 31952,
'Дети - Девочки 8-13 - Водолазка' => 31953,
'Дети - Девочки 8-13 - Блузка/Рубашка' => 31945,
'Дети - Девочки 8-13 - Поло' => 31954,
'Дети - Девочки 8-13 - Футболка' => 31946,
'Дети - Девочки 8-13 - Майка' => 31964,
'Дети - Девочки 8-13 - Боди' => 31947,
'Дети - Девочки 8-13 - Платье' => 31948,
'Дети - Девочки 8-13 - Юбка' => 31956,
'Дети - Девочки 8-13 - Брюки' => 31955,
'Дети - Девочки 8-13 - Леггинсы/Бриджи' => 31949,
'Дети - Девочки 8-13 - Пижама' => 31957,
'Дети - Девочки 8-13 - Топ/Белье' => 31965,
'Дети - Девочки 8-13 - Трусы' => 31966,
'Дети - Девочки 8-13 - Колготки' => 31950,
'Дети - Девочки 8-13 - Носки/Следки' => 31951,
'Дети - Девочки 8-13 - Балетки' => 31959,
'Дети - Девочки 8-13 - Сапоги' => 31960,
'Дети - Девочки 8-13 - Сумка' => 31961,
'Дети - Мальчики 0-2 - Верхняя одежда' => 31940,
'Дети - Мальчики 0-2 - Шапки/шарфы/ варежки/перчатки' => 31942,
'Дети - Мальчики 0-2 - Вязаный трикотаж' => 31943,
'Дети - Мальчики 0-2 - Джемпер' => 31944,
'Дети - Мальчики 0-2 - Жакет' =>  31967,
'Дети - Мальчики 0-2 - Футболка' => 31946,
'Дети - Мальчики 0-2 - Брюки' => 31955,
'Дети - Мальчики 0-2 - Колготки' => 31950,
'Дети - Мальчики 0-2 - Носки/Следки' => 31951,
'Дети - Мальчики 2-8 - Верхняя одежда' => 31940,
'Дети - Мальчики 2-8 - Шапки/шарфы/ варежки/перчатки' => 31942,
'Дети - Мальчики 2-8 - Вязаный трикотаж' => 31943,
'Дети - Мальчики 2-8 - Джемпер' => 31944,
'Дети - Мальчики 2-8 - Кардиган' => 31952,
'Дети - Мальчики 2-8 - Водолазка' => 31953,
'Дети - Мальчики 2-8 - Толстовка' => 31962,
'Дети - Мальчики 2-8 - Рубашка' => 31945,
'Дети - Мальчики 2-8 - Поло' => 31954,
'Дети - Мальчики 2-8 - Футболка' => 31946,
'Дети - Мальчики 2-8 - Брюки' => 31955,
'Дети - Мальчики 2-8 - Трусы' => 31966,
'Дети - Мальчики 2-8 - Колготки' => 31950,
'Дети - Мальчики 2-8 - Гольфы' => 31958,
'Дети - Мальчики 2-8 - Носки/Следки' => 31951,
'Дети - Мальчики 8-13 - Верхняя одежда' => 31940,
'Дети - Мальчики 8-13 - Шапки/шарфы/ варежки/перчатки' => 31942,
'Дети - Мальчики 8-13 - Вязаный трикотаж' => 31943,
'Дети - Мальчики 8-13 - Джемпер' => 31944,
'Дети - Мальчики 8-13 - Водолазка' => 31953,
'Дети - Мальчики 8-13 - Толстовка' => 31962,
'Дети - Мальчики 8-13 - Рубашка' => 31945,
'Дети - Мальчики 8-13 - Поло' => 31954,
'Дети - Мальчики 8-13 - Футболка' => 31946,
'Дети - Мальчики 8-13 - Майка' => 31964,
'Дети - Мальчики 8-13 - Брюки' => 31955,
'Дети - Мальчики 8-13 - Шорты' => 31963,
'Дети - Мальчики 8-13 - Носки/Следки' => 31951,
'Дети - Мальчики 8-13 - Сапоги' => 31960,
'Семья - Одежда для всей семьи - Одежда для собак' => 31984,
'Семья - Одежда для всей семьи - Евгения Гапчинская' => 31985,
'Семья - Одежда для всей семьи - Миньоны' => 31986,
'Семья - Одежда для всей семьи - Мстители' => 31987,
'Семья - Одежда для всей семьи - Эрейзер (проект Славы Филиппова)' => 31988,
'Семья - Одежда для всей семьи - Adventure Time' => 31989,
'Семья - Одежда для всей семьи - Disney' => 31990,
'Семья - Одежда для всей семьи - Batman' => 31991,
'Семья - Одежда для всей семьи - Looney Tunes' => 31992,
'Семья - Одежда для всей семьи - Superman' => 31993,
'Семья - Одежда для всей семьи - Мумий Тролль' => 31994,
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
      $obj->nc_text .= "<li><strong>{$dKey}</strong> {$dVal}</li>";
    }
    $obj->nc_text .= "</ul>";
  }
  $obj->nc_category = isset($vidv[$item->category]) ? $vidv[$item->category] : false;
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
