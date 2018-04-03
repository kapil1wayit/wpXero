<?php

require('client.php');
require('./GrantType/IGrantType.php');
require('./GrantType/AuthorizationCode.php');
const CLIENT_ID     = 'KBzKzbK-oao2bg6Et2Ze9g';
const CLIENT_SECRET = 'akgvURVNcb-anpqOKDodAA';

const REDIRECT_URI           = 'http://localhost/freea/src/OAuth2';
const AUTHORIZATION_ENDPOINT = 'https://api.freeagent.com/v2/approve_app';
const TOKEN_ENDPOINT         = 'https://api.freeagent.com/v2/token_endpoint';
$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET); 
echo '<pre>';

if (!isset($_GET['code']) && !isset($_GET['token']) )
{
    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URI);
    header('Location: ' . $auth_url);
    die('Redirect');
}elseif (isset($_GET['code']))
{
	$params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
        $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', $params);
	$result=json_decode($response['result'],true); // old version when json was returned
	$access=$response['result']['access_token'];
	header('Location: http://localhost/freea/src/OAuth2?token=' . $access);
	exit();
}elseif (isset($_GET['token']))
{
	$access=$_GET['token'];
	$client->setAccessToken($access);

        $info = $client->fetch('https://api.freeagent.com/v2/contacts',
	  array(), 
          'GET', 
          array('Authorization' => 'Bearer '. $access,'User-Agent' => 'App name')
		);
		print_r($info);
		
}
//$params = array('refresh_token'=>'1UQ2Wtfzu8e4l3L8cSzVNx8ENxbNuW6Ru9LUoGSpL');
//$response = $client->getAccessToken(TOKEN_ENDPOINT, 'refresh_token', $params);


print_r($response);