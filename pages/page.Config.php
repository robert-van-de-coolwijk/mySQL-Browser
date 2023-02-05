<?php

require_once '../core/class.init.php';


include_once("../core/class.filecacher.php");

Init::StartPage();


echo sprintf('<p>Script name: %s<br />%s<br /></p>', __FILE__, date('Y-m-d H:i:s'));


//handle request values
if(!isset($_REQUEST) || (is_array($_REQUEST) && count($_REQUEST) == 0)){
    $normReqVal = false;
}else{
    $normReqVal = (object)$_REQUEST;
}

//echo var_export($normReqVal, true);

//list of valid input values
$possibleInputTypeArr = array(
    'button',
    'checkbox',
    'color',
    'date ',
    'datetime ',
    'datetime-local ',
    'email ',
    'file',
    'hidden',
    'image',
    'month ',
    'number ',
    'password',
    'radio',
    'range ',
    'reset',
    'search',
    'submit',
    'tel',
    'text',
    'time ',
    'url',
    'week'
);

/// FUNCTIONS \\\\

function createField($fFieldConfig, $fFieldVal=''){
    $FC = $fFieldConfig;
    $value = $fFieldVal;
    
    $checked = '';
    
    if($FC->type == 'checkbox'){
        $checked = $fFieldVal ? 'checked' : '';
        $value = 1;
    }
    
    
    $out = '<tr><td>';
    
    $out .= sprintf('<label>%s</label> ', $FC->label);
    
    $out .= '</td><td>';
    
    $out .= sprintf(' <input type="%s" name="%s" value="%s" %s />', $FC->type, $FC->key, $value, $checked);
    
    $out .= '</tr></td>';
    
    return $out;
}

function testConnection($fConfigObj=false){
//    $CO = $fConfigObj;
//    
//    if(!is_object($CO)){
//        return;
//    }
//    
//    include_once("../core/class.database.php");
//    
//    $db = new Database($CO->db_host, $CO->db_user, $CO->db_pass, $CO->db_default_selected_db, $CO->db_port, (bool)$CO->db_persistentconnection);
    include_once("../core/class.database.php");
    
    $db = Database::createConnectionFromConfig();
    
    $db->Close();
}

/// EO FUNCTIONS \\\

$fieldList = json_decode('
    [
        {
            "key" :  "db_host",
            "label" : "Hostname",
            "type" : "text"
        },{
            "key" :  "db_port",
            "label" : "Port",
            "type" : "number"
        },{
            "key" :  "db_default_selected_db",
            "label" : "Default selected DB",
            "type" : "text"
        },{
            "key" :  "db_user",
            "label" : "Username",
            "type" : "text"
        },{
            "key" :  "db_pass",
            "label" : "Password",
            "type" : "password"
        },{
            "key" :  "db_pass_encr",
            "label" : "Encrypt password?",
            "type" : "checkbox"
        },{
            "key" :  "db_persistentconnection",
            "label" : "Use persistent connection?",
            "type" : "checkbox"
        },{
            "key" :  "db_test_connection",
            "label" : "Always test connection here?",
            "type" : "checkbox"
        }
    ]
');


$fc = new FileCacher();

$fileCacheKey = Config::ConfigKey;

//write empty object if does not yet exist
if(!$fc->exists($fileCacheKey)){
    $emtpyObj = new stdClass();
    $fc->put($fileCacheKey, $emtpyObj);
}

$configObj = $fc->get($fileCacheKey);


//save values
if($normReqVal && isset($normReqVal->save_values)){
    
    $isChanged = false;
    
    foreach($fieldList as $fldConfig){
        
        $key = $fldConfig->key;
        
        if(!isset($normReqVal) && $fldConfig->type == 'checkbox'){
            $normReqVal->{$key} = '';
        }
        
        if(isset($normReqVal->{$key})){
            $configObj->{$key} = $normReqVal->{$key};
            $isChanged = true;
        }
        
    }

    if($configObj->db_pass_encr == '1' && !empty($configObj->db_pass)) {
        $configObj->db_pass = Tools::encrypt($configObj->db_pass);
    }
    
    if($isChanged){
        $fc->put($fileCacheKey, $configObj);
    }
    
}


//output form
echo '<form action="?" method="post">';
    echo '<table>';

        foreach($fieldList as $fldConfig){
            $key = $fldConfig->key;
            $value = isset($configObj->{$key}) ? $configObj->{$key} : '';

            if(
                $key == 'db_pass' &&
                $configObj->db_pass_encr == '1' &&
                !empty($configObj->db_pass)
            ) {
                $value = '';
            }
            
            echo createField($fldConfig, $value);
        }


    echo '<table>';
    
    echo '<input type="hidden" name="save_values" value="1">';
    echo '<input type="submit" value="Save">';

echo '</form>';



//test connection
echo '<div style="margin-top: 10px; padding: 5px; border: 1px solid gray;">';
    echo '<form action="?" method="post">';

        echo '<input type="hidden" name="db_test_connection" value="1">';

        echo '<div>';
        if(isset($normReqVal->db_test_connection) && $normReqVal->db_test_connection){
            testConnection($configObj);
        }
        echo '</div>';

        echo '<input type="submit" value="Test connection">';
    echo '</form>';
echo '</div>';



Init::EndPage();

?>