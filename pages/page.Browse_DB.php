<?php

require_once '../core/class.init.php';


include_once("../core/class.filecacher.php");

Init::StartPage();

//handle request values
if(!isset($_REQUEST) || (is_array($_REQUEST) && count($_REQUEST) == 0)){
    $normReqVal = false;
}else{
    $normReqVal = (object)$_REQUEST;
}

$selectedDatabase = '';


$fc = new FileCacher();

$dbsCacheObj = $fc->get('databases');
$dbArr = $dbsCacheObj->databaseList;

// show top menu
$dropdownOptions = [];
$dropdownOptions[] = sprintf('<option value="%s">%s</option>', '', 'all');
foreach($dbArr as $dbName){
    $selectedAttr = '';

    if(isset($normReqVal->select_database) && $normReqVal->select_database == $dbName){
        $selectedDatabase = $dbName;

        $selectedAttr = 'selected="selected"';
    }

    $dropdownOptions[] = sprintf('<option value="%s"%s>%s</option>', $dbName, $selectedAttr, $dbName);
}

echo sprintf('
    <div class="stick-to-top-menu">
        <form method="GET">
            <label>Database: </label>
            <select name="select_database">
                %s
            </select>
            <input type="submit" value="show" />
        </form>
    </div>
', implode('', $dropdownOptions));


// show databases
echo '<h2>Databases</h2>';

foreach($dbArr as $dbName){
    if(!empty($selectedDatabase) && $selectedDatabase != $dbName){
        continue;
    }

    $context = 'database_' . $dbName;
    $dbCacheObj = $fc->get($context);
    
    $dbName = $dbCacheObj->name;
    
    echo sprintf('<br /><h2>%s</h2>', $dbName);
    
    foreach($dbCacheObj->tableList as $tbObj){
        $tableName = $tbObj->name;
        
        echo sprintf('<br />- <span class="suffix-subtext">%s.</span><b>%s</b>', $dbName, $tableName);
        
        foreach($tbObj->fieldList as $fdObj){
            $fieldName = $fdObj->Field;
            $fieldType = $fdObj->Type;
            
            echo sprintf(
                    '<br />-- <span class="suffix-subtext">%s.%s.</span>%s', 
                    $dbName, $tableName, $fieldName
                );
            
        }
        
        echo '<br />';
    }
    
}
unset($dbArr);


Init::EndPage();

?>