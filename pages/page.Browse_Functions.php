<?php

require_once '../core/class.init.php';


include_once("../core/class.filecacher.php");

Init::StartPage();


$fc = new FileCacher();


//functions
echo '<h2>Functions</h2>';

$fnCacheObj = $fc->get('functions');
$fnArr = $fnCacheObj->functionList;

$count = 0;
foreach($fnArr as $row){

    
    $key = 'p' . $count++;
    
    echo '<p>';
    
    echo sprintf(
        '- %s.<i>%s</i>(<span class="subtext-params">%s</span>) <br />&nbsp;&nbsp;&nbsp;&nbsp;<span class="subtext-returntype">%s</span>', 
        $row->Db, $row->Name, $row->Params, $row->ReturnType
    );

    echo sprintf(
        '<div class="text-block-statement" onclick="applyHighlightJsToEl(\'%s\');" id="%s">%s</div>',
        $key, $key, $row->AlterStatement
    );
    
    
    echo '</p>';
}


Init::EndPage();

?>