<?php

class FileCacher {

    public function __construct() {
        $path = Config::FileCacherDataPath;
        
//        echo '<br />Init FileCacher';
//        Tools::debugFilePath($path);
//        echo '<br />Done (Init FileCacher)';
    }
    
    public function put($fContextString, $fData, $fDebug = true){
        $path = $this->getContextPath($fContextString);
        
        if($fDebug){
            Tools::debugFilePath($path);
        }
        
        $serializedData = json_encode($fData);
        
        file_put_contents($path, $serializedData);
        
        if($fDebug){
            Tools::debug('FileCacher.put() - Write file ok<br />' . $path);
        }
    }
    
    public function exists($fContextString){
        $path = $this->getContextPath($fContextString);
        
        return file_exists($path);
    }
    
    public function get($fContextString){
        $path = $this->getContextPath($fContextString);
        //Tools::debugFilePath($path);
        
        $serializedData = file_get_contents($path);
        
        $fData = json_decode($serializedData);
        
        return $fData;
    }
    
    private function getContextPath($fContextString){
        $crc = crc32($fContextString);
        $path = Config::FileCacherDataPath . '/' . $fContextString . '_' . $crc . '.json';
        
        return $path;
    }
    
}
