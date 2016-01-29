<?php

// load the database name
require_once("../ContentReferencerTableName.php");

// find the MediaWiki install
define("MWEntryPoint", "/maintenance/commandLine.inc");

// Check the MW_INSTALL_PATH environment variable, to see if it is set.  If so,
// this is the root folder of the MediaWiki installation.
$MWPath = getenv("MW_INSTALL_PATH");

if ($MWPath === false) {
    
    // if it is not set, then assume the default location
    $MWPath = dirname(__FILE__) . "/../../..";

    // if the guess was wrong, then die
    if (!file_exists($MWPath . MWEntryPoint)) {
        die("Unable to locate MediaWiki installation.  "
            . "Try setting the MW_INSTALL_PATH environment variable to the mediawiki folder.\n");
    }
} elseif (!file_exists($MWPath . MWEntryPoint)) {
    // if it was set and the maintence directory still can't be found, then die
    die("MediaWiki not found at MW_INSTALL_PATH (" . $MWPath . ").\n");
}
    
// If we get here, then MediaWiki was found, so load Maintenance.php
require_once($MWPath . MWEntryPoint);

if(!ExtensionRegistry::getInstance()->isLoaded('ContentReferencer')) {
    die("This script requries that the ContentReferencer extension is loaded. 
    Please add `wfLoadExtension('ContentReferencer')` to your LocalSettings.php file.");
}

// load the database
wfWaitForSlaves(false);
$DB =& wfGetDB(DB_MASTER);

// check if the ContentReferencer table exists
$hasContentRefTable = $DB->tableExists(ContentReferencerTableName);

if(!$hasContentRefTable) {
    
    echo("Creating table \"" . ContentReferencerTableName . "\".\n");

    // now we need to actually create the table
    $DB->query(
    "
    CREATE TABLE IF NOT EXISTS " . ContentReferencerTableName  . "(
        reference_name varchar(255),
        reference_page_name varchar(255),
        
        PRIMARY KEY (reference_name)
    )
    ");
}
else {
    echo("Table \"" . ContentReferencerTableName . "\" already exists, not creating.\n");
}

