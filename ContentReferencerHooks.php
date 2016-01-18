<?php

if ( !defined( 'MEDIAWIKI' ) ){
    die( );
}

class ContentReferencer {
    
    function onParserFirstCallSetup( Parser $parser ) {
        
        $parser->setHook( 'content', 'ContentReferencer::parse_content' );
        $parser->setHook( 'content-ref', 'ContentReferencer::parse_content_ref' );
        
    }
    
    function parse_content( $input, $args, Parser $parser, PPFrame $frame ) {
                
        return Html::rawElement( 'div',  array(  "id"=>$args['id'] )  , $parser->recursiveTagParse( $input, $frame ) );
        
    }
    
    function parse_content_ref( $input, $args, Parser $parser, PPFrame $frame ) {
                
        return $parser->recursiveTagParse('[[' . $args['page'] . '#' . $args['id'] . '|' . $input . ']]');
        
    }
}

?>