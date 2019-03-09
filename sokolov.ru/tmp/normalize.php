<?php
require_once __DIR__."/../fw.cs-cart.php";
header('Content-type: text/plain');

$prod = db_get_hash_single_array("SELECT product_id, product_code FROM `?:products`", array('product_code', 'product_id'));
//print_r($prod);

$vars = db_get_hash_single_array("SELECT variant_id, variant FROM `?:product_feature_variant_descriptions`", array('variant', 'variant_id'));
print_r($vars);
/*
$sz = array();
$sizes = db_get_array("SELECT product_id, value FROM `?:product_features_values` WHERE feature_id IN (17,18)");
foreach($sizes as &$size){
    if(strpos($size['value'],":")){
        $pid = $size['product_id'];
        if(!isset($sz[$pid])) $sz[$pid] = array();
        $size = explode("/", $size['value']);
        foreach($size as &$s){
            list($z, $q) = explode(":", $s.":0");
            if(isset($vars[$z]) && !isset($sz[$pid][$z])){
                $sz[$pid][$z] = true;
                $item = array(
                    'feature_id'    => 51,
                    'product_id'    => $pid,
                    'variant_id'    => $vars[$z],
                    'value'         => "",
                    'value_int'     => null,
                    'lang_code'     => "ru",
                );
                db_query("INSERT INTO ?:product_features_values ?e", $item);
            }
        }
    }
}
*/
//$props = json_decode(file_get_contents(__DIR__."/CatalogSmallRDEALER.props.json"), true);
//foreach($props as $sku => &$prop){
/*
    if(isset($prop['Металл'])){
        $item = array(
            'feature_id'    => 27,
            'product_id'    => $prod[$sku],
            'variant_id'    => 0,
            'value'         => trim($prop['Металл']),
            'value_int'     => null,
            'lang_code'     => "ru",
        );
        db_query("INSERT INTO ?:product_features_values ?e", $item);
        
        $item = array(
            'feature_id'    => 48,
            'product_id'    => $prod[$sku],
            'variant_id'    => $vars[trim($prop['Металл'])],
            'value'         => "",
            'value_int'     => null,
            'lang_code'     => "ru",
        );
        db_query("INSERT INTO ?:product_features_values ?e", $item);
    }
    if(isset($prop['Для кого'])){
        $item = array(
            'feature_id'    => 50,
            'product_id'    => $prod[$sku],
            'variant_id'    => $vars[trim($prop['Для кого'])],
            'value'         => "",
            'value_int'     => null,
            'lang_code'     => "ru",
        );
        db_query("INSERT INTO ?:product_features_values ?e", $item);
    }
    if(isset($prop['Вставка'])){
        $ins = explode(",", preg_replace(array('|\([^\)]*\)|isU', '|&nbsp;|isU'), "", $prop['Вставка']));
        foreach($ins as &$in) $in = trim($in);
        $ins = array_unique($ins);
        foreach($ins as &$in){
            $item = array(
                'feature_id'    => 49,
                'product_id'    => $prod[$sku],
                'variant_id'    => $vars[$in],
                'value'         => "",
                'value_int'     => null,
                'lang_code'     => "ru",
            );
            db_query("INSERT INTO ?:product_features_values ?e", $item);
        }
    }
*/
//}
?>

