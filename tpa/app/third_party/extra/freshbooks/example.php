<?php
//include particular file for entity you need (Client, Invoice, Category...)
include_once "library/FreshBooks/Client.php";

//you API url and token obtained from freshbooks.com
$url = "https://1wayit.freshbooks.com/api/2.1/xml-in";
$token = "5965bc72e4fd20590aba878d0702567a";

//init singleton FreshBooks_HttpClient
FreshBooks_HttpClient::init($url,$token);


//new Client object
$client = new FreshBooks_Client();

echo '<pre>';
//print_r($client);
//try to get client with client_id 3
if(!$client->get(112122)){
//no data - read error

	echo $client->lastError;
}
else{
//investigate populated data
	print_r($client);
}
?>