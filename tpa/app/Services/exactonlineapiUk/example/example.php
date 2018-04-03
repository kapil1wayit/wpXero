<?php

// Autoload composer installed libraries
//require __DIR__ . '/../vendor/autoload.php';
require app_path('Services/exactonlineapiUk/'). 'vendor/autoload.php';
/**
 * Function to retrieve persisted data for the example
 * @param string $key
 * @return null|string
 */
function getValue($key)
{
    $storage = json_decode(file_get_contents(app_path('Services/exactonlineapiUk/example/').'storage.json'), true);
    if (array_key_exists($key, $storage)) {
        return $storage[$key];
    }
    return null;
}

/**
 * Function to persist some data for the example
 * @param string $key
 * @param string $value
 */
function setValue($key, $value)
{
    $storage       = json_decode(file_get_contents(app_path('Services/exactonlineapiUk/example/').'storage.json'), true);
    $storage[$key] = $value;
    file_put_contents('storage.json', json_encode($storage));
}

/**
 * Function to authorize with Exact, this redirects to Exact login promt and retrieves authorization code
 * to set up requests for oAuth tokens
 */
function authorize($client_id = null,$client_secret = null,$callbackUrl =null)
{
    $connection = new \Picqer\Financials\Exact\Connection();
    $connection->setRedirectUrl($callbackUrl);
    $connection->setExactClientId($client_id);
    $connection->setExactClientSecret($client_secret);
	$connection->redirectForAuthorization();
}

/**
 * Function to connect to Exact, this creates the client and automatically retrieves oAuth tokens if needed
 *
 * @return \Picqer\Financials\Exact\Connection
 * @throws Exception
 */
function connect($client_id = null,$client_secret = null,$callbackUrl =null)
{
	
    $connection = new \Picqer\Financials\Exact\Connection();
    $connection->setRedirectUrl($callbackUrl);
    $connection->setExactClientId($client_id);
    $connection->setExactClientSecret($client_secret);

    // if (getValue('authorizationcode')) // Retrieves authorizationcode from database
    // {
        // $connection->setAuthorizationCode(getValue('authorizationcode'));
    // }

    // if (getValue('accesstoken')) // Retrieves accesstoken from database
    // {
        // $connection->setAccessToken(getValue('accesstoken'));
    // }

    // if (getValue('refreshtoken')) // Retrieves refreshtoken from database
    // {
        // $connection->setRefreshToken(getValue('refreshtoken'));
    // }

    // if (getValue('expires_in')) // Retrieves expires timestamp from database
    // {
        // $connection->setTokenExpires(getValue('expires_in'));
    // }

    // Make the client connect and exchange tokens
	
	
    try {
       // $connection->connect();
       // $connection->connect();
		
		//die('aaaaa');
    } catch (\Exception $e) {
        throw new Exception('Could not connect to Exact: ' . $e->getMessage());
    }

    // Save the new tokens for next connections
    // setValue('accesstoken', $connection->getAccessToken());
    // setValue('refreshtoken', $connection->getRefreshToken());

    // // Save expires time for next connections
    // setValue('expires_in', $connection->getTokenExpires());

    return $connection;
}

// If authorization code is returned from Exact, save this to use for token request
if (isset($_GET['code']) && is_null(getValue('authorizationcode'))) {
    setValue('authorizationcode', $_GET['code']);
}

// If we do not have a authorization code, authorize first to setup tokens
if (getValue('authorizationcode') === null) {
   // authorize();
}

// Create the Exact client


// Get the journals from our administration
// try {
    // $journals = new \Picqer\Financials\Exact\Contact($connection);
    // $result   = $journals->get();
	// echo '<pre>';
	
    // foreach ($result as $key=>$journal) {
		// //echo $key.'<br>';
		// print_r($journal); die;
        // //echo 'FF: ' . $journal->FirstName . '<br>';
    // }
// } catch (\Exception $e) {
    // echo get_class($e) . ' : ' . $e->getMessage();
// }