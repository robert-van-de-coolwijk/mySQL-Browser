<?php


class MemoryCacherMode_file extends MemoryCacher implements MemoryCacherInterface  {
    
    private $fileCacherObject;
    
    const ContextPrefix = 'MCF_';
    
    public function __construct() {
        include_once "class.filecacher.php";
        
        $this->fileCacherObject = new FileCacher();
    }
    
    public function put($fContextString, $fData, $fDebug = true){
        $contextPath = $this->prefixContextPath($fContextString);
        $this->fileCacherObject->put($contextPath, $fData, $fDebug);
    }
    
    public function exists($fContextString){
        $contextPath = $this->prefixContextPath($fContextString);
        
        return $this->fileCacherObject->exists($contextPath);
    }
    
    public function get($fContextString){
        $contextPath = $this->prefixContextPath($fContextString);
        
        return $this->fileCacherObject->get($contextPath);
    }
    
    private function prefixContextPath($fContextString){
        $path = self::ContextPrefix . $fContextString;
        
        return $path;
    }
    
}
