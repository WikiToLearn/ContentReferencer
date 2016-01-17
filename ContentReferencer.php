<?php

if (!defined('MEDIAWIKI')){
    die();
}

if(function_exists('wfLoadExtension')) {
    wfLoadExtension('ContentReferencer');
    
    wfWarn( "Deprecated entry point to ContentReferencer. Please use wfLoadExtension('ContentReferencer').");
    
}
else
{
    die("MediaWiki version 1.25+ is required to use the ContentReferencer extension");
}

?>