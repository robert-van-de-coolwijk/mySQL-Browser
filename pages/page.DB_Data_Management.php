<?php

set_time_limit(60 * 60);

require_once '../core/class.init.php';

Init::StartPage();

include_once("../core/class.database.php");
include_once("../core/class.filecacher.php");

include_once("./funct.DB_Data_Management.php");

echo sprintf('<p>Script name: %s<br />%s<br />', __FILE__, date('Y-m-d H:i:s'));

$fc = new FileCacher();
#$db = Database::createConnectionFromConfig();
$dm = new DataManagement();

$configObj = $fc->get(Config::ConfigKey);

$databaseMetaObj = $dm->getAllDatabaseMetaDataFromCache();

echo '<h2>Databases</h2> ';
echo '<table>';
    echo '<tr>';
        echo '<th>DB name</th>';
        echo '<th>Size</th>';
        echo '<th>Last update date time</th>';
        echo '<th>Nr tables</th>';
        echo '<th> </th>';
    echo '</tr>';
    
foreach($databaseMetaObj->databaseList as $dbRow){
    echo '<tr>';
    
    #echo sprintf('<div>%s</div>', var_export($dbRow, true));
    echo sprintf('<td>%s</td>', $dbRow->name);
    echo sprintf('<td style="text-align: right;">%s</td>', Tools::formatBytes($dbRow->size));
    echo sprintf('<td>%s</td>', $dbRow->lastUpdated);
    echo sprintf('<td style="text-align: right;">%s</td>', $dbRow->numberOfTables);
    echo sprintf('<td><a href="?action=push_joblist&db=%s">%s</a></td>', $dbRow->name, 'Update');
    
    echo '</tr>';
}
echo '</table>';


//Show GUI
// Show known databases, databasenames file, functions and procedures file, last update date+time
//
/// Setting run jobs when added
/// Add Job - Get Database names
/// Add Job - Get functions / procedures
/// Add Job - Get [selected] database->tables information
//// Setting get tables sizes
/// Add All Jobs
/// Run Jobs

// Show jobs
/// Start time, progress (reported from job), status (cue, in progress, done, error)
//// Information about these are grabbed from there respective 



//Job run
// Push job file(s) to data/update_cue/waiting
/// Job file contains which function should be called with wich parameters
// Job runner gets started after all jobs that should be done are pushed on cue
/// Job runner moves the job file to data/update_cue/in_progress 
/// Job runner starts the job in a seperate PHP process on that file
//// The lose PHP process, recieves progress information from the function being run and writes that back to the job file
//// Every lose PHP process also has a ticker that writes a timestamp in to the memory cacher so the Job runner can check if it is still updating
/// When the job is done it is placed in to the data/update_cue/done folder




echo '<p>EO script</p>';

Init::EndPage();