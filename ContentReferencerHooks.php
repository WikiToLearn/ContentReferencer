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
        
        $id = $args['id'];
        $name = $args['name'];
        
        // wrap in div
        $text = Html::rawElement( 'div', '' , $parser->recursiveTagParse( $input, $frame ) );
        
        $header = Html::rawElement( 'strong', '', "Figure $id: $name" . "<hr />" );
        $header = Html::rawElement( 'div', '', "$header" );
        
        $text = Html::rawElement( 'blockquote', array( 'id' => $id), $header . $text );
        
        return $text;
        
    }
    
    function parse_content_ref( $input, $args, Parser $parser, PPFrame $frame ) {
        
        $id = $args['id'];
        
        
        return $parser->recursiveTagParse('[[' . $args['page'] . "#$id" . '|' . $input . ']]');
        
        
        
        
        
    }
}

?>