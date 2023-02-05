<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

require_once '../core/class.init.php';

Init::StartPage();

include_once("../core/class.database.php");
include_once("../core/class.filecacher.php");
#include_once("../core/class.memorycacher.php");


echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));
echo sprintf('<a href="shell.DB_ConnectionStats_clear.php">Purge data</a><br />');

include_once("funct.DB_ConnectionStats.php");


/// EO FUNCTIONS \\\


$fc = new FileCacher();
#$mc = new MemoryCacher();

$db = Database::createConnectionFromConfig();
$ct = createCurrentTimeObject();


#$date = date('Y-m-d H:i:s');


$connStatsObj = getStoredConnectionsObject($fc);

$procArr = $db->GetConnectionStats();



foreach($procArr as $row){
//    echo var_export($row, true);
    processConnectionsLine($connStatsObj, $row, true);
}

cleanOldConnections($connStatsObj);
finalizeConnectionsObject($connStatsObj);

storeConnectionsObject($fc, $connStatsObj);

echo '<hr />';

//echo var_export($connStatsObj, true);
//echo json_encode($connStatsObj);

$connections = $connStatsObj->connections;

$groupedConnections = array(
    'Active' => array(),
    'Non Active' => array()
);

foreach($connections as $conn){
    if($conn->activeConnection){
        $groupedConnections['Active'][] = $conn;
    }else{
        $groupedConnections['Non Active'][] = $conn;
    }
}

foreach($groupedConnections as $groupName => $groupConnectionsArr){
    echo sprintf('<h2>%s</h2>', $groupName);
    
    foreach($groupConnectionsArr as $conn){

        echo sprintf('<div style="margin-top: 4px; font-size: 80%%;">Pid: %s</div>', $conn->pid);
        //echo '<div style="height: 20px; border: 1px solid #AAAAAA;">';
        echo '<div style="border: 1px solid #FFFFFF;">';

        foreach($conn->archive as $connStateObj){
            echo createDrawConnStateObj($ct, $connStateObj);
        }

        echo createDrawConnStateObj($ct, $conn->active);

        echo '<div style="clear: both;"></div>';
        echo '</div>';
    }
}

//close databases
//$db->Close();


echo '
    <script>
        function pageReload(){
            location.reload();
        }
        
        setTimeout(pageReload, 1000);
    </script>
';

$runtime = microTime(true) - CUR_TIMESTAMP_MICRO;
$runtimeOut = number_format($runtime, 3, ',', '.');

echo sprintf('<div>Runtime %ss</div>', $runtimeOut);
echo '<p>EO script</p>';

Init::EndPage();