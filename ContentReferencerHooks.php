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
        
        $ret = Html::rawElement( 'div',  array(  "id"=>$args['id'] ),  $input );
        
        $ret .= "<data table='content-ref' fields=id,link>" . $args['id'] . ',' . $parser->getTitle() . "</data>";
        
        return $parser->recursiveTagParse( $ret, $frame );
    }
    
    function parse_content_ref( $input, $args, Parser $parser, PPFrame $frame ) {
                
        $id = $args['id'];
        
        $exists = strpos($parser->recursiveTagParseFully( "<repeat table='content-ref' criteria='id=$id'>123abctestxyz</repeat>", $frame ), '123abctestxyz') !== false;
        
        
        if (!$exists) {
            return "REFERENCE TO TAG $id doesn't exist " . $parser->recursiveTagParse('[[Category:Broken Reference]]');
        }
        
        return $parser->recursiveTagParse( "<repeat table='content-ref' criteria='id=$id'>[[{{{link}}}#$id|$input]]</repeat>", $frame );

        
    }
}

?>