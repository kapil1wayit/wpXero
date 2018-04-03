<?php
include_once ($_SERVER['DOCUMENT_ROOT']."/freshbooks/samples/include/init.dist.php");

//include particular file for entity you need (Client, Invoice, Category...)
include_once ($_SERVER['DOCUMENT_ROOT']."/freshbooks/library/FreshBooks/Client.php"); 

$clientId = 112122;

//new Client object
$client = new FreshBooks_Client();

//try to get client with client_id $clientId
if(!$client->get($clientId)){
//no data - read error
	echo $client->lastError;
}
else{
//investigate populated data
	print_r($client);
}