<?php

include_once('class.pcshm.php');

class MemoryCacherMode_shmop extends MemoryCacher implements MemoryCacherInterface  {
    
    private $pcshmObject;
    
    public function __construct() {
        //$this->pcshmObjectArr = array();
        $this->pcshmObject = new pc_Shm('../data/');
        
        parent::__construct();
    }
    
//    private function initShmop($fKey){
//        if(isset($this->pcshmObjectArr[$fKey])){
//            return $this->pcshmObjectArr[$fKey];
//        }
//        $pcshmObj = new pc_Shm();
//        
////        $pcshmObj->open($fKey);
//        
//        $this->pcshmObjectArr[$fKey] = $pcshmObj;
//    }
    
    public function put($fContextString, $fData, $fDebug = true){
        $key = self::getContextPath($fContextString);
//        $pcshmObj = $this->initShmop($key);
        
        //$serializedData = json_encode($fData);
        $serializedData = self::serialize($fData);
        
        $this->pcshmObject->save($key, $serializedData);
        
        
        parent::put($fContextString, $fData, $fDebug);
    }
    
    public function exists($fContextString){
        
    }
    
    public function get($fContextString){
        $key = self::getContextPath($fContextString);
//        $pcshmObj = $this->initShmop($key);
        
        $serializedData = $this->pcshmObject->fetch($key);

        
        $serializedDataTemp = trim($serializedData);
        

        
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
