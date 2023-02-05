<?php

define('CUR_TIMESTAMP_MICRO', microtime(true));
define('CUR_TIMESTAMP', round(CUR_TIMESTAMP_MICRO));


set_time_limit(60 * 60);

require_once '../core/class.init.php';

Init::StartPage();

include_once("../core/class.diffobject.php");

/// FUNCTIONS \\\

function test($a, $b, $validJson){
    
    $res = DiffObject::compare($a, $b);
    $resJson = json_encode($res);
    $pass = ($resJson == $validJson);
    
    echo sprintf(
        '
<div style="border: 1px solid %s;">
    <table>
        <tr>
            <th>A</th>
            <th>B</th>
        </tr>
        <tr>
            <td>%s</td>
            <td>%s</td>
        </tr>
        <tr>
            <th>Result</th>
            <th>Expected</th>
        </tr>
        <tr>
            <td>%s</td>
            <td>%s</td>
        </tr>
    </table>
</div>
        ',
        $pass ? 'green' : 'red',
        json_encode($a),
        json_encode($b),
        $resJson,
        $validJson
    );
}



/// EO FUNCTIONS \\\

$testCount = 1;
$label = '<br /><b>Test %s: %s</b>';

#start object
$a = (object)array(
    'arr' => array(
        
    ),
    'bool' => true,
    'int' => 1,
    'string' => date('Y-m-d H:i:s'),
    'double' => 4.000033
);
#add itself as an object to the array
$a->arr[] = clone $a;

#make identical clone
$b = clone $a;


echo sprintf($label, $testCount++, 'Match');
test($a, $b, '[]');


$c = clone $a;
$c->bool = false;

echo sprintf($label, $testCount++, 'Property bool');
test($a, $c, '[{"st":"CHG","br":"stdClass->bool","a":true,"b":false}]');


unset($c->bool);

echo sprintf($label, $testCount++, 'Dell bool');
test($a, $c, '[{"st":"DEL","br":"stdClass->bool","a":"object","b":"DEL"}]');



$c->string2 = 555;

echo sprintf($label, $testCount++, 'Add str2');
test($a, $c, '[{"st":"DEL","br":"stdClass->bool","a":"object","b":"DEL"},{"st":"ADD","br":"stdClass->string2","a":"","b":"NEW"}]');


$c->arr[] = clone $c;
echo sprintf($label, $testCount++, 'Itter');
test($a, $c, '[{"st":"ADD","br":"stdClass->arr[1]","a":"","b":"NEW"},{"st":"DEL","br":"stdClass->bool","a":"object","b":"DEL"},{"st":"ADD","br":"stdClass->string2","a":"","b":"NEW"}]');

//echo '
//    <script>
//        function pageReload(){
//            location.reload();
//        }
//        
//        setTimeout(pageReload, 1000);
//    </script>
//';

$runtime = microTime(true) - CUR_TIMESTAMP_MICRO;
$runtimeOut = number_format($runtime, 3, ',', '.');

echo sprintf('<div>Runtime %ss</div>', $runtimeOut);
echo '<p>EO script</p>';

Init::EndPage();