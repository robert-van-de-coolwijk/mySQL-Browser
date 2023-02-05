<?php

set_time_limit(60 * 60);

require_once '../core/class.init.php';

include_once("../core/class.database.php");
include_once("../core/class.filecacher.php");


class DataManagement{
    
    private $fileCacher = null;
    private $database = null;
    
    public function __construct() {
        $this->fileCacher = new FileCacher();
        $this->database = Database::createConnectionFromConfig();
    }
    
    /// Cache retrieve functions \\\
        
    public function getDatabaseNamesFromCache(){
        return $this->fileCacher->get('databases');
    }
    
    public function getAllDatabaseMetaDataFromCache(){
        
        $databaseList = $this->getDatabaseNamesFromCache();
        

        
        $dbMetaList = array();
        foreach($databaseList->databaseList as $dbName){
            $databaseCacheKey = $this->getDatabaseCacheKey($dbName);
            
            $dbObj = $this->fileCacher->get($databaseCacheKey);
            
            $dbObj->ageInSeconds = time() - strtotime($dbObj->lastUpdated);
            $dbObj->numberOfTables = count($dbObj->tableList);
            unset($dbObj->tableList);
            
            $dbMetaList[] = $dbObj;
        }
        
        $resultObj = new stdClass();
        $resultObj->lastUpdated = $databaseList->lastUpdated;
        $resultObj->databaseList = $dbMetaList;
        
        return $resultObj;
    }

    private function getDatabaseCacheKey($fDatabaseName){
        return 'database_' . $fDatabaseName;
    }
    
    
    /// cache update/fill related functions \\\
    
    /**
     * 
     * @param object $db Database object
     * @param object $fc FileCacher object
     * @return array DB names
     */
    function updateAllDatabaseNames(Database $db, FileCacher $fc){
        //retrieve all databases
        $dbArr = $db->GetAllDatabaseNames();

        //debug($dbArr);

        //build db cache
        $databasesCacheObj = new stdClass();
        $databasesCacheObj->lastUpdated = Tools::GetTimeStamp();
        $databasesCacheObj->databaseList = $dbArr;

        //save db cache
        $fc->put('databases', $databasesCacheObj);

        return $dbArr;
    }



    /**
     * 
     * @param Database $db
     * @param FileCacher $fc
     * @param type $fDatabaseName
     */
    function updateDatabaseDetails(Database $db, FileCacher $fc, $fDatabaseName){

        //retrieve all table names
        $tableNamesArr = $db->GetAllTableNamesFromDatabase($fDatabaseName);
        $tableDetailsList = array();


        foreach($tableNamesArr as $tableName){
            $tableObject = $db->GetTableDetails($fDatabaseName, $tableName);
            $tableObject->fieldList = $db->GetTableFieldDetails($fDatabaseName, $tableName);

            $tableDetailsList[] = $tableObject;
        }

        $databaseCacheObj = $db->GetDatabaseDetails($fDatabaseName);
        $databaseCacheObj->lastUpdated = Tools::GetTimeStamp();
        $databaseCacheObj->name = $fDatabaseName;
        $databaseCacheObj->tableList = $tableDetailsList;

        $dbContext = $this->getDatabaseCacheKey($fDatabaseName);
        $fc->put($dbContext, $databaseCacheObj);

        return $tableDetailsList;

    }

    function updateAllFunctions(Database $db, FileCacher $fc){
        //retrieve all databases
        $fnArr = $db->GetFunctions();

        //debug($dbArr);

        //build db cache
        $databasesCacheObj = new stdClass();
        $databasesCacheObj->lastUpdated = Tools::GetTimeStamp();


        foreach($fnArr as $row){
            $Db = $row->Db;
            $fnName = $row->Name;

            $db->GetFunctionDetails($Db, $fnName, $row);
        }

        $databasesCacheObj->functionList = $fnArr;

        //save db cache
        $fc->put('functions', $databasesCacheObj);

        return $fnArr;
    }

    function updateAllProcedures(Database $db, FileCacher $fc){
        //retrieve all databases
        $prArr = $db->GetProcedures();

        //debug($dbArr);

        //build db cache
        $databasesCacheObj = new stdClass();
        $databasesCacheObj->lastUpdated = Tools::GetTimeStamp();

        foreach($prArr as $row){
            $Db = $row->Db;
            $procName = $row->Name;

            $db->GetProcedureDetails($Db, $procName, $row);
        }

        $databasesCacheObj->procedureList = $prArr;

        //save db cache
        $fc->put('procedures', $databasesCacheObj);

        return $prArr;
    }

}

class DataManagementJobs{
    
    public function getJobList(){
        
    }
    
    public function getJobStatus(){
        
    }
    
    public function startJob(){
        
    }
    
    public function pauseJobCue(){
        
    }
    
    public function unpauseJobCue(){
        
    }
    
}


if(false){

    /// EO FUNCTIONS \\\

    $fc = new FileCacher();
    $db = Database::createConnectionFromConfig();

    $configObj = $fc->get(Config::ConfigKey);

    //update all database names
    Tools::echoNow('<br /><br /><b>Indexing databases:</b>');

    $dbArr = updateAllDatabaseNames($db, $fc);

    #debug($dbArr);
    Tools::echoNow(sprintf('<br />Found: %s<br />', count($dbArr)));



    //get functions
    Tools::echoNow('<br /><br /><b>Indexing functions:</b>');

    $functionArr = updateAllFunctions($db, $fc);

    Tools::echoNow(sprintf('<br />Found: %s<br />', count($functionArr)));



    //get procedure
    Tools::echoNow('<br /><br /><b>Indexing procedures:</b>');

    $procedureArr = updateAllProcedures($db, $fc);

    Tools::echoNow(sprintf('<br />Found: %s<br />', count($procedureArr)));



    //grab tables
    Tools::echoNow('<br /><b>Indexing table information:</b>');

    $dbObj;
    $dbToSkipp = array(
    //    'information_schema'
    );

    foreach($dbArr as $dbName){

        if(in_array($dbName, $dbToSkipp)){
            echoNow(sprintf('<br /><i>DB skipped: %s</i>', $dbName));
            continue;
        }

        $dbObj = new stdClass();
        $dbObj->name = $dbName;
        $dbObj->lastUpdated = Tools::GetTimeStamp();

        $dbObj->tableDataList = getDatabaseDetails($db, $fc, $dbName);

        //debug
        Tools::echoNow(sprintf('<br />DB: <b>%s</b> - tc: %s', $dbName, count($dbObj->tableDataList)));

        foreach($dbObj->tableDataList as $tableObj){
            Tools::echoNow(sprintf('<br />- Table: <b>%s</b> - fc: %s', $tableObj->name, count($tableObj->fieldList)));
        }
    }

    //close databases
    //$db->Close();

}
