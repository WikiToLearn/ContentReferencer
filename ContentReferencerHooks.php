<?php

if ( !defined( 'MEDIAWIKI' ) ){
    die( );
}

require_once ('ContentReferencerTableName.php');


class ContentReferencer {
    
    
    const GetLabelsRegex = "/<\\s*nowiki\\s*>.*<\\s*\\/\\s*nowiki\\s*>|<\\s*label\\s[^>]*label\\s*=\\s*([\"'](.*)[\"']|([\\w-]+))/";
    
    static function onParserFirstCallSetup( Parser &$parser ) {
        $parser->setHook( 'label', 'ContentReferencer::parse_label' );
        $parser->setHook( 'ref', 'ContentReferencer::parse_ref' );
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
            die($e->getMessage());
        }
        
    }
    
    // moves the references
    static function onSpecialMovepageAfterMove(MovePageForm &$form, Title &$oldTitle, Title &$newTitle) {
                
        try {
            
            $DB = wfGetDB(DB_MASTER);
            $DB->begin();
            
            $DB->update(ContentReferencerTableName, array('reference_page_name' => $newTitle), 
                array("reference_page_name='$oldTitle'"));
         
            $DB->commit();
         
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    
    static function onPageContentSave(
        WikiPage &$wikiPage, User &$user, Content &$content, &$summary, 
        $isMinor, $isWatch, $section, &$flags, Status &$status ) {
        
        // get the current tags from the page
        /////////////////////////////////////
        $CurrentReferences;
        {
            preg_match_all(self::GetLabelsRegex, $wikiPage->getText(), $matches);
            // build it as a "set"
            foreach(array_merge( $matches[2], $matches[3] ) as $toAdd) {
                if($toAdd != '')
                    $CurrentReferences[$toAdd] = 1;
            }
        }
        
        
        // get the old tags from the database
        /////////////////////////////////////
        $OldReferences;
        {
            if (($rev = $wikiPage->getRevision()) != null && ($prevRev = $rev->getPrevious()) != null ) {
                preg_match_all(self::GetLabelsRegex, $prevRev->getText(), $matches);// build it as a "set"
                foreach(array_merge( $matches[2], $matches[3] ) as $toAdd) {
                    if($toAdd != '')
                        $OldReferences[$toAdd] = 1;
                }
            }
        }
        
        // Resolve differences
        //////////////////////
        $DB = wfGetDB(DB_MASTER);        
        $DB->begin(); // start a transaction
        // start with the newOnes and make sure that the database is compatable, and remove them 
        foreach(array_keys($CurrentReferences) as $RefToMakeSureExists) {
            if(isset($OldReferences[$RefToMakeSureExists])) {
                // if it is already in the array, then we don't have to worry about it; delete it
                unset($OldReferences[$RefToMakeSureExists]);
            } else {
                // if it doesn't exist, we need to add it
                try {
                    $DB->insert(ContentReferencerTableName, 
                        array('reference_name' => $RefToMakeSureExists, 'reference_page_name' => (string)$wikiPage->getTitle()));
                } catch (Exception $e) {
                    die($e->getMessage());
                }
            }
        }
        
        
        
        // now, anything left in $OldReferences has been deleted. Let's remove it from the database
        foreach(array_keys($OldReferences) as $RefToDelete) {
            try {
                $DB->delete(ContentReferencerTableName, "reference_name='$RefToDelete'");
            } catch(Exception $e) {
                die($e->getMessage());
            }
        }
        
        $DB->commit(); // end the transaction
        
    }

    
    static function parse_label( $input, array $args, Parser $parser, PPFrame $frame) {        
        
        $ThisPageName = (string) $parser->getTitle();
        $ID = $args['label'];
        
        // return a id-ed span of the parsed input
        return Html::rawElement('span', array('id' => $ID), $parser->recursiveTagParse($input, $frame));
        
    }
    
    static function parse_ref( $input, array $args, Parser $parser, PPFrame $frame ) {
        
        // get the database
        $DB = wfGetDB(DB_SLAVE);
        
        // make sure that the table is defined--or else the user needs to run maintenance/SetupDatabase.php
        if(!$DB->tableExists(ContentReferencerTableName)) {
            die("Please run maintenance/SetupDatabase.php to setup the tables");
        }
        
        $ID = $args['label'];
        
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
