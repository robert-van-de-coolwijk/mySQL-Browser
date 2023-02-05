<?php


require_once '../core/class.init.php';


Init::StartPage();

echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));

$stringToCrypt = isset($_REQUEST['string_encrypt']) ? $_REQUEST['string_encrypt'] : false;
       
$debugSet = $stringToCrypt !== false;
$debugSetOut = var_export($debugSet, true);

if($debugSet){
//    $iv = Tools::GetNewIV();
    
    $debugLen = strlen($stringToCrypt);
    $encryptedString = Tools::encrypt($stringToCrypt, Config::CryptKey);
    $uncrptedString = Tools::decrypt($encryptedString, Config::CryptKey);

    $debugTest = strcmp($stringToCrypt, $uncrptedString);
    $debugTestOut = var_export($debugTest, true) . ' - ' . ($debugTest === 0 ? '<b>Match</b>' : '<b>NO MATCH!!!</b>');
    
//    $ivOut = base64_encode($iv);
}else{
    $debugLen = '';
    $encryptedString = '';
    $uncrptedString = '';
    $iv = '';
    $debugTestOut = '';
    $ivOut = '';
}

$output = '';


$output .= sprintf('<br />Is set: %s', $debugSetOut);
$output .= sprintf('<br />Set 64: %s', base64_encode($stringToCrypt));
$output .= sprintf('<br />Length: %s', $debugLen);
$output .= sprintf('<br /><br />Crypt:<br />%s', $encryptedString);
$output .= sprintf('<br /><br />%s', $debugTestOut);
$output .= sprintf('<br />Tst 64: %s', base64_encode($uncrptedString));
//$output .= sprintf('<br /><br />IV: <br />%s', $ivOut);

$output .= sprintf('<br /><div style="border: 1px solid #000000;">%s<br />%s<br />%s</div>', $stringToCrypt, $encryptedString, $uncrptedString);

echo $output;

?>
<hr>
<form action="" method="post">
  <input type="text" name="string_encrypt" value="<?= $uncrptedString ?? ''?>" /><br / >
    <br / >
  <input type="submit" value="Submit" />
</form>


<?php
Init::EndPage();
?>