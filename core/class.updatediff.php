<?php


include_once("../core/class.filecacher.php");
include_once("../core/class.diffobject.php");

/**
 * Description of class
 *
 * @author datvandecor
 */
class UpdateDiff {
    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;
    
    const ENUM_STATE_NODATE = 1;
    const ENUM_STATE_NOFILE = 2;
    const ENUM_STATE_ERROR_UNSERIALIZE = -1;
    
    private $fileCacher;
    
    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        $this->fileCacher = new FileCacher();
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
    
    
    static public function doUpdateCompareDatabaseList($fSourceKey, $fNewObject){
        $t = self::getInstance();
        
        $currentObject = $t->fileCacher->get($fSourceKey);
        
        $t->doUpdateCompare('databases', $currentObject, $fNewObject);
    }
    
    static public function doUpdateCompareDatabase($fSourceKey, $fNewObject){
        $t = self::getInstance();
        
        $currentObject = $t->fileCacher->get($fSourceKey);
        
        $t->doUpdateCompare($fSourceKey, $currentObject, $fNewObject);
    }
    
    public function doUpdateCompare($fContext, $fObjectA, $fObjectB){
        
        $diffArr = DiffObject::compare($fObjectA, $fObjectB);
        
        $comparePath = $this->getUpdateComparePath();
        $compareFileName = $this->getUpdateCompareFileName($fContext);
        
        $data = serialize($diffArr);
        
        if(!file_exists($comparePath)){
            mkdir($comparePath, 0777, true);
        }
        
        $fileFullPath = $comparePath . $compareFileName;
        
        file_put_contents($fileFullPath, $data);
    }
    
    static public function getCompareDiffArray($fDate, $fFileName, $fContext=false){
        $t = self::getInstance();

        $comparePath = $t->getUpdateComparePath($fDate);
        #$compareFileName = $t->getUpdateCompareFileName($fContext);
        $compareFileName = $fFileName;
        
        
        $fileFullPath = $comparePath . $compareFileName;
        
        if(!file_exists($comparePath)){
            return self::ENUM_STATE_NODATE;
        }
        
        if(!file_exists($fileFullPath)){
            return self::ENUM_STATE_NOFILE;
        }
        
        $diffArr = unserialize(file_get_contents($fileFullPath));
        
        if(!is_array($diffArr)){
            return self::ENUM_STATE_ERROR_UNSERIALIZE;
        }
        
        return $diffArr;
    }
    
    static public function getDateArray(){
        $updateDifferenceDataPath = Config::UpdateDifferenceDataPath;
                
        $dateArr = array();
        $dh  = opendir($updateDifferenceDataPath);
        
        while (false !== ($filename = readdir($dh))) {
            if(!Tools::startsWith($filename, '.')){
                $dateArr[] = $filename;
            }
        }
        
        return $dateArr;
    }
    
    static public function getFileArray($fDate){
        $updateDifferenceDataPath = Config::UpdateDifferenceDataPath . $fDate;
                
        $fileArr = array();
        $dh  = opendir($updateDifferenceDataPath);
        
        while (false !== ($filename = readdir($dh))) {
            if(!Tools::startsWith($filename, '.')){
                $fileArr[] = $filename;
            }
        }
        
        return $fileArr;
    }
    
    private function getUpdateComparePath($fDate=false){
        $date = false;
        
        if($fDate){
            $date = $fDate;
        }else if(defined(Config::UpdateDifferenceDateKey)){
            $date = constant(Config::UpdateDifferenceDateKey);
        }else{
            $date = date('Y-m-d');
            define(Config::UpdateDifferenceDateKey, $date);
        }        
        
        return sprintf('%s/%s/', Config::UpdateDifferenceDataPath, $date);
    }
    
    private function getUpdateCompareFileName($fContext){
        return sprintf('%s_%s.diff', $fContext, crc32($fContext));
    }
    

}
