<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

require_once '../core/class.init.php';



include_once("../core/class.filecacher.php");
include_once("../core/class.memorycacher.php");


include_once("funct.DB_ConnectionStats2.php");



$pageSection = false;
if(isset($_REQUEST['page'])){
    $pageSection = trim($_REQUEST['page']);
}


/// EO FUNCTIONS \\\


$fc = new FileCacher();
#$mc = new MemoryCacher();
$mc = MemoryCacher::getMemoryCacherObject();

$ct = createCurrentTimeObject();


#$date = date('Y-m-d H:i:s');


function echoMainFrame(){
    echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));
    echo sprintf('<a href="shell.DB_ConnectionStats_clear2.php" target="frame_blind1">Purge data</a><br />');
    echo sprintf('<a href="shell.DB_ConnectionStatsUpdateDebug.php" target="frame_blind2">Update data</a><br />');
    //echo '<br />';
    
    echo '<div style="width: 1600px; height: 360px;" class="clear-iframe" id="frame_procl" name="frame_procl" src="?page=processlist"></div>';
//    echo '<hr />';
    echo '<div style="width: 1600px; height: 600px;" class="clear-iframe" id="frame_visal" name="frame_visal" src="?page=connection_vissualisation"></div>';
    echo '<div style="width: 1600px; height: 20px;" class="clear-iframe" id="frame_stats" name="frame_stats" src="?page=connection_stats"></div>';
    echo '<iframe style="width: 100px;  height: 40px;" class="clear-iframe"  name="frame_blind1" src="?page=blank"></iframe>';
    echo '<iframe style="width: 100px;  height: 40px;" class="clear-iframe" name="frame_blind2" src="?page=blank"></iframe>';
}



function echoTableProcessList($fProcessListObj){
    $arr = $fProcessListObj;
    
    $row = $arr[key($arr)];
    
    $out = '<table border="1" class="full-width">';
    
    
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
        $processListObj = getStoredProcessList($mc);
        
        echoTableProcessList($processListObj);
        
//        echoJsScriptReload();
        
        echoRuntime(CUR_TIMESTAMP_MICRO);
        
        break;
    case 'connection_vissualisation':
        
        $connStatsObj = getStoredConnectionsObject($mc);
        
        echoDrawBlocks($connStatsObj, $ct);
        
        
        echoRuntime(CUR_TIMESTAMP_MICRO);
        
//        echoJsScriptReload();
        
        break;
    case 'connection_stats':
        echoDrawBackgroundProcessor($mc);
        
        break;
    case 'blank':
        //output nothing
        break;
    default:
        Init::StartPage();
        
        echoMainFrame();
        
        echoJs('
            function loadElement(fTargetID){
                var el = $("#" + fTargetID)
                var scr = el.attr("src");
                el.load(scr);
            }
            
            function updateAll(){
               loadElement("frame_procl");
               loadElement("frame_visal");
               loadElement("frame_stats");
            }
           
            function repeatUpdateAll(){
                updateAll();
                
                setTimeout(repeatUpdateAll, 500);
            }

            $(document).ready(function(){
            
                repeatUpdateAll();

            });
        ');
        
        echoRuntime(CUR_TIMESTAMP_MICRO);
        
        Init::EndPage();
}
