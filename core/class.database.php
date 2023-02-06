<?php

/**
 * Database connection class
 * based on mysqli object oriented variant
 */
class Database {
    
    private $mysqli;

    protected function __construct($fHost, $fUser, $fPass, $fDatabaseName, $fPort = '', $fPersistentConnection = false) {
        if($fPersistentConnection == true) {
            $fHost = 'p:' . $fHost;
        }

        if($fPort == true) {
            $fHost = $fHost . ':' . (int) $fPort;
        }

        $this->mysqli = $this->CreateDatabaseConnection($fHost, $fUser, $fPass, $fDatabaseName);
    }

    /**
     * Returns this class initialized with values from the config
     * 
     * @return \Database
     */
    public static function createConnectionFromConfig() {
        include_once("../config/config.php");
        include_once("../core/class.filecacher.php");

        $fc = new FileCacher();

        $fileCacheKey = Config::ConfigKey;

        $configObj = $fc->get($fileCacheKey);

        $CO = $configObj;
        $password = $CO->db_pass;

        if(isset($CO->db_pass_encr) && $CO->db_pass_encr == '1') {
            $password = Tools::decrypt($CO->db_pass);
        }

        $db = new Database($CO->db_host, $CO->db_user, $password, $CO->db_default_selected_db, $CO->db_port, (bool) $CO->db_persistentconnection);

        return $db;
    }

    /**
     * Wrapper method for initializing the msqli object
     * 
     * @param string $fHost
     * @param string $fUser
     * @param string $fPass
     * @param string $fDatabaseName
     * @return mysqli
     */
    protected function CreateDatabaseConnection($fHost, $fUser, $fPass, $fDatabaseName = null) {
        $mysqli = mysqli_init();
        if(!$mysqli) {
            die('mysqli_init failed');
        }

        if(!$mysqli->options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 0')) {
            die('Setting MYSQLI_INIT_COMMAND failed');
        }

        if(!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
            die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
        }

        $conn = $mysqli->real_connect($fHost, $fUser, $fPass, $fDatabaseName);
        if($conn === false) {
            die(
                'Connect Error (' . mysqli_connect_errno() . ') '
                . mysqli_connect_error()
            );
        }

        echo sprintf('<p>DB connect success! [%s]</p>', $mysqli->host_info);

        return $mysqli;
    }

    /**
     * Execute given query and return the resultset or exit
     * 
     * @param string $fQuery
     * @param enuml $fResultModus
     * @return resultset
     */
    protected function DoQuery($fQuery, $fResultModus = MYSQLI_USE_RESULT) {
        $msqlResult = mysqli_query($this->mysqli, $fQuery, $fResultModus);

        if($msqlResult === false) {
            Tools::debugError(sprintf('Error executing query: %s<br />Error msg: %s', $fQuery, mysqli_error($this->mysqli)));

            exit;
        }

        return $msqlResult;
    }

    /**
     * Retrieves all databases
     * 
     * @return array
     */
    public function GetAllDatabaseNames() {
        $query = 'show databases';

        $msqlResult = $this->DoQuery($query);

        $resultData = mysqli_fetch_all($msqlResult);

        $msqlResult->close();

        //debug($resultData);

        $dbArr = [];
        foreach($resultData as $row) {
            $dbArr[] = $row[0];
        }


        return $dbArr;
    }

    /**
     * Get the table scheme and size of given table name
     * 
     * @param string $fDatabaseName
     * @return object
     */
    public function GetDatabaseDetails($fDatabaseName) {
        $query = sprintf(
            '
                SELECT 
                    table_schema                    as name,
                    ifnull(SUM(ifnull(data_length + index_length, 0)), 0) as size
                FROM information_schema.TABLES 
                WHERE table_schema = "%s"
                GROUP BY table_schema
            ',
            $fDatabaseName
        );

        $msqlResult = $this->DoQuery($query);

        $resultData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);


        $msqlResult->close();

        //debug($resultData);

        $dbObj = (object) $resultData[0];

        if(!isset($dbObj->size)) {
            $dbObj->size = 0;
        }


        return $dbObj;
    }

    /**
     * Gets a list of tables on given databasename
     * 
     * @param string $fDatabaseName
     * @return array
     */
    public function GetAllTableNamesFromDatabase($fDatabaseName) {
        //retrieve tables
        $query = sprintf('show tables from `%s`', $fDatabaseName);

        $msqlResult = $this->DoQuery($query);


        $resultData = mysqli_fetch_all($msqlResult);

//        debug($resultData);

        $msqlResult->close();

        //debug($resultData);

        $tableArr = [];
        foreach($resultData as $row) {
            $tableArr[] = $row[0];
        }


        return $tableArr;
    }

    /**
     * @param sting $fDatabaseName
     * @param string $fTableName
     * @return object
     */
    public function GetTableDetails($fDatabaseName, $fTableName) {
        $query = sprintf(
            " 
                SELECT 
                    TABLE_NAME as name,
                    TABLE_ROWS as numberOfRows, 
                    AVG_ROW_LENGTH as averageRowLength, 
                    CREATE_TIME as createDateTime, 
                    TABLE_COMMENT as comment
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '%s'
                AND   TABLE_NAME = '%s'
            ",
            $fDatabaseName,
            $fTableName
        );

        $msqlResult = $this->DoQuery($query);


        $resultData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        debug($resultData);

        $msqlResult->close();

        //debug($resultData);

        $tableDetailsObj = (object) $resultData[0];

        if(!$tableDetailsObj->numberOfRows) {
            $tableDetailsObj->numberOfRows = 0;
        }


        return $tableDetailsObj;
    }

    /**
     * @param string $fDatabaseName
     * @param string $fTableName
     * @return array
     */
    public function GetTableFieldDetails($fDatabaseName, $fTableName) {
        //retrieve tables
        $query = sprintf('desc `%s`.`%s`', $fDatabaseName, $fTableName);

        $msqlResult = $this->DoQuery($query);


        $resultData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        debug($resultData);

        $msqlResult->close();

        //debug($resultData);

        $fieldArr = [];
        foreach($resultData as $row) {
            $row = (object) $row;

            if(!isset($row->Default)) {
                $row->Default = '';
            }

            $fieldArr[] = $row;
        }


        return $fieldArr;
    }

    /**
     * Get an array describing the index on given table
     * 
     * @param string $fDatabaseName
     * @param string $fTableName
     * @return array
     */
    public function GetIndexes($fDatabaseName, $fTableName) {
        $query = sprintf("
        select 
            index_schema,
            index_name,
            group_concat(column_name order by seq_in_index) as index_columns,
            index_type,
            case non_unique
                when 1 
                then 'Not Unique'
                else 'Unique'
                end as is_unique,
            table_name
            from information_schema.statistics
            #where table_schema not in ('information_schema', 'mysql',
            #                           'performance_schema', 'sys')
            where table_schema = '%s'
            and   table_name = '%s'
            group by index_schema,
                     index_name,
                     index_type,
                     non_unique,
                     table_name
            order by index_schema,
                     index_name;
        ", $fDatabaseName, $fTableName);

//        Tools::debug($query);

        $msqlResult = $this->DoQuery($query);

        $resultSetData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        Tools::debug($resultSetData);

        $msqlResult->close();

        //Tools::debug($resultData);

        $resArr = [];
        foreach($resultSetData as $row) {
            $resArr[] = (object) $row;
        }


        return $resArr;
    }

    /**
     * Retrieves a list of all functions on the whole SQL server
     * 
     * @return array
     */
    public function GetFunctions() {
        $query = sprintf('SHOW FUNCTION STATUS');

        $msqlResult = $this->DoQuery($query);


        $resultSetData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        Tools::debug($resultData);

        $msqlResult->close();

        //Tools::debug($resultData);

        $resArr = [];
        foreach($resultSetData as $row) {
            $resArr[] = (object) $row;
        }


        return $resArr;
    }

    /**
     * Retrieves a list of all stored procedures on the whole SQL server
     * 
     * @return array
     */
    public function GetProcedures() {
        $query = sprintf('SHOW PROCEDURE STATUS');

        $msqlResult = $this->DoQuery($query);


        $resultSetData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        debug($resultData);

        $msqlResult->close();

        //debug($resultData);

        $resArr = [];
        foreach($resultSetData as $row) {
            $resArr[] = (object) $row;
        }


        return $resArr;
    }

    /**
     * Gets a dump of the create command of given function
     * 
     * @param string $fDatabaseName
     * @param string $fFnName
     * @param string $fBaseObject
     * @return object
     */
    public function GetFunctionDetails($fDatabaseName, $fFnName, $fBaseObject) {
        $query = sprintf('SHOW CREATE FUNCTION `%s`.`%s`', $fDatabaseName, $fFnName);

        $msqlResult = $this->DoQuery($query);


        $resultSetData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        debug($resultData);

        $msqlResult->close();

        //debug($resultData);

        $functionDetailsObj = (object) $resultSetData[0];


        //get params
        $alterStatement = $functionDetailsObj->{'Create Function'};

        $fnNamePos = strpos($alterStatement, $fFnName);
        $fnHeaderOpen = strpos($alterStatement, '(', $fnNamePos);
        $fnHeaderClose = strpos($alterStatement, ') RETURNS', $fnHeaderOpen);

        $headerSubStr = substr($alterStatement, $fnHeaderOpen + 1, $fnHeaderClose - $fnHeaderOpen - 1);


        //get return type
        $fnReturnPos = strpos($alterStatement, 'RETURNS');
        $fnReturnOpen = strpos($alterStatement, ' ', $fnReturnPos);
        $fnReturnClose = strpos($alterStatement, "\n", $fnReturnOpen);

        $returnSubStr = substr($alterStatement, $fnReturnOpen, $fnReturnClose - $fnReturnOpen);


        $fBaseObject->Params = Tools::CleanString($headerSubStr);
        $fBaseObject->ReturnType = Tools::CleanString($returnSubStr);
        $fBaseObject->AlterStatement = $alterStatement;


        return $fBaseObject;
    }

    /**
     * Gets a dump of the create command of given stored procedure
     * 
     * @param string $fDatabaseName
     * @param string $fProcedureName
     * @param string $fBaseObject
     * @return object
     */
    public function GetProcedureDetails($fDatabaseName, $fProcedureName, $fBaseObject) {
        //databases to skip (which will fail the query)
        $skipDB = ['Print', 'VIEW'];

        if(in_array($fDatabaseName, $skipDB)) {
            $fBaseObject->Params = '';
            $fBaseObject->AlterStatement = '';

            return $fBaseObject;
        }

        //query for retrieving procedure details
        $query = sprintf('SHOW CREATE PROCEDURE `%s`.`%s`', $fDatabaseName, $fProcedureName);

        //Tools::debug($query);

        $msqlResult = $this->DoQuery($query);


        $resultSetData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        Tools::debug($resultData);

        $msqlResult->close();

        //Tools::debug($resultData);

        $functionDetailsObj = (object) $resultSetData[0];


        //get params
        $alterStatement = $functionDetailsObj->{'Create Procedure'};

        $fnNamePos = strpos($alterStatement, $fProcedureName);
        $fnHeaderOpen = strpos($alterStatement, '(', $fnNamePos);
        $fnHeaderClose = strpos($alterStatement, ")\nBEGIN", $fnHeaderOpen);


        $headerSubStr = substr($alterStatement, $fnHeaderOpen + 1, $fnHeaderClose - $fnHeaderOpen - 1);

        //Tools::debug($headerSubStr);

        //get return type
//        $fnReturnPos = strpos($alterStatement, 'RETURNS');
//        $fnReturnOpen = strpos($alterStatement, ' ', $fnReturnPos);
//        $fnReturnClose = strpos($alterStatement, "\n", $fnReturnOpen);
//        
//        $returnSubStr = substr($alterStatement, $fnReturnOpen, $fnReturnClose - $fnReturnOpen);


        $fBaseObject->Params = Tools::CleanString($headerSubStr);
//        $fBaseObject->ReturnType = Tools::CleanString($returnSubStr);
        $fBaseObject->AlterStatement = $alterStatement;


        return $fBaseObject;
    }

    /**
     * Gives a list of connection produced by the processlist command
     * 
     * @return type
     */
    public function GetConnectionStats() {
        $query = sprintf('SHOW FULL PROCESSLIST');

        $msqlResult = $this->DoQuery($query);


        $resultSetData = mysqli_fetch_all($msqlResult, MYSQLI_ASSOC);

//        debug($resultData);

        $msqlResult->close();

        //debug($resultData);

        $resArr = [];
        foreach($resultSetData as $row) {
            $resArr[] = (object) $row;
        }


        return $resArr;
    }
    
    /**
     * Close connection
     * 
     * @return void
     */
    public function Close() {
        $this->mysqli->close();
    }
}
