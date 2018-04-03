<?php
/**
 * Copy this file as init.php and update url and token
 */

define("LIB_PATH", realpath(dirname(__FILE__) . '/../../library'));
include_once LIB_PATH . '/FreshBooks/HttpClient.php';
//you API url and token obtained from freshbooks.com
$url = "https://1wayit.freshbooks.com/api/2.1/xml-in";
$token = "5965bc72e4fd20590aba878d0702567a";

//init singleton FreshBooks_HttpClient
FreshBooks_HttpClient::init($url,$token);
