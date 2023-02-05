<?php

require_once '../core/class.encrypt.php';

class Tools {
    
    public static $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB'); 
    
    function __construct(){
        throw new Exception('This class should not be initiated throug NEW. It is filled with static functions');
    }
    
    public static function GetTimeStamp(){
        return date('Y-m-d H:i:s');
    }

//    public static function GetNewIV(){
//        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
//        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
//
//        return $iv;
//    }
//
//    /**
//     * Returns an encrypted & utf8-encoded
//     */
//    public static function encrypt($pure_string, $encryption_key) {
//        $iv = self::GetNewIV();
//
//        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, serialize($pure_string), MCRYPT_MODE_ECB, $iv);
//
//        $base64_encrypted_string = base64_encode($encrypted_string);
//
//        return $base64_encrypted_string;
//    }
//
//    /**
//     * Returns decrypted original string
//     */
//    public static function decrypt($base64_encrypted_string, $encryption_key) {
//        $iv = self::GetNewIV();
//
//        $encrypted_string = base64_decode($base64_encrypted_string);
//        $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
//
//        return unserialize($decrypted_string);
//    }



    /**
     * Returns an encrypted & utf8-encoded
     */
    public static function encrypt($pure_string, $encryption_key = null) : string {

        if(empty($encryption_key)) {
            $encryption_key = Encrypt::GetEncryptionKey();
        }

        $encrypted_string = Encrypt::encrypt($pure_string, $encryption_key);

        $base64_encrypted_string = base64_encode($encrypted_string);

        return $base64_encrypted_string;
    }

    /**
     * Returns decrypted original string
     */
    public static function decrypt($base64_encrypted_string, $encryption_key = null) {

        if(empty($encryption_key)) {
            $encryption_key = Encrypt::GetEncryptionKey();
        }

        $encrypted_string = base64_decode($base64_encrypted_string);

        $decrypted_string = Encrypt::decrypt($encrypted_string, $encryption_key);

        //return unserialize($decrypted_string);
        return $decrypted_string;
    }


    public static function debug(){
        $bt = debug_backtrace();
        $caller = array_shift($bt);

        $argsArr = func_get_args();
        $argOut = '';

        foreach($argsArr as $arg){
            $argOut .= sprintf('<div style="border: 1px solid #333333;">%s</div>', var_export($arg, true));
        }

        echo sprintf(
            '<p>
                %s [Ln %s]<br />
                <pre>%s</pre>
            </p>',
            $caller['file'],
            $caller['line'],
            $argOut
        );

    }

    public static function debugError(){
        $bt = debug_backtrace();
        $caller = array_shift($bt);

        $argsArr = func_get_args();
        $argOut = '';

        foreach($argsArr as $arg){
            $argOut .= sprintf('%s\n', var_export($arg, true));
        }

        echo sprintf(
            '<div style="border: 1px solid #FF2222; color: #BB0000">
                %s [Ln %s]<br />
                <pre>%s</pre>
            </div>',
            $caller['file'],
            $caller['line'],
            $argOut
        );
    }

    public static function debugFilePath($fPath){
        $out = sprintf(
            '
                <div>
                    Path: %s<br />
                    Real path: %s<br />
                    <b>Exists</b>: %s, <b>read</b>: %s, <b>write</b>: %s
                </div>
            ',
            $fPath,
            realpath($fPath),
            var_export(file_exists($fPath), true),
            var_export(is_readable($fPath), true),
            var_export(is_writable($fPath), true)
        );

        echo $out;
    }

    public static function echoNow($fInput){
        echo $fInput;

        flush();
        ob_flush();
    }

    public static function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }

    public static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }
    
    public static function formatBytes($bytes, $precision = 2) { 
        if(!is_numeric($bytes) || $bytes == 0){
            return $bytes;
        }
        
        $base = log($bytes, 1024);
        
        return round(pow(1024, $base - floor($base)), $precision) . self::$units[floor($base)];
    } 
    
    public static function cleanString($fInput){
        $fInput = trim($fInput);
        
        $fInput = str_replace(
                array('\n',     '\r'), 
                array(' ',      ''), 
                $fInput
            );
        
                
        return $fInput;
    }

    public static function buildHtmlTable($array){
        if(!is_array($array) || count($array) == 0) {
            return '';
        }

        // start table
        $html = '<table>';
        // header row
        $html .= '<tr>';
        foreach($array[0] as $key=>$value){
            $html .= '<th>' . htmlspecialchars($key) . '</th>';
        }
        $html .= '</tr>';

        // data rows
        foreach( $array as $key=>$value){
            if(is_object($value)){
                $value = (array)$value;
            }

            $html .= '<tr>';
            foreach($value as $key2=>$value2){
                $html .= '<td>' . htmlspecialchars($value2) . '</td>';
            }
            $html .= '</tr>';
        }

        // finish table and return it

        $html .= '</table>';
        return $html;
    }
}