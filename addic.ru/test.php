<?php
require_once __DIR__ . "/../fw.joomla.php";
require_once __DIR__ . "/config.php";

$table = "#__ncatalogues_object{$type}";

$db = JFactory::getDBO();

$db->setQuery("SELECT id, title FROM #__ncatalogues_field_dictionary_value WHERE parent={$GLOBALS['brand']}");
$catId = $db->loadColumn(0);
$catTitle = $db->loadColumn(1);
$catArr = array_combine($catTitle, $catId);
// var_dump($catArr);

foreach ($catArr as $key => &$val) {
  echo "{$key} => {$val}\n";
  $db->setQuery("SELECT id, title FROM #__ncatalogues_field_dictionary_value WHERE parent={$val}");
  $catIdInner = $db->loadColumn(0);
  $catTitleInner = $db->loadColumn(1);
  $catArrInner = array_combine($catTitleInner, $catIdInner);
  foreach ($catArrInner as $keyInner => &$valInner) {
    echo "\t{$keyInner} => {$valInner}\n";
  }
}
?>
