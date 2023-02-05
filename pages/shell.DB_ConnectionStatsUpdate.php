<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

$refreshInMilliSec = 500;
$refreshInMicroSec = 1000 * $refreshInMilliSec;

require_once '../core/class.init.php';


include_once("../core/class.database.php");
include_once("../core/class.filecacher.php");
#include_once("../core/class.memorycacher.php");


echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));
echo sprintf('<a href="shell.DB_ConnectionStats_clear.php">Purge data</a><br />');

include_once("funct.DB_ConnectionStats.php");


/// EO FUNCTIONS \\\


$fc = new FileCacher();
#$mc = new MemoryCacher();

$ct = createCurrentTimeObject();

$db = Database::createConnectionFromConfig();

$connMetaObj = createConnectionStatsMeta(CUR_TIMESTAMP_MICRO);
$scriptTimeStampHash = getTimeStampHash(CUR_TIMESTAMP_MICRO);

storeConnectionStatsMeta($fc, $connMetaObj);

//get connections object (at start)
$connStatsObj = getStoredConnectionsObject($fc);

$itterCount = 0;
$loopMicroTime = $ct->microTimeStamp;

while(true){
    //save old loop time
    $loopMicroTimeOld = $loopMicroTime;
    
    //get meta connection object
    $connStatMeta = getConnectionStatsMeta($fc);

    //get the current time object
    $ct = createCurrentTimeObject();
    
    //set new loop micro time
    $loopMicroTime = $ct->microTimeStamp;

    if($ct->H > 19){
        echo sprintf('After %sh stop execution<br />', 19);
        break;
    }

    //check if script start micro time is the same as in the meta file (and thus is the only running script updating the meta file)
    if($connStatMeta->startTimeStampHash != $scriptTimeStampHash){
        echo sprintf(
            'Meta file start missmatch stop execution<br />debug:<br />%s<br />%s', 
            var_export($connStatMeta->startTimeStampHash, true),
            var_export($scriptTimeStampHash, true)
        );
        break;
    }
   


    #$date = date('Y-m-d H:i:s');

    $procArr = $db->GetConnectionStats();

    storeProcessList($fc, $procArr);

    $connStatsObj = getStoredConnectionsObject($fc);

    foreach($procArr as $row){
    //    echo var_export($row, true);
        processConnectionsLine($connStatsObj, $row, false);
    }

    cleanOldConnections($connStatsObj);
    finalizeConnectionsObject($connStatsObj);

    storeConnectionsObject($fc, $connStatsObj);


    $runtime = microTime(true) - $loopMicroTime;
    $runtimeOut = number_format($runtime, 3, ',', '.');
    $intervalTime = $loopMicroTime - $loopMicroTimeOld;
    $intervalTimeOut = number_format($intervalTime, 3, ',', '.');

    echo sprintf(
        '<div>Runtime %ss - Time between interval %ss - %s</div>', 
        $runtimeOut, $intervalTimeOut, date('Y-m-d H:i:s')
    );
    
    updateConnectionStatsMeta($connStatMeta, $loopMicroTime, $runtime, $intervalTime);
    

    storeConnectionStatsMeta($fc, $connStatMeta);
    
    //usleep is in microseconds (as in 1000 * 1000 is a second)
    usleep($refreshInMicroSec);
}

echo '<p>EO script</p>';