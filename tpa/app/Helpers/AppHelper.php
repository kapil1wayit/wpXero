<?php

namespace App\Helpers;

use SoapClient;
use App\Services\qboauth\QuickBooks_IPP_OAuth;
use App\Services\clearbooks\src\Clearbooks\Soap;
use App\Services\sageonev3\SageoneSigner;
use Picqer\Financials\Exact;

class AppHelper {
    /* public function bladeHelper($someValue) {
      return "increment $someValue";
      }

      public function startQueryLog() {
      \DB::enableQueryLog();
      }

      public function showQueries() {
      dd(\DB::getQueryLog());
      }

      public static function instance() {
      return new AppHelper();
      } */

    public static function qbOauthSignature($url, $user) {
        //require_once(APPPATH . $GLOBALS['apipathoauth'] . 'OAuth.php');
        //echo "<prE>"; print_r($user); die;
        $length = 11;
        $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
        $consumer_key = $user->consumer_key;
        $consumer_secret = $user->consumer_secret;
        $token = $user->access_token;
        $oauth_access_token_secret = $user->access_token_secret;

        $oauth = array('oauth_consumer_key' => $consumer_key,
            'oauth_token' => $token,
            'oauth_nonce' => $randomString,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0');
        $oauth1 = new QuickBooks_IPP_OAuth($consumer_key, $consumer_secret);
        $signature = $oauth1->sign('GET', $url, $token, $oauth_access_token_secret, $oauth);

        return $signature;
    }

    public static function qbCurlRequest($url, $signature, $type = NULL) {

        $options = array(CURLOPT_HTTPHEADER => array($signature),
            CURLOPT_HEADER => FALSE,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false);
        //echo "<prE>"; print_r($options); die;
        $feed = curl_init();

        //echo "<pre>"; print_r($feed); die;

        curl_setopt_array($feed, $options);

        $information = curl_getinfo($feed);

        $json = curl_exec($feed);
        $err = curl_error($feed);
        curl_close($feed);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            if ($type == 'transactionList') {
                $array = json_decode($json, TRUE);
                //echo "<pre>";print_r($array);
                return $array;
            } else {
                $xml = simplexml_load_string($json);
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                //echo "<pre>";print_r($array);
                return $array;
            }
        }
    }

    // with client id
    public static function freshbookGetRefreshToken($client_id, $client_secret, $callbackUrl, $grant_type, $request_type, $request_type_val) {
        $curl = curl_init();
        //echo $client_id.'--'.$client_secret.'--'.$callbackUrl.'--'.$grant_type.'--'.$request_type.'--'.$request_type_val;
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.freshbooks.com/auth/oauth/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n    \"grant_type\": \"$grant_type\",\n    \"client_secret\": \"$client_secret\",\n    \"$request_type\": \"$request_type_val\",\n    \"client_id\": \"$client_id\",\n    \"redirect_uri\": \"$callbackUrl\"\n}",
            CURLOPT_HTTPHEADER => array(
                "api-version: alpha",
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 471a0741-8466-2e3f-0006-8b9c3794ef9d"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function getCurlData($url, $parms) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $parms);

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public static function getKashflow($parameters, $method) {
        $client = new SoapClient("https://securedwebapp.com/api/service.asmx?WSDL");
        $response = $client->$method($parameters);
        return $response;
    }

    public static function renewAccessToken($client_id, $client_secret, $refresh_token, $token_endpoint) {
        $params = array("client_id" => $client_id,
            "client_secret" => $client_secret,
            "refresh_token" => $refresh_token,
            "grant_type" => "refresh_token");
        //print_r($params);die;
        $response = AppHelper::getToken($params, $token_endpoint);
        return $response;
    }

    public static function getToken($params, $token) {
        $url = $token;
        $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($params)));
        $context = stream_context_create($options);
        //print_r($context);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */
            var_dump($result);
        }
        return $result;
    }

    public static function callApi($base_endpoint, $endpoint4, $signing_secret, $token, $sageone_guid, $apim_subscription_key) {
        $params = '';
        $url4 = $base_endpoint . $endpoint4;
        $nonce4 = bin2hex(openssl_random_pseudo_bytes(32));
        $header4 = array("Accept: *.*",
            "Content-Type: application/json",
            "User-Agent: Frenns App",
            "ocp-apim-subscription-key: " . $apim_subscription_key
        );
        //require_once(app_path('third_party/sageonev3/') . 'sageone_signer.php');
        $signature_object4 = new SageoneSigner("get", $url4, $params, $nonce4, $signing_secret, $token, $sageone_guid);
        $signature4 = $signature_object4->signature();
        array_push($header4, "Authorization: Bearer " . $token, "X-Signature: " . $signature4, "X-Nonce: " . $nonce4, "X-Site: " . $sageone_guid);
        $response4 = self::getData($url4, $header4);
        $apiData = json_decode($response4);
        return $apiData;
    }

    public static function getData($endpoint, $header) {
        $curl = curl_init($endpoint);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($curl);
        if (!$response) { /* Handle error */
        }

        return $response;
    }

    public static function reeleezeeAuth($url, $username, $password) {
        $headers = array(
            'Accept: application/json',
            'Accept-Language: en',
            'Content-Type: application/json; charset=UTF-8',
            'Prefer: return=representation'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        curl_close($ch);
        return $result;
    }

    public static function exactApi($connection, $type) {
        $response = new \Picqer\Financials\Exact\SalesInvoice($connection);
        return $response;
    }

}
