<?php

require_once '../core/class.init.php';


include_once("../core/class.filecacher.php");

Init::StartPage();


$fc = new FileCacher();

$dbsCacheObj = $fc->get('databases');
$dbArr = $dbsCacheObj->databaseList;

echo '<h2>Databases</h2>';
$totalSize = 0;

echo '<table>';

foreach($dbArr as $dbName){
    $context = 'database_' . $dbName;
    $dbCacheObj = $fc->get($context);
    
    if(!isset($dbCacheObj->name)){
        echo sprintf(
            '<tr><td>- <b>%s</b></td><td class="col-number-format">%s</td></tr>', 
            '- DB Skipped -', 
            '-'
        );
        
        continue;
    }
    
    $dbName = $dbCacheObj->name;
    $dbSize = isset($dbCacheObj->size) ?  $dbCacheObj->size : '';
    
    
    echo sprintf(
            '<tr><td>- <b>%s</b></td><td class="col-number-format">%s</td></tr>', 
            $dbName, 
            Tools::formatBytes($dbSize)
        );
    
    
    
    $totalSize += $dbSize;
}

echo sprintf(
        '<tr><td><b>TOTAAL</td><td class="col-number-format">%s</td></tr>', 
        Tools::formatBytes($totalSize)
    );
    
echo '</table>';
    
unset($dbArr);


Init::EndPage();

?>