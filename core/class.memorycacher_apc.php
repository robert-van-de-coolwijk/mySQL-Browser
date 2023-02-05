<?php


class MemoryCacherMode_apc extends MemoryCacher implements MemoryCacherInterface  {
    
    private $apcObject;
    
    public function __construct() {
//        $this->apcObject = new pc_Shm('../data/');
    }
    
    
    public function put($fContextString, $fData, $fDebug = true){
        $key = self::getContextPath($fContextString);
//        $pcshmObj = $this->initShmop($key);
        
        //$serializedData = json_encode($fData);
        $serializedData = self::serialize($fData);
        
        apc_store($key, $serializedData);
    }
    
    public function exists($fContextString){
        $key = self::getContextPath($fContextString);
        
        return apc_exists($key);
    }
    
    public function get($fContextString){
        $key = self::getContextPath($fContextString);
//        $pcshmObj = $this->initShmop($key);
        
        $serializedData = apc_fetch($key);

        
//        $serializedDataTemp = trim($serializedData);
        

        
        $data = self::unserialize($serializedDataTemp);
        
        if(self::DebugMode == true){
            echo '<pre style="border: 1px solid red;">';
            echo var_dump($serializedData);
            echo '</pre>';

            echo '<pre style="border: 1px solid blue;">';
            echo var_dump($serializedDataTemp);
            echo '</pre>';

            echo '<pre style="border: 1px solid green;">';
            echo var_dump($data);
            echo '</pre>';
        }
        
        return $data;
    }
    
}
