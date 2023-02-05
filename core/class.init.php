<?php

require_once '../config/config.php';
require_once '../core/class.tools.php';


/**
 * Description of class
 *
 * @author datvandecor
 */
class Init {
//put your code here
    public static function StartPage(){
        if(defined('PAGE_START_SET')){
            throw new Exception('Page start is already set');
        }
        
        include '../html/part.html.start.html';
        
        define('PAGE_START_SET', true);
    }
    
    public static function EndPage(){
        if(defined('PAGE_END_SET')){
            throw new Exception('Page end is already set');
        }
        
        include '../html/part.html.end.html';
        
        define('PAGE_END_SET', true);
    }
    
}
