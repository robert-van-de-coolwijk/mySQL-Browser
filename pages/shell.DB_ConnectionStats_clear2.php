<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

require_once '../core/class.init.php';

Init::StartPage();

#include_once("../core/class.database.php");
include_once("../core/class.filecacher.php");
include_once("../core/class.memorycacher.php");


echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));

/// FUNCTIONS \\\

include_once("funct.DB_ConnectionStats2.php");

$fc = new FileCacher();
#$mc = new MemoryCacher();
$mc = MemoryCacher::getMemoryCacherObject();


#$date = date('Y-m-d H:i:s');


$connStatsObj = getStoredConnectionsObject($mc, true);



finalizeConnectionsObject($connStatsObj);

storeConnectionsObject($mc, $connStatsObj);


echo '<p>Purge completed</p>';
echo '<p>EO script</p>';