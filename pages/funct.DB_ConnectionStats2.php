<?php
/// FUNCTIONS \\\

// Storage functions

function storeConnectionStatsMeta(MemoryCacher $fc, $connStatsObj){
    $cacheKey = Config::DatabaseConnectionStatsMetaKey;
    
    $fc->put($cacheKey, $connStatsObj, false);
}

function getConnectionStatsMeta(MemoryCacher $fc){
    $cacheKey = Config::DatabaseConnectionStatsMetaKey;
    
    $metaObj = $fc->get($cacheKey);
    
    return $metaObj;
}



function storeProcessList(MemoryCacher $fc, $connStatsObj){
    $cacheKey = Config::DatabaseConnectionStatsProclistKey;
    
    $fc->put($cacheKey, $connStatsObj, false);
}

function getStoredProcessList(MemoryCacher $fc){
    $cacheKey = Config::DatabaseConnectionStatsProclistKey;
    
    $procList = $fc->get($cacheKey);
    
    return $procList;
}

#function getConnectionObject(Database $db, FileCacher $fc){
function getStoredConnectionsObject(MemoryCacher $fc, $fForceNew = false){
    $cacheKey = Config::DatabaseConnectionStatsKey;
    
    $connStatsObj = $fc->get($cacheKey);
    
    if(!is_object($connStatsObj) || $fForceNew === true){
        $connStatsObj = new stdClass();
        
        $connStatsObj->connections = new stdClass();    //searchable
//        $connStatsObj->oldConnections = array();        //non searchable 
    }
    
    
    
    return $connStatsObj;
}

function storeConnectionsObject(MemoryCacher $fc, $connStatsObj){
    $cacheKey = Config::DatabaseConnectionStatsKey;
    
     $fc->put($cacheKey, $connStatsObj, false);
}

// db stats functions \\

function createConnectionStatsMeta($fStartTimeStamp){
    $obj = new stdClass();
    
    $obj->startTimeStamp = $fStartTimeStamp;
    $obj->lastUpdatedTimeStamp = $fStartTimeStamp;
    $obj->startTimeStampHash = getTimeStampHash($fStartTimeStamp);
    
    $obj->pollCount = 0;
    
    $obj->lastRuntime = 0;
    $obj->avgRuntime = 0;
    $obj->minRuntime = 10000000;
    $obj->maxRuntime = 0;
    
    $obj->intervalRuntime = 0;
    
    #$obj->peakMemoryUsage = memory_get_peak_usage(false); // not useful (is just peak memory usage for the poller)
    
    return $obj;
}

function getTimeStampHash($fTimeStamp){
    return sha1($fTimeStamp);
}

function updateConnectionStatsMeta($fMetaObj, $fUpdateTimeStamp, $fRunTime, $fIntervalTime){
    $obj = $fMetaObj;
    
    $obj->lastUpdatedTimeStamp = $fUpdateTimeStamp;
    
    $obj->pollCount++;
            
    $obj->lastRuntime = $fRunTime;
    $obj->intervalRuntime = $fIntervalTime;
    
    //take previous poll count * previous averaged runtime
    //add current runtime
    //devide with new poll count (increasing or decreasing the runtime proportional with the part
    $obj->avgRuntime = ((($obj->pollCount - 1) * $obj->avgRuntime) + $fRunTime) / $obj->pollCount;
    
    $obj->minRuntime = $fRunTime < $obj->minRuntime ? $fRunTime : $obj->minRuntime;
    $obj->maxRuntime = $fRunTime > $obj->maxRuntime ? $fRunTime : $obj->maxRuntime;
    
}

function getConnectionStatus($fLastUpdateTimeStamp){
    $timeSinceLastUpdate = microtime(true) - $fLastUpdateTimeStamp;
    
	if($timeSinceLastUpdate < 2){
	    //is active
	    $stateOut = sprintf('<div class="status-block status-positive">Active</div>');
	}elseif($timeSinceLastUpdate < 5){
		$stateOut = sprintf('<div class="status-block status-negative">Stall %ss</div>', msNumFormat($timeSinceLastUpdate));
	}else{
	    $stateOut = sprintf('<div class="status-block status-critical">Stopped %sm</div>', round(($timeSinceLastUpdate) / 60));
	}
	
	return $stateOut;
}

function echoConnectionStatsMeta($fMetaObj){
   
    $stateOut = getConnectionStatus($fMetaObj->lastUpdatedTimeStamp);
    
    echo sprintf(
        'Runtime stats - last %s - avg %s -  min %s -  max %s [interval %s] - %s %s',
        msNumFormat($fMetaObj->lastRuntime, false),
        msNumFormat($fMetaObj->avgRuntime, false),
        msNumFormat($fMetaObj->minRuntime, false),
        msNumFormat($fMetaObj->maxRuntime, false),
        msNumFormat($fMetaObj->intervalRuntime, false),
        date('Y-m-d H:i:s', $fMetaObj->lastUpdatedTimeStamp),
        $stateOut
    );
}

function processConnectionsLine($fConnStatsObj, $fLine, $fVerbose=false){
    if($fVerbose){
        echo var_export($fLine, true) . '<br />';
    }
    
    
    $pid = $fLine->Id;
    $connObj = $fConnStatsObj->connections;
    

    if(!isset($connObj->{$pid})){
        $connLine = createConnObj($fLine, $pid);
        $connObj->{$pid} = $connLine;
    }else{
        $connLine = $connObj->{$pid};
         
         
        updateConnObj($connLine, $fLine);
    }
}



function createConnObj($fLine, $fPid){
    
    //(active) state object
    $activeStateObj = new stdClass();
    $activeStateObj->pid = $fPid;
    $activeStateObj->procListLine = $fLine;
    $activeStateObj->command = $fLine->Command;
    $activeStateObj->runTime = $fLine->Time;
    $activeStateObj->startTimeStamp = CUR_TIMESTAMP - $fLine->Time; //@todo only get ONCE
    
    //connection object
    $obj = new stdClass();
    
    $obj->pid = $fLine->Id;
    $obj->active = $activeStateObj;
    
    $obj->archive = array();
    
    $obj->updateTimeStamp = CUR_TIMESTAMP;
    $obj->activeConnection = true;
    
    return $obj;
}

function updateConnObj($fConnLine, $fLine){
    $activeStateObj = $fConnLine->active;
    $newState = false;
    
    if($activeStateObj->command != $fLine->Command){
        $fConnLine->archive[] = clone $activeStateObj;
        
        $newState = true;
    }
    
    if(!$newState){
        if($activeStateObj->runTime > $fLine->Time){
            $fConnLine->archive[] = clone $activeStateObj;
        
            $newState = true;
        }
    }
    
    $activeStateObj->procListLine = $fLine;
    $activeStateObj->command = $fLine->Command;
    $activeStateObj->runTime = $fLine->Time;
    $activeStateObj->startTimeStamp = CUR_TIMESTAMP - $fLine->Time; //@todo only get ONCE
    $activeStateObj->updateTimeStamp = CUR_TIMESTAMP;
    
    //update connection object
    $fConnLine->updateTimeStamp = CUR_TIMESTAMP;
    $fConnLine->activeConnection = true;
}

function cleanOldConnections($fConnStatsObj){
    $connObj = $fConnStatsObj->connections;
    
    $diffSecTrigger = 30;
    $curTimeStamp = CUR_TIMESTAMP;
    
    foreach($connObj as $pidKey => $conn){
//        echo var_export(array($conn->updateTimeStamp, $curTimeStamp, $diffSecTrigger, ($curTimeStamp - $conn->updateTimeStamp)), true) . '<br />';
        $trigger = ($curTimeStamp - $conn->updateTimeStamp) > $diffSecTrigger;
        
        if($trigger){
            $conn->activeConnection = false;
        }
    }
}

function finalizeConnectionsObject($fConnStatsObj){
    $fConnStatsObj->lastUpdatedTimeStamp = CUR_TIMESTAMP; //@todo replace with current time stamp
    
    
}

function createDrawConnStateObj($ct, $activeStateObj){
    $styleArr = array(
        'Sleep'  => 'background-color: #DDDDDD;',
        'Query'  => 'background-color: #DDFFDD;',
        '-'      => 'background-color: #000000; color: #FFFFFF;',
    );
    
    $shortMessage = sprintf(
        '%s - Started: %s',
        $activeStateObj->command,
        humanDate($ct, $activeStateObj->startTimeStamp)
    );
    
    if(isset($styleArr[$activeStateObj->command])){
        $style = $styleArr[$activeStateObj->command];
    }else{
        $style = $styleArr['-'];
    }
    
    $out = sprintf(
        '<a style="float: left; height: 14px; width: %spx; margin-left: 1px; font-size: 50%%; %s" title="%s">%ss</a>',
        ($activeStateObj->runTime + 10),    //width
        $style,
        $shortMessage,                      //title
        $activeStateObj->runTime            //link message
    );
    
    return $out;
}


// generic functions \\

function msNumFormat($runtime, $fShowFloat=true){
    if($fShowFloat){
        return number_format($runtime, 3, ',', '.');
    }else{
        return round($runtime * 1000) . 'ms';
    }
    
}

function humanDate($curDateObjNow, $fTimeStamp){
    $out = false;
    
    $curDateObjTarget = createCurrentTimeObject($fTimeStamp);
    
    $format = array(
        'Y' => 'Y-m-d H:i:s',
        'm' => 'd M H:i:s',
        'd' => 'd M H:i:s',
        'H' => 'H:i:s',
        'i' => 'H:i:s',
        's' => 'H:i:s',
    );
    
    foreach($format as $letter => $dateFormat){
        if($curDateObjNow->{$letter} != $curDateObjTarget->{$letter}){
            $out = date($dateFormat);
        }
    }
    
    if(!$out){
        $out = date('Y-m-d H:i:s') . '!';
    }
    
    
    return $out;
}


function createCurrentTimeObject($fTimeStamp = false){
//    $LTR = $lettersToRetrieve = array('Y', 'm', 'd', 'H', 'i', 's');
    $LTR = $lettersToRetrieve = 'Y-m-d-H-i-s';
    
    $microTimeStamp = (float)$fTimeStamp;
    if(!$fTimeStamp){
        $microTimeStamp = microtime(true);
        $fTimeStamp = (int)$microTimeStamp;
    }
    
    $curDateObj = new stdClass();
    $curDateObj->microTimeStamp = $microTimeStamp;
    $dateOut = date($LTR, $fTimeStamp);
    
    $letterArr = explode('-', $LTR);
    $dateArr = explode('-', $dateOut);
    
    foreach($dateArr as $key => $dateVal){
        $letter = $letterArr[$key];
        $curDateObj->{$letter} = $dateVal;
    }
    
    return $curDateObj;
}

function echoJsScriptReload($fReloadTimeInMs=1000){
    echoJs(sprintf('
        function pageReload(){
            location.reload();
        }
        
        setTimeout(pageReload, %s);
    '), $fReloadTimeInMs);
}

function echoJs($fJs){
    echo sprintf('<script>%s</script>', $fJs);
}

function echoRuntime($fOldTimeStamp){
    $runtime = microTime(true) - $fOldTimeStamp;
    $runtimeOut = number_format($runtime, 3, ',', '.');

    echo sprintf('<div>Runtime %ss</div>', $runtimeOut);
}