<?php

require_once '../core/class.init.php';


include_once("../core/class.filecacher.php");

Init::StartPage();


$fc = new FileCacher();


//procedure
echo '<h2>Procedure</h2>';

$prCacheObj = $fc->get('procedures');
$prArr = $prCacheObj->procedureList;

$count = 0;
foreach($prArr as $row){
    $key = 'p' . $count++;
    
    echo '<p>';
    
    echo sprintf('- %s.<i>%s</i>(<span class="subtext-params">%s</span>)', 
            $row->Db, $row->Name, $row->Params
        );
    
        echo sprintf(
            '<div class="text-block-statement" onclick="applyHighlightJsToEl(\'%s\');" id="%s">%s</div>',
            $key, $key, $row->AlterStatement
        );
    
    
    echo '</p>';
}


Init::EndPage();

?>