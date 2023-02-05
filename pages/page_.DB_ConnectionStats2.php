<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

require_once '../core/class.init.php';

Init::StartPage();

include_once("../core/class.filecacher.php");
#include_once("../core/class.memorycacher.php");


include_once("funct.DB_ConnectionStats.php");



$pageSection = false;
if(isset($_REQUEST['page'])){
    $pageSection = trim($_REQUEST['page']);
}


/// EO FUNCTIONS \\\


$fc = new FileCacher();
#$mc = new MemoryCacher();

$ct = createCurrentTimeObject();


#$date = date('Y-m-d H:i:s');


function echoMainFrame(){
    echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));
    echo sprintf('<a href="shell.DB_ConnectionStats_clear.php" target="frame_blind">Purge data</a><br />');
    //echo '<br />';
    
    echo '<iframe style="width: 1600px; height: 400px;" class="clear-iframe" name="frame_procl" src="?page=processlist"></iframe>';
//    echo '<hr />';
    echo '<iframe style="width: 1600px; height: 600px;" class="clear-iframe" name="frame_visal" src="?page=connection_vissualisation"></iframe>';
    echo '<iframe style="width: 100px;  height: 400px  ;" class="clear-iframe" name="frame_blind" src="?"></iframe>';
}



function echoTableProcessList($fProcessListObj){
    $arr = $fProcessListObj;
    
    $row = $arr[key($arr)];
    
    $out = '<table border="1">';
    
    
    $out .= '<tr>';
    foreach($row as $colName => $colVal){
        $out .=  sprintf('<th>%s</th>', $colName);
    }
    $out .= '</tr>';
    
    
    foreach($arr as $row){
        $out .= '<tr>';
        
        foreach($row as $colName => $colVal){
            $out .= sprintf('<td>%s</td>', $colVal);
        }
        
        $out .= '</tr>';
    }
    
    $out .= '</table>';
    
    echo $out;
}


function echoDrawBlocks($connStatsObj, $ct){
        
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
    
}

function echoDrawBackgroundProcessor($ct){
    
    $metaObj = getConnectionStatsMeta($ct);
        
    echoConnectionStatsMeta($metaObj);
}

//close databases
//$db->Close();



switch($pageSection){
    case 'processlist':
        $processListObj = getStoredProcessList($fc);
        
        echoTableProcessList($processListObj);
        
        echoJsScriptReload();
        
        echoRuntime(CUR_TIMESTAMP_MICRO);
        
        break;
    case 'connection_vissualisation':
        
        $connStatsObj = getStoredConnectionsObject($fc);
        
        echoDrawBlocks($connStatsObj, $ct);
        
        echoDrawBackgroundProcessor($fc);
        
        
        echoRuntime(CUR_TIMESTAMP_MICRO);
        
        echoJsScriptReload();
        
        break;
    default:
        echoMainFrame();
        
        echoRuntime(CUR_TIMESTAMP_MICRO);
}



//$runtime = microTime(true) - CUR_TIMESTAMP_MICRO;
//$runtimeOut = number_format($runtime, 3, ',', '.');

//echo sprintf('<div>Runtime %ss</div>', $runtimeOut);
//echo '<p>EO script</p>';

Init::EndPage();