<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

$refreshInMilliSec = 500;
$refreshInMicroSec = 1000 * $refreshInMilliSec;

$verboseModus = 0;

require_once '../core/class.init.php';


include_once("../core/class.database.php");
include_once("../core/class.filecacher.php");
include_once("../core/class.memorycacher.php");


echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));
echo sprintf('<a href="shell.DB_ConnectionStats_clear.php">Purge data</a><br />');


include_once("funct.DB_ConnectionStats2.php");


/// EO FUNCTIONS \\\


//$fc = new FileCacher();
$mc = MemoryCacher::getMemoryCacherObject();

$ct = createCurrentTimeObject();

$db = Database::createConnectionFromConfig();

$connMetaObj = createConnectionStatsMeta(CUR_TIMESTAMP_MICRO);
$scriptTimeStampHash = getTimeStampHash(CUR_TIMESTAMP_MICRO);

storeConnectionStatsMeta($mc, $connMetaObj);

//get connections object (at start)
$connStatsObj = getStoredConnectionsObject($mc);

$itterCount = 0;
$loopMicroTime = $ct->microTimeStamp;

while(true){
    $itterCount++;
    
    //save old loop time
    $loopMicroTimeOld = $loopMicroTime;
    
    //get meta connection object
    $connStatMeta = getConnectionStatsMeta($mc);
    

    //get the current time object
    $ct = createCurrentTimeObject();
    
    //set new loop micro time
    $loopMicroTime = $ct->microTimeStamp;

    if($ct->H > 19){
        echo sprintf('After %sh stop execution<br />', 19);
//        break;
    }

    //check if script start micro time is the same as in the meta file (and thus is the only running script updating the meta file)
    if($connStatMeta->startTimeStampHash != $scriptTimeStampHash){
        echo sprintf(
            'Meta file start missmatch stop execution<br />debug:<br />%s<br />%s', 
            var_export($connStatMeta->startTimeStampHash, true),
            var_export($scriptTimeStampHash, true)
        );
//        break;
    }
   

    $procArr = $db->GetConnectionStats();

    storeProcessList($mc, $procArr);

    $connStatsObj = getStoredConnectionsObject($mc);

    foreach($procArr as $row){
    //    echo var_export($row, true);
        processConnectionsLine($connStatsObj, $row, false);
    }

    cleanOldConnections($connStatsObj);
    finalizeConnectionsObject($connStatsObj);

    storeConnectionsObject($mc, $connStatsObj);


    $runtime = microTime(true) - $loopMicroTime;
    $intervalTime = $loopMicroTime - $loopMicroTimeOld;
    
    if($verboseModus){
        $runtimeOut = number_format($runtime, 3, ',', '.');
        $intervalTimeOut = number_format($intervalTime, 3, ',', '.');

        echo sprintf(
            '<div>Runtime %ss - Time between interval %ss - %s</div>', 
            $runtimeOut, $intervalTimeOut, date('Y-m-d H:i:s')
        );
    }else{
        echo '.';
    }
    
    updateConnectionStatsMeta($connStatMeta, $loopMicroTime, $runtime, $intervalTime);
    

    storeConnectionStatsMeta($mc, $connStatMeta);
    
    //usleep is in microseconds (as in 1000 * 1000 is a second)
    usleep($refreshInMicroSec);
}

echo '<p>EO script</p>';