<?php
/**
 * UNFINISHED CLASS
 * The intention of this class is to provide memory sharing/caching
 * 
 */

include_once('../core/class.filecacher.php');

include_once('../core/class.memorycacher_file.php');
include_once('../core/class.memorycacher_apc.php');
include_once('../core/class.memorycacher_shmop.php');


abstract class MemoryCacher {

    const SnapshotPreFix = 'Snapshot_';
    const MemoryProfilerPreFix = 'MemoryProfiler_';
    
    const MemoryCacherMode_file = 'file';
    #const MemoryCacherMode_file_on_ramdisk = 'ramfile';
    const MemoryCacherMode_shmop = 'shmop';
    const MemoryCacherMode_apc = 'apc';
    
    const DebugMode = false;
    const TraceMemoryUsage = true;
    
    public function __construct(){
//        if(self::TraceMemoryUsage){
//            $this->MemoryTraceArray = array();
//        }
        
    }
    
    public function put($fContextString, $fData, $fDebug = true){
        
        if(self::TraceMemoryUsage){
            $fileCacher = new FileCacher();
            $memoryProfilerKey = self::getMemoryProfilerKey();
            $memoryTraceArray = new stdClass();
            
            if($fileCacher->exists($fContextString)){
                $memoryTraceArray = $fileCacher->get($memoryProfilerKey);
            }
            
            $serializedData = json_encode($fData);
            $memoryTraceArray->{$fContextString} = mb_strlen($serializedData) * 8;            

            $fileCacher->put($memoryProfilerKey, $memoryTraceArray);
        }
        
    }
    
    public function getMemoryProfilerReport(){
        $fileCacher = new FileCacher();
        $memoryProfilerKey = self::getMemoryProfilerKey();
        $data = $fileCacher->get($memoryProfilerKey);
        
        return '<pre>' . var_export($data , true) . '</pre>';
    }
    
    static public function getSupportMemoryCacherModes(){
        return array(
            self::MemoryCacherMode_file,
            self::MemoryCacherMode_shmop,
            self::MemoryCacherMode_apc
        );
    }
    
    static public function getMemoryCacherObject() {
        $memoryCacheObject = null;
        
        $cacheMode = Config::DefaultMemoryCacherMode;
        
        switch($cacheMode){
            
            case self::MemoryCacherMode_shmop:
                $memoryCacheObject = new MemoryCacherMode_shmop();
                break;
            case self::MemoryCacherMode_apc:
                $memoryCacheObject = new MemoryCacherMode_apc();
                break;
            case self::MemoryCacherMode_file:
            default:
                $memoryCacheObject = new MemoryCacherMode_file();
        }
        
        
        return $memoryCacheObject;
    }
    
    static protected function serialize($fData){
        return serialize($fData);
    }
    
    static protected function unserialize($fData){
        return unserialize($fData);
    }
    
    static protected function getContextPath($fContextString){
        $crc = crc32($fContextString);
        $path = $fContextString . '_' . $crc;
        
        return $path;
    }
    
    static protected function snapshotToFileCacher(MemoryCacher $fMemcached, $fKey){
        $fileCacher = new FileCacher();
        $snapshotKey = self::getSnapshotKey($fKey);
                
        $data = $fMemcached->get($fKey);
        
        $fileCacher->put($snapshotKey, $data);
    }
    
    static protected function restoreFromFileCacher(MemoryCacher $fMemcached, $fKey){
        $fileCacher = new FileCacher();
        $snapshotKey = self::getSnapshotKey($fKey);
        
        $data = $fileCacher->get($snapshotKey);
                
        $fMemcached->put($fKey, $data);
    }
    
    static protected function getSnapshotKey($fKey){
        return self::SnapshotPreFix . $fKey;
    }
    
    static protected function getMemoryProfilerKey(){
        return self::MemoryProfilerPreFix;
    }
    
}


interface MemoryCacherInterface{
    
    public function put($fContextString, $fData, $fDebug = true);
    
    public function exists($fContextString);
    
    public function get($fContextString);
    
}