<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

require_once '../core/class.init.php';

Init::StartPage();

#include_once("../core/class.database.php");
include_once("../core/class.memorycacher.php");


echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));

include_once("funct.DB_ConnectionStats.php");


/// EO FUNCTIONS \\\


#$fc = new FileCacher();
$mc = MemoryCacher::getMemoryCacherObject();

#$db = Database::createConnectionFromConfig();
#$ct = createCurrentTimeObject();

echo $mc->getMemoryProfilerReport();


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