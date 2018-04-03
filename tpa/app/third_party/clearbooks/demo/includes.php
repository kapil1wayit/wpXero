<?php

		
spl_autoload_register( function( $class ) {
	echo '<pre>';
	print_r($class);
    if ( strpos( $class, 'Clearbooks_' ) !== 0 ) {
        return false;
    }
    require_once __DIR__ . '/../src/' . str_replace( '_', '/', $class ) . '.php';
    return true;
} );
$client = new \Clearbooks_Soap_1_0( 'demo' );

print_r($client);