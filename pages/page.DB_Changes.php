<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

require_once '../core/class.init.php';

Init::StartPage();

//include_once("../core/class.database.php");
//include_once("../core/class.filecacher.php");
#include_once("../core/class.memorycacher.php");
include_once("../core/class.updatediff.php");

/// FUNCTIONS \\\
function getLink($fDate){
    return $actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'] . '?date=' . $fDate;
}


/// EO FUNCTIONS \\\


$selectedDate = isset($_REQUEST['date']) ? $_REQUEST['date'] : false;
        

echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));

if(!$selectedDate){
    $dateArr = UpdateDiff::getDateArray();
    
    echo sprintf('<h2>Registerd update dates</h2>');
    foreach($dateArr as $date){
        echo sprintf('- <a href="%s">%s</a><br />', getLink($date), $date);
    }
    
}else{
    
    $fileArr = UpdateDiff::getFileArray($selectedDate);
    
    foreach($fileArr as $fileName){
        $diffArr = UpdateDiff::getCompareDiffArray($selectedDate, $fileName);
        
        echo sprintf('<br /><b>%s</b><br />', $fileName);
        echo DiffObject::toTable($diffArr);
    }
    
    
    
}






//echo '
//    <script>
//        function pageReload(){
//            location.reload();
//        }
//        
//        setTimeout(pageReload, 1000);
//    </script>
//';

$runtime = microTime(true) - CUR_TIMESTAMP_MICRO;
$runtimeOut = number_format($runtime, 3, ',', '.');

echo sprintf('<div>Runtime %ss</div>', $runtimeOut);
echo '<p>EO script</p>';

Init::EndPage();