<?php


class Database {
    private $mysqli;


    public function __construct($fHost, $fUser, $fPass, $fDatabaseName, $fPort = '', $fPersistentConnection = false) {
        if($fPersistentConnection == true) {
            $fHost = 'p:' . $fHost;
        }

        if($fPort == true) {
            $fHost = $fHost . ':' . (int) $fPort;
        }

        $this->mysqli = $this->CreateDatabaseConnection($fHost, $fUser, $fPass, $fDatabaseName);
    }

    public static function createConnectionFromConfig() {
        include_once("../config/config.php");
        include_once("../core/class.filecacher.php");

        $fc = new FileCacher();

        $fileCacheKey = Config::ConfigKey;

        $configObj = $fc->get($fileCacheKey);

        $CO = $configObj;
        $password = $CO->db_pass;

        if($CO->db_pass_encr == '1') {
            $password = Tools::decrypt($CO->db_pass);
        }

        $db = new Database($CO->db_host, $CO->db_user, $password, $CO->db_default_selected_db, $CO->db_port, (bool) $CO->db_persistentconnection);

        return $db;
    }

    public function CreateDatabaseConnection($fHost, $fUser, $fPass, $fDatabaseName = null) {
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

    protected function DoQuery($fQuery, $fResultModus = MYSQLI_USE_RESULT) {
        $msqlResult = mysqli_query($this->mysqli, $fQuery, $fResultModus);

        if($msqlResult === false) {
            Tools::debugError(sprintf('Error executing query: %s<br />Error msg: %s', $fQuery, mysqli_error($this->mysqli)));

            exit;
        }

        return $msqlResult;
    }

    #----------------------------------------------------------------------------
    # Retrieves all databases
    #----------------------------------------------------------------------------
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
     * @param $fDatabaseName
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
     * @param $fDatabaseName
     * @param $fTableName
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
     * @param $fDatabaseName
     * @param $fTableName
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

    #----------------------------------------------------------------------------
    # Close connection
    #----------------------------------------------------------------------------
    public function Close() {
        $this->mysqli->close();
    }
}
