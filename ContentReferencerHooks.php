<?php

if ( !defined( 'MEDIAWIKI' ) ){
    die( );
}

require_once ('ContentReferencerTableName.php');


class ContentReferencer {
    
    static function onParserFirstCallSetup( Parser &$parser ) {
        $parser->setHook( 'content', 'ContentReferencer::parse_content' );
        $parser->setHook( 'content-ref', 'ContentReferencer::parse_content_ref' );
    }
    
    // deletes all the references that it uses
    static function onArticleDelete( WikiPage &$article, User &$user, &$reason, &$error ) {
        
        
        try {
        
            $DB = wfGetDB(DB_MASTER); // get the DB
            $DB->begin(); // start a write transatction
            
            // delete the references
            $DB->delete(ContentReferencerTableName, "reference_page_name='" . $article->getTitle() . "'");
            
            $DB->commit(); // stop the transaction
        } catch (Exception $e) {
            
        }
        
    }
    
    static function parse_content( $input, array $args, Parser $parser, PPFrame $frame ) {
        // TODO: is there a better place to put the database insertion code? Maybe on page save is more approiate...
        // get the database
        $DB = wfGetDB(DB_MASTER);
        $DB->begin();
        
        // make sure that the table is defined--or else the user needs to run maintenance/SetupDatabase.php
        if(!$DB->tableExists(ContentReferencerTableName)) {
            die("Please run maintenance/SetupDatabase.php to setup the tables");
        }
        
        $ThisPageName = (string) $parser->getTitle();
        $ID = $args['id'];
        
        // see if the reference already exists and warn if it does
        try {
            $ExistingReference = $DB->select(ContentReferencerTableName, "reference_page_name", "reference_name='$ID'");
        } catch(Exception $e) {
            die($e->getMessage());
        }
        
        $shouldwrite = true;
        foreach($ExistingReference as $RefPageName) {
            $shouldwrite = false;
        }
        $DB->freeResult($ExistingReference);
        
        if($shouldwrite) {
            // write to the database
            try{
                $DB->insert(ContentReferencerTableName, array('reference_name' => $ID, 'reference_page_name' => $ThisPageName));
            } catch(Exception $e) {
                die($e->getMessage());
            }
        }
        
        $DB->commit();
        
        // return a id-ed span of the parsed input
        return Html::rawElement('span', array('id' => $ID), $parser->recursiveTagParse($input, $frame));
        
    }
    
    static function parse_content_ref( $input, array $args, Parser $parser, PPFrame $frame ) {
        
        // get the database
        $DB = wfGetDB(DB_SLAVE);
        
        // make sure that the table is defined--or else the user needs to run maintenance/SetupDatabase.php
        if(!$DB->tableExists(ContentReferencerTableName)) {
            die("Please run maintenance/SetupDatabase.php to setup the tables");
        }
        
        $ID = $args['id'];
        
        try {
            // select it from the database
            $refs = $DB->select(ContentReferencerTableName, "reference_page_name", "reference_name='$ID'");
        } catch (Exception $e) {
            die($e->getMessage());
        }
        
        $PageToReferenceTo = "";
        foreach($refs as $PageForRef) {
            $PageToReferenceTo = $PageForRef->reference_page_name;
        }
        $DB->freeResult($refs);
        
        $output = (string)"";
        
        if($PageToReferenceTo == "") {
            // if it is still empty, then it is an empty ref, so add it to a broken category.
            $output .= "[[Category:BrokenReference]]";
            $PageToReferenceTo = "How to fix a broken reference";
        }
        
        $output .= "[[" . $PageToReferenceTo . "#$ID|$input]]";
        
        return $parser->recursiveTagParse($output, $frame);
        
    }
}
