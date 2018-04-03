<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use SoapClient;
use DB;
use Helper;
use Session;
use Redirect;
use App\Services\OAuth2;

class Datacollector extends Controller {

    /**
     * Fetch Data from APIs.
     *
     * @param  int  $id
     * @return Response
     */
    ############################## Twinfield API Start Here #######################

    public function twinfieldData() {

        #Fetch all twinfield user details             
        $accounting_system = 'twinfield';
        if (!empty($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        //$getdetail = $this->apidetail->getSingleUserDetail($accounting_system, $userNumber);      
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }

        //echo "<pre>"; print_r($getdetail); die;

        if (!empty($getdetail)) {
            foreach ($getdetail as $user) {
                //echo "<pre>"; print_r($user); echo $user->Password; //die;
                $uniqueId = $user->usernumber;
                $uniqueFrennsId = $user->accounting_system . "-" . $user->usernumber;

                $params = array(
                    'user' => $user->UserName,
                    'password' => $user->Password,
                    'organisation' => $user->realm
                );

                //logon
                try {
                    $session = new SoapClient("https://login.twinfield.com/webservices/session.asmx?wsdl", array('trace' => 1));
                    $result = $session->logon($params);
                    /*
                      echo '<pre>';
                      print_r($result);
                      echo '</pre>';
                     */
                } catch (SoapFault $e) {
                    echo $e->getMessage();
                }

                // header
                $cluster = $result->cluster;
                $qq = new \DOMDocument();
                $qq->loadXML($session->__getLastResponse());
                $sessionID = $qq->getElementsByTagName('SessionID')->item(0)->textContent;
                $newurl = $cluster . '/webservices/processxml.asmx?wsdl';
                try {
                    $client = new \SoapClient($newurl);
                    $header = new \SoapHeader('http://www.twinfield.com/', 'Header', array('SessionID' => $sessionID));
                    /* echo '<pre>';
                      print_r($header);
                      echo '</pre>'; */


                    // max time for

                    $getLastUpdatedContact = DB::table('syncsupplier')->select('last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();
                    if (count($getLastUpdatedContact) > 0) {
                        $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                    } else {
                        $ContactlastUpdatedTime = '';
                    }

                    $idArray = array();
                    $getTodayInsertedContact = DB::table('syncsupplier')->select('updateId')->where('unique_frenns_id', $uniqueFrennsId)->where('last_update', 'like', '%' . $ContactlastUpdatedTime . '%')->get();
                    //print_r($getTodayInsertedContact); die;
                    if (!empty($getTodayInsertedContact)) {
                        foreach ($getTodayInsertedContact as $dbData) {
                            $exp = explode("-", $dbData->updateId);
                            $idArray[] = $exp[2];
                        }
                    } else {
                        $idArray[] = array();
                    }


                    ################## Customer Data Start Here ####################  

                    $localContactsArr = array();
                    try {
                        // Function
                        //echo '<br /><br />Resultaat ProcessXmlString<br /><br />';
                        $xml = "<read>
                <type>dimensions</type>
                <office>NLA002821</office>
                <dimtype>DEB</dimtype>
                </read>";
                        $result = $client->__soapCall('ProcessXmlString', array(array('xmlRequest' => $xml)), null, $header);

                        //echo '<xmp>';
                        //print_r($result->ProcessXmlStringResult);
                        //echo '</xmp>';

                        $xml = simplexml_load_string($result->ProcessXmlStringResult, "SimpleXMLElement", LIBXML_NOCDATA);
                        $json = json_encode($xml);
                        $contacts = json_decode($json, TRUE);

                        //echo '<pre>';
                        //print_r($contacts);
                        //echo '</pre>'; die('All Contacts');

                        if (!empty($contacts)) {

                            ## Delete all customer from database
                            DB::table('syncsupplier')->where('unique_frenns_id', $uniqueFrennsId)->where('type', 'customer')->delete();

                            foreach ($contacts['dimension'] as $key => $customer) {
                                //echo '<pre>'; print_r($customer); die(' Single Contact');
                                $contactId = $customer['uid'];
                                $updateId = $uniqueId . '-' . $customer['uid'];

                                $customerData[$key]['frenns_id'] = $uniqueId;
                                $customerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $customerData[$key]['company_name'] = isset($customer['company']) ? $customer['company'] : '';
                                $customerData[$key]['company_account_number'] = '';
                                $customerData[$key]['company_number'] = '';
                                $customerData[$key]['Type'] = 'customer';
                                $customerData[$key]['cust_supp_company'] = '';
                                $customerData[$key]['custsupp_companynumber'] = '';
                                $customerData[$key]['account_number'] = '';

                                if (is_array($customer['name'])) {
                                    $customerData[$key]['name'] = '';
                                } else {
                                    $customerData[$key]['name'] = isset($customer['name']) ? $customer['name'] : '';
                                }

                                if (is_array($customer['addresses']['address']['field1'])) {
                                    $customerData[$key]['Address'] = '';
                                } else {
                                    $customerData[$key]['Address'] = isset($customer['addresses']['address']['field1']) ? $customer['addresses']['address']['field1'] : '';
                                }

                                if (is_array($customer['addresses']['address']['postcode'])) {
                                    $customerData[$key]['Postcode'] = '';
                                } else {
                                    $customerData[$key]['Postcode'] = isset($customer['addresses']['address']['postcode']) ? $customer['addresses']['address']['postcode'] : '';
                                }

                                if (is_array($customer['addresses']['address']['city'])) {
                                    $customerData[$key]['City'] = '';
                                } else {
                                    $customerData[$key]['City'] = isset($customer['addresses']['address']['city']) ? $customer['addresses']['address']['city'] : '';
                                }

                                $customerData[$key]['country'] = isset($customer['addresses']['address']['country']) ? $customer['addresses']['address']['country'] : '';

                                if (is_array($customer['addresses']['address']['name'])) {
                                    $customerData[$key]['contact_person'] = '';
                                } else {
                                    $customerData[$key]['contact_person'] = isset($customer['addresses']['address']['name']) ? $customer['addresses']['address']['name'] : '';
                                }

                                if (is_array($customer['addresses']['address']['telephone'])) {
                                    $customerData[$key]['phone_number'] = '';
                                } else {
                                    $customerData[$key]['phone_number'] = isset($customer['addresses']['address']['telephone']) ? $customer['addresses']['address']['telephone'] : '';
                                }

                                if (is_array($customer['addresses']['address']['email'])) {
                                    $customerData[$key]['Email'] = '';
                                } else {
                                    $customerData[$key]['Email'] = isset($customer['addresses']['address']['email']) ? $customer['addresses']['address']['email'] : '';
                                }

                                $customerData[$key]['collection_date'] = date('Y-m-d');
                                $customerData[$key]['last_update'] = date('Y-m-d');
                                $customerData[$key]['contactId'] = $contactId;
                                $customerData[$key]['updateId'] = $updateId;
                            }

                            //echo "<pre>"; print_r($customerData); die;
                            ## Insert customer into database
                            DB::table('syncsupplier')->insert($customerData);
                        }
                    } catch (SoapFault $e) {
                        echo $e->getMessage();
                    }

                    ################## Customer Data End Here ####################
                    ################## Supplier Data Start Here ##################

                    try {
                        // Function
                        //echo '<br /><br />Resultaat ProcessXmlString<br /><br />';
                        $xml = "<read>
                <type>dimensions</type>
                <office>NLA002821</office>
                <dimtype>CRD</dimtype>
                </read>";
                        $result = $client->__soapCall('ProcessXmlString', array(array('xmlRequest' => $xml)), null, $header);
                        //echo '<xmp>';
                        //print_r($result->ProcessXmlStringResult);
                        //echo '</xmp>';

                        $xml = simplexml_load_string($result->ProcessXmlStringResult, "SimpleXMLElement", LIBXML_NOCDATA);
                        $json = json_encode($xml);
                        $contacts = json_decode($json, TRUE);

                        //echo '<pre>';
                        //print_r($contacts);
                        //echo '</pre>';

                        if (!empty($contacts)) {
                            ## Delete all customer from database
                            DB::table('syncsupplier')->where('unique_frenns_id', $uniqueFrennsId)->where('type', 'supplier')->delete();

                            foreach ($contacts['dimension'] as $key => $customer) {

                                //echo '<pre>';print_r($customer);  die(' Single Contact');
                                $contactId = $customer['uid'];
                                $updateId = $uniqueId . '-' . $customer['uid'];

                                $customerData[$key]['frenns_id'] = $uniqueId;
                                $customerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $customerData[$key]['company_name'] = isset($customer['company']) ? $customer['company'] : '';
                                $customerData[$key]['company_account_number'] = '';
                                $customerData[$key]['company_number'] = '';
                                $customerData[$key]['Type'] = 'supplier';
                                $customerData[$key]['cust_supp_company'] = '';
                                $customerData[$key]['custsupp_companynumber'] = '';
                                $customerData[$key]['account_number'] = '';

                                if (is_array($customer['name'])) {
                                    $customerData[$key]['name'] = '';
                                } else {
                                    $customerData[$key]['name'] = isset($customer['name']) ? $customer['name'] : '';
                                }

                                if (is_array($customer['addresses']['address']['field1'])) {
                                    $customerData[$key]['Address'] = '';
                                } else {
                                    $customerData[$key]['Address'] = isset($customer['addresses']['address']['field1']) ? $customer['addresses']['address']['field1'] : '';
                                }

                                if (is_array($customer['addresses']['address']['postcode'])) {
                                    $customerData[$key]['Postcode'] = '';
                                } else {
                                    $customerData[$key]['Postcode'] = isset($customer['addresses']['address']['postcode']) ? $customer['addresses']['address']['postcode'] : '';
                                }

                                if (is_array($customer['addresses']['address']['city'])) {
                                    $customerData[$key]['City'] = '';
                                } else {
                                    $customerData[$key]['City'] = isset($customer['addresses']['address']['city']) ? $customer['addresses']['address']['city'] : '';
                                }

                                $customerData[$key]['country'] = isset($customer['addresses']['address']['country']) ? $customer['addresses']['address']['country'] : '';

                                if (is_array($customer['addresses']['address']['name'])) {
                                    $customerData[$key]['contact_person'] = '';
                                } else {
                                    $customerData[$key]['contact_person'] = isset($customer['addresses']['address']['name']) ? $customer['addresses']['address']['name'] : '';
                                }

                                if (is_array($customer['addresses']['address']['telephone'])) {
                                    $customerData[$key]['phone_number'] = '';
                                } else {
                                    $customerData[$key]['phone_number'] = isset($customer['addresses']['address']['telephone']) ? $customer['addresses']['address']['telephone'] : '';
                                }

                                if (is_array($customer['addresses']['address']['email'])) {
                                    $customerData[$key]['Email'] = '';
                                } else {
                                    $customerData[$key]['Email'] = isset($customer['addresses']['address']['email']) ? $customer['addresses']['address']['email'] : '';
                                }

                                $customerData[$key]['collection_date'] = date('Y-m-d');
                                $customerData[$key]['last_update'] = date('Y-m-d');
                                $customerData[$key]['contactId'] = $contactId;
                                $customerData[$key]['updateId'] = $updateId;
                            }

                            ## Insert supplier into database
                            $id = DB::table('syncsupplier')->insert($customerData);
                        }
                    } catch (SoapFault $e) {
                        echo $e->getMessage();
                    }
                    ################## Supplier Data End Here ####################
                } catch (SoapFault $e) {
                    echo $e->getMessage();
                }
            }
        }
        die('Twinfield Customer/Suppliers Data Saved successfully');
    }

    ####################################### Twinfield API End Here #######################################################
####################################### Quickbook API Start Here #####################################################

    public function quickbookData() {

        #Fetch all quickbook user details             
        $accounting_system = 'quickbook';
        if (!empty($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        if (!empty($_REQUEST['sandbox'])) {
            //$sandbox = $_REQUEST['sandbox'];
            $qbRequest = "https://sandbox-quickbooks.api.intuit.com/v3/company/";
        } else {
            //$sandbox = '';
            $qbRequest = "https://quickbooks.api.intuit.com/v3/company/";
        }

        //$getdetail = $this->apidetail->getSingleUserDetail($accounting_system, $userNumber);      
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }


        if (!empty($getdetail)) {
            foreach ($getdetail as $user) {
                $uniqueId = $user->usernumber;
                $uniqueFrennsId = $user->accounting_system . "-" . $user->usernumber;
                $realm = $user->realm;
                $consumer_key = $user->consumer_key;
                $consumer_secret = $user->consumer_secret;
                $access_token = $user->access_token;
                $access_token_secret = $user->access_token_secret;

                ########################### Vendor Section ################################################
                
                $url = $qbRequest . $realm . "/query?query=select%20*%20from%20Vendor";
                $signature = Helper::qbOauthSignature($url, $user);
                $response = Helper::qbCurlRequest($url, $signature[3]);
                //echo "<prE>"; print_r($response); die("#335#");
                if (isset($response['Fault']['Error']['@attributes']['code']) && ($response['Fault']['Error']['@attributes']['code'] == 3200 || $response['Fault']['Error']['@attributes']['code'] == 100)) {
                    //die("Application Authentication Failed For <b>" . $uniqueId . "</b>. Please contact to administrator.");
                    return view('authenticationFailed', ['uniqueId' => $uniqueId]);
                } else if (!empty($response['QueryResponse']['Vendor'])) {
                    #Get Last Cron Time
                    $data['accounting_system'] = 'quickbook';
                    $data['item_type'] = 'vendor';
                    $data['user'] = $uniqueId;
                    //$lastCronTime = $this->apidetail->getCronTime($data);
                    $lastCronTime = DB::table('appcrontracker')->where('accounting_system', 'quickbook')->where('item_type', 'vendor')->orderBy('id', 'desc')->first();
                    $lastCronTime = isset($lastCronTime->cron_time) ? $lastCronTime->cron_time : '';
                    #Save Cron time 
                    $responseTime = explode(".", $response['@attributes']['time']);
                    $CronTime = $responseTime[0] . "-07:00";
                    $cronData[0]['accounting_system'] = 'quickbook';
                    $cronData[0]['item_type'] = 'vendor';
                    $cronData[0]['user'] = $uniqueId;
                    $cronData[0]['cron_time'] = $CronTime;
                    DB::table('appcrontracker')->insert($cronData);

                    ## Vendor Function

                    foreach ($response['QueryResponse']['Vendor'] as $key => $customer) {
                        //echo "<br>".$customer['MetaData']['CreateTime'],' > '. $lastCronTime; 
                        //echo "<pre>"; print_r($customer); die;
                        $uniqueUpdateId = $uniqueFrennsId . '-' . $customer['Id'];
                        if ($customer['MetaData']['CreateTime'] > $lastCronTime || $lastCronTime == '') {
                            $customerData[$key]['frenns_id'] = $uniqueId;
                            $customerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $customerData[$key]['company_name'] = isset($customer['CompanyName']) ? $customer['CompanyName'] : '';
                            $customerData[$key]['company_account_number'] = '';
                            $customerData[$key]['company_number'] = '';
                            $customerData[$key]['Type'] = 'Supplier';
                            $customerData[$key]['cust_supp_company'] = '';
                            $customerData[$key]['custsupp_companynumber'] = '';
                            $customerData[$key]['account_number'] = '';
                            $customerData[$key]['name'] = '';
                            $customerData[$key]['Address'] = isset($customer['BillAddr']['Line1']) ? $customer['BillAddr']['Line1'] : '';
                            $customerData[$key]['Postcode'] = isset($customer['BillAddr']['PostalCode']) ? $customer['BillAddr']['PostalCode'] : '';
                            $customerData[$key]['City'] = isset($customer['BillAddr']['City']) ? $customer['BillAddr']['City'] : '';
                            $customerData[$key]['country'] = isset($customer['BillAddr']['CountrySubDivisionCode']) ? $customer['BillAddr']['CountrySubDivisionCode'] : '';
                            $customerData[$key]['contact_person'] = isset($customer['DisplayName']) ? $customer['DisplayName'] : '';
                            $customerData[$key]['phone_number'] = isset($customer['PrimaryPhone']['FreeFormNumber']) ? $customer['PrimaryPhone']['FreeFormNumber'] : '';
                            $customerData[$key]['Email'] = isset($customer['PrimaryEmailAddr']['Address']) ? $customer['PrimaryEmailAddr']['Address'] : '';
                            $customerData[$key]['collection_date'] = date('Y-m-d');
                            $customerData[$key]['last_update'] = isset($customer['MetaData']['LastUpdatedTime']) ? $customer['MetaData']['LastUpdatedTime'] : '';
                            $customerData[$key]['contactId'] = isset($customer['Id']) ? $customer['Id'] : '';
                            $customerData[$key]['updateId'] = $uniqueUpdateId;

                            #Save Vendor Data
                            if (!empty($customerData)) {
                                // echo "<pre>"; print_r($customerData); die('Quickbook Vendor Data 384'); 
                                ## Insert customer into database 
                                DB::table('syncsupplier')->insert($customerData[$key]);
                            }
                        } else if ($customer['MetaData']['LastUpdatedTime'] > $lastCronTime) {
                            $updateCustomerData[$key]['frenns_id'] = $uniqueId;
                            $updateCustomerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateCustomerData[$key]['company_name'] = isset($customer['CompanyName']) ? $customer['CompanyName'] : '';
                            $updateCustomerData[$key]['company_account_number'] = '';
                            $updateCustomerData[$key]['company_number'] = '';
                            $updateCustomerData[$key]['Type'] = 'Supplier';
                            $updateCustomerData[$key]['cust_supp_company'] = '';
                            $updateCustomerData[$key]['custsupp_companynumber'] = '';
                            $updateCustomerData[$key]['account_number'] = '';
                            $updateCustomerData[$key]['name'] = '';
                            $updateCustomerData[$key]['Address'] = isset($customer['BillAddr']['Line1']) ? $customer['BillAddr']['Line1'] : '';
                            $updateCustomerData[$key]['Postcode'] = isset($customer['BillAddr']['PostalCode']) ? $customer['BillAddr']['PostalCode'] : '';
                            $updateCustomerData[$key]['City'] = isset($customer['BillAddr']['City']) ? $customer['BillAddr']['City'] : '';
                            $updateCustomerData[$key]['country'] = isset($customer['BillAddr']['CountrySubDivisionCode']) ? $customer['BillAddr']['CountrySubDivisionCode'] : '';
                            $updateCustomerData[$key]['contact_person'] = isset($customer['DisplayName']) ? $customer['DisplayName'] : '';
                            $updateCustomerData[$key]['phone_number'] = isset($customer['PrimaryPhone']['FreeFormNumber']) ? $customer['PrimaryPhone']['FreeFormNumber'] : '';
                            $updateCustomerData[$key]['Email'] = isset($customer['PrimaryEmailAddr']['Address']) ? $customer['PrimaryEmailAddr']['Address'] : '';
                            $updateCustomerData[$key]['collection_date'] = date('Y-m-d');
                            $updateCustomerData[$key]['last_update'] = isset($customer['MetaData']['LastUpdatedTime']) ? $customer['MetaData']['LastUpdatedTime'] : '';
                            $updateCustomerData[$key]['contactId'] = isset($customer['Id']) ? $customer['Id'] : '';
                            $updateCustomerData[$key]['updateId'] = $uniqueUpdateId;

                            #Update Vendor Data
                            if (!empty($updateCustomerData)) {
                                $where['updateId'] = $updateCustomerData[$key]['updateId'];
                                $update = $updateCustomerData[$key];
                                DB::table('syncsupplier')->where($where)->update($update);
                            }
                        } else {
                            ## No Action needed.
                        }
                    }
                }

                ################################ Customer Section #####################################

                $url = $qbRequest . $realm . "/query?query=Select%20*%20from%20Customer";
                $signature = Helper::qbOauthSignature($url, $user);
                $cresponse = Helper::qbCurlRequest($url, $signature[3]);
                //echo "<pre>"; print_r($response); die('--------------'); 
                if (isset($response['Fault']['Error']['@attributes']['code']) && $response['Fault']['Error']['@attributes']['code'] == 3200) {
                    die("Application Authentication Failed For <b>" . $uniqueId . "</b>. Please contact to administrator.");
                } else if (!empty($cresponse['QueryResponse']['Customer'])) {
                    #Get Last Cron Time
                    $data['accounting_system'] = 'quickbook';
                    $data['item_type'] = 'customer';
                    $data['user'] = $uniqueId;
                    //$lastCronTime = $this->apidetail->getCronTime($data);
                    $lastCronTime = DB::table('appcrontracker')->where('accounting_system', 'quickbook')->get();
                    $lastCronTime = DB::table('appcrontracker')->where('accounting_system', 'quickbook')->where('item_type', 'customer')->orderBy('id', 'desc')->first();
                    $lastCronTime = isset($lastCronTime->cron_time) ? $lastCronTime->cron_time : '';

                    #Save Cron time 
                    $responseTime = explode(".", $cresponse['@attributes']['time']);
                    $CronTime = $responseTime[0] . "-07:00";
                    $cronData[0]['accounting_system'] = 'quickbook';
                    $cronData[0]['item_type'] = 'customer';
                    $cronData[0]['user'] = $uniqueId;
                    $cronData[0]['cron_time'] = $CronTime;
                    //$this->apidetail->saveCronTime($cronData);
                    DB::table('appcrontracker')->insert($cronData);

                    ## Customer Function

                    foreach ($cresponse['QueryResponse']['Customer'] as $key => $customer) {
                        //echo "<br>".$customer['MetaData']['CreateTime'],' > '. $lastCronTime; 
                        //echo "<pre>"; print_r($customer); die;  
                        $uniqueUpdateId = $uniqueFrennsId . '-' . $customer['Id'];
                        if ($customer['MetaData']['CreateTime'] > $lastCronTime || $lastCronTime == '') {
                            $customerData[$key]['frenns_id'] = $uniqueId;
                            $customerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $customerData[$key]['company_name'] = isset($customer['CompanyName']) ? $customer['CompanyName'] : '';
                            $customerData[$key]['company_account_number'] = '';
                            $customerData[$key]['company_number'] = '';
                            $customerData[$key]['Type'] = 'Customer';
                            $customerData[$key]['cust_supp_company'] = '';
                            $customerData[$key]['custsupp_companynumber'] = '';
                            $customerData[$key]['account_number'] = '';
                            $customerData[$key]['name'] = '';
                            $customerData[$key]['Address'] = isset($customer['BillAddr']['Line1']) ? $customer['BillAddr']['Line1'] : '';
                            $customerData[$key]['Postcode'] = isset($customer['BillAddr']['PostalCode']) ? $customer['BillAddr']['PostalCode'] : '';
                            $customerData[$key]['City'] = isset($customer['BillAddr']['City']) ? $customer['BillAddr']['City'] : '';
                            $customerData[$key]['country'] = isset($customer['BillAddr']['CountrySubDivisionCode']) ? $customer['BillAddr']['CountrySubDivisionCode'] : '';
                            $customerData[$key]['contact_person'] = isset($customer['BillAddr']['CountrySubDivisionCode']) ? $customer['BillAddr']['CountrySubDivisionCode'] : '';
                            $customerData[$key]['phone_number'] = isset($customer['PrimaryPhone']['FreeFormNumber']) ? $customer['PrimaryPhone']['FreeFormNumber'] : '';
                            $customerData[$key]['Email'] = isset($customer['PrimaryEmailAddr']['Address']) ? $customer['PrimaryEmailAddr']['Address'] : '';
                            $customerData[$key]['collection_date'] = date('Y-m-d');
                            $customerData[$key]['last_update'] = isset($customer['MetaData']['LastUpdatedTime']) ? $customer['MetaData']['LastUpdatedTime'] : '';
                            $customerData[$key]['contactId'] = isset($customer['Id']) ? $customer['Id'] : '';
                            $customerData[$key]['updateId'] = $uniqueUpdateId;

                            #Save Customer Data
                            if (!empty($customerData)) {
                                DB::table('syncsupplier')->insert($customerData[$key]);
                            }
                        } else if ($customer['MetaData']['LastUpdatedTime'] > $lastCronTime) {

                            $updateCustomerData[$key]['frenns_id'] = $uniqueId;
                            $updateCustomerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateCustomerData[$key]['company_name'] = isset($customer['CompanyName']) ? $customer['CompanyName'] : '';
                            $updateCustomerData[$key]['company_account_number'] = '';
                            $updateCustomerData[$key]['company_number'] = '';
                            $updateCustomerData[$key]['Type'] = 'Customer';
                            $updateCustomerData[$key]['cust_supp_company'] = '';
                            $updateCustomerData[$key]['custsupp_companynumber'] = '';
                            $updateCustomerData[$key]['account_number'] = '';
                            $updateCustomerData[$key]['name'] = '';
                            $updateCustomerData[$key]['Address'] = isset($customer['BillAddr']['Line1']) ? $customer['BillAddr']['Line1'] : '';
                            $updateCustomerData[$key]['Postcode'] = isset($customer['BillAddr']['PostalCode']) ? $customer['BillAddr']['PostalCode'] : '';
                            $updateCustomerData[$key]['City'] = isset($customer['BillAddr']['City']) ? $customer['BillAddr']['City'] : '';
                            $updateCustomerData[$key]['country'] = isset($customer['BillAddr']['CountrySubDivisionCode']) ? $customer['BillAddr']['CountrySubDivisionCode'] : '';
                            $updateCustomerData[$key]['contact_person'] = isset($customer['BillAddr']['CountrySubDivisionCode']) ? $customer['BillAddr']['CountrySubDivisionCode'] : '';
                            $updateCustomerData[$key]['phone_number'] = isset($customer['PrimaryPhone']['FreeFormNumber']) ? $customer['PrimaryPhone']['FreeFormNumber'] : '';
                            $updateCustomerData[$key]['Email'] = isset($customer['PrimaryEmailAddr']['Address']) ? $customer['PrimaryEmailAddr']['Address'] : '';
                            $updateCustomerData[$key]['collection_date'] = date('Y-m-d');
                            $updateCustomerData[$key]['last_update'] = isset($customer['MetaData']['LastUpdatedTime']) ? $customer['MetaData']['LastUpdatedTime'] : '';
                            $updateCustomerData[$key]['contactId'] = isset($customer['Id']) ? $customer['Id'] : '';
                            $updateCustomerData[$key]['updateId'] = $uniqueUpdateId;

                            #Update Customer Data
                            if (!empty($updateCustomerData)) {
                                $where['updateId'] = $updateCustomerData[$key]['updateId'];
                                $update = $updateCustomerData[$key];
                                DB::table('syncsupplier')->where($where)->update($update);
                            }
                        } else {
                            ## No Action needed.
                        }
                    }
                }
                
                ######################## Invoice Section ##################################################

                $url = $qbRequest . $realm . "/query?query=select%20*%20from%20Invoice";
                $signature = Helper::qbOauthSignature($url, $user);
                $response = Helper::qbCurlRequest($url, $signature[3]);
                //echo "#@@#<pre>"; print_r($response); //die('Quickbook Invoice Responce 522');
                if (isset($response['Fault']['Error']['@attributes']['code']) && $response['Fault']['Error']['@attributes']['code'] == 3200) {
                    die("Application Authentication Failed For <b>" . $uniqueId . "</b>. Please contact to administrator.");
                } else if (!empty($response['QueryResponse']['Invoice'])) {

                    #Get Last Cron Time
                    $data['accounting_system'] = 'quickbook';
                    $data['item_type'] = 'invoice';
                    $data['user'] = $uniqueId;
                    //$lastCronTime = $this->apidetail->getCronTime($data);
                    $lastCronTime = DB::table('appcrontracker')->where('accounting_system', 'quickbook')->where('item_type', 'invoice')->orderBy('id', 'desc')->first();
                    $lastCronTime = isset($lastCronTime->cron_time) ? $lastCronTime->cron_time : '';

                    #Save Cron time 
                    $responseTime = explode(".", $response['@attributes']['time']);
                    $CronTime = $responseTime[0] . "-07:00";
                    $cronData[0]['accounting_system'] = 'quickbook';
                    $cronData[0]['item_type'] = 'invoice';
                    $cronData[0]['user'] = $uniqueId;
                    $cronData[0]['cron_time'] = $CronTime;
                    //$this->apidetail->saveCronTime($cronData);
                    DB::table('appcrontracker')->insert($cronData);

                    ## Invoice function

                    foreach ($response['QueryResponse']['Invoice'] as $key => $invoice) {
                        //echo "**--**<prE>"; print_r($invoice);//die('single invoice');                         
                        if ($invoice['Balance'] == 0) {
                            $paymentId = isset($invoice['LinkedTxn'][0]['TxnId']) ? $invoice['LinkedTxn'][0]['TxnId'] : $invoice['LinkedTxn']['TxnId'];
                            $url = $qbRequest . $realm . "/payment/" . $paymentId;
                            $signature = Helper::qbOauthSignature($url, $user);
                            $response = Helper::qbCurlRequest($url, $signature[3]);
                            //echo "<pre>";print_r($response); print_r($response['Payment']['TxnDate']); 
                            if (isset($response['Fault'])) {
                                $pay_date = '';
                            } else {
                                $pay_date = $response['Payment']['TxnDate'];
                            }
                        } else {
                            $pay_date = "";
                        }

                        $uniqueUpdateId = $uniqueFrennsId . '-' . $invoice['Id'];
                        $address = '';
                        if (isset($invoice['BillAddr']['Line1'])) {
                            $address = $invoice['BillAddr']['Line1'];
                        }
                        if (isset($invoice['BillAddr']['Line2'])) {
                            $address = $address . ', ' . $invoice['BillAddr']['Line2'];
                        } else if (isset($invoice['BillAddr']['City'])) {
                            $address = $address . ', ' . $invoice['BillAddr']['City'];
                        }

                        if (isset($invoice['BillAddr']['Line3'])) {
                            $address = $address . ', ' . $invoice['BillAddr']['Line3'];
                        } else if (isset($invoice['BillAddr']['CountrySubDivisionCode'])) {
                            $address = $address . ', ' . $invoice['BillAddr']['CountrySubDivisionCode'];
                        }

                        if (isset($invoice['BillAddr']['Line4'])) {
                            $address = $address . ', ' . $invoice['BillAddr']['Line4'];
                        } else if (isset($invoice['BillAddr']['PostalCode'])) {
                            $address = $address . ', ' . $invoice['BillAddr']['PostalCode'];
                        }

                        if ($invoice['MetaData']['CreateTime'] > $lastCronTime || $lastCronTime == '') {
                            // echo $invoice['MetaData']['CreateTime'] . " > " . $lastCronTime . "<br>";
                            $invoiceData[$key]['frenns_id'] = $uniqueId;
                            $invoiceData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $invoiceData[$key]['company_account_number'] = '';
                            $invoiceData[$key]['collection_date'] = date('Y-m-d');
                            $invoiceData[$key]['creation_date'] = isset($invoice['MetaData']['CreateTime']) ? $invoice['MetaData']['CreateTime'] : '';
                            $invoiceData[$key]['last_updated'] = isset($invoice['MetaData']['LastUpdatedTime']) ? $invoice['MetaData']['LastUpdatedTime'] : '';
                            $invoiceData[$key]['name'] = isset($invoice['BillAddr']['Line1']) ? $invoice['BillAddr']['Line1'] : '';
                            $invoiceData[$key]['address'] = $address;
                            $invoiceData[$key]['postcode'] = isset($invoice['ShipAddr']['PostalCode']) ? $invoice['ShipAddr']['PostalCode'] : '';
                            $invoiceData[$key]['city'] = isset($invoice['ShipAddr']['City']) ? $invoice['ShipAddr']['City'] : '';
                            $invoiceData[$key]['country'] = isset($invoice['ShipAddr']['CountrySubDivisionCode']) ? $invoice['ShipAddr']['CountrySubDivisionCode'] : '';
                            $invoiceData[$key]['company_number'] = '';
                            $invoiceData[$key]['vat_registration_number'] = '';
                            $invoiceData[$key]['contact_person'] = isset($invoice['BillAddr']['Line1']) ? $invoice['BillAddr']['Line1'] : '';
                            $invoiceData[$key]['phone_no'] = '';
                            $invoiceData[$key]['email'] = isset($invoice['BillEmail']['Address']) ? $invoice['BillEmail']['Address'] : '';
                            $invoiceData[$key]['type'] = isset($invoice['DocNumber']) ? $invoice['DocNumber'] : '';
                            $invoiceData[$key]['invoice_number'] = isset($invoice['DocNumber']) ? $invoice['DocNumber'] : '';
                            $invoiceData[$key]['issue_date'] = isset($invoice['MetaData']['CreateTime']) ? $invoice['MetaData']['CreateTime'] : '';
                            $invoiceData[$key]['due_date'] = isset($invoice['DueDate']) ? $invoice['DueDate'] : '';
                            $invoiceData[$key]['payment_terms'] = '';
                            $invoiceData[$key]['payment_method'] = '';
                            $invoiceData[$key]['delivery_date'] = '';
                            $invoiceData[$key]['currency'] = isset($invoice['CurrencyRef']['value']) ? $invoice['CurrencyRef']['value'] : '';
                            $invoiceData[$key]['amount'] = isset($invoice['TotalAmt']) ? $invoice['TotalAmt'] : '';
                            $invoiceData[$key]['vat_amount'] = isset($invoice['TxnTaxDetail']['TotalTax']) ? $invoice['TxnTaxDetail']['TotalTax'] : '0.0';
                            $invoiceData[$key]['outstanding_amount'] = '0.0';
                            $invoiceData[$key]['paid'] = '';
                            $invoiceData[$key]['pay_date'] = $pay_date;
                            $invoiceData[$key]['invoiceId'] = $invoice['Id'];
                            $invoiceData[$key]['updateId'] = $uniqueUpdateId;

                            #Save Quickbook Invoices 
                            if (!empty($invoiceData)) {
                                //echo "<prE>"; print_r($invoiceData[$key]); die('Quickbook Invoice Data 597');                                
                                DB::table('syncinvoice')->insert($invoiceData[$key]);
                            }

                            #New Invoice item data
                            if (isset($invoice['Line'])) {
                                $itemKey = 0;
                                $invoiceItemData = array();
                                foreach ($invoice['Line'] as $invoiceLine) {
                                    if (isset($invoiceLine['Id'])) {
                                        $quickbookItemId = $invoiceLine['Id'];
                                        $uniqueItemUpdateId = $uniqueUpdateId . '-' . $quickbookItemId;
                                        $invoiceItemData[$itemKey]['frenns_id'] = $uniqueId;
                                        $invoiceItemData[$itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                        $invoiceItemData[$itemKey]['invoice_number'] = isset($invoice['DocNumber']) ? $invoice['DocNumber'] : '';
                                        $invoiceItemData[$itemKey]['line_number'] = isset($invoiceLine['Id']) ? $invoiceLine['Id'] : '';
                                        $invoiceItemData[$itemKey]['product_code'] = '';
                                        $invoiceItemData[$itemKey]['description'] = isset($invoiceLine['description']) ? $invoiceLine['description'] : '';
                                        $invoiceItemData[$itemKey]['qty'] = isset($invoiceLine['SalesItemLineDetail']['Qty']) ? $invoiceLine['SalesItemLineDetail']['Qty'] : '0';
                                        $invoiceItemData[$itemKey]['rate'] = isset($invoiceLine['SalesItemLineDetail']['UnitPrice']) ? $invoiceLine['SalesItemLineDetail']['UnitPrice'] : '0';
                                        $invoiceItemData[$itemKey]['amount_net'] = isset($invoiceLine['Amount']) ? $invoiceLine['Amount'] : '';
                                        $invoiceItemData[$itemKey]['invoiceline_vat_amount'] = '0.0';
                                        $invoiceItemData[$itemKey]['amount_total'] = isset($invoiceLine['Amount']) ? $invoiceLine['Amount'] : '';
                                        $invoiceItemData[$itemKey]['invoice_id'] = isset($invoice['Id']) ? $invoice['Id'] : '';
                                        $invoiceItemData[$itemKey]['updateId'] = $uniqueItemUpdateId;
                                        #Save Quickbook Invoices items 
                                        if (!empty($invoiceItemData)) {
                                            //echo "<prE>"; print_r($invoiceItemData[$key . $itemKey]); die('Quickbook Invoice Item Data 622'); 
                                            //$this->apidetail->addInvoiceItem($invoiceItemData);
                                            DB::table('syncinvoice_item')->insert($invoiceItemData[$itemKey]);
                                        }
                                        $itemKey++;
                                    }
                                }
                            }
                        } else if ($invoice['MetaData']['LastUpdatedTime'] > $lastCronTime) {
                            #Update invoice data

                            $updateInvoiceData[$key]['frenns_id'] = $uniqueId;
                            $updateInvoiceData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateInvoiceData[$key]['company_account_number'] = '';
                            $updateInvoiceData[$key]['collection_date'] = date('Y-m-d');
                            $updateInvoiceData[$key]['creation_date'] = isset($invoice['MetaData']['CreateTime']) ? $invoice['MetaData']['CreateTime'] : '';
                            $updateInvoiceData[$key]['last_updated'] = isset($invoice['MetaData']['LastUpdatedTime']) ? $invoice['MetaData']['LastUpdatedTime'] : '';
                            $updateInvoiceData[$key]['name'] = isset($invoice['CustomerRef']['name']) ? $invoice['CustomerRef']['name'] : '';
                            $updateInvoiceData[$key]['address'] = $address;
                            $updateInvoiceData[$key]['postcode'] = isset($invoice['ShipAddr']['name']) ? $invoice['ShipAddr']['name'] : '';
                            $updateInvoiceData[$key]['city'] = isset($invoice['ShipAddr']['name']) ? $invoice['ShipAddr']['name'] : '';
                            $updateInvoiceData[$key]['country'] = isset($invoice['ShipAddr']['name']) ? $invoice['ShipAddr']['name'] : '';
                            $updateInvoiceData[$key]['company_number'] = '';
                            $updateInvoiceData[$key]['vat_registration_number'] = '';
                            $updateInvoiceData[$key]['contact_person'] = isset($invoice['BillAddr']['Line1']) ? $invoice['BillAddr']['Line1'] : '';
                            $updateInvoiceData[$key]['phone_no'] = '';
                            $updateInvoiceData[$key]['email'] = isset($invoice['BillEmail']['Address']) ? $invoice['BillEmail']['Address'] : '';
                            $updateInvoiceData[$key]['type'] = isset($invoice['DocNumber']) ? $invoice['DocNumber'] : '';
                            $updateInvoiceData[$key]['invoice_number'] = isset($invoice['DocNumber']) ? $invoice['DocNumber'] : '';
                            $updateInvoiceData[$key]['issue_date'] = isset($invoice['MetaData']['CreateTime']) ? $invoice['MetaData']['CreateTime'] : '';
                            $updateInvoiceData[$key]['due_date'] = isset($invoice['DueDate']) ? $invoice['DueDate'] : '';
                            $updateInvoiceData[$key]['payment_terms'] = '';
                            $updateInvoiceData[$key]['payment_method'] = '';
                            $updateInvoiceData[$key]['delivery_date'] = '';
                            $updateInvoiceData[$key]['currency'] = isset($invoice['CurrencyRef']['value']) ? $invoice['CurrencyRef']['value'] : '';
                            $updateInvoiceData[$key]['amount'] = isset($invoice['TotalAmt']) ? $invoice['TotalAmt'] : '';
                            $updateInvoiceData[$key]['vat_amount'] =  isset($invoice['TxnTaxDetail']['TotalTax']) ? $invoice['TxnTaxDetail']['TotalTax'] : '0.0';
                            $updateInvoiceData[$key]['outstanding_amount'] = '0.0';
                            $updateInvoiceData[$key]['paid'] = '';
                            $updateInvoiceData[$key]['pay_date'] = $pay_date;
                            $updateInvoiceData[$key]['invoiceId'] = $invoice['Id'];
                            $updateInvoiceData[$key]['updateId'] = $uniqueUpdateId;

                            #Update Quickbook Invoices 
                            if (!empty($updateInvoiceData)) {
                                DB::table('syncinvoice')->where('updateId', $uniqueUpdateId)->update($updateInvoiceData[$key]);
                            }

                            #Upsate invoice item data
                            if (isset($invoice['Line'])) {
                                $itemKey = 0;
                                $UpdateInvoiceItemData = array();
                                #Delete All Lines for this user
                                DB::table('syncinvoice_item')->where('unique_frenns_id', $uniqueFrennsId)->where('invoice_id', $invoice['Id'])->delete();
                                foreach ($invoice['Line'] as $invoiceLine) {
                                    if (isset($invoiceLine['Id'])) {
                                        $quickbookItemId = $invoiceLine['Id'];
                                        $uniqueItemUpdateId = $uniqueUpdateId . "-" . $quickbookItemId;
                                        $UpdateInvoiceItemData[$itemKey]['frenns_id'] = $uniqueId;
                                        $UpdateInvoiceItemData[$itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                        $UpdateInvoiceItemData[$itemKey]['invoice_number'] = isset($invoice['DocNumber']) ? $invoice['DocNumber'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['line_number'] = isset($invoiceLine['Id']) ? $invoiceLine['Id'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['product_code'] = '';
                                        $UpdateInvoiceItemData[$itemKey]['description'] = isset($invoiceLine['description']) ? $invoiceLine['description'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['qty'] = isset($invoiceLine['SalesItemLineDetail']['Qty']) ? $invoiceLine['SalesItemLineDetail']['Qty'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['rate'] = isset($invoiceLine['SalesItemLineDetail']['UnitPrice']) ? $invoiceLine['SalesItemLineDetail']['UnitPrice'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['amount_net'] = isset($invoiceLine['Amount']) ? $invoiceLine['Amount'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['invoiceline_vat_amount'] = '0.0';
                                        $UpdateInvoiceItemData[$itemKey]['amount_total'] = isset($invoiceLine['Amount']) ? $invoiceLine['Amount'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['invoice_id'] = isset($invoice['Id']) ? $invoice['Id'] : '';
                                        $UpdateInvoiceItemData[$itemKey]['updateId'] = $uniqueItemUpdateId;

                                        #Update Quickbook Invoices items 
                                        if (!empty($UpdateInvoiceItemData)) {
                                            //echo "<pre>"; print_r($UpdateInvoiceItemData); 
                                            //DB::table('syncinvoice_item')->where('updateId', $uniqueItemUpdateId)->update($UpdateInvoiceItemData[$itemKey]);
                                            DB::table('syncinvoice_item')->insert($UpdateInvoiceItemData[$itemKey]);
                                        }
                                        $itemKey++;
                                    }
                                }
                            }
                        }
                    }
                }

                ################## Transaction List Section #############################################################################

                $url = $qbRequest . $realm . "/reports/TransactionList?start_date=2015-06-01&end_date=2017-07-01&group_by=Customer";
                $signature = Helper::qbOauthSignature($url, $user);
                $response = Helper::qbCurlRequest($url, $signature[3], 'transactionList');
                if (isset($response['Fault']['Error']['@attributes']['code']) && $response['Fault']['Error']['@attributes']['code'] == 3200) {
                    die("Application Authentication Failed For <b>" . $uniqueId . "</b>. Please contact to administrator.");
                } else if (!empty($response['Header'])) {
                    $uniqueUpdateId = $uniqueFrennsId;
                    #Delete ALl Ledger Transaction For current customer            
                    //$this->apidetail->deleteQuickbookLedgerTransactions($uniqueId);
                    DB::table('syncledger_transaction')->where('unique_frenns_id', $uniqueUpdateId)->delete();

                    #Delete ALl Nominal Ledger For current customer            
                    //$this->apidetail->deleteQuickbookNominalLedger($uniqueId);
                    DB::table('syncnominal')->where('unique_frenns_id', $uniqueUpdateId)->delete();

                    if (!empty($response)) {

                        foreach ($response['Rows']['Row'] as $key => $value) {

                            foreach ($value['Rows']['Row'] as $key2 => $value2) {
                                if (isset($value2['ColData'][8]['value']) && $value2['ColData'][8]['value'] != '') {
                                    //echo "<pre>"; print_r($value2); //die;
                                    ## if Default account number is not set then set a random number                           
                                    $defaultAccountNo = '';
                                    if (isset($value2['ColData'][6]['value'])) {
                                        //$dan = explode($defaultAccountNo, $value2['ColData'][6]['value']);
                                        $dan = explode(' ', $value2['ColData'][6]['value']);
                                        if (is_numeric($dan[0])) {
                                            $defaultAccountNo = $dan[0];
                                        } else {
                                            $defaultAccountNo = rand(100, 10000);
                                            $defaultAccountNo = 1234;
                                        }
                                    }
                                    if ($defaultAccountNo < 4000) {
                                        $transactionData[$key2]['frenns_id'] = $uniqueId;
                                        $transactionData[$key2]['unique_frenns_id'] = $uniqueUpdateId;
                                        //$transactionData['company_account_number'] = '';
                                        $transactionData[$key2]['companyname'] = isset($value2['ColData'][4]['value']) ? $value2['ColData'][4]['value'] : '';
                                        //$transactionData[$key2]['companynumber'] = '';
                                        $transactionData[$key2]['collection_date'] = date('Y-m-d');
                                        $transactionData[$key2]['last_updated'] = isset($value2['ColData'][0]['value']) ? $value2['ColData'][0]['value'] : '';
                                        $transactionData[$key2]['entry_number'] = '';
                                        $transactionData[$key2]['sourcetype'] = isset($value2['ColData'][1]['value']) ? $value2['ColData'][1]['value'] : '';
                                        $transactionData[$key2]['sourceid'] = isset($value2['ColData'][6]['id']) ? $value2['ColData'][6]['id'] : '';
                                        $transactionData[$key2]['Reference'] = '';
                                        $transactionData[$key2]['description'] = isset($value2['ColData'][5]['value']) ? $value2['ColData'][5]['value'] : '';
                                        $transactionData[$key2]['entry_date'] = isset($value2['ColData'][0]['value']) ? $value2['ColData'][0]['value'] : '';
                                        $transactionData[$key2]['debit_amount'] = isset($value2['ColData'][8]['value']) ? $value2['ColData'][8]['value'] : '';
                                        $transactionData[$key2]['CreditAmount'] = isset($value2['ColData'][8]['value']) ? $value2['ColData'][8]['value'] : '';
                                        $transactionData[$key2]['TransactionLastUpdatedOn'] = isset($value2['ColData'][0]['value']) ? $value2['ColData'][0]['value'] : '';
                                        $transactionData[$key2]['NominalAccountCode'] = $defaultAccountNo;
                                        $transactionData[$key2]['InvoiceDescription'] = isset($value2['ColData'][5]['value']) ? $value2['ColData'][5]['value'] : '';
                                        $transactionData[$key2]['updateId'] = $uniqueUpdateId;

                                        #Save Transaction For current customer
                                        if (!empty($transactionData)) {
                                            //echo "<pre>"; print_r($transactionData); die('Quickbook Transaction Data 757');                                       
                                            DB::table('syncledger_transaction')->insert($transactionData[$key2]);
                                            $transactionData = '';
                                        }
                                    } else {
                                        $nominalTransactionData[$key2]['frenns_id'] = $uniqueId;
                                        $nominalTransactionData[$key2]['unique_frenns_id'] = $uniqueUpdateId;
                                        //$transactionData['company_account_number'] = '';
                                        $nominalTransactionData[$key2]['companyname'] = isset($value2['ColData'][4]['value']) ? $value2['ColData'][4]['value'] : '';
                                        //$transactionData[$key2]['companynumber'] = '';
                                        $nominalTransactionData[$key2]['type'] = $defaultAccountNo;
                                        $nominalTransactionData[$key2]['name'] = '';
                                        $nominalTransactionData[$key2]['account_type'] = isset($value2['ColData'][1]['value']) ? $value2['ColData'][1]['value'] : '';
                                        $nominalTransactionData[$key2]['total_debit'] = isset($value2['ColData'][8]['value']) ? $value2['ColData'][8]['value'] : '';
                                        $nominalTransactionData[$key2]['total_credit'] = isset($value2['ColData'][8]['value']) ? $value2['ColData'][8]['value'] : '';
                                        $nominalTransactionData[$key2]['collection_date'] = date('Y-m-d');
                                        $nominalTransactionData[$key2]['last_updated'] = isset($value2['ColData'][0]['value']) ? $value2['ColData'][0]['value'] : '';
                                        $nominalTransactionData[$key2]['updateId'] = $uniqueUpdateId;

                                        #Save Nominal Transaction For current customer
                                        if (!empty($nominalTransactionData)) {
                                            //echo "<pre>"; print_r($nominalTransactionData[$key2]); die('Quickbook Nominal Transaction Data 777s');                                           
                                            DB::table('syncnominal')->insert($nominalTransactionData[$key2]);
                                            $nominalTransactionData = '';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }// Customer Response Foreach
            die('All information has been stored successfully!!');
        } else {
            echo "No user available in database.";
            exit;
        }
    }

    ################################ Quickbook API End Here ############################
    ################################# Clearbook Start ###################################

    function clearbookData() {

        $type = 'clearbook';
        if (isset($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        //$getdetail = $this->apidetail->getSingleUserDetail($type, $userNumber);
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $type)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $type)->where('usernumber', $userNumber)->get();
        }
        //echo "<prE>"; print_r($getdetail); die;
        if (!empty($getdetail)) {
            for ($i = 0; $i < count($getdetail); $i++) {
                $usernumber = $getdetail[$i]->usernumber;
                $apiKey = $getdetail[$i]->apiKey;
                $uniqueFrennsId = $getdetail[$i]->accounting_system . "-" . $usernumber;

                // max time for invoice 
                $intype = '1';
                //$getLastUpdatedInvoice = $this->apidetail->getLastUpdatedInvoice($usernumber, $intype);
                $getLastUpdatedInvoice = DB::table('syncinvoice')->where('unique_frenns_id', $uniqueFrennsId)->where('type', '!=', 'expense')->orderBy('last_updated', 'desc')->limit(1)->get();
                // echo "<prE>";
                // print_r($getLastUpdatedInvoice);
                // die;

                if (count($getLastUpdatedInvoice) > 0) {
                    $lastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                    $lastUpdatedDate = date("Y-m-d", strtotime($lastUpdatedTime));
                } else {
                    $lastUpdatedTime = '';
                    $lastUpdatedDate = '';
                }
                // get inserted invoice
                $idArray = array();
                //$getTodayInsertedInvoice = $this->apidetail->getTodayInsertedInvoice($usernumber, '2');
                $getTodayInsertedInvoice = DB::table('syncinvoice')->where('unique_frenns_id', $uniqueFrennsId)->Where('last_updated', 'like', '%' . $lastUpdatedDate . '%')->get();
                //echo '<pre>';print_r($getTodayInsertedInvoice); die;
                if (count($getTodayInsertedInvoice) > 0) {
                    foreach ($getTodayInsertedInvoice as $dbData) {
                        $exp = explode("-", $dbData->updateId);
                        $idArray[] = $exp[2];
                    }
                } else {
                    $idArray[] = array();
                }
                //print_r($idArray);
                //$class = 'ListInvoices';
                spl_autoload_register(function( $class ) {
                    if (strpos($class, 'Clearbooks_') !== 0) {
                        return false;
                    }

                    require_once (app_path('third_party/clearbooks/src/') . str_replace('_', '/', $class) . '.php');
                    return true;
                });
                $client = new \Clearbooks_Soap_1_0($apiKey);

                // sales invoice
                $invoiceQuery = new \Clearbooks_Soap_1_0_InvoiceQuery();
                // $invoiceQuery->ledger = 'sales';
                //$invoices = $client->listInvoices( $invoiceQuery );
                // echo '<pre>';
                // print_r( $invoices );
                // echo "<pre>";print_r($invoiceQuery); die;


                if (!empty($lastUpdatedDate)) {
                    $invoiceQuery->modifiedSince = $lastUpdatedDate;
                }
                $invoiceQuery->ledger = 'sales';
                //$invoiceQuery->offset = '1000';
                $invoices = $client->listInvoices($invoiceQuery);


                if (empty($invoices)) {
                    //die("Application Authentication Failed For <b>" . $uniqueId . "</b>. Please contact to administrator.");
                    return view('authenticationFailed', ['uniqueId' => $usernumber, 'response' => 'Your free trial has expired ']);
                }
//                
                //echo "<pre>";                print_r($invoices);                die;
                foreach ($invoices as $key2 => $value2) {
                    $invoice_prefix = $value2->invoice_prefix;
                    if ($invoice_prefix == 'INV') {
                        $createdTimeInvoice = date("Y-m-d", strtotime($value2->dateCreated));
                        $updatedTimeInvoice = date("Y-m-d", strtotime($value2->dateModified));
                        $invoiceId = $value2->type . $value2->invoiceNumber;
                        $invoiceIdd = $value2->invoiceNumber;
                        $invoiceUpdateId = $uniqueFrennsId . '-' . $value2->invoiceNumber;
                        $invoiceItemUpdateId = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;

                        if ($value2->status == 'paid') {
                            $paid = 'true';
                        } else {
                            $paid = 'false';
                        }


                        //echo $createdTimeInvoice . ' > ' . $lastUpdatedDate . '====' . $invoiceId . '<br>';
                        if ($createdTimeInvoice >= $lastUpdatedDate) {
                            // echo $invoiceId.'<br>';
                            $existId = in_array($invoiceId, $idArray);
                            if (!empty($existId)) {
                                //echo 'up 1 ';
                                $updateInvoice[$key2]['frenns_id'] = $usernumber;
                                $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvoice[$key2]['last_updated'] = $value2->dateModified;
                                $updateInvoice[$key2]['invoice_number'] = $value2->invoiceNumber;
                                $updateInvoice[$key2]['amount'] = $value2->net;
                                $updateInvoice[$key2]['issue_date'] = $value2->dateCreated;
                                $updateInvoice[$key2]['creation_date'] = $value2->dateCreated;
                                $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                                $updateInvoice[$key2]['vat_amount'] = $value2->vat;
                                //$addInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                                //$addInvDetailList[$key2]['currency'] = $value2->currency->id;
                                $updateInvoice[$key2]['paid'] = $paid;
                                //$updateInvoice[$key2]['pay_date'] = $value2->dateAccrual;
                                $updateInvoice[$key2]['due_date'] = $value2->dateDue;

                                if ($value2->type == 'S') {
                                    $typ = 'sales invoice';
                                }

                                $entityQuery = new \Clearbooks_Soap_1_0_EntityQuery();
                                $entityQuery->id[] = $value2->entityId;
                                $entities = $client->listEntities($entityQuery);
                                //print_r($entities);

                                $updateInvoice[$key2]['name'] = $entities[0]->company_name;
                                $updateInvoice[$key2]['company_number'] = $entities[0]->company_number;
                                $updateInvoice[$key2]['contact_person'] = $entities[0]->contact_name;
                                $updateInvoice[$key2]['phone_no'] = $entities[0]->phone1;
                                $updateInvoice[$key2]['email'] = $entities[0]->email;
                                $updateInvoice[$key2]['type'] = $typ;
                                $updateInvoice[$key2]['account_number'] = $entities[0]->bankAccount->accountNumber;
                                if (!empty($entities[0]->customer)) {
                                    $entityType = 'customer';
                                }
                                if (!empty($entities[0]->supplier)) {
                                    $entityType = 'supplier';
                                }

                                $updateInvoice[$key2]['payment_terms'] = $entities[0]->$entityType->default_credit_terms;
                                //$addInvDetailList[$key2]['payment_method'] = $entities[0]->company_name;
                                $updateInvoice[$key2]['vat_registration_number'] = $entities[0]->vat_number;
                                $updateInvoice[$key2]['address'] = $entities[0]->building . ', ' . $entities[0]->address1 . ', ' . $entities[0]->address2 . ', ' . $entities[0]->town;
                                $updateInvoice[$key2]['city'] = $entities[0]->county;
                                $updateInvoice[$key2]['country'] = $entities[0]->country;
                                $updateInvoice[$key2]['postcode'] = $entities[0]->postcode;
                                $updateInvoice[$key2]['outstanding_amount'] = $value2->balance;
                                $updateInvoice[$key2]['updateId'] = $invoiceItemUpdateId;

                                ################ add update invoice here ###################                                
                                if (!empty($updateInvoice)) {
                                    //$updateInvoice = $this->apidetail->updateInvoice($updateInvoice, 'updateId');
                                    $updateInvoice = DB::table('syncinvoice')->where('updateId', $invoiceItemUpdateId)->update($updateInvoice[$key2]);
                                    $updateInvoice = array();
                                }

                                if (isset($value2->items)) {
                                    $itemKey = 0;
                                    $itemLine = $itemKey + 1;
                                    foreach ($value2->items as $key => $invoiceLine) {
                                        $invoiceItemData[$key . $itemKey]['frenns_id'] = $usernumber;
                                        $invoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                        $invoiceItemData[$key . $itemKey]['invoice_number'] = isset($value2->invoiceNumber) ? $value2->invoiceNumber : '';
                                        $invoiceItemData[$key . $itemKey]['line_number'] = $itemLine;
                                        $invoiceItemData[$key . $itemKey]['product_code'] = '';
                                        $invoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine->description) ? $invoiceLine->description : '';
                                        $invoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine->quantity) ? $invoiceLine->quantity : '';
                                        $invoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine->unitPrice) ? $invoiceLine->unitPrice : '';
                                        $invoiceItemData[$key . $itemKey]['amount_net'] = $invoiceLine->unitPrice * $invoiceLine->quantity;
                                        $invoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine->vat) ? $invoiceLine->vat : '';
                                        $invoiceItemData[$key . $itemKey]['amount_total'] = $invoiceLine->unitPrice * $invoiceLine->quantity + $value2->vat;
                                        $invoiceItemData[$key . $itemKey]['invoice_id'] = $value2->type . $value2->invoiceNumber;
                                        $invoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                        ## Delete all invoice items
                                        DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                        $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItemData[$key . $itemKey]);

                                        $itemKey++;
                                    }
                                    //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItemData, $updateId);
                                }
                            } else {
                                //echo 'add 1 ';
                                //echo $value2->invoiceNumber.'addexi<br>'; die;
                                $addInvoice[$key2]['frenns_id'] = $usernumber;
                                $addInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $addInvoice[$key2]['last_updated'] = $value2->dateModified;
                                $addInvoice[$key2]['invoice_number'] = $value2->invoiceNumber;
                                $addInvoice[$key2]['amount'] = $value2->net;
                                $addInvoice[$key2]['creation_date'] = $value2->dateCreated;
                                $addInvoice[$key2]['issue_date'] = $value2->dateCreated;
                                $addInvoice[$key2]['collection_date'] = date('Y-m-d');
                                $addInvoice[$key2]['vat_amount'] = $value2->vat;
                                //$addInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                                //$addInvDetailList[$key2]['currency'] = $value2->currency->id;
                                $addInvoice[$key2]['paid'] = $paid;
                                //$addInvoice[$key2]['pay_date'] = $value2->dateAccrual;
                                $addInvoice[$key2]['due_date'] = $value2->dateDue;

                                if ($value2->type == 'S') {
                                    $typ = 'sales invoice';
                                } else {
                                    $typ = 'purchase invoice';
                                }

                                $entityQuery = new \Clearbooks_Soap_1_0_EntityQuery();
                                $entityQuery->id[] = $value2->entityId;
                                $entities = $client->listEntities($entityQuery);
                                //print_r($entities);

                                $addInvoice[$key2]['name'] = $entities[0]->company_name;
                                $addInvoice[$key2]['company_number'] = $entities[0]->company_number;
                                $addInvoice[$key2]['contact_person'] = $entities[0]->contact_name;
                                $addInvoice[$key2]['phone_no'] = $entities[0]->phone1;
                                $addInvoice[$key2]['email'] = $entities[0]->email;
                                $addInvoice[$key2]['type'] = $typ;
                                $addInvoice[$key2]['account_number'] = $entities[0]->bankAccount->accountNumber;
                                if (!empty($entities[0]->customer)) {
                                    $entityType = 'customer';
                                }
                                if (!empty($entities[0]->supplier)) {
                                    $entityType = 'supplier';
                                }

                                $addInvoice[$key2]['payment_terms'] = $entities[0]->$entityType->default_credit_terms;
                                //$addInvDetailList[$key2]['payment_method'] = $entities[0]->company_name;
                                $addInvoice[$key2]['vat_registration_number'] = $entities[0]->vat_number;
                                $addInvoice[$key2]['address'] = $entities[0]->building . ', ' . $entities[0]->address1 . ', ' . $entities[0]->address2 . ', ' . $entities[0]->town;
                                $addInvoice[$key2]['city'] = $entities[0]->county;
                                $addInvoice[$key2]['country'] = $entities[0]->country;
                                $addInvoice[$key2]['postcode'] = $entities[0]->postcode;
                                $addInvoice[$key2]['invoiceId'] = $value2->type . $value2->invoiceNumber;
                                $addInvoice[$key2]['outstanding_amount'] = $value2->balance;
                                $addInvoice[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;

                                ################ add update invoice here ###################
                                if (!empty($addInvoice)) {

                                    //$addInvoice = $this->apidetail->addInvoice($addInvoice);
                                    $addSuppliersItem = DB::table('syncinvoice')->insert($addInvoice[$key2]);
                                    //$addInvoice = array();
                                }

                                if (isset($value2->items)) {
                                    $itemKey = 0;
                                    $invoiceItemData = array();
                                    $itemLine = $itemKey + 1;
                                    //print_r($value2->items);
                                    foreach ($value2->items as $key => $invoiceLine) {
                                        $invoiceItemData[$key . $itemKey]['frenns_id'] = $usernumber;
                                        $invoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                        $invoiceItemData[$key . $itemKey]['invoice_number'] = isset($value2->invoiceNumber) ? $value2->invoiceNumber : '';
                                        $invoiceItemData[$key . $itemKey]['line_number'] = $itemLine;
                                        $invoiceItemData[$key . $itemKey]['product_code'] = '';
                                        $invoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine->description) ? $invoiceLine->description : '';
                                        $invoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine->quantity) ? $invoiceLine->quantity : '';
                                        $invoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine->unitPrice) ? $invoiceLine->unitPrice : '';
                                        $invoiceItemData[$key . $itemKey]['amount_net'] = $invoiceLine->unitPrice * $invoiceLine->quantity;
                                        $invoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine->vat) ? $invoiceLine->vat : '';
                                        $invoiceItemData[$key . $itemKey]['amount_total'] = $invoiceLine->unitPrice * $invoiceLine->quantity + $value2->vat;
                                        $invoiceItemData[$key . $itemKey]['invoice_id'] = $value2->type . $value2->invoiceNumber;
                                        $invoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                        ## Delete all invoice items
                                        DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                        $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItemData[$key . $itemKey]);

                                        $itemKey++;
                                    }
                                    //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItemData, $updateId);
                                }
                            }
                        } else if ($updatedTimeInvoice > $lastUpdatedDate) {
                            //echo 'up 2 ';
                            $updateInvoice[$key2]['frenns_id'] = $usernumber;
                            $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateInvoice[$key2]['last_updated'] = $value2->dateModified;
                            $updateInvoice[$key2]['invoice_number'] = $value2->invoiceNumber;
                            $updateInvoice[$key2]['amount'] = $value2->net;
                            $updateInvoice[$key2]['issue_date'] = $value2->dateCreated;
                            $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                            $updateInvoice[$key2]['vat_amount'] = $value2->vat;
                            //$addInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                            //$addInvDetailList[$key2]['currency'] = $value2->currency->id;
                            $updateInvoice[$key2]['paid'] = $paid;
                            //$updateInvoice[$key2]['pay_date'] = $value2->dateAccrual;
                            $updateInvoice[$key2]['due_date'] = $value2->dateDue;

                            if ($value2->type == 'S') {
                                $typ = 'sales invoice';
                            }

                            $entityQuery = new \Clearbooks_Soap_1_0_EntityQuery();
                            $entityQuery->id[] = $value2->entityId;
                            $entities = $client->listEntities($entityQuery);
                            //print_r($entities);

                            $updateInvoice[$key2]['name'] = $entities[0]->company_name;
                            $updateInvoice[$key2]['company_number'] = $entities[0]->company_number;
                            $updateInvoice[$key2]['contact_person'] = $entities[0]->contact_name;
                            $updateInvoice[$key2]['phone_no'] = $entities[0]->phone1;
                            $updateInvoice[$key2]['email'] = $entities[0]->email;
                            $updateInvoice[$key2]['type'] = $typ;
                            $updateInvoice[$key2]['account_number'] = $entities[0]->bankAccount->accountNumber;
                            if (!empty($entities[0]->customer)) {
                                $entityType = 'customer';
                            }
                            if (!empty($entities[0]->supplier)) {
                                $entityType = 'supplier';
                            }
                            $updateInvoice[$key2]['payment_terms'] = $entities[0]->$entityType->default_credit_terms;
                            //$addInvDetailList[$key2]['payment_method'] = $entities[0]->company_name;
                            $updateInvoice[$key2]['vat_registration_number'] = $entities[0]->vat_number;
                            $updateInvoice[$key2]['address'] = $entities[0]->building . ', ' . $entities[0]->address1 . ', ' . $entities[0]->address2 . ', ' . $entities[0]->town;
                            $updateInvoice[$key2]['city'] = $entities[0]->county;
                            $updateInvoice[$key2]['country'] = $entities[0]->country;
                            $updateInvoice[$key2]['postcode'] = $entities[0]->postcode;
                            $updateInvoice[$key2]['outstanding_amount'] = $value2->balance;
                            $updateInvoice[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;

                            ################ add update invoice here ###################                            
                            if (!empty($updateInvoice)) {
                                //$updateInvoice = $this->apidetail->updateInvoice($updateInvoice, 'updateId');
                                $updateInvoice = DB::table('syncinvoice')->where('updateId', $invoiceUpdateId)->update($updateInvoice[$key2]);
                                $updateInvoice = array();
                            }

                            if (isset($value2->items)) {
                                $itemKey = 0;
                                $itemLine = $itemKey + 1;
                                foreach ($value2->items as $key => $invoiceLine) {

                                    $invoiceItemData[$key . $itemKey]['frenns_id'] = $usernumber;
                                    $invoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                    $invoiceItemData[$key . $itemKey]['invoice_number'] = isset($value2->invoiceNumber) ? $value2->invoiceNumber : '';
                                    $invoiceItemData[$key . $itemKey]['line_number'] = $itemLine;
                                    $invoiceItemData[$key . $itemKey]['product_code'] = '';
                                    $invoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine->description) ? $invoiceLine->description : '';
                                    $invoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine->quantity) ? $invoiceLine->quantity : '';
                                    $invoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine->unitPrice) ? $invoiceLine->unitPrice : '';
                                    $invoiceItemData[$key . $itemKey]['amount_net'] = $invoiceLine->unitPrice * $invoiceLine->quantity;
                                    $invoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine->vat) ? $invoiceLine->vat : '';
                                    $invoiceItemData[$key . $itemKey]['amount_total'] = $invoiceLine->unitPrice * $invoiceLine->quantity + $value2->vat;
                                    $invoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                    ## Delete all invoice items
                                    DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                    $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItemData[$key . $itemKey]);

                                    $itemKey++;
                                }
                                $addInvoiceItem = array();

                                //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItemData, $updateId);
                            }
                        } else {
                            ##
                        }
                    }
                }

                //purchase invoice
                $purchaseinvoiceQuery = new \Clearbooks_Soap_1_0_InvoiceQuery();
                if (!empty($lastUpdatedDate)) {
                    //$purchaseinvoiceQuery->modifiedSince = $lastUpdatedDate;
                }
                $purchaseinvoiceQuery->ledger = 'purchase';
                //$purchaseinvoiceQuery->offset = '1000';
                $purchaseinvoices = $client->listInvoices($purchaseinvoiceQuery);
                foreach ($purchaseinvoices as $key2 => $value2) {
                    $invoice_prefix = $value2->invoice_prefix;
                    if ($invoice_prefix == 'PUR') {
                        $createdTimeInvoice = $value2->dateCreated;
                        $updatedTimeInvoice = $value2->dateModified;
                        $invoiceId = $value2->type . $value2->invoiceNumber;
                        $updateId = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;

                        if ($value2->status == 'paid') {
                            $paid = 'true';
                        } else {
                            $paid = 'false';
                        }
                        //echo $createdTimeInvoice . '---' . $lastUpdatedTime . '====' . $invoiceId . '<br>';
                        if ($createdTimeInvoice >= $lastUpdatedDate) {
                            $existId = in_array($invoiceId, $idArray);
                            //print_r($existId); echo '<br>';
                            if (!empty($existId)) {

                                $updateInvoice[$key2]['frenns_id'] = $usernumber;
                                $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvoice[$key2]['last_updated'] = $value2->dateModified;
                                $updateInvoice[$key2]['invoice_number'] = $value2->invoiceNumber;
                                $updateInvoice[$key2]['amount'] = $value2->net;
                                $updateInvoice[$key2]['issue_date'] = $value2->dateCreated;
                                $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                                $updateInvoice[$key2]['vat_amount'] = $value2->vat;
                                //$addInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                                //$addInvDetailList[$key2]['currency'] = $value2->currency->id;
                                $updateInvoice[$key2]['paid'] = $paid;
                                //$updateInvoice[$key2]['pay_date'] = $value2->dateAccrual;
                                $updateInvoice[$key2]['due_date'] = $value2->dateDue;

                                if ($value2->type == 'P') {
                                    $typ = 'purchase invoice';
                                }

                                $entityQuery = new \Clearbooks_Soap_1_0_EntityQuery();
                                $entityQuery->id[] = $value2->entityId;
                                $entities = $client->listEntities($entityQuery);
                                //print_r($entities);

                                $updateInvoice[$key2]['name'] = $entities[0]->company_name;
                                $updateInvoice[$key2]['company_number'] = $entities[0]->company_number;
                                $updateInvoice[$key2]['contact_person'] = $entities[0]->contact_name;
                                $updateInvoice[$key2]['phone_no'] = $entities[0]->phone1;
                                $updateInvoice[$key2]['email'] = $entities[0]->email;
                                $updateInvoice[$key2]['type'] = $typ;
                                $updateInvoice[$key2]['account_number'] = $entities[0]->bankAccount->accountNumber;
                                if (!empty($entities[0]->customer)) {
                                    $entityType = 'customer';
                                }
                                if (!empty($entities[0]->supplier)) {
                                    $entityType = 'supplier';
                                }

                                $updateInvoice[$key2]['payment_terms'] = $entities[0]->$entityType->default_credit_terms;
                                //$addInvDetailList[$key2]['payment_method'] = $entities[0]->company_name;
                                $updateInvoice[$key2]['vat_registration_number'] = $entities[0]->vat_number;
                                $updateInvoice[$key2]['address'] = $entities[0]->building . ', ' . $entities[0]->address1 . ', ' . $entities[0]->address2 . ', ' . $entities[0]->town;
                                $updateInvoice[$key2]['city'] = $entities[0]->county;
                                $updateInvoice[$key2]['country'] = $entities[0]->country;
                                $updateInvoice[$key2]['postcode'] = $entities[0]->postcode;
                                $updateInvoice[$key2]['outstanding_amount'] = $value2->balance;
                                $updateInvoice[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;

                                ################ add update invoice here ###################
                                if (!empty($updateInvoice)) {
                                    //$updateInvoice = $this->apidetail->updateInvoice($updateInvoice, 'updateId');
                                    $addSuppliersItem = DB::table('syncinvoice')->insert($updateInvoice[$key2]);
                                    $updateInvoice = array();
                                }

                                if (isset($value2->items)) {
                                    $itemKey = 0;
                                    $itemLine = $itemKey + 1;
                                    foreach ($value2->items as $key => $invoiceLine) {
                                        $invoiceItemData[$key . $itemKey]['frenns_id'] = $usernumber;
                                        $invoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                        $invoiceItemData[$key . $itemKey]['invoice_number'] = isset($value2->invoiceNumber) ? $value2->invoiceNumber : '';
                                        $invoiceItemData[$key . $itemKey]['line_number'] = $itemLine;
                                        $invoiceItemData[$key . $itemKey]['product_code'] = '';
                                        $invoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine->description) ? $invoiceLine->description : '';
                                        $invoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine->quantity) ? $invoiceLine->quantity : '';
                                        $invoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine->unitPrice) ? $invoiceLine->unitPrice : '';
                                        $invoiceItemData[$key . $itemKey]['amount_net'] = $invoiceLine->unitPrice * $invoiceLine->quantity;
                                        $invoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine->vat) ? $invoiceLine->vat : '';
                                        $invoiceItemData[$key . $itemKey]['amount_total'] = $invoiceLine->unitPrice * $invoiceLine->quantity + $value2->vat;
                                        $invoiceItemData[$key . $itemKey]['invoice_id'] = $value2->type . $value2->invoiceNumber;
                                        $invoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                        ## Delete all invoice items
                                        DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                        $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItemData[$key . $itemKey]);

                                        $itemKey++;
                                    }

                                    //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItemData, $updateId);
                                }
                            } else {

                                $addInvoice[$key2]['frenns_id'] = $usernumber;
                                $addInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $addInvoice[$key2]['last_updated'] = $value2->dateModified;
                                $addInvoice[$key2]['invoice_number'] = $value2->invoiceNumber;
                                $addInvoice[$key2]['amount'] = $value2->net;
                                $addInvoice[$key2]['issue_date'] = $value2->dateCreated;
                                $addInvoice[$key2]['collection_date'] = date('Y-m-d');
                                $addInvoice[$key2]['vat_amount'] = $value2->vat;
                                //$addInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                                //$addInvDetailList[$key2]['currency'] = $value2->currency->id;
                                $addInvoice[$key2]['paid'] = $paid;
                                //$addInvoice[$key2]['pay_date'] = $value2->dateAccrual;
                                $addInvoice[$key2]['due_date'] = $value2->dateDue;

                                if ($value2->type == 'P') {
                                    $typ = 'purchase invoice';
                                }

                                $entityQuery = new \Clearbooks_Soap_1_0_EntityQuery();
                                $entityQuery->id[] = $value2->entityId;
                                $entities = $client->listEntities($entityQuery);
                                //print_r($entities);

                                $addInvoice[$key2]['name'] = $entities[0]->company_name;
                                $addInvoice[$key2]['company_number'] = $entities[0]->company_number;
                                $addInvoice[$key2]['contact_person'] = $entities[0]->contact_name;
                                $addInvoice[$key2]['phone_no'] = $entities[0]->phone1;
                                $addInvoice[$key2]['email'] = $entities[0]->email;
                                $addInvoice[$key2]['type'] = $typ;
                                $addInvoice[$key2]['account_number'] = $entities[0]->bankAccount->accountNumber;
                                if (!empty($entities[0]->customer)) {
                                    $entityType = 'customer';
                                }
                                if (!empty($entities[0]->supplier)) {
                                    $entityType = 'supplier';
                                }

                                $addInvoice[$key2]['payment_terms'] = $entities[0]->$entityType->default_credit_terms;
                                //$addInvDetailList[$key2]['payment_method'] = $entities[0]->company_name;
                                $addInvoice[$key2]['vat_registration_number'] = $entities[0]->vat_number;
                                $addInvoice[$key2]['address'] = $entities[0]->building . ', ' . $entities[0]->address1 . ', ' . $entities[0]->address2 . ', ' . $entities[0]->town;
                                $addInvoice[$key2]['city'] = $entities[0]->county;
                                $addInvoice[$key2]['country'] = $entities[0]->country;
                                $addInvoice[$key2]['postcode'] = $entities[0]->postcode;
                                $addInvoice[$key2]['invoiceId'] = $value2->type . $value2->invoiceNumber;
                                $addInvoice[$key2]['outstanding_amount'] = $value2->balance;
                                $addInvoice[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;

                                ################ add update invoice here ###################
                                if (!empty($addInvoice)) {
                                    //$addInvoice = $this->apidetail->addInvoice($addInvoice);
                                    $addSuppliersItem = DB::table('syncinvoice')->insert($addInvoice[$key2]);
                                    $addInvoice = array();
                                }

                                if (isset($value2->items)) {
                                    $itemKey = 0;
                                    $invoiceItemData = array();
                                    $itemLine = $itemKey + 1;
                                    //print_r($value2->items);
                                    $updateId = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;
                                    foreach ($value2->items as $key => $invoiceLine) {
                                        $invoiceItemData[$key . $itemKey]['frenns_id'] = $usernumber;
                                        $invoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                        $invoiceItemData[$key . $itemKey]['invoice_number'] = isset($value2->invoiceNumber) ? $value2->invoiceNumber : '';
                                        $invoiceItemData[$key . $itemKey]['line_number'] = $itemLine;
                                        $invoiceItemData[$key . $itemKey]['product_code'] = '';
                                        $invoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine->description) ? $invoiceLine->description : '';
                                        $invoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine->quantity) ? $invoiceLine->quantity : '';
                                        $invoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine->unitPrice) ? $invoiceLine->unitPrice : '';
                                        $invoiceItemData[$key . $itemKey]['amount_net'] = $invoiceLine->unitPrice * $invoiceLine->quantity;
                                        $invoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine->vat) ? $invoiceLine->vat : '';
                                        $invoiceItemData[$key . $itemKey]['amount_total'] = $invoiceLine->unitPrice * $invoiceLine->quantity + $value2->vat;
                                        $invoiceItemData[$key . $itemKey]['invoice_id'] = $value2->type . $value2->invoiceNumber;
                                        $invoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                        ## Delete all invoice items
                                        DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                        $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItemData[$key . $itemKey]);

                                        $itemKey++;
                                    }
                                    //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItemData, $updateId);
                                }
                            }
                        } else if ($updatedTimeInvoice > $lastUpdatedDate) {
                            $updateInvoice[$key2]['frenns_id'] = $usernumber;
                            $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateInvoice[$key2]['last_updated'] = $value2->dateModified;
                            $updateInvoice[$key2]['invoice_number'] = $value2->invoiceNumber;
                            $updateInvoice[$key2]['amount'] = $value2->net;
                            $updateInvoice[$key2]['issue_date'] = $value2->dateCreated;
                            $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                            $updateInvoice[$key2]['vat_amount'] = $value2->vat;
                            //$addInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                            //$addInvDetailList[$key2]['currency'] = $value2->currency->id;
                            $updateInvoice[$key2]['paid'] = $paid;
                            // $updateInvoice[$key2]['pay_date'] = $value2->dateAccrual;
                            $updateInvoice[$key2]['due_date'] = $value2->dateDue;

                            if ($value2->type == 'P') {
                                $typ = 'purchase invoice';
                            }

                            $entityQuery = new \Clearbooks_Soap_1_0_EntityQuery();
                            $entityQuery->id[] = $value2->entityId;
                            $entities = $client->listEntities($entityQuery);
                            //print_r($entities);

                            $updateInvoice[$key2]['name'] = $entities[0]->company_name;
                            $updateInvoice[$key2]['company_number'] = $entities[0]->company_number;
                            $updateInvoice[$key2]['contact_person'] = $entities[0]->contact_name;
                            $updateInvoice[$key2]['phone_no'] = $entities[0]->phone1;
                            $updateInvoice[$key2]['email'] = $entities[0]->email;
                            $updateInvoice[$key2]['type'] = $typ;
                            $updateInvoice[$key2]['account_number'] = $entities[0]->bankAccount->accountNumber;
                            if (!empty($entities[0]->customer)) {
                                $entityType = 'customer';
                            }
                            if (!empty($entities[0]->supplier)) {
                                $entityType = 'supplier';
                            }

                            $updateInvoice[$key2]['payment_terms'] = $entities[0]->$entityType->default_credit_terms;
                            //$addInvDetailList[$key2]['payment_method'] = $entities[0]->company_name;
                            $updateInvoice[$key2]['vat_registration_number'] = $entities[0]->vat_number;
                            $updateInvoice[$key2]['address'] = $entities[0]->building . ', ' . $entities[0]->address1 . ', ' . $entities[0]->address2 . ', ' . $entities[0]->town;
                            $updateInvoice[$key2]['city'] = $entities[0]->county;
                            $updateInvoice[$key2]['country'] = $entities[0]->country;
                            $updateInvoice[$key2]['postcode'] = $entities[0]->postcode;
                            $updateInvoice[$key2]['outstanding_amount'] = $value2->balance;
                            $updateInvoice[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2->type . $value2->invoiceNumber;

                            ################ add update invoice here ###################                            
                            if (!empty($updateInvoice)) {
                                //$updateInvoice = $this->apidetail->updateInvoice($updateInvoice, 'updateId');
                                $addSuppliersItem = DB::table('syncinvoice')->insert($updateInvoice[$key2]);
                                $updateInvoice = array();
                            }

                            if (isset($value2->items)) {
                                $itemKey = 0;
                                $itemLine = $itemKey + 1;
                                foreach ($value2->items as $key => $invoiceLine) {
                                    $invoiceItemData[$key . $itemKey]['frenns_id'] = $usernumber;
                                    $invoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                    $invoiceItemData[$key . $itemKey]['invoice_number'] = isset($value2->invoiceNumber) ? $value2->invoiceNumber : '';
                                    $invoiceItemData[$key . $itemKey]['line_number'] = $itemLine;
                                    $invoiceItemData[$key . $itemKey]['product_code'] = '';
                                    $invoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine->description) ? $invoiceLine->description : '';
                                    $invoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine->quantity) ? $invoiceLine->quantity : '';
                                    $invoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine->unitPrice) ? $invoiceLine->unitPrice : '';
                                    $invoiceItemData[$key . $itemKey]['amount_net'] = $invoiceLine->unitPrice * $invoiceLine->quantity;
                                    $invoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine->vat) ? $invoiceLine->vat : '';
                                    $invoiceItemData[$key . $itemKey]['amount_total'] = $invoiceLine->unitPrice * $invoiceLine->quantity + $value2->vat;
                                    $invoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                    ## Delete all invoice items
                                    DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                    $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItemData[$key . $itemKey]);

                                    $itemKey++;
                                }
                                //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItemData, $updateId);
                            }
                        } else {
                            ##
                        }
                    }
                }

                //CUSTOMER/SUPPLIER 
                // max time for supplier/customer
                $ContactlastUpdatedTime = '';
                //$getLastUpdatedContact = $this->apidetail->getLastUpdatedContact($usernumber);
                $getLastUpdatedContact = DB::table('syncsupplier')->select('last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();
                if (count($getLastUpdatedContact) > 0) {
                    $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                    $ContactlastUpdatedDate = date("Y-m-d", strtotime($ContactlastUpdatedTime));
                } else {
                    $ContactlastUpdatedTime = '';
                    $ContactlastUpdatedDate = '';
                }
                //get cust/suplier from db
                //$localContacts = $this->apidetail->getCustomerSuppliersIds($usernumber);
                $localContacts = DB::table('syncsupplier')->select('updateId')->where('unique_frenns_id', $uniqueFrennsId)->where('last_update', 'like', '%' . $ContactlastUpdatedDate . '%')->get();
                //echo '<pre>';print_r($localContacts);
                $localContactsArr = array();
                if (count($localContacts) > 0) {
                    foreach ($localContacts as $key => $localContact) {
                        // print_r($localContact);
                        $lc = explode('-', $localContact->updateId);
                        $localContactsArr[] = $lc[2];
                    }
                } else {
                    $localContactsArr = array();
                }
                //die;
                //print_r($localContactsArr);
                $entityQuery = new \Clearbooks_Soap_1_0_EntityQuery();
                if (!empty($ContactlastUpdatedDate)) {
                    $entityQuery->modifiedSince = $ContactlastUpdatedDate;
                }
                $entities = $client->listEntities($entityQuery);
                foreach ($entities as $key => $value1) {
                    if (!empty($value1->customer)) {
                        $type = 'customer';
                    }
                    if (!empty($value1->supplier)) {
                        $type = 'supplier';
                    }
                    //  echo $value1->id . '---' . $value1->date_modified . '====' . $ContactlastUpdatedDate . '<br>';

                    $updateId = $uniqueFrennsId . '-' . $value1->id;

                    if (!in_array($value1->id, $localContactsArr) && $type != '') {
                        // echo 'add';
                        $addClients[$key]['frenns_id'] = $usernumber;
                        $addClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                        $addClients[$key]['company_name'] = $value1->company_name;
                        $addClients[$key]['company_number'] = $value1->company_number;

                        $addClients[$key]['collection_date'] = date('Y-m-d');
                        $addClients[$key]['last_update'] = $value1->date_modified;
                        $addClients[$key]['type'] = $type;

                        $addClients[$key]['vat_registration'] = isset($value1->vat_number) ? $value1->vat_number : '';
                        $addClients[$key]['account_number'] = isset($value1->bankAccount->accountNumber) ? $value1->bankAccount->accountNumber : '';

                        $addClients[$key]['address'] = $value1->building . ', ' . $value1->address1 . ', ' . $value1->address2 . ', ' . $value1->town;
                        $addClients[$key]['city'] = $value1->county;
                        $addClients[$key]['country'] = $value1->country;
                        $addClients[$key]['postcode'] = $value1->postcode;
                        // contact person detail
                        $addClients[$key]['contact_person'] = $value1->contact_name;
                        $addClients[$key]['phone_number'] = $value1->phone2;
                        $addClients[$key]['email'] = $value1->email;
                        $addClients[$key]['updateId'] = $updateId;

                        // add client                       
                        if (!empty($addClients)) {
                            //$addSuppliersItem = $this->apidetail->addSuppliers($addClients);
                            $addSuppliersItem = DB::table('syncsupplier')->insert($addClients[$key]);
                            $addClients = array();
                        }
                    } else if ($value1->date_modified > $ContactlastUpdatedDate && $ContactlastUpdatedDate != '' && $type != '') {
                        //echo 'up kk';
                        $updateClients[$key]['frenns_id'] = $usernumber;
                        $updateClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                        $updateClients[$key]['company_name'] = $value1->company_name;
                        $updateClients[$key]['company_number'] = $value1->company_number;

                        $updateClients[$key]['collection_date'] = date('Y-m-d');
                        $updateClients[$key]['last_update'] = $value1->date_modified;
                        $updateClients[$key]['type'] = $type;

                        $updateClients[$key]['vat_registration'] = isset($value1->vat_number) ? $value1->vat_number : '';
                        $updateClients[$key]['account_number'] = isset($value1->bankAccount->accountNumber) ? $value1->bankAccount->accountNumber : '';

                        $updateClients[$key]['address'] = $value1->building . ', ' . $value1->address1 . ', ' . $value1->address2 . ', ' . $value1->town;
                        $updateClients[$key]['city'] = $value1->county;
                        $updateClients[$key]['country'] = $value1->country;
                        $updateClients[$key]['postcode'] = $value1->postcode;
                        // contact person detail
                        $updateClients[$key]['contact_person'] = $value1->contact_name;
                        $updateClients[$key]['phone_number'] = $value1->phone2;
                        $updateClients[$key]['email'] = $value1->email;
                        $updateClients[$key]['updateId'] = $updateId;

                        //update client
                        if (!empty($updateClients)) {
                            //$updateInvoiceItems = $this->apidetail->updateSuppliers($updateClients, 'updateId');
                            $updateInvoiceItems = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateClients[$key]);
                            $updateClients = array();
                        }
                    }
                }
            }
            echo "All information has been stored successfully!!";
        }
    }

    ############################### clearbook Here #####################################
    ################################ freshbook start ###################################

    public function freshbookDataTest1() {
        $client_id = 'c96790d42d843c1c9d65014485ad4f634d8eda4c667d66fefa10baf364d4de1e';
        $client_secret = '0fddf2c3be3d07eb0c7b429d6f64e65848a140f7eb5f870ff0e953faa717009e';
        $callbackUrl = 'https://blazeaccounting.co.uk/cisageone/freshbookData';
        $grant_type = 'authorization_code';
        $request_type = 'code';
        $request_type_val = '9ca8d51fbcdadf0251ef8336bc135b452e3902b996e830e5795c315c0631dade';
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
            print_r($response);
        }
    }

    public function freshbookData() {
        //echo '<pre>';
        $addExpense = array();
        $updateExpense = array();

        $accounting_system = 'freshbook';
        if (isset($_GET['code'])) {
            $code = $_GET['code'];
            //$userNumber = $_GET['userNumber'];
            //$this->apidetail->updateFreshbookCode($accounting_system, $code);
            DB::table('appDetail')->where('accounting_system', $accounting_system)->update('code', $code);
        }

        if (isset($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        //$getdetail = $this->apidetail->getSingleUserDetail($accounting_system, $userNumber);      
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }

        //print_r($getdetail);
        if (!empty($getdetail)) {
            $response = array();
            $addSupplierDetailList = array();
            $updateSupplierDetailList = array();

            for ($i = 0; $i < count($getdetail); $i++) {
                $usernumber = $getdetail[$i]->usernumber;
                $accountId = $getdetail[$i]->accountId;
                $access_token_db = $getdetail[$i]->access_token;
                $refresh_token_db = $getdetail[$i]->refresh_token;
                $codeDb = $getdetail[$i]->code;
                $uniqueFrennsId = $getdetail[$i]->accounting_system . "-" . $usernumber;


                //$detail = $this->apidetail->getApiDetail($accounting_system);
                $detail = $getdetail = DB::table('appDetail')->where('accounting_system', $accounting_system)->get();
                //print_r($detail); die;
                $client_id = '';
                $client_secret = '';
                $callbackUrl = '';
                // get refresh token
                if (!empty($client_id) && !empty($callbackUrl)) {
                    $responseCode = 'https://my.freshbooks.com/service/auth/oauth/authorize?client_id=' . $client_id . '&response_type=code&redirect_uri=' . $callbackUrl;

                    if (empty($codeDb)) {
                        $responseCodeResult = header("Location: $responseCode");
                    }
                }

                if (empty($refresh_token_db)) {
                    // echo 'a<br>';
                    $grant_type = 'authorization_code';
                    $request_type = 'code';
                    $request_type_val = $code;
                    $responseToken = Helper::freshbookGetRefreshToken($client_id, $client_secret, $callbackUrl, $grant_type, $request_type, $request_type_val);
                }

                if (!empty($refresh_token_db)) {
                    $grant_type = 'refresh_token';
                    $request_type = 'refresh_token';
                    $request_type_val = $refresh_token_db;
                    $responseToken = Helper::freshbookGetRefreshToken($client_id, $client_secret, $callbackUrl, $grant_type, $request_type, $request_type_val);
                }

                //print_r$getaccess_token$responseToken); echo '<br>'.$usernumber; die;

                $getaccess_token = json_decode($responseToken, true);
                // print_r($getaccess_token);
                if (isset($getaccess_token['error'])) {
                    if ($getaccess_token['error'] == 'invalid_grant') {
                        $error = $getaccess_token['error_description'];
                        return view('authenticationFailed', ['uniqueId' => $usernumber, 'response' => $error]);
                        exit;
                    }
                }
                $access_token = json_decode($responseToken, true)["access_token"];
                $refresh_token = json_decode($responseToken, true)["refresh_token"];

                //update refresh token
                if (!empty($access_token) && !empty($refresh_token)) {
                    //$updateTokendetail = $this->apidetail->updateUserDetail($usernumber, $access_token, $refresh_token);
                    $values = array('access_token' => $access_token, 'refresh_token' => $refresh_token);
                    $updateTokendetail = DB::table('synccredential')->where('usernumber', $usernumber)->update($values);
                }
                $parm = [
                    "Api-Version: alpha",
                    "Authorization: Bearer $access_token",
                    "Content-Type: application/json"
                ];

                //get account id
                $accountIdUrl = "https://api.freshbooks.com/auth/api/v1/users/me";
                $getAccountId = Helper::getCurlData($accountIdUrl, $parm);
                $getAccountId = json_decode($getAccountId, true)["response"]["business_memberships"][0]["business"]["account_id"];
                //echo '<pre>';print_r($getAccountId); die('----------');
                if (!empty($getAccountId)) {
                    //$updateAccountId = $this->apidetail->updateAccountIdFreshbook($usernumber, $getAccountId);
                    $values = array('accountId' => $getAccountId);
                    $updateTokendetail = DB::table('synccredential')->where('usernumber', $usernumber)->update($values);
                }

                // max time for supplier/customer
                $ContactlastUpdatedTime = '';
                //$getLastUpdatedContact = $this->apidetail->getLastUpdatedContact($usernumber);
                $getLastUpdatedContact = DB::table('syncsupplier')->select('syncsupplier.last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();
                //echo "<pre>"; print_r($getLastUpdatedContact); die('=========');
                if (count($getLastUpdatedContact) > 0) {
                    $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                    $ContactlastUpdatedDate = date("Y-m-d", strtotime($ContactlastUpdatedTime));
                } else {
                    $ContactlastUpdatedTime = '';
                    $ContactlastUpdatedDate = '';
                }
                //echo $ContactlastUpdatedDate;
                // get client list 

                if (!empty($ContactlastUpdatedTime)) {
                    $clientUrl = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients?search%5Bupdated_min%5D=$ContactlastUpdatedDate&per_page=2000";
                } else {
                    $clientUrl = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients?per_page=2000";
                }
                $getClients = Helper::getCurlData($clientUrl, $parm);
                //echo '<pre>';
                $getTodayInsertedContact = '';
                $idArray = array();
                //$getTodayInsertedContact = $this->apidetail->getTodayInsertedContact($usernumber, '2', $ContactlastUpdatedDate);
                $getTodayInsertedContact = DB::table('syncsupplier')->select('updateId')->where('last_update', 'like', '%' . $ContactlastUpdatedDate . '%')->get();
                //echo "<pre>"; print_r($getTodayInsertedContact); die('===========');
                if (!empty($getTodayInsertedContact)) {
                    foreach ($getTodayInsertedContact as $dbData) {
                        $exp = explode("-", $dbData->updateId);
                        $idArray[] = $exp[2];
                    }
                } else {
                    $idArray[] = array();
                }
                // print_r($idArray);
                $clientList = json_decode($getClients, true);
                if (!empty($clientList['response']['errors'])) {
                    //echo $clientList['response']['errors']['0']['message'].' for '.$usernumber; die();                                       
                    return view('authenticationFailed', ['uniqueId' => $usernumber, 'response' => $clientList['response']['errors']['0']['message']]);
                }
                //echo "<pre>"; print_r($clientList['response']['errors']['0']['message']); die('===========');

                foreach ($clientList['response'] as $valueResponse) {
                    foreach ($valueResponse['clients'] as $key => $value1) {
                        $updatedTimeSupplier = $value1['updated'];
                        $createdTimeSupplier = $value1['signup_date'];
                        $contactIDss = $value1['id'];

                        if ($createdTimeSupplier >= $ContactlastUpdatedTime) {
                            $existId = in_array($contactIDss, $idArray);
                            //echo '<pre>';
                            //print_r($contactIDss);
                            //print_r($idArray);
                            if (!empty($existId)) {
                                // echo 'blanck';
                            } else {
                                // echo 'add';
                                $addClients[$key]['frenns_id'] = $usernumber;
                                $addClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $addClients[$key]['company_name'] = $value1['organization'];

                                $addClients[$key]['collection_date'] = date('Y-m-d');
                                $addClients[$key]['last_update'] = $value1['updated'];
                                $addClients[$key]['type'] = $value1['role'];

                                $addClients[$key]['vat_registration'] = isset($value1['vat_number']) ? $value1['vat_number'] : '';
                                //$supplierDetailList['contact']['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                                $addClients[$key]['address'] = $value1['p_city'];
                                $addClients[$key]['postcode'] = $value1['p_code'];
                                $addClients[$key]['city'] = $value1['p_province'];
                                $addClients[$key]['country'] = $value1['p_country'];

                                // contact person detail
                                $addClients[$key]['contact_person'] = $value1['fname'] . ' ' . $value1['lname'];
                                $addClients[$key]['phone_number'] = $value1['home_phone'];
                                $addClients[$key]['email'] = $value1['email'];
                                $addClients[$key]['contactId'] = $value1['id'];
                                $addClients[$key]['updateId'] = $uniqueFrennsId . '-' . $value1['id'];

                                // add client
                                if (!empty($addClients)) {
                                    //echo 'add';
                                    //print_r($addClients);
                                    //$addSuppliersItem = $this->apidetail->addSuppliers($addClients);
                                    $addSuppliersItem = DB::table('syncsupplier')->insert($addClients[$key]);
                                    $addClients = array();
                                }
                            }
                        } else if ($updatedTimeSupplier > $ContactlastUpdatedTime) {
                            //echo 'update';
                            $updateClientsUpdateId = $uniqueFrennsId . '-' . $value1['id'];
                            $updateClients[$key]['frenns_id'] = $usernumber;
                            $updateClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateClients[$key]['company_name'] = $value1['organization'];

                            $updateClients[$key]['collection_date'] = date('Y-m-d');
                            $updateClients[$key]['last_update'] = $value1['updated'];
                            $updateClients[$key]['type'] = $value1['role'];

                            $updateClients[$key]['vat_registration'] = isset($value1['vat_number']) ? $value1['vat_number'] : '';
                            //$supplierDetailList['contact']['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                            $updateClients[$key]['address'] = $value1['p_city'];
                            $updateClients[$key]['postcode'] = $value1['p_code'];
                            $updateClients[$key]['city'] = $value1['p_province'];
                            $updateClients[$key]['country'] = $value1['p_country'];

                            // contact person detail
                            $updateClients[$key]['contact_person'] = $value1['fname'] . ' ' . $value1['lname'];
                            $updateClients[$key]['phone_number'] = $value1['home_phone'];
                            $updateClients[$key]['email'] = $value1['email'];
                            $updateClients[$key]['updateId'] = $updateClientsUpdateId;

                            //update client
                            if (!empty($updateClients)) {
                                // echo 'update';
                                // print_r($updateClients);
                                //$updateInvoiceItems = $this->apidetail->updateSuppliers($updateClients, 'updateId');
                                $updateInvoiceItems = DB::table('syncsupplier')->where('updateId', $updateClientsUpdateId)->update($updateClients[$key]);
                                $updateClients = array();
                            }
                        }
                    }
                }


                //max time for invoice
                $intype = '1';
                //$getLastUpdatedInvoice = $this->apidetail->getLastUpdatedInvoice($usernumber, $intype);
                $getLastUpdatedInvoice = DB::table('syncinvoice')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->get();
                //print_r($getLastUpdatedInvoice); die;
                //$updateAccountId = $this->apidetail->updateAccountIdFreshbook($usernumber, $getAccountId);
                $updateData['accountId'] = $getAccountId;
                $updateAccountId = DB::table('synccredential')->where('usernumber', $usernumber)->update($updateData);

                // max time for supplier/customer
                $ContactlastUpdatedTime = '';
                //$getLastUpdatedContact = $this->apidetail->getLastUpdatedContact($usernumber);
                $getLastUpdatedContact = DB::table('syncsupplier')->select('last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();
                //print_r($getLastUpdatedContact); die;
                if (count($getLastUpdatedContact) > 0) {
                    $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                    $ContactlastUpdatedDate = date("Y-m-d", strtotime($ContactlastUpdatedTime));
                }
                // get client list 
                if (!empty($ContactlastUpdatedTime)) {
                    $clientUrl = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients?search%5Bupdated_min%5D=$ContactlastUpdatedDate";
                } else {
                    $clientUrl = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients";
                }
                $getClients = Helper::getCurlData($clientUrl, $parm);
                //echo '<pre>';

                $idArray = array();
                //$getTodayInsertedContact = $this->apidetail->getTodayInsertedContact($usernumber, '2');
                $getTodayInsertedContact = DB::table('syncsupplier')->select('updateId')->where('unique_frenns_id', $uniqueFrennsId)->where('last_update', 'like', '%' . $ContactlastUpdatedDate . '%')->get();
                //print_r($getTodayInsertedContact); die;
                if (!empty($getTodayInsertedContact)) {
                    foreach ($getTodayInsertedContact as $dbData) {
                        $exp = explode("-", $dbData->updateId);
                        $idArray[] = $exp[2];
                    }
                } else {
                    $idArray[] = array();
                }
                // print_r($idArray);
                $clientList = json_decode($getClients, true);
                foreach ($clientList['response'] as $valueResponse) {

                    foreach ($valueResponse['clients'] as $key => $value1) {

                        $updatedTimeSupplier = $value1['updated'];
                        $createdTimeSupplier = $value1['signup_date'];
                        $contactIDss = $value1['id'];

                        if ($createdTimeSupplier >= $ContactlastUpdatedTime) {
                            $existId = in_array($contactIDss, $idArray);
                            if (!empty($existId) || empty($idArray)) {
                                
                            } else {
                                $addClients[$key]['frenns_id'] = $usernumber;
                                $addClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $addClients[$key]['company_name'] = $value1['organization'];

                                $addClients[$key]['collection_date'] = date('Y-m-d');
                                $addClients[$key]['last_update'] = $value1['updated'];
                                $addClients[$key]['type'] = $value1['role'];

                                $addClients[$key]['vat_registration'] = isset($value1['vat_number']) ? $value1['vat_number'] : '';
                                //$supplierDetailList['contact']['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                                $addClients[$key]['address'] = $value1['p_city'];
                                $addClients[$key]['postcode'] = $value1['p_code'];
                                $addClients[$key]['city'] = $value1['p_province'];
                                $addClients[$key]['country'] = $value1['p_country'];

                                // contact person detail
                                $addClients[$key]['contact_person'] = $value1['fname'] . ' ' . $value1['lname'];
                                $addClients[$key]['phone_number'] = $value1['home_phone'];
                                $addClients[$key]['email'] = $value1['email'];
                                $addClients[$key]['updateId'] = $uniqueFrennsId . '-' . $value1['id'];

                                // add client
                                if (!empty($addClients)) {
                                    //echo 'add';
                                    //print_r($addClients);
                                    //$addSuppliersItem = $this->apidetail->addSuppliers($addClients);
                                    $addSuppliersItem = DB::table('syncsupplier')->insert($addClients[$key]);
                                    $addClients = array();
                                }
                            }
                        } else if ($updatedTimeSupplier > $ContactlastUpdatedTime) {
                            $updateId = $uniqueFrennsId . '-' . $value1['id'];
                            $updateClients[$key]['frenns_id'] = $usernumber;
                            $updateClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateClients[$key]['company_name'] = $value1['organization'];

                            $updateClients[$key]['collection_date'] = date('Y-m-d');
                            $updateClients[$key]['last_update'] = $value1['updated'];
                            $updateClients[$key]['type'] = $value1['role'];

                            $updateClients[$key]['vat_registration'] = isset($value1['vat_number']) ? $value1['vat_number'] : '';
                            //$supplierDetailList['contact']['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                            $updateClients[$key]['address'] = $value1['p_city'];
                            $updateClients[$key]['postcode'] = $value1['p_code'];
                            $updateClients[$key]['city'] = $value1['p_province'];
                            $updateClients[$key]['country'] = $value1['p_country'];

                            // contact person detail
                            $updateClients[$key]['contact_person'] = $value1['fname'] . ' ' . $value1['lname'];
                            $updateClients[$key]['phone_number'] = $value1['home_phone'];
                            $updateClients[$key]['email'] = $value1['email'];
                            $updateClients[$key]['updateId'] = $updateId;


                            //update client
                            if (!empty($updateClients)) {
                                //echo 'update';
                                //print_r($updateClients);
                                //$updateInvoiceItems = $this->apidetail->updateSuppliers($updateClients, 'updateId');
                                $updateInvoiceItems = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateClients[$key]);
                                $updateClients = array();
                            }
                        }
                    }
                }

                // max time for invoice
                $intype = '1';
                //$getLastUpdatedInvoice = $this->apidetail->getLastUpdatedInvoice($usernumber, $intype);
                $getLastUpdatedInvoice = DB::table('syncinvoice')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->where('type', '!=', 'expense')->orderBy('last_updated', 'desc')->limit(1)->get();
                //print_r($getLastUpdatedInvoice); die('==');
                if (count($getLastUpdatedInvoice) > 0) {
                    $lastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                    $lastUpdatedDate = date("Y-m-d", strtotime($lastUpdatedTime));
                } else {
                    $lastUpdatedTime = '';
                    $lastUpdatedDate = '';
                }
                //echo $lastUpdatedTime.'dddd<br>';
                $idArray = array();
                //$getTodayInsertedInvoice = $this->apidetail->getTodayInsertedInvoice($usernumber, $lastUpdatedDate);
                $getTodayInsertedInvoice = DB::table('syncinvoice')->select('updateId')->where('unique_frenns_id', $uniqueFrennsId)->where('last_updated', 'like', '%' . $lastUpdatedDate . '%')->get();
                //print_r($getTodayInsertedInvoice); die('===');
                if (count($getTodayInsertedInvoice) > 0) {
                    foreach ($getTodayInsertedInvoice as $dbData) {
                        $exp = explode("-", $dbData->updateId);
                        $idArray1[] = $exp[2];
                    }
                } else {
                    $idArray1[] = array();
                }
                //print_r($idArray1);
                // get invoice
                if (!empty($lastUpdatedTime)) {
                    $invoiceUrl = "https://api.freshbooks.com/accounting/account/$getAccountId/invoices/invoices?search%5Bupdated_min%5D=$lastUpdatedDate&per_page=2000&include%5B%5D=lines";
                } else {
                    $invoiceUrl = "https://api.freshbooks.com/accounting/account/$getAccountId/invoices/invoices?per_page=2000&include%5B%5D=lines";
                }
                $getInvoices = Helper::getCurlData($invoiceUrl, $parm);
                $InvoiceList = json_decode($getInvoices, true);
                //echo '<pre>';print_r($InvoiceList);   die('====');
                foreach ($InvoiceList['response'] as $valueResponse) {
                    foreach ($valueResponse['invoices'] as $key2 => $value2) {
                        $updatedTimeInvoice = $value2['updated'];
                        $createdTimeInvoice = $value2['created_at'];
                        $updateId = $uniqueFrennsId . '-' . $value2['invoiceid'];
                        $invoiceIDss = $value2['id'];

                        if ($createdTimeInvoice >= $lastUpdatedTime) {
                            $existId1 = in_array($invoiceIDss, $idArray1);
                            //print_r($existId);
                            if (!empty($existId1) || empty($idArray1)) {
                                
                            } else {
                                if ($value2['payment_status'] == 'paid') {
                                    $paid = 'true';
                                } else {
                                    $paid = 'false';
                                }

                                $addInvDetailList[$key2]['frenns_id'] = $usernumber;
                                $addInvDetailList[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $addInvDetailList[$key2]['last_updated'] = $value2['updated'];
                                $addInvDetailList[$key2]['invoice_number'] = $value2['invoice_number'];
                                $addInvDetailList[$key2]['amount'] = $value2['amount']['amount'];
                                $addInvDetailList[$key2]['issue_date'] = $value2['created_at'];
                                $addInvDetailList[$key2]['creation_date'] = $value2['created_at'];
                                $addInvDetailList[$key2]['collection_date'] = date('Y-m-d');
                                //$addInvDetailList[$key2]['vat_amount'] = $value2->tax_amount;
                                $addInvDetailList[$key2]['outstanding_amount'] = $value2['outstanding']['amount'];
                                $addInvDetailList[$key2]['currency'] = $value2['outstanding']['code'];
                                $addInvDetailList[$key2]['paid'] = $paid;
                                $addInvDetailList[$key2]['pay_date'] = $value2['date_paid'];
                                $addInvDetailList[$key2]['due_date'] = $value2['due_date'];
                                $addInvDetailList[$key2]['invoiceId'] = $value2['id'];
                                $addInvDetailList[$key2]['type'] = 'Sale Invoice';

                                // get client 
                                $clientInfo = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients/" . $value2['customerid'];
                                $clientInfos = Helper::getCurlData($clientInfo, $parm);
                                $clientInffo = json_decode($clientInfos, true);

                                //print_r($clientInffo);

                                $addInvDetailList[$key2]['contact_person'] = $clientInffo['response']['result']['client']['fname'] . ' ' . $clientInffo['response']['result']['client']['lname'];
                                $addInvDetailList[$key2]['name'] = $clientInffo['response']['result']['client']['organization'];
                                $addInvDetailList[$key2]['phone_no'] = $clientInffo['response']['result']['client']['home_phone'];
                                $addInvDetailList[$key2]['email'] = $clientInffo['response']['result']['client']['email'];
                                // $addInvDetailList[$key2]['type'] = str_replace('_', " ", $typ);
                                //$addInvDetailList[$key2]['account_number'] = $contactPerson->bank_account_details->account_number;
                                //$addInvDetailList[$key2]['payment_terms'] = $contactPerson->credit_days;
                                //$addInvDetailList[$key2]['payment_method'] = $contactPerson->bank_account_details->iban;
                                //addInvDetailList[$key2]['company_number'] = $contactPerson->registered_number;
                                // $addInvDetailList['type'] = $contactPerson->contact_types->displayed_as;
                                $addInvDetailList[$key2]['vat_registration_number'] = $clientInffo['response']['result']['client']['vat_number'];
                                $addInvDetailList[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2['invoiceid'];
                                $addInvDetailList[$key2]['address'] = $clientInffo['response']['result']['client']['p_province'];
                                $addInvDetailList[$key2]['city'] = $clientInffo['response']['result']['client']['p_city'];
                                $addInvDetailList[$key2]['country'] = $clientInffo['response']['result']['client']['p_country'];
                                $addInvDetailList[$key2]['postcode'] = $clientInffo['response']['result']['client']['p_code'];

                                // add invoice
                                if (!empty($addInvDetailList)) {
                                    //echo 'add 1';
                                    //print_r($addInvDetailList);
                                    //$addInvoice = $this->apidetail->addInvoice($addInvDetailList);
                                    $addInvoice = DB::table('syncinvoice')->insert($addInvDetailList[$key2]);
                                    $addInvDetailList = array();
                                }

                                 $addInvDetailList .= count($value2['lines']); 
                                 //print_r()
                                //invoice line
                                $prodDetailList = array();
                                $kk = 0;
                                foreach ($value2['lines'] as $key3 => $value3) {
                                    $lines = $key3 + 1;
                                    $prodDetailList[$kk . $key3]['invoice_id'] = $value3['invoiceid'];
                                    $prodDetailList[$kk . $key3]['invoice_number'] = $value2['invoice_number'];

                                    $prodDetailList[$kk . $key3]['product_code'] = $value3['name'];
                                    //$prodDetailList[$kk]['product_id'] = $v->product->id;
                                    $prodDetailList[$kk . $key3]['frenns_id'] = $usernumber;
                                    $prodDetailList[$kk . $key3]['unique_frenns_id'] = $uniqueFrennsId;
                                    $prodDetailList[$kk . $key3]['description'] = $value3['description'];
                                    $prodDetailList[$kk . $key3]['qty'] = $value3['qty'];
                                    $prodDetailList[$kk . $key3]['rate'] = $value3['unit_cost']['amount'];
                                    $prodDetailList[$kk . $key3]['amount_net'] = $value3['amount']['amount'];
                                    $prodDetailList[$kk . $key3]['invoiceline_vat_amount'] = $value3['taxAmount1'];
                                    $prodDetailList[$kk . $key3]['amount_total'] = $value3['amount']['amount'] + $value3['taxAmount1'];
                                    $prodDetailList[$kk . $key3]['line_number'] = $lines;
                                    $prodDetailList[$kk . $key3]['updateId'] = $uniqueFrennsId . '-' . $value2['invoiceid'];
                                    //echo 'll';
                                    DB::table('syncinvoice_item')->where('invoice_id', '"' . $value3['invoiceid'] . '"')->delete();
                                    $addprodDetailList = DB::table('syncinvoice_item')->insert($prodDetailList[$kk . $key3]);
                                    $prodDetailList = '';
                                }
                                //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $value3['invoiceid']);

                                $kk++;
                            }
                        } else if ($updatedTimeInvoice > $lastUpdatedTime) {
                            $contactIDss = $value2['id'];

                            if ($createdTimeInvoice > $lastUpdatedTime) {

                                if ($value2['payment_status'] == 'paid') {
                                    $paid = 'true';
                                } else {
                                    $paid = 'false';
                                }

                                $addInvDetailList[$key2]['frenns_id'] = $usernumber;
                                $addInvDetailList[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $addInvDetailList[$key2]['last_updated'] = $value2['updated'];
                                $addInvDetailList[$key2]['invoice_number'] = $value2['invoice_number'];
                                $addInvDetailList[$key2]['amount'] = $value2['amount']['amount'];
                                $addInvDetailList[$key2]['issue_date'] = $value2['created_at'];
                                $addInvDetailList[$key2]['collection_date'] = date('Y-m-d');
                                $addInvDetailList[$key2]['collection_date'] = date('Y-m-d');
                                //$addInvDetailList[$key2]['vat_amount'] = $value2->tax_amount;
                                $addInvDetailList[$key2]['outstanding_amount'] = $value2['outstanding']['amount'];
                                $addInvDetailList[$key2]['currency'] = $value2['outstanding']['code'];
                                $addInvDetailList[$key2]['paid'] = $paid;
                                $addInvDetailList[$key2]['pay_date'] = $value2['date_paid'];
                                $addInvDetailList[$key2]['due_date'] = $value2['due_date'];
                                $addInvDetailList[$key2]['invoiceId'] = $value2['id'];
                                $addInvDetailList[$key2]['type'] = 'Sale Invoice';

                                $clientInfo = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients/" . $value2['customerid'];
                                $clientInfos = Helper::getCurlData($clientInfo, $parm);
                                $clientInffo = json_decode($clientInfos, true);
                                //print_r($clientInffo);

                                $addInvDetailList[$key2]['contact_person'] = $clientInffo['response']['result']['client']['fname'] . ' ' . $clientInffo['response']['result']['client']['lname'];
                                $addInvDetailList[$key2]['name'] = $clientInffo['response']['result']['client']['organization'];
                                $addInvDetailList[$key2]['phone_no'] = $clientInffo['response']['result']['client']['home_phone'];
                                $addInvDetailList[$key2]['email'] = $clientInffo['response']['result']['client']['email'];
                                // $addInvDetailList[$key2]['type'] = str_replace('_', " ", $typ);
                                //$addInvDetailList[$key2]['account_number'] = $contactPerson->bank_account_details->account_number;
                                //$addInvDetailList[$key2]['payment_terms'] = $contactPerson->credit_days;
                                //$addInvDetailList[$key2]['payment_method'] = $contactPerson->bank_account_details->iban;
                                //addInvDetailList[$key2]['company_number'] = $contactPerson->registered_number;
                                // $addInvDetailList['type'] = $contactPerson->contact_types->displayed_as;
                                $addInvDetailList[$key2]['vat_registration_number'] = $clientInffo['response']['result']['client']['vat_number'];
                                $addInvDetailList[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2['invoiceid'];
                                $addInvDetailList[$key2]['address'] = $clientInffo['response']['result']['client']['p_province'];
                                $addInvDetailList[$key2]['city'] = $clientInffo['response']['result']['client']['p_city'];
                                $addInvDetailList[$key2]['country'] = $clientInffo['response']['result']['client']['p_country'];
                                $addInvDetailList[$key2]['postcode'] = $clientInffo['response']['result']['client']['p_code'];

                                // add invoice
                                if (!empty($addInvDetailList)) {
                                    //echo 'add 2';
                                    // print_r($addInvDetailList);
                                    //$addInvoice = $this->apidetail->addInvoice($addInvDetailList);
                                    $addInvoice = DB::table('syncinvoice')->insert($addInvDetailList[$key2]);
                                    $addInvDetailList = array();
                                }


                                //}
                            } else if ($updatedTimeInvoice > $lastUpdatedTime) {

                                if ($value2['payment_status'] == 'paid') {
                                    $paid = 'true';
                                } else {
                                    $paid = 'false';
                                }

                                $updateInvDetailList[$key2]['frenns_id'] = $usernumber;
                                $updateInvDetailList[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvDetailList[$key2]['last_updated'] = $value2['updated'];
                                $updateInvDetailList[$key2]['invoice_number'] = $value2['invoice_number'];
                                $updateInvDetailList[$key2]['amount'] = $value2['amount']['amount'];
                                $updateInvDetailList[$key2]['issue_date'] = $value2['created_at'];
                                $updateInvDetailList[$key2]['creation_date'] = $value2['created_at'];
                                $updateInvDetailList[$key2]['collection_date'] = date('Y-m-d');

                                //$addInvDetailList[$key2]['vat_amount'] = $value2->tax_amount;
                                $updateInvDetailList[$key2]['outstanding_amount'] = $value2['outstanding']['amount'];
                                $updateInvDetailList[$key2]['currency'] = $value2['outstanding']['code'];
                                $updateInvDetailList[$key2]['paid'] = $paid;
                                $updateInvDetailList[$key2]['pay_date'] = $value2['date_paid'];
                                $updateInvDetailList[$key2]['due_date'] = $value2['due_date'];
                                $updateInvDetailList[$key2]['type'] = 'Sale Invoice';
                                $updateInvDetailList[$key2]['invoiceId'] = $value2['id'];


                                $clientInfo = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients/" . $value2['customerid'];
                                $clientInfos = Helper::getCurlData($clientInfo, $parm);
                                $clientInffo = json_decode($clientInfos, true);

                                //print_r($clientInffo);

                                $updateInvDetailList[$key2]['contact_person'] = $clientInffo['response']['result']['client']['fname'] . ' ' . $clientInffo['response']['result']['client']['lname'];
                                $updateInvDetailList[$key2]['name'] = $clientInffo['response']['result']['client']['organization'];
                                $updateInvDetailList[$key2]['phone_no'] = $clientInffo['response']['result']['client']['home_phone'];
                                $updateInvDetailList[$key2]['email'] = $clientInffo['response']['result']['client']['email'];
                                // $addInvDetailList[$key2]['type'] = str_replace('_', " ", $typ);
                                //$addInvDetailList[$key2]['account_number'] = $contactPerson->bank_account_details->account_number;
                                //$addInvDetailList[$key2]['payment_terms'] = $contactPerson->credit_days;
                                //$addInvDetailList[$key2]['payment_method'] = $contactPerson->bank_account_details->iban;
                                //addInvDetailList[$key2]['company_number'] = $contactPerson->registered_number;
                                // $addInvDetailList['type'] = $contactPerson->contact_types->displayed_as;
                                $updateInvDetailList[$key2]['vat_registration_number'] = $clientInffo['response']['result']['client']['vat_number'];
                                $updateInvDetailList[$key2]['updateId'] = $updateId;
                                $updateInvDetailList[$key2]['address'] = $clientInffo['response']['result']['client']['p_province'];
                                $updateInvDetailList[$key2]['city'] = $clientInffo['response']['result']['client']['p_city'];
                                $updateInvDetailList[$key2]['country'] = $clientInffo['response']['result']['client']['p_country'];
                                $updateInvDetailList[$key2]['postcode'] = $clientInffo['response']['result']['client']['p_code'];

                                //update invoice
                                if (!empty($updateInvDetailList)) {

                                    //echo 'update 2';
                                    //print_r($updateInvDetailList);
                                    //$updateInvoice = $this->apidetail->updateInvoice($updateInvDetailList, 'updateId');
                                    $updateInvoice = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateInvDetailList[$key2]);
                                    $updateInvDetailList = array();
                                }

                                $kk = 0;
                                foreach ($value2['lines'] as $key3 => $value3) {
                                    $lines = $key3 + 1;
                                    $prodDetailList[$kk . $key3]['invoice_id'] = $value3['invoiceid'];
                                    $prodDetailList[$kk . $key3]['invoice_number'] = $value2['invoice_number'];

                                    $prodDetailList[$kk . $key3]['product_code'] = $value3['name'];
                                    //$prodDetailList[$kk]['product_id'] = $v->product->id;
                                    $prodDetailList[$kk . $key3]['frenns_id'] = $usernumber;
                                    $prodDetailList[$kk . $key3]['unique_frenns_id'] = $uniqueFrennsId;
                                    $prodDetailList[$kk . $key3]['description'] = $value3['description'];
                                    $prodDetailList[$kk . $key3]['qty'] = $value3['qty'];
                                    $prodDetailList[$kk . $key3]['rate'] = $value3['unit_cost']['amount'];
                                    $prodDetailList[$kk . $key3]['amount_net'] = $value3['amount']['amount'];
                                    $prodDetailList[$kk . $key3]['invoiceline_vat_amount'] = $value3['taxAmount1'];
                                    $prodDetailList[$kk . $key3]['amount_total'] = $value3['amount']['amount'] + $value3['taxAmount1'];
                                    $prodDetailList[$kk . $key3]['line_number'] = $lines;
                                    $prodDetailList[$kk . $key3]['updateId'] = $uniqueFrennsId . '-' . $value2['invoiceid'];

                                    DB::table('syncinvoice_item')->where('invoice_id', $value3['invoiceid'])->delete();
                                    $addprodDetailList = DB::table('syncinvoice_item')->insert($prodDetailList[$kk . $key3]);
                                    $prodDetailList = '';
                                }
                                //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $value3['invoiceid']);
                                $kk++;
                            }//print_r($prodDetailList);
                        }
                    }

                    // expense                               
                    /* //max time for expense
                      $intype = '2';
                      //$getLastUpdatedInvoice = $this->apidetail->getLastUpdatedInvoice($usernumber, $intype);
                      $getLastUpdatedInvoice = DB::table('syncinvoice')->select('last_updated')->where('frenns_id', $usernumber)->orderBy('last_updated', 'desc')->limit(1)->get();
                      //print_r($getLastUpdatedInvoice);
                      if (count($getLastUpdatedInvoice) > 0) {
                      $lastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                      $lastUpdatedDate = date("Y-m-d", strtotime($lastUpdatedTime));
                      } else {
                      $lastUpdatedTime = '';
                      $lastUpdatedDate = '';
                      }
                      //die('----------');
                      $idArray = array();
                      //$getTodayInsertedInvoice = $this->apidetail->getTodayInsertedInvoice($usernumber, $lastUpdatedDate);
                      $getTodayInsertedInvoice = DB::table('syncinvoice')->select('updateId')->where('frenns_id', $usernumber)->where('last_updated', 'like', $lastUpdatedDate)->get();

                      if (count($getTodayInsertedInvoice) > 0) {
                      foreach ($getTodayInsertedInvoice as $dbData) {
                      $exp = explode("-", $dbData->updateId);
                      $idArray1[] = $exp[1];
                      }
                      } else {
                      $idArray1[] = array();
                      }

                      //print_r($idArray1);
                      // get invoice

                      $expenseInfo = "https://api.freshbooks.com/accounting/account/$getAccountId/expenses/expenses?per_page=2000";

                      $expenseInfos = Helper::getCurlData($expenseInfo, $parm);
                      $expenseInffo = json_decode($expenseInfos, true);

                      $addExpense = '';
                      //print_r($expenseInffo); die;
                      foreach ($expenseInffo['response'] as $valueResponse) {
                      foreach ($valueResponse['expenses'] as $key2 => $value2) {
                      $updatedTimeExpense = $value2['updated'];
                      $expIDss = $value2['id'];
                      $updateId = $usernumber . '-' . $value2['expenseid'];
                      //echo $updatedTimeExpense.'********'.$lastUpdatedTime;
                      if ($updatedTimeExpense > $lastUpdatedTime && !empty($lastUpdatedTime)) {

                      $updateExpense[$key2]['frenns_id'] = $usernumber;
                      $updateExpense[$key2]['last_updated'] = $value2['updated'];
                      //$addInvDetailList[$key2]['invoice_number'] = $value2['invoice_number'];
                      $updateExpense[$key2]['amount'] = $value2['amount']['amount'];
                      $updateExpense[$key2]['issue_date'] = $value2['date'];
                      $updateExpense[$key2]['collection_date'] = date('Y-m-d');
                      $updateExpense[$key2]['vat_amount'] = $value2['taxAmount1']['amount'];
                      //$addInvDetailList[$key2]['outstanding_amount'] = $value2['outstanding']['amount'];
                      $updateExpense[$key2]['currency'] = $value2['amount']['code'];
                      $updateExpense[$key2]['updateId'] = $updateId;
                      $updateExpense[$key2]['type'] = 'expense';

                      if (!empty($value2['clientid'])) {
                      $clientInfo = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients/" . $value2['clientid'];
                      $clientInfos = getCurlData($clientInfo, $parm);
                      $clientInffo = json_decode($clientInfos, true);
                      //print_r($clientInffo);
                      $updateExpense[$key2]['contact_person'] = $clientInffo['response']['result']['client']['fname'] . ' ' . $clientInffo['response']['result']['client']['lname'];
                      $updateExpense[$key2]['name'] = $clientInffo['response']['result']['client']['organization'];
                      $updateExpense[$key2]['phone_no'] = $clientInffo['response']['result']['client']['home_phone'];
                      $updateExpense[$key2]['email'] = $clientInffo['response']['result']['client']['email'];
                      //$addInvDetailList[$key2]['type'] = str_replace('_', " ", $typ);
                      //$addInvDetailList[$key2]['account_number'] = $contactPerson->bank_account_details->account_number;
                      //$addInvDetailList[$key2]['payment_terms'] = $contactPerson->credit_days;
                      //$addInvDetailList[$key2]['payment_method'] = $contactPerson->bank_account_details->iban;
                      //addInvDetailList[$key2]['company_number'] = $contactPerson->registered_number;
                      $updateExpense[$key2]['vat_registration_number'] = $clientInffo['response']['result']['client']['vat_number'];
                      $updateExpense[$key2]['address'] = $clientInffo['response']['result']['client']['p_province'];
                      $updateExpense[$key2]['city'] = $clientInffo['response']['result']['client']['p_city'];
                      $updateExpense[$key2]['country'] = $clientInffo['response']['result']['client']['p_country'];
                      $updateExpense[$key2]['postcode'] = $clientInffo['response']['result']['client']['p_code'];
                      } else {
                      $updateExpense[$key2]['contact_person'] = '';
                      $updateExpense[$key2]['name'] = '';
                      $updateExpense[$key2]['phone_no'] = '';
                      $updateExpense[$key2]['email'] = '';

                      $updateExpense[$key2]['vat_registration_number'] = '';

                      $updateExpense[$key2]['address'] = '';
                      $updateExpense[$key2]['city'] = '';
                      $updateExpense[$key2]['country'] = '';
                      $updateExpense[$key2]['postcode'] = '';
                      }


                      //update invoice
                      if (!empty($updateExpense)) {
                      // echo 'update';
                      //print_r($updateExpense);
                      //$updateInvoice = $this->apidetail->updateInvoice($updateExpense, 'updateId');
                      $updateInvoice = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateExpense[$key2]);
                      $updateExpense = array();
                      }



                      $existId1 = in_array($expIDss, $idArray1);

                      //print_r($existId);
                      if (!empty($existId1) || empty($idArray1)) {

                      } else {
                      $addExpense[$key2]['frenns_id'] = $usernumber;
                      $addExpense[$key2]['last_updated'] = $value2['updated'];
                      //$addInvDetailList[$key2]['invoice_number'] = $value2['invoice_number'];
                      $addExpense[$key2]['amount'] = $value2['amount']['amount'];
                      $addExpense[$key2]['issue_date'] = $value2['date'];
                      $addExpense[$key2]['collection_date'] = date('Y-m-d');
                      $addExpense[$key2]['vat_amount'] = $value2['taxAmount1']['amount'];
                      //$addInvDetailList[$key2]['outstanding_amount'] = $value2['outstanding']['amount'];
                      $addExpense[$key2]['currency'] = $value2['amount']['code'];
                      $addExpense[$key2]['updateId'] = $usernumber . '-' . $value2['expenseid'];
                      $addExpense[$key2]['type'] = 'expense';
                      // get client
                      if (!empty($value2['clientid'])) {
                      $clientInfo = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients/" . $value2['clientid'];
                      $clientInfos = getCurlData($clientInfo, $parm);
                      $clientInffo = json_decode($clientInfos, true);

                      $addExpense[$key2]['contact_person'] = $clientInffo['response']['result']['client']['fname'] . ' ' . $clientInffo['response']['result']['client']['lname'];
                      $addExpense[$key2]['name'] = $clientInffo['response']['result']['client']['organization'];
                      $addExpense[$key2]['phone_no'] = $clientInffo['response']['result']['client']['home_phone'];
                      $addExpense[$key2]['email'] = $clientInffo['response']['result']['client']['email'];

                      $addExpense[$key2]['vat_registration_number'] = $clientInffo['response']['result']['client']['vat_number'];

                      $addExpense[$key2]['address'] = $clientInffo['response']['result']['client']['p_province'];
                      $addExpense[$key2]['city'] = $clientInffo['response']['result']['client']['p_city'];
                      $addExpense[$key2]['country'] = $clientInffo['response']['result']['client']['p_country'];
                      $addExpense[$key2]['postcode'] = $clientInffo['response']['result']['client']['p_code'];
                      } else {
                      $addExpense[$key2]['contact_person'] = '';
                      $addExpense[$key2]['name'] = '';
                      $addExpense[$key2]['phone_no'] = '';
                      $addExpense[$key2]['email'] = '';

                      $addExpense[$key2]['vat_registration_number'] = '';

                      $addExpense[$key2]['address'] = '';
                      $addExpense[$key2]['city'] = '';
                      $addExpense[$key2]['country'] = '';
                      $addExpense[$key2]['postcode'] = '';
                      }

                      // add expense
                      if (!empty($addExpense)) {
                      // echo 'add';
                      // print_r($addExpense);
                      // $addInvoice = $this->apidetail->addInvoice($addExpense);
                      $addInvoice = DB::table('syncinvoice')->insert($addExpense[$key2]);
                      $addExpense = array();
                      }
                      }
                      } else if (empty($lastUpdatedTime)) {
                      $addExpense[$key2]['frenns_id'] = $usernumber;
                      $addExpense[$key2]['last_updated'] = $value2['updated'];
                      //$addInvDetailList[$key2]['invoice_number'] = $value2['invoice_number'];
                      $addExpense[$key2]['amount'] = $value2['amount']['amount'];
                      $addExpense[$key2]['issue_date'] = $value2['date'];
                      $addExpense[$key2]['collection_date'] = date('Y-m-d');
                      $addExpense[$key2]['vat_amount'] = $value2['taxAmount1']['amount'];
                      //$addInvDetailList[$key2]['outstanding_amount'] = $value2['outstanding']['amount'];
                      $addExpense[$key2]['currency'] = $value2['amount']['code'];
                      $addExpense[$key2]['updateId'] = $usernumber . '-' . $value2['expenseid'];
                      $addExpense[$key2]['type'] = 'expense';
                      // get client
                      if (!empty($value2['clientid'])) {
                      $clientInfo = "https://api.freshbooks.com/accounting/account/$getAccountId/users/clients/" . $value2['clientid'];
                      $clientInfos = getCurlData($clientInfo, $parm);
                      $clientInffo = json_decode($clientInfos, true);

                      $addExpense[$key2]['contact_person'] = $clientInffo['response']['result']['client']['fname'] . ' ' . $clientInffo['response']['result']['client']['lname'];
                      $addExpense[$key2]['name'] = $clientInffo['response']['result']['client']['organization'];
                      $addExpense[$key2]['phone_no'] = $clientInffo['response']['result']['client']['home_phone'];
                      $addExpense[$key2]['email'] = $clientInffo['response']['result']['client']['email'];

                      $addExpense[$key2]['vat_registration_number'] = $clientInffo['response']['result']['client']['vat_number'];

                      $addExpense[$key2]['address'] = $clientInffo['response']['result']['client']['p_province'];
                      $addExpense[$key2]['city'] = $clientInffo['response']['result']['client']['p_city'];
                      $addExpense[$key2]['country'] = $clientInffo['response']['result']['client']['p_country'];
                      $addExpense[$key2]['postcode'] = $clientInffo['response']['result']['client']['p_code'];
                      } else {
                      $addExpense[$key2]['contact_person'] = '';
                      $addExpense[$key2]['name'] = '';
                      $addExpense[$key2]['phone_no'] = '';
                      $addExpense[$key2]['email'] = '';

                      $addExpense[$key2]['vat_registration_number'] = '';

                      $addExpense[$key2]['address'] = '';
                      $addExpense[$key2]['city'] = '';
                      $addExpense[$key2]['country'] = '';
                      $addExpense[$key2]['postcode'] = '';
                      }

                      // add expense
                      if (!empty($addExpense)) {
                      // echo 'add';
                      // print_r($addExpense);
                      // $addInvoice = $this->apidetail->addInvoice($addExpense);
                      $addInvoice = DB::table('syncinvoice')->insert($addExpense[$key2]);
                      $addExpense = array();
                      }
                      }
                      }
                      } */
                }
            } echo 'All information has been stored successfully!!';
        }
    }

    ################################ freshbook end ###################################
    ################################ Kashflow ###################################

    public function kashflowData() {

        $contactTypes = array('GetSuppliers', 'GetCustomers');

        // echo '<pre>';
        $type = 'kashflow';
        if (isset($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        $client = new SoapClient("https://securedwebapp.com/api/service.asmx?WSDL");
        //$getdetail = $this->apidetail->getSingleUserDetail($type, $userNumber);
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $type)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $type)->where('usernumber', $userNumber)->get();
        }
        //print_r($getdetail); die('==============');
        if (!empty($getdetail)) {
            $response = array();
            $addSupplierDetailList = array();
            $updateSupplierDetailList = array();

            for ($i = 0; $i < count($getdetail); $i++) {
                $usernumber = $getdetail[$i]->usernumber;
                $uniqueFrennsId = $getdetail[$i]->accounting_system . "-" . $usernumber;
                $parameters['UserName'] = $getdetail[$i]->UserName;
                $parameters['Password'] = $getdetail[$i]->Password;
                $parameters['Password'] = $getdetail[$i]->Password;


                // get suppliers and customer

                foreach ($contactTypes as $contact) {
                    // max time for supplier/customer
                    //$getLastUpdatedContact = $this->apidetail->getLastUpdatedContact($usernumber);
                    $getLastUpdatedContact = DB::table('syncsupplier')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    //print_r($getLastUpdatedContact); die;
                    if (count($getLastUpdatedContact) > 0) {
                        $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                    } else {
                        $ContactlastUpdatedTime = '';
                    }
                    //print_r($getdetail[$i]);die;
                    // $parameters['Created'] = '2017-06-30T00:00:00';
                    $response = $client->$contact($parameters);
                    //print_r($response); 
                    //die('-------------');
                    $status = $response->Status;
                    if ($status == 'NO') {
                        $error = $response->StatusDetail;
                        return view('authenticationFailed', ['uniqueId' => $getdetail[$i]->usernumber, 'response' => $error]);

                        //$errDetails[] = $error . ' for ' . $getdetail[$i]->UserName;
                    }
                    if ($status == 'OK') {
                        if ($contact == 'GetSuppliers') {
                            $outputArray1 = 'GetSuppliersResult';
                            $postCosee = 'PostCode';
                            $contactID = 'SupplierID';
                            $contactType = 'Supplier';
                        }
                        if ($contact == 'GetCustomers') {
                            $outputArray1 = 'GetCustomersResult';
                            $postCosee = 'Postcode';
                            $contactID = 'CustomerID';
                            $contactType = 'Customer';
                        }
                        // echo $ContactlastUpdatedTime;
                        $idArray = array();
                        //$getTodayInsertedContact = $this->apidetail->getTodayInsertedContact($usernumber, '1', $ContactlastUpdatedTime);
                        $getTodayInsertedContact = DB::table('syncsupplier')->where('unique_frenns_id', $uniqueFrennsId)->get();
                        //print_r($getTodayInsertedContact); 
                        if (!empty($getTodayInsertedContact)) {
                            foreach ($getTodayInsertedContact as $dbData) {
                                $exp = explode("-", $dbData->updateId);
                                $idArray[] = $exp[2];
                            }
                        } else {
                            $idArray[] = array();
                        }
                        $j = 0;
                        foreach ($response->$outputArray1 as $key => $value) {
                            //  echo '<pre>';print_r($value); die('==============');
                            $totalRecord = count($value);
                            if ($totalRecord == 1) {
                                $updatedTimeSupplier1 = $value->Updated;
                                $createdTimeSupplier1 = $value->Created;
                                $contactIDss = $value->$contactID;
                                //
                                if ($createdTimeSupplier1 >= $ContactlastUpdatedTime) {
                                    //print_r($contactIDss); echo '<br>';
                                    //print_r($idArray);
                                    $existId = in_array($contactIDss, $idArray);

                                    if (!empty($existId)) {
                                        //    echo 'ex'; die;
                                    } else {
                                        //   echo 'assss'; die;
                                        //$addSupplierDetailList[$key]['company_account_number'] = $usernumber;
                                        $addSupplierDetailList['contact'][$key][$j]['frenns_id'] = $usernumber;
                                        $addSupplierDetailList['contact'][$key][$j]['unique_frenns_id'] = $uniqueFrennsId;
                                        $addSupplierDetailList['contact'][$key][$j]['company_name'] = $value->Name;
                                        //$addSupplierDetailList['contact'][$key][$j]['customer_id'] = $SupplierContactDetail->id;
                                        //$supplierDetailList['contact']['CollectionDate'] = $SupplierContactDetail->created_at;
                                        $addSupplierDetailList['contact'][$key][$j]['collection_date'] = date('Y-m-d');
                                        $addSupplierDetailList['contact'][$key][$j]['last_update'] = $value->Updated;
                                        $addSupplierDetailList['contact'][$key][$j]['type'] = $contactType;

                                        // $addSupplierDetailList['contact'][$key][$j]['CompanyNumber'] = $value->Code;

                                        $addSupplierDetailList['contact'][$key][$j]['vat_registration'] = isset($value->VATNumber) ? $value->VATNumber : '';
                                        //$supplierDetailList['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                                        $addSupplierDetailList['contact'][$key][$j]['address'] = $value->Address1 . ' ' . $value->Address2;
                                        $addSupplierDetailList['contact'][$key][$j]['postcode'] = $value->$postCosee;
                                        $addSupplierDetailList['contact'][$key][$j]['city'] = $value->Address3;
                                        $addSupplierDetailList['contact'][$key][$j]['country'] = $value->Address4;

                                        // contact person detail
                                        $addSupplierDetailList['contact'][$key][$j]['contact_person'] = $value->Contact;
                                        $addSupplierDetailList['contact'][$key][$j]['phone_number'] = $value->Mobile;
                                        $addSupplierDetailList['contact'][$key][$j]['email'] = $value->Email;
                                        $addSupplierDetailList['contact'][$key][$j]['updateId'] = $uniqueFrennsId . '-' . $value->$contactID;

                                        $addSupplierDetailList['contact'][$key][$j]['account_number'] = 0;

                                        if (!empty($addSupplierDetailList)) {
                                            //echo 'Add supplier array <br>';
                                            //print_r($addSupplierDetailList);
                                            if (!empty($addSupplierDetailList['contact']['Supplier'])) {
                                                //$addSuppliersItem = $this->apidetail->addSuppliers($addSupplierDetailList['contact']['Supplier']);
                                                $addSuppliersItem = DB::table('syncsupplier')->insert($addSupplierDetailList['contact']['Supplier'][$j]);
                                            }
                                            if (!empty($addSupplierDetailList['contact']['Customer'])) {
                                                //$addSuppliersItem = $this->apidetail->addSuppliers($addSupplierDetailList['contact']['Customer']);
                                                $addSuppliersItem = DB::table('syncsupplier')->insert($addSupplierDetailList['contact']['Customer'][$j]);
                                            }
                                            $addSupplierDetailList = array();
                                            //  echo 'add'; die;
                                        }
                                    }
                                } else {  //die('-=-=-=-=-');
                                    //if ($updatedTimeSupplier1 > $ContactlastUpdatedTime) {    
                                    //$updateSupplierDetailList[$key]['company_account_number'] = $usernumber;
                                    $updateSupplierDetailList['contact'][$key][$j]['frenns_id'] = $usernumber;
                                    $updateSupplierDetailList['contact'][$key][$j]['unique_frenns_id'] = $uniqueFrennsId;
                                    $updateSupplierDetailList['contact'][$key][$j]['company_name'] = $value->Name;
                                    //$addSupplierDetailList[$key]['customer_id'] = $SupplierContactDetail->id;
                                    //$supplierDetailList['CollectionDate'] = $SupplierContactDetail->created_at;
                                    $updateSupplierDetailList['contact'][$key][$j]['collection_date'] = date('Y-m-d');
                                    $updateSupplierDetailList['contact'][$key][$j]['last_update'] = $value->Updated;
                                    $updateSupplierDetailList['contact'][$key][$j]['type'] = $contactType;

                                    //$updateSupplierDetailList[$key]['CompanyNumber'] = sset($value->Code) ? $value->Code : '';

                                    $updateSupplierDetailList['contact'][$key][$j]['vat_registration'] = isset($value->VATNumber) ? $value->VATNumber : '';
                                    //$supplierDetailList['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                                    $updateSupplierDetailList['contact'][$key][$j]['address'] = $value->Address1 . ' ' . $value->Address2;
                                    $updateSupplierDetailList['contact'][$key][$j]['postcode'] = isset($value->$postCosee) ? $value->$postCosee : '';
                                    $updateSupplierDetailList['contact'][$key][$j]['city'] = isset($value->Address3) ? $value->Address3 : '';
                                    $updateSupplierDetailList['contact'][$key][$j]['country'] = isset($value->Address4) ? $value->Address4 : '';

                                    // contact person detail
                                    $updateSupplierDetailList['contact'][$key][$j]['contact_person'] = isset($value->Contact) ? $value->Contact : '';
                                    $updateSupplierDetailList['contact'][$key][$j]['phone_number'] = isset($value->Mobile) ? $value->Mobile : '';
                                    $updateSupplierDetailList['contact'][$key][$j]['email'] = isset($value->Email) ? $value->Email : '';
                                    $updateSupplierDetailList['contact'][$key][$j]['updateId'] = $usernumber . '-' . $value->$contactID;
                                    $updateSupplierDetailList['contact'][$key][$j]['account_number'] = 0;
                                    //echo 'update'; //die;
                                }
                                $j++;
                            } else { // die('//////////');
                                foreach ($value as $key1 => $value1) {
                                    //echo '<pre>';//print_r($value1);
                                    $updatedTimeSupplier = $value1->Updated;
                                    $createdTimeSupplier = $value1->Created;
                                    $contactIDs = $value1->$contactID;

                                    //print_r($idArray);
                                    $updateId = $uniqueFrennsId . '-' . $value1->$contactID;
                                    if ($createdTimeSupplier >= $ContactlastUpdatedTime) {
                                        $existId = in_array($contactIDs, $idArray);
                                        //print_r($contactIDs);
                                        // print_r($idArray);

                                        if (!empty($existId)) {
                                            // echo 'dddd';
                                        } else {
                                            // echo 'adddssss';
                                            $addSupplierDetailList['contact'][$key][$j]['frenns_id'] = $usernumber;
                                            $addSupplierDetailList['contact'][$key][$j]['unique_frenns_id'] = $uniqueFrennsId;
                                            $addSupplierDetailList['contact'][$key][$j]['company_name'] = $value1->Name;
                                            //$addSupplierDetailList['contact']['customer'][$key][$j]['customer_id'] = $SupplierContactDetail->id;
                                            //$supplierDetailList['contact']['CollectionDate'] = $SupplierContactDetail->created_at;
                                            $addSupplierDetailList['contact'][$key][$j]['collection_date'] = date('Y-m-d');
                                            $addSupplierDetailList['contact'][$key][$j]['last_update'] = $value1->Updated;
                                            $addSupplierDetailList['contact'][$key][$j]['type'] = $contactType;

                                            //$addSupplierDetailList['contact'][$key1]['CompanyNumber'] = $value1->Code;

                                            $addSupplierDetailList['contact'][$key][$j]['vat_registration'] = isset($value1->VATNumber) ? $value1->VATNumber : '';
                                            //$supplierDetailList['contact']['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                                            $addSupplierDetailList['contact'][$key][$j]['address'] = $value1->Address1 . ' ' . $value1->Address2;
                                            $addSupplierDetailList['contact'][$key][$j]['postcode'] = $value1->$postCosee;
                                            $addSupplierDetailList['contact'][$key][$j]['city'] = $value1->Address3;
                                            $addSupplierDetailList['contact'][$key][$j]['country'] = $value1->Address4;

                                            // contact person detail
                                            $addSupplierDetailList['contact'][$key][$j]['contact_person'] = $value1->Contact;
                                            $addSupplierDetailList['contact'][$key][$j]['phone_number'] = $value1->Mobile;
                                            $addSupplierDetailList['contact'][$key][$j]['email'] = $value1->Email;
                                            $addSupplierDetailList['contact'][$key][$j]['updateId'] = $updateId;

                                            $addSupplierDetailList['contact'][$key][$j]['account_number'] = 0;

                                            // echo 'addss';
                                            if (!empty($addSupplierDetailList)) {
                                                //echo 'Add supplier array <br>';
                                                //print_r($addSupplierDetailList);
                                                if (!empty($addSupplierDetailList['contact']['Supplier'])) {
                                                    //$addSuppliersItem = $this->apidetail->addSuppliers($addSupplierDetailList['contact']['Supplier']);
                                                    $addSuppliersItem = DB::table('syncsupplier')->insert($addSupplierDetailList['contact']['Supplier'][$j]);
                                                }
                                                if (!empty($addSupplierDetailList['contact']['Customer'])) {
                                                    //$addSuppliersItem = $this->apidetail->addSuppliers($addSupplierDetailList['contact']['Customer']);
                                                    $addSuppliersItem = DB::table('syncsupplier')->insert($addSupplierDetailList['contact']['Customer'][$j]);
                                                }
                                                $addSupplierDetailList = array();
                                                // echo 'add 2';// die;
                                            }
                                        }
                                    } else {
                                        //if ($updatedTimeSupplier > $ContactlastUpdatedTime) {  
                                        //echo 'rrrrrrrr';
                                        $updateSupplierDetailList['contact'][$key][$j]['frenns_id'] = $usernumber;
                                        $updateSupplierDetailList['contact'][$key][$j]['unique_frenns_id'] = $uniqueFrennsId;
                                        $updateSupplierDetailList['contact'][$key][$j]['company_name'] = $value1->Name;
                                        //$addSupplierDetailList[$key]['customer_id'] = $SupplierContactDetail->id;
                                        //$supplierDetailList['CollectionDate'] = $SupplierContactDetail->created_at;
                                        $updateSupplierDetailList['contact'][$key][$j]['collection_date'] = date('Y-m-d');
                                        $updateSupplierDetailList['contact'][$key][$j]['last_update'] = $value1->Updated;
                                        $updateSupplierDetailList['contact'][$key][$j]['type'] = $contactType;
                                        //$updateSupplierDetailList[$key1]['CompanyNumber'] = isset($value1->Code) ? $value1->Code : '';
                                        $updateSupplierDetailList['contact'][$key][$j]['vat_registration'] = isset($value1->VATNumber) ? $value1->VATNumber : '';
                                        //$supplierDetailList['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;

                                        $updateSupplierDetailList['contact'][$key][$j]['address'] = $value1->Address1 . ' ' . $value1->Address2;
                                        $updateSupplierDetailList['contact'][$key][$j]['postcode'] = isset($value1->$postCosee) ? $value1->$postCosee : '';
                                        $updateSupplierDetailList['contact'][$key][$j]['city'] = isset($value1->Address3) ? $value1->Address3 : '';
                                        $updateSupplierDetailList['contact'][$key][$j]['country'] = isset($value1->Address4) ? $value1->Address4 : '';

                                        // contact person detail

                                        $updateSupplierDetailList['contact'][$key][$j]['contact_person'] = isset($value1->Contact) ? $value1->Contact : '';
                                        $updateSupplierDetailList['contact'][$key][$j]['phone_number'] = isset($value1->Mobile) ? $value1->Mobile : '';
                                        $updateSupplierDetailList['contact'][$key][$j]['email'] = isset($value->Email) ? $value1->Email : '';
                                        $updateSupplierDetailList['contact'][$key][$j]['updateId'] = $updateId;
                                        $updateSupplierDetailList['contact'][$key][$j]['account_number'] = 0;

                                        if (!empty($updateSupplierDetailList)) {
                                            //echo 'update supplier array <br>';
                                            //print_r($updateSupplierDetailList);
                                            if (!empty($updateSupplierDetailList['contact']['Supplier'])) {
                                                //$updateInvoiceItems = $this->apidetail->updateSuppliers($updateSupplierDetailList['contact']['Supplier'], 'updateId');
                                                $updateSupplierDetailList = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateSupplierDetailList['Supplier']['Supplier'][$j]);
                                            }
                                            if (!empty($updateSupplierDetailList['contact']['Customer'])) {
                                                //$updateInvoiceItems = $this->apidetail->updateSuppliers($updateSupplierDetailList['contact']['Customer'], 'updateId');
                                                $updateSupplierDetailList = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateSupplierDetailList['contact']['Customer'][$j]);
                                            }
                                            //echo 'update 2'; //die;
                                            $updateSupplierDetailList = array();
                                        }
                                    }
                                    $j++;
                                }
                            }
                            $j++;
                        }
                    }
                }


                //die('Supplier section complete');
                // get all payment method

                $method1 = 'GetInvPayMethods';
                $paymentMethodResponse = Helper::getKashflow($parameters, $method1);
                foreach ($paymentMethodResponse->GetInvPayMethodsResult as $methodResult) {
                    foreach ($methodResult as $key => $pMethodResult) {
                        $paymentMethodArray[$pMethodResult->MethodID] = $pMethodResult->MethodName;
                    }
                }
                // get company detail
                $method4 = 'GetCompanyDetails';
                $compantDetail = Helper::getKashflow($parameters, $method4);

                // get invoice
                $parameters['NumberOfInvoices'] = '7';
                $method2 = 'GetInvoices_Recent';
                $response1 = Helper::getKashflow($parameters, $method2);
                // echo '<pre>';print_r($response1);  //die;
                $status1 = $response1->Status;
                if ($status1 == 'NO') {
                    $error = $response1->StatusDetail;
                    $errDetails[] = $error . ' for ' . $getdetail[$i]->UserName;
                }
                if ($status1 == 'OK') {
                    //print_r($response1); die('-=-=-');
                    $paid = '';
                    $j = 0;
                    $addInvDetailList = array();
                    $prodDetailList = array();

                    //$deleteInvoiceByUser = $this->apidetail->deleteInvoiceByUser($usernumber);
                    $deleteInvoiceByUser = DB::table('syncinvoice')->where('unique_frenns_id', $uniqueFrennsId)->delete();
                    foreach ($response1->GetInvoices_RecentResult as $key2 => $valueInv) {
                        $totalRecord = count($valueInv);
                        if ($totalRecord == 1) {
                            // echo 'singleRecord<br>';
                            $createdTimeSupplier1 = $valueInv->InvoiceDate;
                            if ($valueInv->Paid == 1) {
                                $paid = 'true';
                                // get payment method
                                $parameters['InvoiceNumber'] = $valueInv->InvoiceNumber;
                                $method3 = 'GetInvoicePayment';
                                $response3 = Helper::getKashflow($parameters, $method3);
                                if (is_object($response3->GetInvoicePaymentResult->Payment)) {
                                    $paymentId = $response3->GetInvoicePaymentResult->Payment->PayMethod;
                                    $paymentMethods = $paymentMethodArray[$paymentId];
                                    $paydate = $response3->GetInvoicePaymentResult->Payment->PayDate;
                                } else {
                                    $paymentId = $response3->GetInvoicePaymentResult->Payment[0]->PayMethod;
                                    $paymentMethods = $paymentMethodArray[$paymentId];
                                    $paydate = $response3->GetInvoicePaymentResult->Payment[0]->PayDate;
                                }
                            } else {
                                $paid = 'false';
                                $paymentMethods = '';
                                $paydate = '';
                            }
                            //echo '*****' . $j . '********* invoice obj';
                            //$addInvDetailList[$key2]['invoice_id'] = $value2->id;
                            $addInvDetailList[$j]['frenns_id'] = $usernumber;
                            //$addInvDetailList[$j]['last_updated'] = $valueInv->InvoiceDate;
                            $addInvDetailList[$j]['unique_frenns_id'] = $uniqueFrennsId;
                            $addInvDetailList[$j]['invoice_number'] = $valueInv->InvoiceNumber;
                            $addInvDetailList[$j]['amount'] = $valueInv->NetAmount;
                            $addInvDetailList[$j]['issue_date'] = $valueInv->InvoiceDate;
                            $addInvDetailList[$j]['creation_date'] = $valueInv->InvoiceDate;
                            $addInvDetailList[$j]['collection_date'] = date('Y-m-d');
                            $addInvDetailList[$j]['vat_amount'] = $valueInv->VATAmount;
                            //$addInvDetailList[$j]['outstanding_amount'] = $valueInv->NetAmount;
                            $addInvDetailList[$j]['currency'] = $valueInv->CurrencyCode;
                            $addInvDetailList[$j]['paid'] = $paid;
                            $addInvDetailList[$j]['pay_date'] = $paydate;
                            $addInvDetailList[$j]['due_date'] = $valueInv->DueDate;
                            $addInvDetailList[$j]['updateId'] = $uniqueFrennsId . '-' . $valueInv->InvoiceDBID;
                            $addInvDetailList[$j]['invoiceId'] = $valueInv->InvoiceDBID;

                            //get cutomer details
                            $parameters['CustomerID'] = $valueInv->CustomerID;
                            $method4 = 'GetCustomerByID';
                            $response2 = Helper::getKashflow($parameters, $method4);

                            $addInvDetailList[$j]['contact_person'] = $response2->GetCustomerByIDResult->Contact;
                            $addInvDetailList[$j]['name'] = $response2->GetCustomerByIDResult->Name;
                            $addInvDetailList[$j]['phone_no'] = $response2->GetCustomerByIDResult->Mobile;
                            $addInvDetailList[$j]['email'] = $response2->GetCustomerByIDResult->Email;
                            $addInvDetailList[$j]['type'] = 'Sale Invoice';
                            //$addInvDetailList[$key2]['account_number'] = $response2->GetCustomerByIDResult->account_number;
                            $addInvDetailList[$j]['payment_terms'] = $response2->GetCustomerByIDResult->PaymentTerms;
                            $addInvDetailList[$j]['payment_method'] = $paymentMethods;
                            //$addInvDetailList[$key2]['company_number'] = $valueInv->registered_number;
                            $addInvDetailList[$j]['vat_registration_number'] = isset($compantDetail->VatRegistrationNumber) ? $compantDetail->VatRegistrationNumber : '';

                            $addInvDetailList[$j]['address'] = $response2->GetCustomerByIDResult->Address1 . ' ' . $response2->GetCustomerByIDResult->Address2;
                            $addInvDetailList[$j]['city'] = $response2->GetCustomerByIDResult->Address3 . ' ' . $response2->GetCustomerByIDResult->Address4;
                            $addInvDetailList[$j]['country'] = $response2->GetCustomerByIDResult->CountryName;
                            $addInvDetailList[$j]['postcode'] = $response2->GetCustomerByIDResult->Postcode;
                            //print_r($addInvDetailList);

                            if (!empty($addInvDetailList)) {
                                //$addInvoice = $this->apidetail->addInvoice($addInvDetailList);
                                $addInvoice = DB::table('syncinvoice')->insert($addInvDetailList[$j]);
                                $addInvDetailList = array();
                            }

                            $j++;

                            $method5 = 'GetInvoiceByID';
                            $parameters['InvoiceID'] = $valueInv->InvoiceDBID;

                            $response5 = Helper::getKashflow($parameters, $method5);
                            //invoice line
                            $kkk = 0;
                            $j = 0;
                            foreach ($response5->GetInvoiceByIDResult->Lines as $kkkk => $value) {
                                $totalLine = count($value);
                                $updateId = $uniqueFrennsId . '-' . $valueInv->InvoiceDBID;
                                DB::table('syncinvoice_item')->where('updateId', $updateId)->delete();
                                if ($totalLine > 1) {
                                    //echo 'one moreeeeeeeee 1<br>';
                                    $kk = 0;
                                    foreach ($value as $keyss => $value1) {
                                        //echo 'iioo-- with array item******' . $kk . '<br>';
                                        $method5 = 'GetSubProductByID';
                                        if (!empty($value1->enc_value->ProductID)) {
                                            $parameters['ProductID'] = $value1->enc_value->ProductID;
                                            $response4 = Helper::getKashflow($parameters, $method5);
                                        }
                                        $prodDetailList[$kk . $keyss]['invoice_id'] = $valueInv->InvoiceDBID;
                                        $prodDetailList[$kk . $keyss]['invoice_number'] = $valueInv->InvoiceNumber;
                                        if (!empty($value1->enc_value->ProductID)) {
                                            $prodDetailList[$kk . $keyss]['product_code'] = $response4->GetSubProductByIDResult->Code;
                                            //$prodDetailList[$kk.$keyss]['product_id'] = $value1->enc_value->ProductID;
                                        } else {
                                            $prodDetailList[$kk . $keyss]['product_code'] = '';
                                            //$prodDetailList[$kk.$keyss]['product_id'] = '';
                                        }

                                        $prodDetailList[$kk . $keyss]['unique_frenns_id'] = $uniqueFrennsId;
                                        $prodDetailList[$kk . $keyss]['frenns_id'] = $usernumber;
                                        $prodDetailList[$kk . $keyss]['description'] = $value1->enc_value->Description;
                                        $prodDetailList[$kk . $keyss]['qty'] = $value1->enc_value->Quantity;
                                        $prodDetailList[$kk . $keyss]['rate'] = $value1->enc_value->Rate;
                                        $prodDetailList[$kk . $keyss]['amount_net'] = $value1->enc_value->Quantity * $value1->enc_value->Rate;
                                        $prodDetailList[$kk . $keyss]['invoiceline_vat_amount'] = $value1->enc_value->VatAmount;
                                        $prodDetailList[$kk . $keyss]['amount_total'] = $value1->enc_value->Quantity * $value1->enc_value->Rate + $value1->enc_value->VatAmount;
                                        $prodDetailList[$kk . $keyss]['line_number'] = $value1->enc_value->Sort;
                                        $prodDetailList[$kk . $keyss]['updateId'] = $updateId;

                                        ## Delete all invoice items
                                        //DB::table('syncinvoice_item')->where('updateId', $updateId)->delete();
                                        $addInvoiceItem = DB::table('syncinvoice_item')->insert($prodDetailList[$kk . $keyss]);
                                        $prodDetailList = array();
                                        $kk++;
                                    }
                                    //print_r($prodDetailList);
                                    //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $valueInv->InvoiceDBID);
                                } else {

                                    //echo 'one lesss 1<br>';
                                    //echo 'iioo-- with object item******' . $kkk . '<br>';
                                    $method5 = 'GetSubProductByID';
                                    if (!empty($value->enc_value->ProductID)) {
                                        $parameters['ProductID'] = $value->enc_value->ProductID;
                                        $response4 = Helper::getKashflow($parameters, $method5);
                                    }

                                    $lines = $kkk + 1;
                                    $prodDetailList[$kkk . $j]['invoice_id'] = $valueInv->InvoiceDBID;
                                    $prodDetailList[$kkk . $j]['invoice_number'] = $valueInv->InvoiceNumber;
                                    if (!empty($value->enc_value->ProductID)) {
                                        $prodDetailList[$kkk . $j]['product_code'] = $response4->GetSubProductByIDResult->Code;
                                        //$prodDetailList[$kkk . $j]['product_id'] = $value->enc_value->ProductID;
                                    } else {
                                        $prodDetailList[$kkk . $j]['product_code'] = '';
                                        //$prodDetailList[$kkk . $j]['product_id'] = '';
                                    }

                                    $prodDetailList[$kkk . $j]['unique_frenns_id'] = $uniqueFrennsId;
                                    $prodDetailList[$kkk . $j]['frenns_id'] = $usernumber;
                                    $prodDetailList[$kkk . $j]['description'] = $value->enc_value->Description;
                                    $prodDetailList[$kkk . $j]['qty'] = $value->enc_value->Quantity;
                                    $prodDetailList[$kkk . $j]['rate'] = $value->enc_value->Rate;
                                    $prodDetailList[$kkk . $j]['amount_net'] = $value->enc_value->Quantity * $value->enc_value->Rate;
                                    $prodDetailList[$kkk . $j]['invoiceline_vat_amount'] = $value->enc_value->VatAmount;
                                    $prodDetailList[$kkk . $j]['amount_total'] = $value->enc_value->Quantity * $value->enc_value->Rate + $value->enc_value->VatAmount;
                                    $prodDetailList[$kkk . $j]['line_number'] = $value->enc_value->Sort;
                                    $prodDetailList[$kkk . $j]['updateId'] = $updateId;

                                    //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $valueInv->InvoiceDBID);
                                    ## Delete all invoice items and add new one
                                    //DB::table('syncinvoice_item')->where('updateId', $updateId)->delete();
                                    $addInvoiceItem = DB::table('syncinvoice_item')->insert($prodDetailList[$kkk . $j]);
                                    $prodDetailList = array();
                                }
                                $kkk++;
                                $j++;
                            }
                            //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $valueInv->InvoiceDBID);
                        } else {

                            foreach ($valueInv as $key1 => $valueInvArr) {
                                if ($valueInvArr->Paid == 1) {
                                    $paid = 'true';
                                    // get payment method
                                    $parameters['InvoiceNumber'] = $valueInvArr->InvoiceNumber;
                                    $method3 = 'GetInvoicePayment';
                                    $response3 = Helper::getKashflow($parameters, $method3);
                                    //echo '<pre>';print_r($response3);
                                    if (is_object($response3->GetInvoicePaymentResult->Payment)) {
                                        $paymentId = $response3->GetInvoicePaymentResult->Payment->PayMethod;
                                        $paymentMethods = $paymentMethodArray[$paymentId];
                                        $paydate = $response3->GetInvoicePaymentResult->Payment->PayDate;
                                    } else {
                                        $paymentId = $response3->GetInvoicePaymentResult->Payment[0]->PayMethod;
                                        $paymentMethods = $paymentMethodArray[$paymentId];
                                        $paydate = $response3->GetInvoicePaymentResult->Payment[0]->PayDate;
                                    }//die;
                                } else {
                                    $paid = 'false';
                                    $paymentMethods = '';
                                    $paydate = '';
                                }

                                $addInvDetailList[$j]['frenns_id'] = $usernumber;
                                //$addInvDetailList[$j]['last_updated'] = $valueInvArr->InvoiceDate;
                                $addInvDetailList[$j]['unique_frenns_id'] = $uniqueFrennsId;
                                $addInvDetailList[$j]['invoice_number'] = $valueInvArr->InvoiceNumber;
                                $addInvDetailList[$j]['amount'] = $valueInvArr->NetAmount;
                                $addInvDetailList[$j]['issue_date'] = $valueInvArr->InvoiceDate;
                                $addInvDetailList[$j]['creation_date'] = $valueInvArr->InvoiceDate;
                                $addInvDetailList[$j]['collection_date'] = date('Y-m-d');
                                $addInvDetailList[$j]['vat_amount'] = $valueInvArr->VATAmount;
                                //$addInvDetailList[$j]['outstanding_amount'] = $valueInvArr->NetAmount;
                                $addInvDetailList[$j]['currency'] = $valueInvArr->CurrencyCode;
                                $addInvDetailList[$j]['paid'] = $paid;
                                $addInvDetailList[$j]['pay_date'] = $paydate;
                                $addInvDetailList[$j]['due_date'] = $valueInvArr->DueDate;
                                $addInvDetailList[$j]['updateId'] = $uniqueFrennsId . '-' . $valueInvArr->InvoiceDBID;
                                $addInvDetailList[$j]['invoiceId'] = $valueInvArr->InvoiceDBID;

                                //get cutomer details
                                $parameters['CustomerID'] = $valueInvArr->CustomerID;
                                $response2 = $client->GetCustomerByID($parameters);

                                $addInvDetailList[$j]['contact_person'] = $response2->GetCustomerByIDResult->Contact;
                                $addInvDetailList[$j]['name'] = $response2->GetCustomerByIDResult->Name;
                                $addInvDetailList[$j]['phone_no'] = $response2->GetCustomerByIDResult->Mobile;
                                $addInvDetailList[$j]['email'] = $response2->GetCustomerByIDResult->Email;
                                $addInvDetailList[$j]['type'] = 'Sales Invoice';
                                //$addInvDetailList[$key2]['account_number'] = $response2->GetCustomerByIDResult->account_number;
                                $addInvDetailList[$j]['payment_terms'] = $response2->GetCustomerByIDResult->PaymentTerms;
                                $addInvDetailList[$j]['payment_method'] = $paymentMethods;
                                $addInvDetailList[$j]['vat_registration_number'] = isset($compantDetail->VatRegistrationNumber) ? $compantDetail->VatRegistrationNumber : '';
                                ////$addInvDetailList[$key2]['company_number'] = $valueInv->registered_number;

                                $addInvDetailList[$j]['address'] = $response2->GetCustomerByIDResult->Address1 . ' ' . $response2->GetCustomerByIDResult->Address2;
                                $addInvDetailList[$j]['city'] = $response2->GetCustomerByIDResult->Address3 . ' ' . $response2->GetCustomerByIDResult->Address4;
                                $addInvDetailList[$j]['country'] = $response2->GetCustomerByIDResult->CountryName;
                                $addInvDetailList[$j]['postcode'] = $response2->GetCustomerByIDResult->Postcode;

                                if (!empty($addInvDetailList)) {
                                    //$addInvoice = $this->apidetail->addInvoice($addInvDetailList);
                                    $addInvoice = DB::table('syncinvoice')->insert($addInvDetailList[$j]);
                                    $addInvDetailList = array();
                                }


                                $method6 = 'GetInvoiceByID';
                                $parameters['InvoiceID'] = $valueInvArr->InvoiceDBID;
                                $response6 = Helper::getKashflow($parameters, $method6);
                                // invoice line
                                $kk1 = 0;

                                foreach ($response6->GetInvoiceByIDResult->Lines as $keysss => $value) {
                                    $totalLine = count($value);
                                    //echo 'iioo--' . count($value) . '<br>';
                                    $updateId = $uniqueFrennsId . '-' . $valueInvArr->InvoiceDBID;
                                    DB::table('syncinvoice_item')->where('updateId', $updateId)->delete();
                                    if ($totalLine > 1) {
                                        //echo 'one moreeeeeeeee 2<br>';
                                        $kk = 0;
                                        foreach ($value as $keyy => $value1) {

                                            $method5 = 'GetSubProductByID';
                                            if (!empty($value1->enc_value->ProductID)) {
                                                $parameters['ProductID'] = $value1->enc_value->ProductID;

                                                $response4 = Helper::getKashflow($parameters, $method5);
                                            }

                                            $lines = $kk + 1;
                                            $prodDetailList[$keyy . $j]['invoice_id'] = $valueInvArr->InvoiceDBID;
                                            $prodDetailList[$keyy . $j]['invoice_number'] = $valueInvArr->InvoiceNumber
                                                    ;
                                            if (!empty($value1->enc_value->ProductID)) {
                                                $prodDetailList[$keyy . $j]['product_code'] = $response4->GetSubProductByIDResult->Code;
                                                //$prodDetailList[$keyy.$j]['product_id'] = $value1->enc_value->ProductID;
                                            } else {
                                                $prodDetailList[$keyy . $j]['product_code'] = '';
                                            }

                                            $prodDetailList[$keyy . $j]['frenns_id'] = $usernumber;
                                            $prodDetailList[$keyy . $j]['unique_frenns_id'] = $uniqueFrennsId;
                                            $prodDetailList[$keyy . $j]['description'] = $value1->enc_value->Description;
                                            $prodDetailList[$keyy . $j]['qty'] = $value1->enc_value->Quantity;
                                            $prodDetailList[$keyy . $j]['rate'] = $value1->enc_value->Rate;
                                            $prodDetailList[$keyy . $j]['amount_net'] = $value1->enc_value->Quantity * $value1->enc_value->Rate;
                                            $prodDetailList[$keyy . $j]['invoiceline_vat_amount'] = $value1->enc_value->VatAmount;
                                            $prodDetailList[$keyy . $j]['amount_total'] = $value1->enc_value->Quantity * $value1->enc_value->Rate + $value1->enc_value->VatAmount;
                                            $prodDetailList[$keyy . $j]['line_number'] = $value1->enc_value->Sort;
                                            $prodDetailList[$keyy . $j]['updateId'] = $updateId;
                                            // print_r($prodDetailList);
                                            ## Delete all invoice items and add new one
                                            //DB::table('syncinvoice_item')->where('updateId', $updateId)->delete();
                                            $addInvoiceItem = DB::table('syncinvoice_item')->insert($prodDetailList[$keyy . $j]);
                                            $prodDetailList = array();
                                        }

                                        //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $valueInvArr->InvoiceDBID);
                                    } else {
                                        //echo 'one lesss 2<br>';
                                        $method5 = 'GetSubProductByID';
                                        if (!empty($value->enc_value->ProductID)) {
                                            $parameters['ProductID'] = $value->enc_value->ProductID;

                                            $response4 = Helper::getKashflow($parameters, $method5);
                                        }

                                        $lines = $keysss + 1;
                                        $prodDetailList[$kk1 . $j]['invoice_id'] = $valueInvArr->InvoiceDBID;
                                        $prodDetailList[$kk1 . $j]['invoice_number'] = $valueInvArr->InvoiceNumber;
                                        if (!empty($value->enc_value->ProductID)) {
                                            $prodDetailList[$kk1 . $j]['product_code'] = $response4->GetSubProductByIDResult->Code;
                                        } else {
                                            $prodDetailList[$kk1 . $j]['product_code'] = '';
                                        }

                                        $prodDetailList[$kk1 . $j]['frenns_id'] = $usernumber;
                                        $prodDetailList[$kk1 . $j]['unique_frenns_id'] = $uniqueFrennsId;
                                        $prodDetailList[$kk1 . $j]['description'] = $value->enc_value->Description;
                                        $prodDetailList[$kk1 . $j]['qty'] = $value->enc_value->Quantity;
                                        $prodDetailList[$kk1 . $j]['rate'] = $value->enc_value->Rate;
                                        $prodDetailList[$kk1 . $j]['amount_net'] = $value->enc_value->Quantity * $value->enc_value->Rate;
                                        $prodDetailList[$kk1 . $j]['invoiceline_vat_amount'] = $value->enc_value->VatAmount;
                                        $prodDetailList[$kk1 . $j]['amount_total'] = $value->enc_value->Quantity * $value->enc_value->Rate + $value->enc_value->VatAmount;
                                        $prodDetailList[$kk1 . $j]['line_number'] = $value->enc_value->Sort;
                                        $prodDetailList[$kk1 . $j]['updateId'] = $updateId;

                                        ## Delete all invoice items and add new one
                                        //DB::table('syncinvoice_item')->where('updateId', $updateId)->delete();
                                        $addInvoiceItem = DB::table('syncinvoice_item')->insert($prodDetailList[$kk1 . $j]);
                                        $prodDetailList = array();
                                    }
                                    $kk1++;
                                }
                                //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $valueInvArr->InvoiceDBID);
                                $j++;
                            }
                        }
                    }
                }

                // p&l 
                $parameters['StartDate'] = '2016-01-01';
                $parameters['EndDate'] = date('Y-m-d');
                $method21 = 'GetProfitAndLoss';
                $response21 = Helper::getKashflow($parameters, $method21);
                //print_r($response21);  //die;
                $status21 = $response21->Status;
                if ($status21 == 'NO') {
                    $error = $response1->StatusDetail;
                    $errDetails[] = $error . ' for ' . $getdetail[$i]->UserName;
                }
                if ($status21 == 'OK') {
                    //echo $userNumber; die;
                    $response21 = json_encode($response21);
                    //$addPl = $this->apidetail->addPlData($usernumber, $response21);
                    $getAddedPlData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    //print_r($getAddedPlData);   die;                    
                    if (count($getAddedPlData) > 0) {
                        $updateData['pl_data'] = $response21;
                        $updatePLData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->update($updateData);
                    } else {
                        $insertData[0]['frenns_id'] = $usernumber;
                        $insertData[0]['unique_frenns_id'] = $uniqueFrennsId;
                        $insertData[0]['pl_data'] = $response21;
                        $addPLData = DB::table('syncreport_pl')->insert($insertData);
                    }
                }
            }
            echo 'All information has been stored successfully!!';
        } else {
            echo 'No user available';
        }
        // error message               
        if (!empty($errDetails)) {
            print_r($errDetails);
        }
    }

    public function testKashflow() {
        echo '<pre>';
        $method = 'GetCustomers';
        // $parameters['NumberOfInvoices'] = '7';
        //$method = 'GetInvoices_Recent';
        //$method = 'GetNominalCodesExtended';
        //$method = 'JournalEntry';
        //$method = 'JournalEntry';

        $parameters['UserName'] = 'sarvjeet315@yopmail.com';
        $parameters['Password'] = 'admin@786';
        //$parameters['InvoiceNumber'] = '3';
        $parameters['AccountID'] = '626771';
        $parameters['StartDate'] = '2017-06-01T00:00:00';
        $parameters['EndDate'] = '2017-07-30T00:00:00';
        $parameters['NominalID'] = '22037179';
        $response = Helper::getKashflow($parameters, $method);
        print_r($response);
        die;
    }

    ################################ Kashflow end ######################################
    ############################### Xero API Start Here #################################

    public function redirectToXero() {
        unset($_SESSION['access_token']);
        unset($_SESSION['oauth_token_secret']);
        unset($_SESSION['session_handle']);
        require_once(app_path('third_party/xerophp/') . 'public.php');
        //redirect(url('/') . 'redirectToXero?authenticate=1');   
        return Redirect::to(url('/') . '/redirectToXero?authenticate=1');
    }

    public function welcomeToXero() {
        require_once(app_path('third_party/xerophp/') . 'public.php');
        return Redirect::to(url('/') . '/xeroData');
    }

    public function xeroData() {
        #Fetch all quickbook user details
        require_once(app_path('third_party/xerophp/') . 'public.php');

        $accounting_system = 'xero';
        if (!empty($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        //$getdetail = $this->apidetail->getSingleUserDetail($accounting_system, $userNumber);      
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }
        if (!empty($getdetail)) {
            foreach ($getdetail as $user) {

                $uniqueId = $user->usernumber;
                $uniqueFrennsId = $user->accounting_system . "-" . $user->usernumber;
                $consumer_key = $user->consumer_key;
                $consumer_secret = $user->consumer_secret;

                ####################################### P & L Report Section ############################################################

                $currentDate = date('Y-m-d');
                $previousDate = date('Y-m-d', strtotime("-365 days", strtotime($currentDate)));
                $response = $XeroOAuth->request('GET', $XeroOAuth->url('Reports/ProfitAndLoss', 'core'), array('fromDate' => $previousDate, 'toDate' => $currentDate), 'xml', 'json');
                //echo '<pre>';print_r($response); die('Xero P&L Response 3114'); 
                if ($XeroOAuth->response['code'] == 200) {
                    $getdetail = DB::table('syncreport_pl')->select('syncreport_pl.frenns_id')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    if (count($getdetail) > 0) {
                        $plData['pl_data'] = $response['response'];
                        $updatePLData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->update($plData);
                    } else {
                        $plData['frenns_id'] = $uniqueId;
                        $plData['unique_frenns_id'] = $uniqueFrennsId;
                        $plData['pl_data'] = $response['response'];
                        $insertPLData = DB::table('syncreport_pl')->insert($plData);
                    }
                } else {
                    ## oauth_problem ## token_expired ## redirect to xero
                    echo "Token Expire for user " . $uniqueFrennsId . ".Please contact to administrator.";
                    exit;
                }

                ####################################### P&L Report Section End ###################################################
                ####################################### Customer Section Start ###################################################                

                $response = $XeroOAuth->request('GET', $XeroOAuth->url('Contacts', 'core'), array(), 'xml', '');
                //echo '<pre>';print_r($response); die(' All Contacts Response 3135');
                if ($XeroOAuth->response['code'] == 200) {
                    $contacts = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                    $contacts = json_decode(json_encode($contacts), true);
                    //echo '<pre>';print_r($contacts['Contacts']['Contact']); die(' All Contacts');
                    #Get Last Cron Time
                    $data['accounting_system'] = 'xero';
                    $data['item_type'] = 'contact';
                    $data['user'] = $uniqueId;
                    //$lastCronTime = $this->apidetail->getCronTime($data);
                    $lastCronTime = DB::table('appcrontracker')->where('accounting_system', 'xero')->where('item_type', 'contact')->orderBy('id', 'desc')->first();
                    $lastCronTime = isset($lastCronTime->cron_time) ? $lastCronTime->cron_time : '';

                    #Save Cron time
                    $responseTime = explode(".", $contacts['DateTimeUTC']);
                    $CronTime = $responseTime[0];
                    $cronData[0]['accounting_system'] = 'xero';
                    $cronData[0]['item_type'] = 'contact';
                    $cronData[0]['user'] = $uniqueId;
                    $cronData[0]['cron_time'] = $CronTime;
                    //$this->apidetail->saveCronTime($cronData);
                    DB::table('appcrontracker')->insert($cronData);

                    ## Fetch all xero contacts ids from local database 
                    //$localContacts = $this->apidetail->getCustomerSuppliersIds($uniqueId);
                    $localContacts = DB::table('syncsupplier')->select('syncsupplier.contactId')->where('unique_frenns_id', $uniqueFrennsId)->get();

                    $localContactsArr = array();
                    if (!empty($localContacts)) {
                        foreach ($localContacts as $key => $localContact) {
                            //$lc = explode('-', $localContact->contactId);
                            //$localContactsArr[] = $lc[1];
                            $localContactsArr[] = $localContact->contactId;
                        }
                    }

                    foreach ($contacts['Contacts']['Contact'] as $key => $customer) {
                        //echo '<pre>';print_r($customer);  die(' Single Contact'); 
                        $contactId = $customer['ContactID'];
                        $uniqueUpdateId = $uniqueFrennsId . '-' . $contactId;
                        if ($customer['IsSupplier'] == 'true') {
                            $contactType = "Supplier";
                        } else if ($customer['IsCustomer'] == 'true') {
                            $contactType = "Customer";
                        } else {
                            $contactType = '';
                        }

                        //echo "<prE>"; print_r($localContactsArr); 
                        //echo "<prE>"; echo $customer['ContactID'];
                        //die('Local Contact 3189');
                        if (!in_array($customer['ContactID'], $localContactsArr) && $contactType != '') {
                            $customerData[$key]['frenns_id'] = $uniqueId;
                            $customerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $customerData[$key]['company_name'] = isset($customer['Name']) ? $customer['Name'] : '';
                            $customerData[$key]['company_account_number'] = '';
                            $customerData[$key]['company_number'] = '';
                            $customerData[$key]['Type'] = $contactType;
                            $customerData[$key]['cust_supp_company'] = '';
                            $customerData[$key]['custsupp_companynumber'] = '';
                            $customerData[$key]['account_number'] = '';
                            $customerData[$key]['name'] = isset($customer['Name']) ? $customer['Name'] : '';
                            $customerData[$key]['Address'] = isset($customer['Addresses'][0]['AddressType']) ? $customer['Addresses'][0]['AddressType'] : '';
                            $customerData[$key]['Postcode'] = isset($customer['Addresses'][0]['PostalCode']) ? $customer['Addresses'][0]['PostalCode'] : '';
                            $customerData[$key]['City'] = isset($customer['Addresses'][0]['City']) ? $customer['Addresses'][0]['City'] : '';
                            $customerData[$key]['country'] = isset($customer['Addresses'][0]['Country']) ? $customer['Addresses'][0]['Country'] : '';
                            $customerData[$key]['contact_person'] = isset($customer['Name']) ? $customer['Name'] : '';
                            $customerData[$key]['phone_number'] = isset($customer['Phones'][3]->PhoneNumber) ? $customer['Phones'][3]->PhoneNumber : '';
                            $customerData[$key]['Email'] = isset($customer['PrimaryEmailAddr']['Address']) ? $customer['PrimaryEmailAddr']['Address'] : '';
                            $customerData[$key]['collection_date'] = date('Y-m-d');
                            $customerData[$key]['last_update'] = isset($customer['UpdatedDateUTC']) ? $customer['UpdatedDateUTC'] : '';
                            $customerData[$key]['contactId'] = $contactId;
                            $customerData[$key]['updateId'] = $uniqueUpdateId;
                            #Save Customer Data
                            if (!empty($customerData)) {
                                //echo "<pre>"; print_r($customerData); die('Xero Customer Data 3112');                               
                                $insertPLData = DB::table('syncsupplier')->insert($customerData[$key]);
                            }
                        } else if ($customer['UpdatedDateUTC'] > $lastCronTime && $lastCronTime != '' && $contactType != '') {
                            $updateCustomerData[$key]['frenns_id'] = $uniqueId;
                            $updateCustomerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateCustomerData[$key]['company_name'] = isset($customer['Name']) ? $customer['Name'] : '';
                            $updateCustomerData[$key]['company_account_number'] = '';
                            $updateCustomerData[$key]['company_number'] = '';
                            $updateCustomerData[$key]['Type'] = $contactType;
                            $updateCustomerData[$key]['cust_supp_company'] = '';
                            $updateCustomerData[$key]['custsupp_companynumber'] = '';
                            $updateCustomerData[$key]['account_number'] = '';
                            $updateCustomerData[$key]['name'] = isset($customer['Name']) ? $customer['Name'] : '';
                            $updateCustomerData[$key]['Address'] = isset($customer['Addresses'][0]['AddressType']) ? $customer['Addresses'][0]['AddressType'] : '';
                            $updateCustomerData[$key]['Postcode'] = isset($customer['Addresses'][0]['PostalCode']) ? $customer['Addresses'][0]['PostalCode'] : '';
                            $updateCustomerData[$key]['City'] = isset($customer['Addresses'][0]['City']) ? $customer['Addresses'][0]['City'] : '';
                            $updateCustomerData[$key]['country'] = isset($customer['Addresses'][0]['Country']) ? $customer['Addresses'][0]['Country'] : '';
                            $updateCustomerData[$key]['contact_person'] = isset($customer['Name']) ? $customer['Name'] : '';
                            $updateCustomerData[$key]['phone_number'] = isset($customer['Phones'][3]->PhoneNumber) ? $customer['Phones'][3]->PhoneNumber : '';
                            $updateCustomerData[$key]['Email'] = isset($customer['PrimaryEmailAddr']['Address']) ? $customer['PrimaryEmailAddr']['Address'] : '';
                            $updateCustomerData[$key]['collection_date'] = date('Y-m-d');
                            $updateCustomerData[$key]['last_update'] = isset($customer['UpdatedDateUTC']) ? $customer['UpdatedDateUTC'] : '';
                            $updateCustomerData[$key]['contactId'] = $contactId;
                            $updateCustomerData[$key]['updateId'] = $uniqueUpdateId;

                            #Update Customer Data
                            if (!empty($updateCustomerData)) {
                                //echo "<pre>"; print_r($updateCustomerData); die('Xero Update Customer Data 3238');   
                                $updatePLData = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateCustomerData[$key]);
                            }
                        } else {
                            ## No Action needed.
                        }
                    }
                } else {
                    ## oauth_problem ## token_expired ## redirect to xero
                    echo "Token Expire for user " . $uniqueId . ".Please contact to administrator.";
                    exit;
                }


                ####################################### Customer Section End ########################################################
                ####################################### Xero Invoice Section ########################################################

                $response = $XeroOAuth->request('GET', $XeroOAuth->url('Invoices', 'core'), array(), 'xml', '');
                if ($XeroOAuth->response['code'] == 200) {

                    $invoices = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                    $invoices = json_decode(json_encode($invoices), true);
                    //echo '###<pre>';print_r($invoices); die(' All Invoices');
                    #Get Last Cron Time
                    $data['accounting_system'] = 'xero';
                    $data['item_type'] = 'invoice';
                    $data['user'] = $uniqueId;
                    //$lastCronTime = $this->apidetail->getCronTime($data);
                    $lastCronTime = DB::table('appcrontracker')->where('accounting_system', 'xero')->where('item_type', 'invoice')->orderBy('id', 'desc')->first();
                    $lastCronTime = isset($lastCronTime->cron_time) ? $lastCronTime->cron_time : '';

                    #Save Cron time 
                    $responseTime = explode(".", $invoices['DateTimeUTC']);
                    $CronTime = $responseTime[0];
                    $cronData[0]['accounting_system'] = 'xero';
                    $cronData[0]['item_type'] = 'invoice';
                    $cronData[0]['user'] = $uniqueId;
                    $cronData[0]['cron_time'] = $CronTime;
                    //$this->apidetail->saveCronTime($cronData);
                    DB::table('appcrontracker')->insert($cronData);

                    ## Fetch all xero invoices ids from local database 
                    //$localInvoices = $this->apidetail->getInvoicesIds($uniqueId);
                    $localInvoices = DB::table('syncinvoice')->select('syncinvoice.invoiceId')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    $localIvoicesArr = array();
                    if (!empty($localInvoices)) {
                        foreach ($localInvoices as $key => $localInvoice) {
                            $localIvoicesArr[] = $localInvoice->invoiceId;
                        }
                    }

                    //echo "<pre>"; print_r($localIvoicesArr); die('Xero Local Invoices 3289');

                    foreach ($invoices['Invoices']['Invoice'] as $key => $invoices) {
                        //echo '<pre>';print_r($invoices); //die(' Single Invoice 3292');
                        $response = $XeroOAuth->request('GET', $XeroOAuth->url('Invoices/' . $invoices['InvoiceID'], 'core'), array(), 'xml', '');
                        if ($XeroOAuth->response['code'] == 200) {
                            $responseSinglInvoice = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                            $invoice = json_decode(json_encode($responseSinglInvoice), true);
                            $invoice = $invoice['Invoices']['Invoice'];

                            $uniqueUpdateId = $uniqueFrennsId . '-' . $invoices['InvoiceID'];
                            if (!in_array($invoice['InvoiceID'], $localIvoicesArr)) {

                                $invoiceData[$key]['frenns_id'] = $uniqueId;
                                $invoiceData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $invoiceData[$key]['company_account_number'] = '';
                                $invoiceData[$key]['collection_date'] = date('Y-m-d');
                                $invoiceData[$key]['creation_date'] = isset($invoice['Date']) ? $invoice['Date'] : '';
                                $invoiceData[$key]['last_updated'] = isset($invoice['UpdatedDateUTC']) ? $invoice['UpdatedDateUTC'] : '';
                                $invoiceData[$key]['name'] = isset($invoice['Contact']['Name']) ? $invoice['Contact']['Name'] : '';
                                $invoiceData[$key]['address'] = isset($invoice['Contact'][0]['AttentionTo']) ? $invoice['Contact'][0]['AttentionTo'] : '';
                                $invoiceData[$key]['postcode'] = isset($invoice['Addresses'][0]['PostalCode']) ? $invoice['Addresses'][0]['PostalCode'] : '';
                                $invoiceData[$key]['city'] = isset($invoice['Addresses'][0]['City']) ? $invoice['Addresses'][0]['City'] : '';
                                $invoiceData[$key]['country'] = isset($invoice['Addresses'][0]['Country']) ? $invoice['Addresses'][0]['Country'] : '';
                                $invoiceData[$key]['company_number'] = '';
                                $invoiceData[$key]['vat_registration_number'] = '';
                                $invoiceData[$key]['contact_person'] = isset($invoice['Contact']['Name']) ? $invoice['Contact']['Name'] : '';
                                $invoiceData[$key]['phone_no'] = isset($invoice['Phones'][3]['PhoneNumber']) ? $invoice['Phones'][3]['PhoneNumber'] : '';
                                $invoiceData[$key]['email'] = isset($invoice['Contact']['EmailAddress']) ? $invoice['Contact']['EmailAddress'] : '';
                                $invoiceData[$key]['type'] = isset($invoice['Type']) ? $invoice['Type'] : '';
                                $invoiceData[$key]['invoice_number'] = isset($invoice['InvoiceNumber']) ? $invoice['InvoiceNumber'] : '';
                                $invoiceData[$key]['issue_date'] = isset($invoice['Date']) ? $invoice['Date'] : '';
                                $invoiceData[$key]['due_date'] = isset($invoice['DueDate']) ? $invoice['DueDate'] : '';
                                $invoiceData[$key]['payment_terms'] = '';
                                $invoiceData[$key]['payment_method'] = '';
                                $invoiceData[$key]['delivery_date'] = '';
                                $invoiceData[$key]['currency'] = isset($invoice['CurrencyCode']) ? $invoice['CurrencyCode'] : '';
                                $invoiceData[$key]['amount'] = isset($invoice['SubTotal']) ? $invoice['SubTotal'] : '';
                                $invoiceData[$key]['vat_amount'] = isset($invoice['TotalTax']) ? $invoice['TotalTax'] : '';
                                $invoiceData[$key]['outstanding_amount'] = isset($invoice['Total']) ? $invoice['Total'] : '';
                                $invoiceData[$key]['paid'] = '';
                                $invoiceData[$key]['pay_date'] = isset($invoice['FullyPaidOnDate']) ? $invoice['FullyPaidOnDate'] : '';
                                $invoiceData[$key]['invoiceId'] = $invoices['InvoiceID'];
                                $invoiceData[$key]['updateId'] = $uniqueUpdateId;

                                #Save Xero Invoices 
                                if (!empty($invoiceData)) {
                                    //$this->apidetail->addInvoice($invoiceData); die('Xero Invoice Data 3331'); 
                                    DB::table('syncinvoice')->insert($invoiceData[$key]);
                                }

                                #New Invoice item data
                                if (isset($invoice['LineItems'])) {
                                    $itemKey = 0;
                                    foreach ($invoice['LineItems'] as $itemKey => $invoiceLine) {
                                        //echo '<pre>';print_r($invoiceLine); //die(' Single Invoice Line 3340');
                                        if (isset($invoiceLine['LineItemID'])) {
                                            $invoiceItemUpdateId = $uniqueUpdateId . "-" . $invoiceLine['LineItemID'];
                                            $invoiceItemData[$key . $itemKey]['frenns_id'] = $uniqueId;
                                            $invoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                            $invoiceItemData[$key . $itemKey]['invoice_number'] = isset($invoice['InvoiceNumber']) ? $invoice['InvoiceNumber'] : '';
                                            $invoiceItemData[$key . $itemKey]['line_number'] = isset($invoiceLine['LineItemID']) ? $invoiceLine['LineItemID'] : '';
                                            $invoiceItemData[$key . $itemKey]['product_code'] = isset($invoiceLine['ItemCode']) ? $invoiceLine['ItemCode'] : '';
                                            $invoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine['Description']) ? $invoiceLine['Description'] : '';
                                            $invoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine['Quantity']) ? $invoiceLine['Quantity'] : '';
                                            $invoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine['UnitAmount']) ? $invoiceLine['UnitAmount'] : '';
                                            //$invoiceItemData[$key . $itemKey]['amount_net'] = isset($invoiceLine['Amount']) ? $invoiceLine['Amount'] : '';
                                            //$invoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine['TaxAmount']) ? $invoiceLine['TaxAmount'] : '';
                                            $invoiceItemData[$key . $itemKey]['amount_total'] = isset($invoiceLine['LineAmount']) ? $invoiceLine['LineAmount'] : '';
                                            $invoiceItemData[$key . $itemKey]['invoice_id'] = isset($invoices['InvoiceID']) ? $invoices['InvoiceID'] : '';
                                            $invoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                            #Save Xero Invoices items 
                                            if (!empty($invoiceItemData)) {
                                                //$this->apidetail->addInvoiceItem($invoiceItemData); die('Xero Invoice Item Data 3358');                                          
                                                DB::table('syncinvoice_item')->insert($invoiceItemData[$key . $itemKey]);
                                            }
                                            $itemKey++;
                                        }
                                    }
                                }
                            } else if ($invoice['UpdatedDateUTC'] > $lastCronTime && $lastCronTime != '') {
                                #Upsate invoice data                                
                                $updateInvoiceData[$key]['frenns_id'] = $uniqueId;
                                $updateInvoiceData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvoiceData[$key]['company_account_number'] = '';
                                $updateInvoiceData[$key]['collection_date'] = date('Y-m-d');
                                $updateInvoiceData[$key]['creation_date'] = isset($invoice['Date']) ? $invoice['Date'] : '';
                                $updateInvoiceData[$key]['last_updated'] = isset($invoice['UpdatedDateUTC']) ? $invoice['UpdatedDateUTC'] : '';
                                $updateInvoiceData[$key]['name'] = isset($invoice['Contact']['Name']) ? $invoice['Contact']['Name'] : '';
                                $updateInvoiceData[$key]['address'] = isset($invoice['Contact'][0]['AttentionTo']) ? $invoice['Contact'][0]['AttentionTo'] : '';
                                $updateInvoiceData[$key]['postcode'] = isset($invoice['Addresses'][0]['PostalCode']) ? $invoice['Addresses'][0]['PostalCode'] : '';
                                $updateInvoiceData[$key]['city'] = isset($invoice['Addresses'][0]['City']) ? $invoice['Addresses'][0]['City'] : '';
                                $updateInvoiceData[$key]['country'] = isset($invoice['Addresses'][0]['Country']) ? $invoice['Addresses'][0]['Country'] : '';
                                $updateInvoiceData[$key]['company_number'] = '';
                                $updateInvoiceData[$key]['vat_registration_number'] = '';
                                $updateInvoiceData[$key]['contact_person'] = isset($invoice['Contact']['Name']) ? $invoice['Contact']['Name'] : '';
                                $updateInvoiceData[$key]['phone_no'] = isset($invoice['Phones'][3]['PhoneNumber']) ? $invoice['Phones'][3]['PhoneNumber'] : '';
                                $updateInvoiceData[$key]['email'] = isset($invoice['Contact']['EmailAddress']) ? $invoice['Contact']['EmailAddress'] : '';
                                $updateInvoiceData[$key]['type'] = isset($invoice['Type']) ? $invoice['Type'] : '';
                                $updateInvoiceData[$key]['invoice_number'] = isset($invoice['InvoiceNumber']) ? $invoice['InvoiceNumber'] : '';
                                $updateInvoiceData[$key]['issue_date'] = isset($invoice['Date']) ? $invoice['Date'] : '';
                                $updateInvoiceData[$key]['due_date'] = isset($invoice['DueDate']) ? $invoice['DueDate'] : '';
                                $updateInvoiceData[$key]['payment_terms'] = '';
                                $updateInvoiceData[$key]['payment_method'] = '';
                                $updateInvoiceData[$key]['delivery_date'] = '';
                                $updateInvoiceData[$key]['currency'] = isset($invoice['CurrencyCode']) ? $invoice['CurrencyCode'] : '';
                                $updateInvoiceData[$key]['amount'] = isset($invoice['SubTotal']) ? $invoice['SubTotal'] : '';
                                $updateInvoiceData[$key]['vat_amount'] = isset($invoice['TotalTax']) ? $invoice['TotalTax'] : '';
                                $updateInvoiceData[$key]['outstanding_amount'] = isset($invoice['Total']) ? $invoice['Total'] : '';
                                $updateInvoiceData[$key]['paid'] = '';
                                $updateInvoiceData[$key]['pay_date'] = isset($invoice['FullyPaidOnDate']) ? $invoice['FullyPaidOnDate'] : '';
                                $updateInvoiceData[$key]['invoiceId'] = isset($invoices['InvoiceID']) ? $invoices['InvoiceID'] : '';
                                $updateInvoiceData[$key]['updateId'] = $uniqueUpdateId;

                                #Update Xero Invoices                               
                                DB::table('syncinvoice')->where('updateId', $uniqueUpdateId)->update($updateInvoiceData[$key]);
                                #Upsate invoice item data

                                if (isset($invoice['Line'])) {
                                    #Delete All Lines for this user
                                    DB::table('syncinvoice_item')->where('unique_frenns_id', $uniqueFrennsId)->where('invoice_id', $invoice['InvoiceID'])->delete();
                                    $itemKey = 0;
                                    foreach ($invoice['Line'] as $invoiceLine) {
                                        if (isset($invoiceLine['Id'])) {
                                            $invoiceItemUpdateId = $uniqueUpdateId . '-' . $invoiceLine['Id'];
                                            $UpdateInvoiceItemData[$key . $itemKey]['frenns_id'] = $uniqueId;
                                            $UpdateInvoiceItemData[$key . $itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                            $UpdateInvoiceItemData[$key . $itemKey]['invoice_number'] = isset($invoice['InvoiceNumber']) ? $invoice['InvoiceNumber'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['line_number'] = isset($invoiceLine['LineItemID']) ? $invoiceLine['LineItemID'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['product_code'] = isset($invoiceLine['ItemCode']) ? $invoiceLine['ItemCode'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['description'] = isset($invoiceLine['Description']) ? $invoiceLine['Description'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['qty'] = isset($invoiceLine['Quantity']) ? $invoiceLine['Quantity'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['rate'] = isset($invoiceLine['UnitAmount']) ? $invoiceLine['UnitAmount'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['amount_net'] = isset($invoiceLine['Amount']) ? $invoiceLine['Amount'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['invoiceline_vat_amount'] = isset($invoiceLine['TaxAmount']) ? $invoiceLine['TaxAmount'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['amount_total'] = isset($invoices['InvoiceID']) ? $invoices['InvoiceID'] : '';
                                            $UpdateInvoiceItemData[$key . $itemKey]['updateId'] = $invoiceItemUpdateId;

                                            #Update Xero Invoices items                                            
                                            //DB::table('syncinvoice')->where('updateId', $invoiceItemUpdateId)->update($UpdateInvoiceItemData[$key . $itemKey]);
                                            DB::table('syncinvoice_item')->insert($UpdateInvoiceItemData[$key . $itemKey]);
                                            $itemKey++;
                                        }
                                    }
                                }
                            } else {
                                ## No Action needed.                    
                            }
                        }
                    }
                } else {
                    ## oauth_problem ## token_expired ## redirect to xero
                    echo "Token Expire for user " . $uniqueId . ".Please contact to administrator.";
                    exit;
                }

                ############################ Xero Invoice Section End  ##########################
            }
            //echo 'All information saved successfully!!';
            return Redirect::to('https://development.frenns.com/apiwork/xr-lib/public.php?success=1');
            //return Redirect::to(url('/') . '/newPage?success=1');
            //return Redirect::to('http://localhost/tpa/newPage?success=1');            
            //exit;
        } else {
            echo "No user available in database.";
            exit;
        }
    }

    ################################ Xero API End  ###################################
    ############################## SAGEONE API HERE ##################################

    public function sageOneRecords() {
        // echo "<Pre>";
        ini_set('mysql.connect_timeout', 500);
        ini_set('default_socket_timeout', 500);
        require_once(app_path('Services/sageonev3/') . 'SageoneSigner.php');
        $invTypes = array('sales_invoices', 'purchase_invoices');
        $accounting_system = 'sageone';
        if (isset($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }
        //$getdetail = $this->apidetail->getSingleUserDetail($type, $userNumber);
        //$getdetail = $this->apidetail->getSingleUserDetail($accounting_system, $userNumber);      
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }
        //print_r($getdetail); die('=========');
        //echo "<Pre>";
        if (!empty($getdetail)) {
            $invDetailList = array();
            $prodDetailList = array();
            $mydata = array();
            $singleLedgerList = array();
            $maxUpdatedTime = '';
            $getLastUpdatedLedger = '';
            $lastUpdateTime = '';
            $lastUpdatedTime = '';
            $lastUpdateTime2 = '';
            $getLastUpdatedLedgerEntries = '';
            $ContactlastUpdatedTime = '';
            $updateSupplierDetailList = array();
            $addSupplierDetailList = array();
            $addInvDetailList = array();
            $updateInvDetailList = array();

            $addSingleLedgerList = array();
            $updateSingleLedgerList = array();
            $addNominalLedger = array();
            $updateNominalLedger = array();

            for ($i = 0; $i < count($getdetail); $i++) {
                $k = 0;
                $usernumber = $getdetail[$i]->usernumber;
                $referesh = $getdetail[$i]->refresh_token;
                $country = $getdetail[$i]->country;
                $uniqueFrennsId = $getdetail[$i]->accounting_system . "-" . $usernumber;

                // max time for invoice
                $intype = '1';
                //$getLastUpdatedInvoice = $this->apidetail->getLastUpdatedInvoice($usernumber, $intype);
                $getLastUpdatedInvoice = DB::table('syncinvoice')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->where('type', '!=', 'expense')->orderBy('last_updated', 'desc')->limit(1)->get();
                //print_r($getLastUpdatedInvoice); die('-=-=-=-');
                if (count($getLastUpdatedInvoice) > 0) {
                    $lastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                } else {
                    $lastUpdatedTime = '';
                }

                // max time for supplier/customer
                //$getLastUpdatedContact = $this->apidetail->getLastUpdatedContact($usernumber);
                $getLastUpdatedContact = DB::table('syncsupplier')->select('last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();

                if (count($getLastUpdatedContact) > 0) {
                    $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                }

                // max time for ledger account
                //$getLastUpdatedLedgerEntries = $this->apidetail->getLastUpdatedLedgerEntries($usernumber);
                $getLastUpdatedLedgerEntriesFirst = DB::table('syncnominal')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_updated', 'desc')->limit(1)->get();
                $getLastUpdatedLedgerEntriesSec = DB::table('syncledger_transaction')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_updated', 'desc')->limit(1)->get();
                $getLastUpdatedLedgerEntries = array(
                    $getLastUpdatedLedgerEntriesFirst, $getLastUpdatedLedgerEntriesSec
                );
                //echo '<pre>';print_r($getLastUpdatedLedgerEntries);
                //$maxUpdatedTime = max($getLastUpdatedLedgerEntries->last_updated);
                $maxUpdatedTime = max($getLastUpdatedLedgerEntries);

                if (count($maxUpdatedTime) > 0) {
                    $getLastUpdatedLedger = $maxUpdatedTime[0]->last_updated;
                } else {
                    $getLastUpdatedLedger = '';
                }

                $accounting_system = 'sageone';
                //$detail = $this->apidetail->getApiDetail($accounting_system);
                $detail = DB::table('appDetail')->where('accounting_system', $accounting_system)->limit(1)->get();
                //print_r($detail); die;
                $this->client_id = $detail[0]->clientId;
                $this->clientSecret = $detail[0]->clientSecret;
                $this->signingSecret = $detail[0]->signingSecret;
                $this->callbackUrl = $detail[0]->callbackUrl;
                $this->apim_subscription_key = '9b2394b00cf141e0ade928b140cc67bf';
                $this->scope = $detail[0]->scope;
                $this->auth_endpoint = 'https://www.sageone.com/oauth2/auth/central';
                $this->token_endpoint = 'https://api.sageone.com/oauth2/token';
                $this->base_endpoint = 'https://api.sageone.com/';
                $this->auth_endpoint = 'http://www.sageone.com/oauth2/auth';
                $this->us_token_endpoint = 'http://mysageone.na.sageone.com/oauth2/token';
                $this->ca_token_endpoint = 'http://mysageone.ca.sageone.com/oauth2/token';
                $this->uki_token_endpoint = 'http://app.sageone.com/oauth2/token';
                $this->us_base_endpoint = 'https://api.columbus.sage.com/us/sageone/';
                $this->ca_base_endpoint = 'https://api.columbus.sage.com/ca/sageone/';
                $this->uki_base_endpoint = 'https://api.columbus.sage.com/uki/sageone/';

                switch ($country) {
                    case "CA":
                        $base_endpoint = $this->us_base_endpoint;
                        $token_endpoint = $this->us_token_endpoint;
                        break;
                    case "US":
                        $base_endpoint = $this->us_base_endpoint;
                        $token_endpoint = $this->us_token_endpoint;
                        break;
                    case "IE": case "GB":
                        $base_endpoint = $this->uki_base_endpoint;
                        $token_endpoint = $this->uki_token_endpoint;
                        break;
                    default:
                        $base_endpoint = $this->uki_base_endpoint;
                        $token_endpoint = $this->uki_token_endpoint;
                        break;
                };

                //echo '---user-----' . $i . '<br>';
                //$response = authRedirect($this->client_id,$this->callbackUrl, $this->auth_endpoint,$this->scope);
                //echo $response;die;
                // $response = getToken($this->client_id, $this->clientSecret, $referesh, $token_endpoint);
                $response = Helper::renewAccessToken($this->client_id, $this->clientSecret, $referesh, $token_endpoint);

                //print_r($response); die;
                $sageone_guid = json_decode($response, true)['resource_owner_id'];
                $token = json_decode($response, true)['access_token'];
                $refresh_token = json_decode($response, true)['refresh_token'];
                $signing_secret = $this->signingSecret;
                if (!empty($refresh_token)) {
                    // $this->apidetail->updateUserDetail($getdetail[$i]->usernumber, $token, $refresh_token);
                    DB::table('synccredential')->where('usernumber', $getdetail[$i]->usernumber)->update(array('access_token' => $token, 'refresh_token' => $refresh_token));
                }

//              invoice and item
                foreach ($invTypes as $typ) {

                    if ($k > 90) {
                        sleep(40);
                    }
                    //echo '1111---' . $k . '---111<br>' . $typ;
                    $nonce = bin2hex(openssl_random_pseudo_bytes(32));
                    $header = array("Accept: *.*",
                        "Content-Type: application/json",
                        "User-Agent: Frenns App",
                        "ocp-apim-subscription-key: " . $this->apim_subscription_key
                    );

                    $endpoint24 = 'accounts/v3/' . $typ;

                    $getInvoiceCount = Helper::callApi($base_endpoint, $endpoint24, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                    $totalInvoiceitem = '$total';
                    $totalInvoices = $getInvoiceCount->$totalInvoiceitem;
                    //echo "<pre>";                    print_r($getInvoiceCount); die;
                    if (!empty($lastUpdatedTime)) {
                        $lastSec = substr($lastUpdatedTime, 11, 8);
                        $add_time = strtotime($lastSec) + 1;
                        $replacement = date('h:i:s', $add_time);
                        $length = strlen($replacement);
                        $position = -9;
                        $lastUpdateTime = substr_replace($lastUpdatedTime, $replacement, $position, $length);
                        //$endpoint = 'accounts/v3/' . $typ . '?updated_or_created_since=' . $lastUpdateTime . '&items_per_page=' . $totalInvoices;
                        //$endpoint = 'accounts/v3/' . $typ . '?items_per_page=' . $totalInvoices;
                        $endpoint = 'accounts/v3/' . $typ . '?items_per_page=' . $totalInvoices;
                    } else {
                        $endpoint = 'accounts/v3/' . $typ . '?items_per_page=' . $totalInvoices;
                    }
                    $url = $base_endpoint . $endpoint;
                    $params = "";
                    /* generate the request signature */
                    $responsess = Helper::callApi($base_endpoint, $endpoint, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);
                    //print_r($responsess);
                    //ob_clean();
                    $json = array();
                    $invoiceIDs = array();
                    $invoiceProducts = array();
                    $inv = '$items';
                    //echo "<pre>";print_r($responsess);die;
                    if (!empty($responsess->$inv)) {
                        $invId = $responsess->$inv;
                    } else {
                        $invId = '';
                    }

                    //print_r($invId);die;
                    if (!empty($invId)) {
                        //echo "<Pre>";
                        //echo 'uuuy';
//                   echo '<pre>';
//                   print_r($responsess);
//                   print_r($invId); //die;
                        foreach ($invId as $key1 => $invoiceData) {

                            if ($k > 90) {
                                sleep(40);
                            }
                            // echo "Invoice --- " . $invoiceData->id . '---------------' . $typ . '<br>';
                            $endpoint2 = 'accounts/v3/' . $typ . '/' . $invoiceData->id . '?show_payments_allocations=true';
                            $invoiceProducts[] = Helper::callApi($base_endpoint, $endpoint2, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);
                            // $k++;
                        }
                        //echo '<pre>'; print_r($invoiceProducts); die;
                        foreach ($invoiceProducts as $key2 => $value2) {
                            if ($k > 90) {
                                sleep(40);
                            }
                            //  echo $value2->updated_at;
                            $updatedTimeInvoice = $value2->updated_at;
                            $createdTimeInvoice = $value2->created_at;

                            /*                             * ******   To get customer contact    ******** */
                            $endpoint3 = 'accounts/v3/contacts/' . $value2->contact->id;
                            $contactPerson = Helper::callApi($base_endpoint, $endpoint3, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);
                            //print_r($contactPerson);
                            //die;
                            /*                             * *******     To get customer contact details    ********* */
                            $endpoint4 = 'accounts/v3/contact_persons/' . $contactPerson->main_contact_person->id;

                            $contactPersonDetail = Helper::callApi($base_endpoint, $endpoint4, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);
                            //echo "<pre>";//print_r($contactPersonDetail);
                            if ($value2->status->id == 'PAID') {
                                $paid = 'true';
                            } else {
                                $paid = 'false';
                            }

                            if (!empty($value2->payments_allocations) && $value2->status->id == 'PAID') {
                                $pay_date = $value2->payments_allocations[0]->date;
                            }


                            if ($createdTimeInvoice > $lastUpdatedTime) {
                                //$addInvDetailList[$key2]['invoice_id'] = $value2->id;
                                $addInvDetailList[$key2]['frenns_id'] = $usernumber;
                                $addInvDetailList[$key2]['last_updated'] = $value2->updated_at;
                                if ($typ == 'sales_invoices') {
                                    $addInvDetailList[$key2]['invoice_number'] = $value2->invoice_number;
                                } else {
                                    $addInvDetailList[$key2]['invoice_number'] = 0;
                                }
                                $addInvDetailList[$key2]['amount'] = $value2->net_amount;
                                $addInvDetailList[$key2]['issue_date'] = $value2->created_at;
                                $addInvDetailList[$key2]['creation_date'] = $value2->created_at;
                                $addInvDetailList[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $addInvDetailList[$key2]['collection_date'] = date('Y-m-d');
                                $addInvDetailList[$key2]['vat_amount'] = $value2->tax_amount;
                                $addInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                                $addInvDetailList[$key2]['currency'] = $value2->currency->id;
                                $addInvDetailList[$key2]['paid'] = $paid;
                                //$addInvDetailList[$key2]['pay_date'] = $value2->date;
                                $addInvDetailList[$key2]['due_date'] = $value2->due_date;
                                $addInvDetailList[$key2]['contact_person'] = $contactPersonDetail->name;
                                $addInvDetailList[$key2]['name'] = $value2->contact_name;
                                $addInvDetailList[$key2]['phone_no'] = $contactPersonDetail->mobile;
                                $addInvDetailList[$key2]['email'] = $contactPersonDetail->email;
                                $addInvDetailList[$key2]['type'] = str_replace('_', " ", $typ);
                                $addInvDetailList[$key2]['account_number'] = $contactPerson->bank_account_details->account_number;
                                $addInvDetailList[$key2]['payment_terms'] = $contactPerson->credit_days;
                                $addInvDetailList[$key2]['payment_method'] = $contactPerson->bank_account_details->iban;
                                $addInvDetailList[$key2]['company_number'] = $contactPerson->registered_number;
                                // $addInvDetailList['type'] = $contactPerson->contact_types->displayed_as;
                                $addInvDetailList[$key2]['pay_date'] = isset($pay_date) ? $pay_date : '';

                                $addInvDetailList[$key2]['vat_registration_number'] = $contactPerson->tax_number;
                                $addInvDetailList[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2->id;

                                if ($typ == 'sales_invoices') {
                                    $addInvDetailList[$key2]['address'] = $value2->main_address->address_line_1 . ' ' . $value2->main_address->address_line_2;
                                    $addInvDetailList[$key2]['city'] = $value2->main_address->city;
                                    $addInvDetailList[$key2]['country'] = $value2->main_address->country->id;
                                    $addInvDetailList[$key2]['postcode'] = $value2->main_address->postal_code;
                                } else {
                                    $addInvDetailList[$key2]['city'] = 0;
                                    $addInvDetailList[$key2]['address'] = 0;
                                    $addInvDetailList[$key2]['country'] = 0;
                                    $addInvDetailList[$key2]['postcode'] = 0;
                                }
                                #Save invoice
                                if (!empty($addInvDetailList)) {
                                    //echo "<pre>"; print_r($addInvDetailList[$key2]); die('======');
                                    DB::table('syncinvoice')->insert($addInvDetailList[$key2]);
                                    $addInvDetailList = '';
                                }
                            } else
                            if ($updatedTimeInvoice > $lastUpdatedTime) {
                                // $updateInvDetailList[$key2]['invoice_id'] = $value2->id;
                                $updateInvDetailList[$key2]['frenns_id'] = $usernumber;
                                $updateInvDetailList[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvDetailList[$key2]['last_updated'] = $value2->updated_at;
                                if ($typ == 'sales_invoices') {
                                    $updateInvDetailList[$key2]['invoice_number'] = $value2->invoice_number;
                                } else {
                                    $updateInvDetailList[$key2]['invoice_number'] = 0;
                                }
                                $updateInvDetailList[$key2]['amount'] = $value2->net_amount;
                                $updateInvDetailList[$key2]['issue_date'] = $value2->created_at;
                                $updateInvDetailList[$key2]['creation_date'] = $value2->created_at;
                                $updateInvDetailList[$key2]['collection_date'] = date('Y-m-d');
                                $updateInvDetailList[$key2]['vat_amount'] = $value2->tax_amount;
                                $updateInvDetailList[$key2]['outstanding_amount'] = $value2->outstanding_amount;
                                $updateInvDetailList[$key2]['currency'] = $value2->currency->id;
                                $updateInvDetailList[$key2]['paid'] = $paid;
                                $updateInvDetailList[$key2]['pay_date'] = isset($pay_date) ? $pay_date : '';
                                //$updateInvDetailList[$key2]['pay_date'] = $value2->date;
                                $updateInvDetailList[$key2]['due_date'] = $value2->due_date;
                                $updateInvDetailList[$key2]['contact_person'] = $contactPersonDetail->name;
                                $updateInvDetailList[$key2]['name'] = $value2->contact_name;
                                $updateInvDetailList[$key2]['phone_no'] = $contactPersonDetail->mobile;
                                $updateInvDetailList[$key2]['email'] = $contactPersonDetail->email;
                                $updateInvDetailList[$key2]['type'] = str_replace('_', " ", $typ);
                                $updateInvDetailList[$key2]['account_number'] = $contactPerson->bank_account_details->account_number;
                                $updateInvDetailList[$key2]['payment_terms'] = $contactPerson->credit_days;
                                $updateInvDetailList[$key2]['payment_method'] = $contactPerson->bank_account_details->iban;
                                $updateInvDetailList[$key2]['company_number'] = $contactPerson->registered_number;
                                // $updateInvDetailList['type'] = $contactPerson->contact_types->displayed_as;
                                $updateInvDetailList[$key2]['vat_registration_number'] = $contactPerson->tax_number;
                                $updateInvDetailList[$key2]['updateId'] = $uniqueFrennsId . '-' . $value2->id;

                                if ($typ == 'sales_invoices') {
                                    $updateInvDetailList[$key2]['address'] = $value2->main_address->address_line_1 . ' ' . $value2->main_address->address_line_2;
                                    $updateInvDetailList[$key2]['city'] = $value2->main_address->city;
                                    $updateInvDetailList[$key2]['country'] = $value2->main_address->country->id;
                                    $updateInvDetailList[$key2]['postcode'] = $value2->main_address->postal_code;
                                } else {
                                    $updateInvDetailList[$key2]['city'] = 0;
                                    $updateInvDetailList[$key2]['address'] = 0;
                                    $updateInvDetailList[$key2]['country'] = 0;
                                    $updateInvDetailList[$key2]['postcode'] = 0;
                                }

                                #Update invoice Data
                                if (!empty($updateInvDetailList)) {
                                    $where['updateId'] = $updateInvDetailList[$key2]['updateId'];
                                    $update = $updateInvDetailList[$key2];
                                    DB::table('syncinvoice')->where($where)->update($update);
                                }
                            } else {
                                ##
                            }

                            $prodDetailList = array();
                            foreach ($value2->invoice_lines as $kk => $v) {
                                if ($k > 90) {

                                    sleep(40);
                                }
                                $lines = $kk + 1;
                                $prodDetailList[$kk]['invoice_id'] = $value2->id;
                                if ($typ == 'sales_invoices') {
                                    $prodDetailList[$kk]['invoice_number'] = $value2->invoice_number;
                                } else {
                                    $prodDetailList[$kk]['invoice_number'] = 0;
                                }

                                $prodDetailList[$kk]['product_code'] = $v->displayed_as;
                                $prodDetailList[$kk]['unique_frenns_id'] = $uniqueFrennsId;
                                //$prodDetailList[$kk]['product_id'] = $v->product->id;
                                $prodDetailList[$kk]['frenns_id'] = $usernumber;
                                $prodDetailList[$kk]['description'] = $v->description;
                                $prodDetailList[$kk]['qty'] = $v->quantity;
                                $prodDetailList[$kk]['rate'] = $v->unit_price;
                                $prodDetailList[$kk]['amount_net'] = $v->net_amount;
                                $prodDetailList[$kk]['invoiceline_vat_amount'] = $v->tax_amount;
                                $prodDetailList[$kk]['amount_total'] = $v->total_amount;
                                $prodDetailList[$kk]['line_number'] = $lines;
                                $prodDetailList[$kk]['updateId'] = $uniqueFrennsId . '-' . $value2->id;
                                $updateIdss = $value2->id;

                                #Save inv item
                                if (!empty($prodDetailList)) {
                                    //echo "<pre>"; print_r($addInvDetailList[$key2]); die('======');                                
                                    ## Delete all invoice items
                                    DB::table('syncinvoice_item')->where('invoice_id', $updateIdss)->delete();
                                    $addInvoiceItem = DB::table('syncinvoice_item')->insert($prodDetailList[$kk]);
                                    $prodDetailList = '';
                                }
                                $k++;
                            }
                            //$addInvoiceItem = $this->apidetail->addInvoiceItem($prodDetailList, $value2->id);
                            $k++;
                        }
                    }


                    $k++;
                }

                // supplier                
                $endpoint23 = 'accounts/v3/contacts';

                $getSupplierCount = Helper::callApi($base_endpoint, $endpoint23, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                $totalitem = '$total';
                $totalSupplier = $getSupplierCount->$totalitem;

                if (!empty($ContactlastUpdatedTime)) {
                    $lastSec1 = substr($ContactlastUpdatedTime, 11, 8);
                    $add_time1 = strtotime($lastSec1) + 1;
                    $replacement1 = date('h:i:s', $add_time1);
                    $length1 = strlen($replacement1);
                    $position1 = -9;
                    $lastUpdateTime1 = substr_replace($ContactlastUpdatedTime, $replacement1, $position1, $length1);
                    //$endpoint5 = 'accounts/v3/contacts?updated_or_created_since=' . $lastUpdateTime1 . '&items_per_page=' . $totalSupplier;
                    $endpoint5 = 'accounts/v3/contacts?items_per_page=' . $totalSupplier;
                } else {
                    $endpoint5 = 'accounts/v3/contacts?items_per_page=' . $totalSupplier;
                }

                $getSupplier = Helper::callApi($base_endpoint, $endpoint5, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                $itm = '$items';
                $mainItem = $getSupplier->$itm;
                foreach ($mainItem as $key => $valueItem) {
                    if ($k > 90) {
                        sleep(40);
                    }
                    /*                     * ******     To get customer /supplier details    ********* */
                    $endpoint6 = 'accounts/v3/contacts/' . $valueItem->id;

                    $SupplierContactDetail = Helper::callApi($base_endpoint, $endpoint6, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);
                    //print_r($SupplierContactDetail);
                    $updatedTimeSupplier = $SupplierContactDetail->updated_at;
                    $createdTimeSupplier = $SupplierContactDetail->created_at;

                    if ($createdTimeSupplier > $ContactlastUpdatedTime) {
                        //$addSupplierDetailList[$key]['company_account_number'] = $usernumber;
                        $addSupplierDetailList[$key]['frenns_id'] = $usernumber;
                        $addSupplierDetailList[$key]['company_name'] = $SupplierContactDetail->name;
                        $addSupplierDetailList[$key]['unique_frenns_id'] = $uniqueFrennsId;
                        //$addSupplierDetailList[$key]['customer_id'] = $SupplierContactDetail->id;
                        //$supplierDetailList['CollectionDate'] = $SupplierContactDetail->created_at;
                        $addSupplierDetailList[$key]['collection_date'] = date('Y-m-d');
                        $addSupplierDetailList[$key]['last_update'] = $SupplierContactDetail->updated_at;
                        $addSupplierDetailList[$key]['type'] = $SupplierContactDetail->contact_types[0]->displayed_as;

                        //$supplierDetailList['CompanyNumber'] = $SupplierContactDetail->registered_number;

                        $addSupplierDetailList[$key]['vat_registration'] = $SupplierContactDetail->tax_number;
                        //$supplierDetailList['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;
                        // address detail
                        $endpoint7 = 'accounts/v3/addresses/' . $SupplierContactDetail->main_address->id;

                        $addressDetail = Helper::callApi($base_endpoint, $endpoint7, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                        $addSupplierDetailList[$key]['address'] = $addressDetail->address_line_1 . ' ' . $addressDetail->address_line_2;
                        $addSupplierDetailList[$key]['postcode'] = $addressDetail->postal_code;
                        $addSupplierDetailList[$key]['city'] = $addressDetail->city;
                        $addSupplierDetailList[$key]['country'] = $addressDetail->country->id;

                        // contact person detail
                        $endpoint8 = 'accounts/v3/contact_persons/' . $SupplierContactDetail->main_contact_person->id;

                        $suppContactPersonDetail = Helper::callApi($base_endpoint, $endpoint8, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                        $addSupplierDetailList[$key]['contact_person'] = $suppContactPersonDetail->name;
                        $addSupplierDetailList[$key]['phone_number'] = $suppContactPersonDetail->mobile;
                        $addSupplierDetailList[$key]['email'] = $suppContactPersonDetail->email;
                        $addSupplierDetailList[$key]['updateId'] = $uniqueFrennsId . '-' . $SupplierContactDetail->id;

                        if (!empty($SupplierContactDetail->default_sales_ledger_account)) {

                            $endpoint21 = 'accounts/v3/ledger_accounts/' . $SupplierContactDetail->default_sales_ledger_account->id;

                            $getAccountNo = Helper::callApi($base_endpoint, $endpoint21, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                            $addSupplierDetailList[$key]['account_number'] = $getAccountNo->nominal_code;
                        } else {
                            $addSupplierDetailList[$key]['account_number'] = 0;
                        }
                        #Save client
                        if (!empty($addSupplierDetailList)) {
                            //echo "<pre>"; print_r($addSupplierDetailList[$key2]); die('======');
                            DB::table('syncsupplier')->insert($addSupplierDetailList[$key]);
                            $addSupplierDetailList = '';
                        }
                    } else
                    if ($updatedTimeSupplier > $ContactlastUpdatedTime) {
                        //$updateSupplierDetailList[$key]['company_account_number'] = $usernumber;
                        $updateSupplierDetailList[$key]['frenns_id'] = $usernumber;
                        $updateSupplierDetailList[$key]['company_name'] = $SupplierContactDetail->name;
                        $updateSupplierDetailList[$key]['unique_frenns_id'] = $uniqueFrennsId;
                        //$updateSupplierDetailList[$key]['customer_id'] = $SupplierContactDetail->id;
                        //$supplierDetailList['CollectionDate'] = $SupplierContactDetail->created_at;
                        $updateSupplierDetailList[$key]['collection_date'] = date('Y-m-d');
                        $updateSupplierDetailList[$key]['last_update'] = $SupplierContactDetail->updated_at;
                        $updateSupplierDetailList[$key]['type'] = $SupplierContactDetail->contact_types[0]->displayed_as;

                        //$supplierDetailList['CompanyNumber'] = $SupplierContactDetail->registered_number;

                        $updateSupplierDetailList[$key]['vat_registration'] = $SupplierContactDetail->tax_number;
                        //$supplierDetailList['AccountNumber'] = $SupplierContactDetail->bank_account_details->account_number;
                        // address detail
                        $endpoint7 = 'accounts/v3/addresses/' . $SupplierContactDetail->main_address->id;

                        $addressDetail = Helper::callApi($base_endpoint, $endpoint7, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                        $updateSupplierDetailList[$key]['address'] = $addressDetail->address_line_1 . ' ' . $addressDetail->address_line_2;
                        $updateSupplierDetailList[$key]['postcode'] = $addressDetail->postal_code;
                        $updateSupplierDetailList[$key]['city'] = $addressDetail->city;
                        $updateSupplierDetailList[$key]['country'] = $addressDetail->country->id;

                        // contact person detail
                        $endpoint8 = 'accounts/v3/contact_persons/' . $SupplierContactDetail->main_contact_person->id;

                        $suppContactPersonDetail = Helper::callApi($base_endpoint, $endpoint8, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                        $updateSupplierDetailList[$key]['contact_person'] = $suppContactPersonDetail->name;
                        $updateSupplierDetailList[$key]['phone_number'] = $suppContactPersonDetail->mobile;
                        $updateSupplierDetailList[$key]['email'] = $suppContactPersonDetail->email;
                        $updateSupplierDetailList[$key]['updateId'] = $uniqueFrennsId . '-' . $SupplierContactDetail->id;

                        if (!empty($SupplierContactDetail->default_sales_ledger_account)) {

                            $endpoint21 = 'accounts/v3/ledger_accounts/' . $SupplierContactDetail->default_sales_ledger_account->id;

                            $getAccountNo = Helper::callApi($base_endpoint, $endpoint21, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                            $updateSupplierDetailList[$key]['account_number'] = $getAccountNo->nominal_code;
                        } else {
                            $updateSupplierDetailList[$key]['account_number'] = 0;
                        }
                        #Update Customer Data
                        if (!empty($updateCustomerData)) {
                            $where['updateId'] = $updateSupplierDetailList[$key]['updateId'];
                            $update = $updateSupplierDetailList[$key];
                            DB::table('syncsupplier')->where($where)->update($update);
                        }
                    } else {
                        ##
                    }

                    $k++;
                }

                // ledger entries
                $endpoint22 = 'accounts/v3/ledger_entries';
                $getledgerEntriesCount = Helper::callApi($base_endpoint, $endpoint22, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);
                $ledgeritms = '$total';
                $totalTransactionCount = $getledgerEntriesCount->$ledgeritms;
                //for get ledger acount 
                if (!empty($getLastUpdatedLedger)) {

                    $lastSec2 = substr($getLastUpdatedLedger, 11, 8);
                    $add_time2 = strtotime($lastSec2) + 1;
                    $replacement2 = date('h:i:s', $add_time2);
                    $length2 = strlen($replacement2);
                    $position1 = -9;
                    $lastUpdateTime2 = substr_replace($getLastUpdatedLedger, $replacement2, $position1, $length2);

                    // $endpoint10 = 'accounts/v3/ledger_entries?updated_or_created_since=' . $lastUpdateTime2 . '&items_per_page=' . $totalTransactionCount;
                    $endpoint10 = 'accounts/v3/ledger_entries?items_per_page=' . $totalTransactionCount;
                } else {
                    $endpoint10 = 'accounts/v3/ledger_entries?items_per_page=' . $totalTransactionCount;
                }
                $getledgerEntries = Helper::callApi($base_endpoint, $endpoint10, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                $ledgeritm = '$items';

                $mainledgerEntries = $getledgerEntries->$ledgeritm;
                foreach ($mainledgerEntries as $keyy => $ledgervalueItem) {

                    //echo '333---' . $k . '---333<br>';
                    $endpoint11 = 'accounts/v3/ledger_entries/' . $ledgervalueItem->id;

                    $getSingleledgerEntries = Helper::callApi($base_endpoint, $endpoint11, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                    $endpoint13 = 'accounts/v3/ledger_accounts/' . $getSingleledgerEntries->ledger_account->id;

                    $getSingleledgerAccount = Helper::callApi($base_endpoint, $endpoint13, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                    // print_r($getSingleledgerAccount);
                    $endpoint13 = 'accounts/v3/contacts/' . $getSingleledgerEntries->contact->id;

                    $refrenceDetail = Helper::callApi($base_endpoint, $endpoint13, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);
                    //print_r($refrenceDetail);

                    $nominalBal = $getSingleledgerAccount->nominal_code;
                    $updatedTime = $getSingleledgerEntries->updated_at;
                    $createdTime = $getSingleledgerEntries->created_at;
                    $last_updated_Id = $usernumber . '-' . $ledgervalueItem->id;

                    // for transaction api call
                    $endpoint12 = 'accounts/v3/transactions/' . $getSingleledgerEntries->transaction->id;

                    $getSingleledgerTransactions = Helper::callApi($base_endpoint, $endpoint12, $signing_secret, $token, $sageone_guid, $this->apim_subscription_key);

                    if ($nominalBal < '4000') {
                        if ($createdTime > $lastUpdateTime2) {
                            $addSingleLedgerList[$keyy]['NominalAccountCode'] = $nominalBal;
                            $addSingleLedgerList[$keyy]['TransactionLastUpdatedOn'] = $getSingleledgerAccount->updated_at;

                            $addSingleLedgerList[$keyy]['frenns_id'] = $usernumber;
                            $addSingleLedgerList[$keyy]['unique_frenns_id'] = $uniqueFrennsId;
                            //$addSingleLedgerList[$keyy]['ledger_entries_id'] = $ledgervalueItem->id;
                            $addSingleLedgerList[$keyy]['collection_date'] = date('Y-m-d');
                            $addSingleLedgerList[$keyy]['last_updated'] = $getSingleledgerEntries->updated_at;
                            $addSingleLedgerList[$keyy]['CreditAmount'] = $getSingleledgerEntries->credit;
                            $addSingleLedgerList[$keyy]['debit_amount'] = $getSingleledgerEntries->debit;
                            $addSingleLedgerList[$keyy]['description'] = isset($refrenceDetail->reference) ? $refrenceDetail->reference : '';
                            $addSingleLedgerList[$keyy]['Reference'] = isset($refrenceDetail->reference) ? $refrenceDetail->reference : '';
                            $addSingleLedgerList[$keyy]['entry_number'] = $getSingleledgerEntries->id;
                            $addSingleLedgerList[$keyy]['entry_date'] = $getSingleledgerEntries->date;

                            //$customerData[$key]['CollectionDate'] = isset($customer['MetaData']['CreateTime'])?$customer['MetaData']['CreateTime']:'';
                            $addSingleLedgerList[$keyy]['sourcetype'] = isset($getSingleledgerTransactions->transaction_type->displayed_as) ? $getSingleledgerTransactions->transaction_type->displayed_as : '';
                            $addSingleLedgerList[$keyy]['sourceid'] = isset($getSingleledgerTransactions->origin->displayed_as) ? $getSingleledgerTransactions->origin->displayed_as : '';
                            $addSingleLedgerList[$keyy]['InvoiceDescription'] = isset($getSingleledgerTransactions->origin->displayed_as) ? $getSingleledgerTransactions->origin->displayed_as : '';
                            $addSingleLedgerList[$keyy]['companyname'] = isset($getSingleledgerTransactions->contact->displayed_as) ? $getSingleledgerTransactions->contact->displayed_as : '';
                            $addSingleLedgerList[$keyy]['updateId'] = $uniqueFrennsId . '-' . $ledgervalueItem->id;

                            #Save transaction
                            if (!empty($addSingleLedgerList)) {
                                //echo "<pre>"; print_r($addSupplierDetailList[$key2]); die('======');
                                DB::table('syncledger_transaction')->insert($addSingleLedgerList[$keyy]);
                                $addSingleLedgerList = array();
                            }
                        } else
                        if ($updatedTime > $lastUpdateTime2) {
                            $updateSingleLedgerList[$keyy]['NominalAccountCode'] = $nominalBal;
                            $updateSingleLedgerList[$keyy]['TransactionLastUpdatedOn'] = $getSingleledgerAccount->updated_at;

                            $updateSingleLedgerList[$keyy]['frenns_id'] = $usernumber;
                            $updateSingleLedgerList[$keyy]['unique_frenns_id'] = $uniqueFrennsId;
                            //$updateSingleLedgerList[$keyy]['ledger_entries_id'] = $ledgervalueItem->id;
                            $updateSingleLedgerList[$keyy]['collection_date'] = date('Y-m-d');
                            $updateSingleLedgerList[$keyy]['last_updated'] = $getSingleledgerEntries->updated_at;
                            $updateSingleLedgerList[$keyy]['CreditAmount'] = $getSingleledgerEntries->credit;
                            $updateSingleLedgerList[$keyy]['debit_amount'] = $getSingleledgerEntries->debit;
                            $updateSingleLedgerList[$keyy]['description'] = isset($refrenceDetail->reference) ? $refrenceDetail->reference : '';
                            $updateSingleLedgerList[$keyy]['Reference'] = isset($refrenceDetail->reference) ? $refrenceDetail->reference : '';
                            $updateSingleLedgerList[$keyy]['entry_number'] = $getSingleledgerEntries->id;
                            $updateSingleLedgerList[$keyy]['entry_date'] = $getSingleledgerEntries->date;

                            //$customerData[$key]['CollectionDate'] = isset($customer['MetaData']['CreateTime'])?$customer['MetaData']['CreateTime']:'';
                            $updateSingleLedgerList[$keyy]['sourcetype'] = isset($getSingleledgerTransactions->transaction_type->displayed_as) ? $getSingleledgerTransactions->transaction_type->displayed_as : '';
                            $updateSingleLedgerList[$keyy]['sourceid'] = isset($getSingleledgerTransactions->origin->displayed_as) ? $getSingleledgerTransactions->origin->displayed_as : '';
                            $updateSingleLedgerList[$keyy]['InvoiceDescription'] = isset($getSingleledgerTransactions->origin->displayed_as) ? $getSingleledgerTransactions->origin->displayed_as : '';
                            $updateSingleLedgerList[$keyy]['companyname'] = isset($getSingleledgerTransactions->contact->displayed_as) ? $getSingleledgerTransactions->contact->displayed_as : '';
                            $updateSingleLedgerList[$keyy]['updateId'] = $uniqueFrennsId . '-' . $ledgervalueItem->id;

                            if (!empty($updateSingleLedgerList)) {
                                // echo 'update';
                                // print_r($updateClients);
                                //$updateInvoiceItems = $this->apidetail->updateSuppliers($updateClients, 'updateId');
                                $updateInvoiceItems = DB::table('syncledger_transaction')->where('updateId', $last_updated_Id)->update($updateSingleLedgerList[$keyy]);
                                $updateSingleLedgerList = array();
                            }
                        } else {
                            ##
                        }
                    }
                    if ($nominalBal > '3999') {

                        if ($createdTime > $lastUpdateTime2) {
                            $addNominalLedger[$keyy]['frenns_id'] = $usernumber;
                            $addNominalLedger[$keyy]['companyname'] = $getSingleledgerTransactions->contact->displayed_as;
                            //$addNominalLedger[$keyy]['ledger_entries_id'] = $ledgervalueItem->id;
                            $addNominalLedger[$keyy]['unique_frenns_id'] = $uniqueFrennsId;
                            $addNominalLedger[$keyy]['collection_date'] = date('Y-m-d');
                            $addNominalLedger[$keyy]['last_updated'] = $getSingleledgerEntries->updated_at;
                            $addNominalLedger[$keyy]['total_credit'] = $getSingleledgerEntries->credit;
                            $addNominalLedger[$keyy]['total_debit'] = $getSingleledgerEntries->debit;
                            //$getNominalLedger['Name'] = $getSingleledgerAccount->display_name;
                            $addNominalLedger[$keyy]['account_type'] = $getSingleledgerAccount->ledger_account_type->displayed_as;
                            $addNominalLedger[$keyy]['account'] = $nominalBal;
                            $addNominalLedger[$keyy]['type'] = $getSingleledgerAccount->name;
                            $addNominalLedger[$keyy]['updateId'] = $usernumber . '-' . $ledgervalueItem->id;
                            #add nominal ledger 
                            if (!empty($addNominalLedger)) {
                                //echo "<pre>"; print_r($addNominalLedger[$key2]); die('======');
                                DB::table('syncnominal')->insert($addNominalLedger[$keyy]);
                                $addNominalLedger = '';
                            }
                        } else
                        if ($updatedTime > $lastUpdateTime2) {
                            $updateNominalLedger[$keyy]['frenns_id'] = $usernumber;
                            $updateNominalLedger[$keyy]['companyname'] = $getSingleledgerTransactions->contact->displayed_as;
                            //$updateNominalLedger[$keyy]['ledger_entries_id'] = $ledgervalueItem->id;
                            $updateNominalLedger[$keyy]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateNominalLedger[$keyy]['collection_date'] = date('Y-m-d');
                            $updateNominalLedger[$keyy]['last_updated'] = $getSingleledgerEntries->updated_at;
                            $updateNominalLedger[$keyy]['total_credit'] = $getSingleledgerEntries->credit;
                            $updateNominalLedger[$keyy]['total_debit'] = $getSingleledgerEntries->debit;
                            //$getNominalLedger['Name'] = $getSingleledgerAccount->display_name;
                            $updateNominalLedger[$keyy]['account_type'] = $getSingleledgerAccount->ledger_account_type->displayed_as;
                            $updateNominalLedger[$keyy]['account'] = $nominalBal;
                            $updateNominalLedger[$keyy]['type'] = $getSingleledgerAccount->name;
                            $updateNominalLedger[$keyy]['updateId'] = $usernumber . '-' . $ledgervalueItem->id;

                            #Update nominal ledger
                            if (!empty($updateNominalLedger)) {
                                $where['updateId'] = $updateNominalLedger[$keyy]['updateId'];
                                $update = $updateNominalLedger[$keyy];
                                DB::table('syncnominal')->where($where)->update($update);
                            }
                        } else {
                            ##
                        }
                    }
                    $k++;
                }
            }
            echo "All information has been stored successfully!!";
        }
    }

    ############################## SAGEONE API END HERE ###############################
    ############################## EXCAT API START HERE ###############################
    // exact nl

    public function exactData() {
        $addExpense = array();
        $updateExpense = array();
        //require_once (APPPATH . $GLOBALS['exactonlineapi'] . '/example/example.php');
        require_once(app_path('Services/exactonlineapi/') . 'example/example.php');
        $accounting_system = 'exactNL';

        if (!empty($_REQUEST['userNumber'])) {
            $userNumber = $_REQUEST['userNumber'];
        } else {
            $userNumber = '';
        }

        //$getdetail = $this->apidetail->getSingleUserDetail($accounting_system, $userNumber = '');
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }
        //print_r($getdetail);
        if (!empty($getdetail)) {
            $response = array();
            $addSupplierDetailList = array();
            $updateSupplierDetailList = array();
            for ($i = 0; $i < count($getdetail); $i++) {
                $usernumber = $getdetail[$i]->usernumber;
                $accountId = $getdetail[$i]->accountId;
                $access_token_db = $getdetail[$i]->access_token;
                $refresh_token_db = $getdetail[$i]->refresh_token;
                $codee_db = $getdetail[$i]->code;
                $expires_in = $getdetail[$i]->expires_in;
                $uniqueFrennsId = $getdetail[$i]->accounting_system . "-" . $usernumber;

                if (isset($_GET['code'])) {
                    $code = $_GET['code'];
                    //$userNumber = $_GET['userNumber'];
                    //$this->apidetail->updateFreshbookCode($accounting_system, $code);
                    DB::table('appDetail')->where('accounting_system', $accounting_system)->update('code', $code);
                    //$this->apidetail->updateExactCode($usernumber, $code);
                    DB::table('synccredential')->where('accounting_system', $accounting_system)->update('code', $code);
                }
                //$detail = $this->apidetail->getApiDetail($accounting_system);
                $detail = DB::table('appDetail')->where('accounting_system', $accounting_system)->get();
                $client_id = $detail[0]->clientId;
                $client_secret = $detail[0]->clientSecret;
                $callbackUrl = $detail[0]->callbackUrl;
                $codeDb = $detail[0]->code;
                //$auth = authorize($client_id, $client_secret, $callbackUrl);
                //print_r($auth);
                //die('pop');
                $connection = connect($client_id, $client_secret, $callbackUrl);
                //print_r($connection); 

                if (!empty($codee_db)) { // Retrieves authorizationcode from database
                    $connection->setAuthorizationCode($codee_db);
                }

                if (!empty($access_token_db)) { // Retrieves accesstoken from database
                    $connection->setAccessToken($access_token_db);
                }

                if (!empty($refresh_token_db)) { // Retrieves refreshtoken from database
                    $connection->setRefreshToken($refresh_token_db);
                }

                if (!empty($expires_in)) { // Retrieves expires timestamp from database
                    $connection->setTokenExpires($expires_in);
                }

                $access_token = $connection->getAccessToken();
                $refresh_token = $connection->getRefreshToken();
                $expire_ins = $connection->getTokenExpires();
                $data_db = array(
                    'access_token' => $access_token,
                    'refresh_token' => $refresh_token,
                    'expires_in' => $expire_ins,
                );


                //$this->apidetail->updateUserDetails($usernumber, $data_db);
                DB::table('synccredential')->where('usernumber', $usernumber)->update($data_db);
                //die;

                try {

                    // get invoice
                    // max time for invoice 
                    $intype = '1';
                    //$getLastUpdatedInvoice = $this->apidetail->getLastUpdatedInvoice($usernumber, $intype);                    
                    $getLastUpdatedInvoice = DB::table('syncinvoice')->where('unique_frenns_id', $uniqueFrennsId)->where('type', '!=', 'expense')->get();
                    if (count($getLastUpdatedInvoice) > 0) {
                        $lastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                        $lastUpdatedDate = date("Y-m-d", strtotime($lastUpdatedTime));
                    } else {
                        $lastUpdatedTime = '';
                        $lastUpdatedDate = '';
                    }
                    //get invoice 
                    //$dbInvoice = $this->apidetail->getInvoicesIds($usernumber);
                    $dbInvoice = DB::table('syncinvoice')->select('syncinvoice.invoiceId')->where('unique_frenns_id', $uniqueFrennsId)->get();

                    $dbInvoiceArr = array();
                    if (!empty($dbInvoice)) {
                        foreach ($dbInvoice as $key => $dbInvoices) {
                            $dbInvoiceArr[] = $dbInvoices->invoiceId;
                        }
                    } else {
                        $dbInvoiceArr = array();
                    }
                    //echo $usernumber;
                    $invoice = new \Picqer\Financials\Exact\SalesInvoice($connection);
                    $invoiceRes = $invoice->get();
                    //echo '<pre>';
                    //print_r($invoiceRes);
                    //echo '<pre>';
                    //print_r($invoiceRes);
                    //die;
                    foreach ($invoiceRes as $j => $valueInv) {
                        $last_modify = date('Y-m-d', substr($valueInv->Modified, 6, 10));
                        $updateId = $uniqueFrennsId . '-' . $valueInv->InvoiceID;
                        if (!in_array($valueInv->InvoiceID, $dbInvoiceArr)) {
                            $addInvDetailList[$j]['invoiceId'] = $valueInv->InvoiceID;
                            $addInvDetailList[$j]['unique_frenns_id'] = $uniqueFrennsId;
                            $addInvDetailList[$j]['frenns_id'] = $usernumber;
                            $addInvDetailList[$j]['last_updated'] = date('Y-m-d', substr($valueInv->Modified, 6, 10));
                            $addInvDetailList[$j]['invoice_number'] = $valueInv->InvoiceNumber;
                            $addInvDetailList[$j]['name'] = $valueInv->InvoiceToName;
                            $addInvDetailList[$j]['amount'] = $valueInv->AmountFC;
                            //$addInvDetailList[$j]['issue_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));

                            $addInvDetailList[$j]['collection_date'] = date('Y-m-d');
                            $addInvDetailList[$j]['vat_amount'] = $valueInv->VATAmountFC;
                            //$addInvDetailList[$j]['outstanding_amount'] = $valueInv->NetAmount;
                            $addInvDetailList[$j]['currency'] = $valueInv->Currency;
                            //$addInvDetailList[$j]['paid'] = $paid;
                            //$addInvDetailList[$j]['pay_date'] = date('Y-m-d', substr($valueInv->InvoiceDate, 6, 10));
                            $addInvDetailList[$j]['due_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));
                            $addInvDetailList[$j]['updateId'] = $updateId;

                            //get cutomer details
                            $addInvDetailList[$j]['contact_person'] = $valueInv->OrderedByContactPersonFullName;
                            ########### add update invoice here
                            if (!empty($addInvDetailList)) {
                                //echo 'add Inv';
                                //print_r($addInvDetailList);
                                //$addInvoice = $this->apidetail->addInvoice($addInvDetailList);
                                $addInvoice = DB::table('syncinvoice')->insert($addInvDetailList[$j]);
                                $addInvDetailList = array();
                            }
                        } else if ($last_modify >= $lastUpdatedDate && $lastUpdatedDate != '') {

                            $updateInvoice[$j]['invoiceId'] = $valueInv->InvoiceID;
                            $updateInvoice[$j]['frenns_id'] = $usernumber;
                            $updateInvoice[$j]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateInvoice[$j]['last_updated'] = date('Y-m-d', substr($valueInv->Modified, 6, 10));
                            $updateInvoice[$j]['invoice_number'] = $valueInv->InvoiceNumber;
                            $updateInvoice[$j]['name'] = $valueInv->InvoiceToName;
                            $updateInvoice[$j]['amount'] = $valueInv->AmountFC;
                            //$updateInvoice[$j]['issue_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));
                            $updateInvoice[$j]['collection_date'] = date('Y-m-d');
                            $updateInvoice[$j]['vat_amount'] = $valueInv->VATAmountFC;
                            //$addInvDetailList[$j]['outstanding_amount'] = $valueInv->NetAmount;
                            $updateInvoice[$j]['currency'] = $valueInv->Currency;
                            //$addInvDetailList[$j]['paid'] = $paid;
                            //$updateInvoice[$j]['pay_date'] = date('Y-m-d', substr($valueInv->InvoiceDate, 6, 10));
                            $updateInvoice[$j]['due_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));
                            $updateInvoice[$j]['updateId'] = $updateId;

                            //get cutomer details
                            $updateInvoice[$j]['contact_person'] = $valueInv->OrderedByContactPersonFullName;

                            if (!empty($updateInvoice)) {
                                //echo 'update inv';
                                //print_r($updateInvoice);
                                //$updateInvoice = $this->apidetail->updateInvoice($updateInvoice, 'updateId');
                                $updateInvoice = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateInvoice[$j]);
                                $updateInvoice = array();
                            }
                        }
                    }

                    // invoice item
                    $SalesInvoiceLine = new \Picqer\Financials\Exact\SalesInvoiceLine($connection);
                    $InvoiceLines = $SalesInvoiceLine->get();
                    //echo '<preprint_r($InvoiceLines); die;
                    foreach ($InvoiceLines as $key => $invoiceLine) {
                        //$this->apidetail->deleteInvoiceItem($invoiceLine->InvoiceID);
                        DB::table('syncinvoice_item')->where('invoice_id', $invoiceLine->InvoiceID)->delete();
                        $invoiceItem[$key]['frenns_id'] = $usernumber;
                        $invoiceItem[$key]['unique_frenns_id'] = $uniqueFrennsId;
                        //$invoiceItem[$key]['invoice_number'] = isset($invoiceLine->invoiceNumber) ? $invoiceLine->invoiceNumber : '';
                        $invoiceItem[$key]['line_number'] = $invoiceLine->LineNumber;
                        $invoiceItem[$key]['product_code'] = $invoiceLine->ItemCode;
                        $invoiceItem[$key]['description'] = $invoiceLine->ItemDescription;
                        $invoiceItem[$key]['qty'] = $invoiceLine->Quantity;
                        $invoiceItem[$key]['rate'] = $invoiceLine->NetPrice;
                        $invoiceItem[$key]['amount_net'] = $invoiceLine->AmountFC;
                        $invoiceItem[$key]['invoiceline_vat_amount'] = $invoiceLine->VATAmountFC;
                        $invoiceItem[$key]['amount_total'] = $invoiceLine->NetPrice * $invoiceLine->Quantity + $invoiceLine->VATAmountFC;
                        $invoiceItem[$key]['invoice_id'] = $invoiceLine->InvoiceID;
                        $invoiceItem[$key]['updateId'] = $uniqueFrennsId . '-' . $invoiceLine->InvoiceID;
                        if (!empty($invoiceItem)) {
                            //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItem, '');
                            $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItem[$key]);
                        }
                    }


                    // get contacts
                    // max time for supplier/customer
                    $ContactlastUpdatedTime = '';
                    //$getLastUpdatedContact = $this->apidetail->getLastUpdatedContact($usernumber);
                    $getLastUpdatedContact = DB::table('syncsupplier')->select('last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();
                    if (count($getLastUpdatedContact) > 0) {
                        $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                        $ContactlastUpdatedDate = date("Y-m-d", strtotime($ContactlastUpdatedTime));
                    } else {
                        $ContactlastUpdatedTime = '';
                        $ContactlastUpdatedDate = '';
                    }
                    //get cust/suplier from db
                    //$localContacts = $this->apidetail->getCustomerSuppliersIds($usernumber);
                    $localContacts = DB::table('syncsupplier')->select('contactId')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    $localContactsArr = array();
                    if (!empty($localContacts)) {
                        foreach ($localContacts as $key => $localContact) {
                            $localContactsArr[] = $localContact->contactId;
                        }
                    } else {
                        $localContactsArr = array();
                    }

                    $Contact = new \Picqer\Financials\Exact\Contact($connection);
                    $result = $Contact->get();
                    //echo "<pre>";
                    //print_r($localContactsArr); 
                    //die('=========');
                    foreach ($result as $key => $value1) {
                        if ($value1->AccountIsSupplier == 1) {
                            $type = 'supplier';
                        }
                        if ($value1->AccountIsCustomer == 1) {
                            $type = 'customer';
                        }
                        $last_mod = date('Y-m-d', substr($value1->Modified, 6, 10));
                        $updateId = $uniqueFrennsId . '-' . $value1->ID;

                        if (!in_array($value1->ID, $localContactsArr)) {

                            $addClients[$key]['frenns_id'] = $usernumber;
                            $addClients[$key]['company_name'] = $value1->AccountName;
                            $addClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            //$addClients[$key]['company_number'] = $value1->company_number;
                            $addClients[$key]['collection_date'] = date('Y-m-d');
                            $addClients[$key]['last_update'] = date('Y-m-d', substr($value1->Modified, 6, 10));
                            $addClients[$key]['type'] = $type;

                            // contact person detail
                            $addClients[$key]['contact_person'] = $value1->FullName;
                            $addClients[$key]['phone_number'] = $value1->Phone;
                            $addClients[$key]['email'] = $value1->Email;
                            $addClients[$key]['contactId'] = $value1->ID;
                            $addClients[$key]['updateId'] = $updateId;

                            // add client                       
                            if (!empty($addClients)) {
                                //addSuppliers = $this->apidetail->addSuppliers($addClients);
                                $addSuppliers = DB::table('syncsupplier')->insert($addClients[$key]);
                                $addClients = array();
                            }
                        } else if ($last_mod >= $ContactlastUpdatedDate && $ContactlastUpdatedDate != '') {

                            $updateClients[$key]['company_name'] = $value1->AccountName;
                            $updateClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateClients[$key]['collection_date'] = date('Y-m-d');
                            $updateClients[$key]['last_update'] = date('Y-m-d', substr($value1->Modified, 6, 10));
                            $updateClients[$key]['type'] = $type;
                            //contact person detail
                            $updateClients[$key]['contact_person'] = $value1->FullName;
                            $updateClients[$key]['phone_number'] = $value1->Phone;
                            $updateClients[$key]['email'] = $value1->Email;
                            $updateClients[$key]['contactId'] = $value1->ID;
                            $updateClients[$key]['updateId'] = $updateId;
                            //update client
                            if (!empty($updateClients)) {
                                //$updateSuppliers = $this->apidetail->updateSuppliers($updateClients, 'updateId');
                                $updateSuppliers = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateClients[$key]);
                                $updateClients = array();
                            }
                        }
                    }


                    //get P&L
                    $ProfitLossOverview = new \Picqer\Financials\Exact\ProfitLossOverview($connection);
                    $pandL = $ProfitLossOverview->get();
                    $plJson = json_encode($pandL);
                    //echo "<pre>";print_r(json_encode($pandL)); die('=====');
                    if (!empty($pandL)) {
                        //$addPl = $this->apidetail->addPlData($usernumber, $plJson);
                        $getAddedPlData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->get();
                        if (count($getAddedPlData) > 0) {
                            $updateData['pl_data'] = $plJson;
                            $updatePLData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->update($updateData);
                        } else {
                            $insertData[0]['frenns_id'] = $usernumber;
                            $insertData[0]['unique_frenns_id'] = $uniqueFrennsId;
                            $insertData[0]['pl_data'] = $plJson;
                            $addPLData = DB::table('syncreport_pl')->insert($insertData);
                        }
                    }
                } catch (\Exception $e) {
                    echo get_class($e) . ' : ' . $e->getMessage();
                }
            }
            echo "All information has been stored successfully!!";
        } else {

            echo "No user available indatabase.";
        }
    }

    #

    #
       public function exactDataUK() {
        $addExpense = array();
        $updateExpense = array();
        //require_once (APPPATH . $GLOBALS['exactonlineapiUk'] . '/example/example.php');
        require_once(app_path('Services/exactonlineapiUk/') . 'example/example.php');
        $accounting_system = 'exactUK';

        if (!empty($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        //$getdetail = $this->apidetail->getSingleUserDetail($accounting_system, $userNumber = '');
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }
        //print_r($getdetail);
        if (!empty($getdetail)) {
            $response = array();

            for ($i = 0; $i < count($getdetail); $i++) {
                $usernumber = $getdetail[$i]->usernumber;
                $accountId = $getdetail[$i]->accountId;
                $access_token_db = $getdetail[$i]->access_token;
                $refresh_token_db = $getdetail[$i]->refresh_token;
                $codee_db = $getdetail[$i]->code;
                $expires_in = $getdetail[$i]->expires_in;
                $uniqueFrennsId = $getdetail[$i]->accounting_system . "-" . $usernumber;

                if (isset($_GET['code'])) {
                    $code = $_GET['code'];
                    //$userNumber = $_GET['userNumber'];
                    //$this->apidetail->updateFreshbookCode($accounting_system, $code);
                    //DB::table('appDetail')->where('accounting_system', $accounting_system)->update('code', $code);
                    //$this->apidetail->updateExactCode($usernumber, $code);
                    //DB::table('synccredential')->where('accounting_system', $accounting_system)->update('code', $code);
                }
                //$detail = $this->apidetail->getApiDetail($accounting_system);
                $detail = $getdetail = DB::table('appDetail')->where('accounting_system', $accounting_system)->get();
                $client_id = $detail[0]->clientId;
                $client_secret = $detail[0]->clientSecret;
                $callbackUrl = $detail[0]->callbackUrl;
                $codeDb = $detail[0]->code;
                //$auth = authorize($client_id, $client_secret, $callbackUrl);
                //print_r($auth);
                //die('pop');
                $connection = connect($client_id, $client_secret, $callbackUrl);
                //echo "<pre>"; print_r($connection);  

                if (!empty($codee_db)) { // Retrieves authorizationcode from database
                    $connection->setAuthorizationCode($codee_db);
                }

                if (!empty($access_token_db)) { // Retrieves accesstoken from database
                    $connection->setAccessToken($access_token_db);
                }

                if (!empty($refresh_token_db)) { // Retrieves refreshtoken from database
                    $connection->setRefreshToken($refresh_token_db);
                }

                if (!empty($expires_in)) { // Retrieves expires timestamp from database
                    $connection->setTokenExpires($expires_in);
                }
                //echo "<pre>"; print_r($connection);  die;
                $access_token = $connection->getAccessToken();
                $refresh_token = $connection->getRefreshToken();
                $expire_ins = $connection->getTokenExpires();
                $data_db = array(
                    'access_token' => $access_token,
                    'refresh_token' => $refresh_token,
                    'expires_in' => $expire_ins,
                );

                //$this->apidetail->updateUserDetails($usernumber, $data_db);
                DB::table('synccredential')->where('usernumber', $usernumber)->update($data_db);
                //die;
                try {

                    // get invoice
                    // max time for invoice 
                    $intype = '1';
                    //$getLastUpdatedInvoice = $this->apidetail->getLastUpdatedInvoice($usernumber, $intype);                    
                    $getLastUpdatedInvoice = DB::table('syncinvoice')->where('unique_frenns_id', $uniqueFrennsId)->where('type', '!=', 'expense')->get();
                    if (count($getLastUpdatedInvoice) > 0) {
                        $lastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                        $lastUpdatedDate = date("Y-m-d", strtotime($lastUpdatedTime));
                    } else {
                        $lastUpdatedTime = '';
                        $lastUpdatedDate = '';
                    }
                    //get invoice 
                    //$dbInvoice = $this->apidetail->getInvoicesIds($usernumber);
                    $dbInvoice = DB::table('syncinvoice')->select('syncinvoice.invoiceId')->where('unique_frenns_id', $uniqueFrennsId)->get();

                    $dbInvoiceArr = array();
                    if (!empty($dbInvoice)) {
                        foreach ($dbInvoice as $key => $dbInvoices) {
                            $dbInvoiceArr[] = $dbInvoices->invoiceId;
                        }
                    } else {
                        $dbInvoiceArr = array();
                    }
                    //echo $usernumber;
                    $invoice = new \Picqer\Financials\Exact\SalesInvoice($connection);
                    $invoiceRes = $invoice->get();
                    //echo '<pre>';
                    //print_r($invoice);
                    //echo '<pre>';
                    //print_r($invoiceRes);
                    //die;
                    foreach ($invoiceRes as $j => $valueInv) {
                        $last_modify = date('Y-m-d', substr($valueInv->Modified, 6, 10));
                        $updateId = $uniqueFrennsId . '-' . $valueInv->InvoiceID;
                        if (!in_array($valueInv->InvoiceID, $dbInvoiceArr)) {
                            $addInvDetailList[$j]['invoiceId'] = $valueInv->InvoiceID;
                            $addInvDetailList[$j]['unique_frenns_id'] = $uniqueFrennsId;
                            $addInvDetailList[$j]['frenns_id'] = $usernumber;
                            $addInvDetailList[$j]['last_updated'] = date('Y-m-d', substr($valueInv->Modified, 6, 10));
                            $addInvDetailList[$j]['creation_date'] = date('Y-m-d', substr($valueInv->Created, 6, 10));
                            //$addInvDetailList[$j]['invoice_number'] = $valueInv->InvoiceNumber;
                            $addInvDetailList[$j]['name'] = $valueInv->InvoiceToName;
                            $addInvDetailList[$j]['amount'] = $valueInv->AmountFC;
                            $addInvDetailList[$j]['issue_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));

                            $addInvDetailList[$j]['collection_date'] = date('Y-m-d');
                            $addInvDetailList[$j]['vat_amount'] = $valueInv->VATAmountFC;
                            //$addInvDetailList[$j]['outstanding_amount'] = $valueInv->NetAmount;
                            $addInvDetailList[$j]['currency'] = $valueInv->Currency;
                            //$addInvDetailList[$j]['paid'] = $paid;
                            //$addInvDetailList[$j]['pay_date'] = date('Y-m-d', substr($valueInv->InvoiceDate, 6, 10));
                            $addInvDetailList[$j]['due_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));
                            $addInvDetailList[$j]['updateId'] = $updateId;

                            //get cutomer details
                            $addInvDetailList[$j]['contact_person'] = $valueInv->OrderedByContactPersonFullName;
                            ########### add update invoice here
                            if (!empty($addInvDetailList)) {
                                //echo 'add Inv';
                                //print_r($addInvDetailList);
                                //$addInvoice = $this->apidetail->addInvoice($addInvDetailList);
                                $addInvoice = DB::table('syncinvoice')->insert($addInvDetailList[$j]);
                                $addInvDetailList = array();
                            }
                        } else if ($last_modify >= $lastUpdatedDate && $lastUpdatedDate != '') {

                            $updateInvoice[$j]['invoiceId'] = $valueInv->InvoiceID;
                            $updateInvoice[$j]['frenns_id'] = $usernumber;
                            $updateInvoice[$j]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateInvoice[$j]['creation_date'] = date('Y-m-d', substr($valueInv->Created, 6, 10));
                            $updateInvoice[$j]['last_updated'] = date('Y-m-d', substr($valueInv->Modified, 6, 10));
                            //$updateInvoice[$j]['invoice_number'] = $valueInv->InvoiceNumber;
                            $updateInvoice[$j]['name'] = $valueInv->InvoiceToName;
                            $updateInvoice[$j]['amount'] = $valueInv->AmountFC;
                            $updateInvoice[$j]['issue_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));
                            $updateInvoice[$j]['collection_date'] = date('Y-m-d');
                            $updateInvoice[$j]['vat_amount'] = $valueInv->VATAmountFC;
                            //$addInvDetailList[$j]['outstanding_amount'] = $valueInv->NetAmount;
                            $updateInvoice[$j]['currency'] = $valueInv->Currency;
                            //$addInvDetailList[$j]['paid'] = $paid;
                            //$updateInvoice[$j]['pay_date'] = date('Y-m-d', substr($valueInv->InvoiceDate, 6, 10));
                            $updateInvoice[$j]['due_date'] = date('Y-m-d', substr($valueInv->DueDate, 6, 10));
                            $updateInvoice[$j]['updateId'] = $updateId;

                            //get cutomer details
                            $updateInvoice[$j]['contact_person'] = $valueInv->OrderedByContactPersonFullName;

                            if (!empty($updateInvoice)) {
                                //echo 'update inv';
                                //print_r($updateInvoice);
                                //$updateInvoice = $this->apidetail->updateInvoice($updateInvoice, 'updateId');
                                $updateInvoice = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateInvoice[$j]);
                                $updateInvoice = array();
                            }
                        }
                    }

                    // invoice item
                    $SalesInvoiceLine = new \Picqer\Financials\Exact\SalesInvoiceLine($connection);
                    $InvoiceLines = $SalesInvoiceLine->get();

                    foreach ($InvoiceLines as $key => $invoiceLine) {
                        //$this->apidetail->deleteInvoiceItem($invoiceLine->InvoiceID);
                        DB::table('syncinvoice_item')->where('invoice_id', $invoiceLine->InvoiceID)->delete();
                        $invoiceItem[$key]['frenns_id'] = $usernumber;
                        $invoiceItem[$key]['unique_frenns_id'] = $uniqueFrennsId;
                        //$invoiceItem[$key]['invoice_number'] = isset($invoiceLine->invoiceNumber) ? $invoiceLine->invoiceNumber : '';
                        $invoiceItem[$key]['line_number'] = $invoiceLine->LineNumber;
                        $invoiceItem[$key]['product_code'] = $invoiceLine->ItemCode;
                        $invoiceItem[$key]['description'] = $invoiceLine->ItemDescription;
                        $invoiceItem[$key]['qty'] = $invoiceLine->Quantity;
                        $invoiceItem[$key]['rate'] = $invoiceLine->NetPrice;
                        $invoiceItem[$key]['amount_net'] = $invoiceLine->AmountFC;
                        $invoiceItem[$key]['invoiceline_vat_amount'] = $invoiceLine->VATAmountFC;
                        $invoiceItem[$key]['amount_total'] = $invoiceLine->NetPrice * $invoiceLine->Quantity + $invoiceLine->VATAmountFC;
                        $invoiceItem[$key]['invoice_id'] = $invoiceLine->InvoiceID;
                        $invoiceItem[$key]['updateId'] = $uniqueFrennsId . '-' . $invoiceLine->InvoiceID;
                        if (!empty($invoiceItem)) {
                            //$addInvoiceItem = $this->apidetail->addInvoiceItem($invoiceItem, '');
                            $addInvoiceItem = DB::table('syncinvoice_item')->insert($invoiceItem[$key]);
                        }
                    }


                    // get contacts
                    // max time for supplier/customer
                    $ContactlastUpdatedTime = '';
                    //$getLastUpdatedContact = $this->apidetail->getLastUpdatedContact($usernumber);
                    $getLastUpdatedContact = DB::table('syncsupplier')->select('last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();
                    if (count($getLastUpdatedContact) > 0) {
                        $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                        $ContactlastUpdatedDate = date("Y-m-d", strtotime($ContactlastUpdatedTime));
                    } else {
                        $ContactlastUpdatedTime = '';
                        $ContactlastUpdatedDate = '';
                    }
                    //get cust/suplier from db
                    //$localContacts = $this->apidetail->getCustomerSuppliersIds($usernumber);
                    $localContacts = DB::table('syncsupplier')->select('contactId')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    $localContactsArr = array();
                    if (!empty($localContacts)) {
                        foreach ($localContacts as $key => $localContact) {
                            $localContactsArr[] = $localContact->contactId;
                        }
                    } else {
                        $localContactsArr = array();
                    }

                    $Contact = new \Picqer\Financials\Exact\Contact($connection);
                    $result = $Contact->get();
                    //echo "<pre>";
                    //print_r($localContactsArr); 
                    //die('=========');
                    foreach ($result as $key => $value1) {
                        if ($value1->AccountIsSupplier == 1) {
                            $type = 'supplier';
                        }
                        if ($value1->AccountIsCustomer == 1) {
                            $type = 'customer';
                        }
                        $last_mod = date('Y-m-d', substr($value1->Modified, 6, 10));
                        $updateId = $uniqueFrennsId . '-' . $value1->ID;

                        if (!in_array($value1->ID, $localContactsArr)) {

                            $addClients[$key]['frenns_id'] = $usernumber;
                            $addClients[$key]['company_name'] = $value1->AccountName;
                            $addClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            //$addClients[$key]['company_number'] = $value1->company_number;
                            $addClients[$key]['collection_date'] = date('Y-m-d');
                            $addClients[$key]['last_update'] = date('Y-m-d', substr($value1->Modified, 6, 10));
                            $addClients[$key]['type'] = $type;

                            // contact person detail
                            $addClients[$key]['contact_person'] = $value1->FullName;
                            $addClients[$key]['phone_number'] = $value1->Phone;
                            $addClients[$key]['email'] = $value1->Email;
                            $addClients[$key]['contactId'] = $value1->ID;
                            $addClients[$key]['updateId'] = $updateId;

                            // add client                       
                            if (!empty($addClients)) {
                                //addSuppliers = $this->apidetail->addSuppliers($addClients);
                                $addSuppliers = DB::table('syncsupplier')->insert($addClients[$key]);
                                $addClients = array();
                            }
                        } else if ($last_mod >= $ContactlastUpdatedDate && $ContactlastUpdatedDate != '') {

                            $updateClients[$key]['company_name'] = $value1->AccountName;
                            $updateClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateClients[$key]['collection_date'] = date('Y-m-d');
                            $updateClients[$key]['last_update'] = date('Y-m-d', substr($value1->Modified, 6, 10));
                            $updateClients[$key]['type'] = $type;
                            //contact person detail
                            $updateClients[$key]['contact_person'] = $value1->FullName;
                            $updateClients[$key]['phone_number'] = $value1->Phone;
                            $updateClients[$key]['email'] = $value1->Email;
                            $updateClients[$key]['contactId'] = $value1->ID;
                            $updateClients[$key]['updateId'] = $updateId;
                            //update client
                            if (!empty($updateClients)) {
                                //$updateSuppliers = $this->apidetail->updateSuppliers($updateClients, 'updateId');
                                $updateSuppliers = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateClients[$key]);
                                $updateClients = array();
                            }
                        }
                    }


                    //get P&L
                    $ProfitLossOverview = new \Picqer\Financials\Exact\ProfitLossOverview($connection);
                    $pandL = $ProfitLossOverview->get();
                    $plJson = json_encode($pandL);
                    //echo "<pre>";print_r(json_encode($pandL)); die('=====');
                    if (!empty($pandL)) {
                        //$addPl = $this->apidetail->addPlData($usernumber, $plJson);
                        $getAddedPlData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->get();
                        if (count($getAddedPlData) > 0) {
                            $updateData['pl_data'] = $plJson;
                            $updatePLData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->update($updateData);
                        } else {
                            $insertData[0]['frenns_id'] = $usernumber;
                            $insertData[0]['unique_frenns_id'] = $uniqueFrennsId;
                            $insertData[0]['pl_data'] = $plJson;
                            $addPLData = DB::table('syncreport_pl')->insert($insertData);
                        }
                    }
                } catch (\Exception $e) {
                    echo get_class($e) . ' : ' . $e->getMessage();
                }
            }
            echo "All information has been stored successfully!!";
        } else {

            echo "No user available indatabase.";
        }
    }

    ############################## EXCAT API END HERE ##################################
    #
    ################################ Reeleezee start ###################################

    public function reeleezeeData() {
        //echo '<pre>';
        $accounting_system = 'reeleezee';
        if (isset($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }
        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }
        if (!empty($getdetail)) {
            foreach ($getdetail as $user) {

                $usernumber = $user->usernumber;
                $uniqueFrennsId = $user->accounting_system . "-" . $user->usernumber;
                $username = $user->UserName;
                $password = $user->Password;

                // sale invoice  
                $getLastUpdatedInvoice = DB::table('syncinvoice')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->where('type', 'Sale Invoice')->orderBy('last_updated', 'desc')->limit(1)->get();
                //print_r($getLastUpdatedInvoice); die('==');
                if (count($getLastUpdatedInvoice) > 0) {
                    $lastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                    $lastUpdatedDate = date("Y-m-d", strtotime($lastUpdatedTime));
                } else {
                    $lastUpdatedTime = '';
                    $lastUpdatedDate = '';
                }
                $idArray = array();
                $getTodayInsertedInvoice = DB::table('syncinvoice')->select('invoiceId')->where('unique_frenns_id', $uniqueFrennsId)->where('type', 'Sale Invoice')->where('last_updated', 'like', $lastUpdatedTime)->get();

                if (count($getTodayInsertedInvoice) > 0) {
                    foreach ($getTodayInsertedInvoice as $dbData) {
                        $idArray1[] = $dbData->invoiceId;
                    }
                } else {
                    $idArray1[] = array();
                }

                $invoice = 'https://portal.reeleezee.nl/api/v1/SalesInvoices';
                $result = Helper::reeleezeeAuth($invoice, $username, $password);
                //print_r($result); die;
                if (!empty($result)) {
                    $result1 = json_decode($result);
                    //echo '<pre>'; print_r($result1); die;
                    foreach ($result1->value as $key2 => $value2) {
                        $updateId = $uniqueFrennsId . '-' . $value2->id;
                        $invoiceID = $value2->id;
                        if ($value2->TotalNetAmount != 0) {
                            //echo $value2->Date . '------' . $lastUpdatedTime . '<br>';
                            if ($value2->Date >= $lastUpdatedTime || $lastUpdatedTime == '') {

                                $existId = in_array($invoiceID, $idArray1);
                                //print_r($existId);
                                if (!empty($existId)) {
                                    $updateInvoice[$key2]['invoiceId'] = $value2->id;
                                    $updateInvoice[$key2]['frenns_id'] = $usernumber;
                                    $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                    $updateInvoice[$key2]['creation_date'] = $value2->BookDate;
                                    $updateInvoice[$key2]['last_updated'] = $value2->Date;
                                    $updateInvoice[$key2]['invoice_number'] = $value2->InvoiceNumber;
                                    $updateInvoice[$key2]['amount'] = $value2->TotalNetAmount;
                                    $updateInvoice[$key2]['issue_date'] = $value2->BookDate;
                                    $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                                    $updateInvoice[$key2]['vat_amount'] = $value2->TotalTaxAmount;
                                    $updateInvoice[$key2]['outstanding_amount'] = $value2->BaseRemainingAmount;
                                    $updateInvoice[$key2]['due_date'] = $value2->DueDate;
                                    $updateInvoice[$key2]['updateId'] = $updateId;
                                } else {
                                    $addInvDetailList[$key2]['invoiceId'] = $value2->id;
                                    $addInvDetailList[$key2]['frenns_id'] = $usernumber;
                                    $addInvDetailList[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                    $addInvDetailList[$key2]['creation_date'] = $value2->BookDate;
                                    $addInvDetailList[$key2]['last_updated'] = $value2->Date;
                                    $addInvDetailList[$key2]['invoice_number'] = $value2->InvoiceNumber;
                                    $addInvDetailList[$key2]['amount'] = $value2->TotalNetAmount;
                                    $addInvDetailList[$key2]['issue_date'] = $value2->BookDate;
                                    $addInvDetailList[$key2]['collection_date'] = date('Y-m-d');
                                    $addInvDetailList[$key2]['type'] = 'Sale Invoice';
                                    $addInvDetailList[$key2]['vat_amount'] = $value2->TotalTaxAmount;
                                    $addInvDetailList[$key2]['outstanding_amount'] = $value2->BaseRemainingAmount;
                                    $addInvDetailList[$key2]['due_date'] = $value2->DueDate;
                                    $addInvDetailList[$key2]['updateId'] = $updateId;
                                }
                            } else if ($value2->Date < $lastUpdatedTime) {
                                $updateInvoice[$key2]['invoiceId'] = $value2->id;
                                $updateInvoice[$key2]['frenns_id'] = $usernumber;
                                $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvoice[$key2]['creation_date'] = $value2->BookDate;
                                $updateInvoice[$key2]['last_updated'] = $value2->Date;
                                $updateInvoice[$key2]['invoice_number'] = $value2->InvoiceNumber;
                                $updateInvoice[$key2]['amount'] = $value2->TotalNetAmount;
                                $updateInvoice[$key2]['issue_date'] = $value2->BookDate;
                                $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                                $updateInvoice[$key2]['vat_amount'] = $value2->TotalTaxAmount;
                                $updateInvoice[$key2]['outstanding_amount'] = $value2->BaseRemainingAmount;
                                $updateInvoice[$key2]['due_date'] = $value2->DueDate;
                                $updateInvoice[$key2]['updateId'] = $updateId;
                            }

                            #Save invoice
                            if (!empty($addInvDetailList)) {
                                //echo 'addd';
                                //// echo "<pre>"; print_r($addInvDetailList[$key2]);
                                //echo 'invvvvvoice';
                                DB::table('syncinvoice')->insert($addInvDetailList[$key2]);
                                $addInvDetailList = '';
                            }
                            #update invoice
                            if (!empty($updateInvoice)) {
                                //echo 'uppp';
                                //echo "<pre>"; print_r($updateInvoice[$key2]);
                                //echo 'invvvvvoice';
                                $updateInvoice = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateInvoice[$key2]);
                                $updateInvoice = '';
                            }

                            $invoiceLines = 'https://portal.reeleezee.nl/api/v1/SalesInvoices/' . $value2->id . '/Lines';
                            $result2 = Helper::reeleezeeAuth($invoiceLines, $username, $password);
                            //print_r($result2);
                            if (!empty($result2)) {
                                $result3 = json_decode($result2);
                                //                            print_r($result3);
                                //                            die;
                                foreach ($result3->value as $key => $invoiceLine) {
                                    $invoiceItemUpdateId = $updateId . '-' . $invoiceLine->id;
                                    $invoiceItemData[$key]['frenns_id'] = $usernumber;
                                    $invoiceItemData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                    $invoiceItemData[$key]['invoice_number'] = isset($value2->InvoiceNumber) ? $value2->InvoiceNumber : '';
                                    $invoiceItemData[$key]['line_number'] = isset($invoiceLine->Sequence) ? $invoiceLine->Sequence : '';
                                    //$invoiceItemData[$key]['product_code'] = isset($invoiceLine['ItemCode']) ? $invoiceLine['ItemCode'] : '';
                                    $invoiceItemData[$key]['description'] = isset($invoiceLine->Description) ? $invoiceLine->Description : '';
                                    $invoiceItemData[$key]['qty'] = isset($invoiceLine->Quantity) ? $invoiceLine->Quantity : '';
                                    $invoiceItemData[$key]['rate'] = isset($invoiceLine->Price) ? $invoiceLine->Price : '';
                                    $invoiceItemData[$key]['amount_net'] = isset($invoiceLine->NetAmount) ? $invoiceLine->NetAmount : '';
                                    $invoiceItemData[$key]['invoiceline_vat_amount'] = isset($invoiceLine->TaxAmount) ? $invoiceLine->TaxAmount : '';
                                    $invoiceItemData[$key]['amount_total'] = isset($invoiceLine->LineTotalPayableAmount) ? $invoiceLine->LineTotalPayableAmount : '';
                                    $invoiceItemData[$key]['invoice_id'] = $value2->id;
                                    $invoiceItemData[$key]['updateId'] = $invoiceItemUpdateId;

                                    #Save Invoices items
                                    if (!empty($invoiceItemData)) {
                                        DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                        DB::table('syncinvoice_item')->insert($invoiceItemData[$key]);
                                        $invoiceItemData = '';
                                    }
                                }
                            }
                        }
                    }
                }

                // purchase invoice

                $getLastUpdatedPInvoice = DB::table('syncinvoice')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->where('type', 'Purchase Invoice')->orderBy('last_updated', 'desc')->limit(1)->get();
                //print_r($getLastUpdatedInvoice); die('==');
                if (count($getLastUpdatedPInvoice) > 0) {
                    $lastUpdatedTime1 = $getLastUpdatedPInvoice[0]->last_updated;
                    $lastUpdatedDate1 = date("Y-m-d", strtotime($lastUpdatedTime));
                } else {
                    $lastUpdatedTime1 = '';
                    $lastUpdatedDate1 = '';
                }
                $idArray2 = array();
                $getTodayInsertedPInvoice = DB::table('syncinvoice')->select('invoiceId')->where('unique_frenns_id', $uniqueFrennsId)->where('type', 'Purchase Invoice')->where('last_updated', 'like', $lastUpdatedTime1)->get();

                if (count($getTodayInsertedPInvoice) > 0) {
                    foreach ($getTodayInsertedPInvoice as $dbData) {
                        $idArray2[] = $dbData->invoiceId;
                    }
                } else {
                    $idArray2[] = array();
                }

                $pinvoice = 'https://portal.reeleezee.nl/api/v1/PurchaseInvoices';
                $result11 = Helper::reeleezeeAuth($pinvoice, $username, $password);
                //print_r($result); die;
                if (!empty($result11)) {
                    $result12 = json_decode($result11);
                    //print_r($result12); die;
                    foreach ($result12->value as $key2 => $value2) {
                        $updateId = $uniqueFrennsId . '-' . $value2->id;
                        $invoiceID = $value2->id;
                        if ($value2->BaseInvoiceAmount != 0) {
                            //echo $value2->Date . '------' . $lastUpdatedTime . '<br>';
                            if ($value2->Date >= $lastUpdatedTime || $lastUpdatedTime == '') {

                                $existId = in_array($invoiceID, $idArray2);
                                //print_r($existId);
                                if (!empty($existId)) {
                                    $updateInvoice[$key2]['invoiceId'] = $value2->id;
                                    $updateInvoice[$key2]['frenns_id'] = $usernumber;
                                    $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                    $updateInvoice[$key2]['creation_date'] = $value2->Date;
                                    $updateInvoice[$key2]['last_updated'] = $value2->Date;
                                    $updateInvoice[$key2]['invoice_number'] = $value2->ReceiptNumber;
                                    $updateInvoice[$key2]['amount'] = $value2->BaseInvoiceAmount;
                                    $updateInvoice[$key2]['issue_date'] = $value2->Date;
                                    $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                                    //$updateInvoice[$key2]['vat_amount'] = $value2->TotalTaxAmount;
                                    $updateInvoice[$key2]['outstanding_amount'] = $value2->BaseRemainingAmount;
                                    $updateInvoice[$key2]['due_date'] = $value2->DueDate;
                                    $updateInvoice[$key2]['updateId'] = $updateId;
                                } else {
                                    $addInvDetailList[$key2]['invoiceId'] = $value2->id;
                                    $addInvDetailList[$key2]['frenns_id'] = $usernumber;
                                    $addInvDetailList[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                    $addInvDetailList[$key2]['creation_date'] = $value2->Date;
                                    $addInvDetailList[$key2]['last_updated'] = $value2->Date;
                                    $addInvDetailList[$key2]['invoice_number'] = isset($value2->ReceiptNumber) ? $value2->ReceiptNumber : '';
                                    $addInvDetailList[$key2]['amount'] = isset($value2->BaseInvoiceAmount) ? $value2->BaseInvoiceAmount : '';
                                    $addInvDetailList[$key2]['issue_date'] = $value2->Date;
                                    $addInvDetailList[$key2]['collection_date'] = date('Y-m-d');
                                    $addInvDetailList[$key2]['type'] = 'Purchase Invoice';
                                    //$addInvDetailList[$key2]['vat_amount'] = isset($value2->TotalTaxAmount)?$value2->TotalTaxAmount:'';
                                    $addInvDetailList[$key2]['outstanding_amount'] = isset($value2->BaseRemainingAmount) ? $value2->BaseRemainingAmount : '';
                                    $addInvDetailList[$key2]['due_date'] = isset($value2->DueDate) ? $value2->DueDate : '';
                                    $addInvDetailList[$key2]['updateId'] = $updateId;
                                }
                            } else if ($value2->Date < $lastUpdatedTime) {
                                $updateInvoice[$key2]['invoiceId'] = $value2->id;
                                $updateInvoice[$key2]['frenns_id'] = $usernumber;
                                $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvoice[$key2]['creation_date'] = $value2->Date;
                                $updateInvoice[$key2]['last_updated'] = $value2->Date;
                                $updateInvoice[$key2]['invoice_number'] = $value2->ReceiptNumber;
                                $updateInvoice[$key2]['amount'] = $value2->BaseInvoiceAmount;
                                $updateInvoice[$key2]['issue_date'] = $value2->Date;
                                $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                                //$updateInvoice[$key2]['vat_amount'] = $value2->TotalTaxAmount;
                                $updateInvoice[$key2]['outstanding_amount'] = $value2->BaseRemainingAmount;
                                $updateInvoice[$key2]['due_date'] = $value2->DueDate;
                                $updateInvoice[$key2]['updateId'] = $updateId;
                            }

                            #Save invoice
                            if (!empty($addInvDetailList)) {
                                //echo 'addd';
                                //// echo "<pre>"; print_r($addInvDetailList[$key2]);
                                //echo 'invvvvvoice';
                                DB::table('syncinvoice')->insert($addInvDetailList[$key2]);
                                $addInvDetailList = '';
                            }
                            #update invoice
                            if (!empty($updateInvoice)) {
                                //echo 'uppp';
                                //echo "<pre>"; print_r($updateInvoice[$key2]);
                                //echo 'invvvvvoice';
                                $updateInvoice = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateInvoice[$key2]);
                                $updateInvoice = '';
                            }

                            $pinvoiceLines = 'https://portal.reeleezee.nl/api/v1/PurchaseInvoices/' . $value2->id . '/Lines';
                            $result12 = Helper::reeleezeeAuth($pinvoiceLines, $username, $password);
                            //print_r($result12);
                            if (!empty($result12)) {
                                $result14 = json_decode($result12);
                                foreach ($result14->value as $key => $invoiceLine) {
                                    $invoiceItemUpdateId = $updateId . '-' . $invoiceLine->id;
                                    $invoiceItemData[$key]['frenns_id'] = $usernumber;
                                    $invoiceItemData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                    $invoiceItemData[$key]['invoice_number'] = isset($value2->InvoiceNumber) ? $value2->InvoiceNumber : '';
                                    $invoiceItemData[$key]['line_number'] = isset($invoiceLine->Sequence) ? $invoiceLine->Sequence : '';
                                    //$invoiceItemData[$key]['product_code'] = isset($invoiceLine['ItemCode']) ? $invoiceLine['ItemCode'] : '';
                                    $invoiceItemData[$key]['description'] = isset($invoiceLine->Description) ? $invoiceLine->Description : '';
                                    $invoiceItemData[$key]['qty'] = isset($invoiceLine->Quantity) ? $invoiceLine->Quantity : '';
                                    $invoiceItemData[$key]['rate'] = isset($invoiceLine->Price) ? $invoiceLine->Price : '';
                                    $invoiceItemData[$key]['amount_net'] = isset($invoiceLine->NetAmount) ? $invoiceLine->NetAmount : '';
                                    $invoiceItemData[$key]['invoiceline_vat_amount'] = isset($invoiceLine->TaxAmount) ? $invoiceLine->TaxAmount : '';
                                    $invoiceItemData[$key]['amount_total'] = isset($invoiceLine->LineTotalPayableAmount) ? $invoiceLine->LineTotalPayableAmount : '';
                                    $invoiceItemData[$key]['invoice_id'] = $value2->id;
                                    $invoiceItemData[$key]['updateId'] = $invoiceItemUpdateId;

                                    #Save Invoices items
                                    if (!empty($invoiceItemData)) {
                                        DB::table('syncinvoice_item')->where('updateId', $invoiceItemUpdateId)->delete();
                                        //echo "<pre>";
                                        // print_r($invoiceItemData[$key]);
                                        //echo 'itttttttemmmmmmm';
                                        DB::table('syncinvoice_item')->insert($invoiceItemData[$key]);
                                        $invoiceItemData = '';
                                    }
                                }
                            }
                        }
                    }
                }

                // get customer/suppliers
                $localContacts = DB::table('syncsupplier')->select('contactId')->where('unique_frenns_id', $uniqueFrennsId)->where('type', 'Customer')->get();
                $localContactsArr = array();
                if (!empty($localContacts)) {
                    foreach ($localContacts as $key => $localContact) {
                        $localContactsArr[] = $localContact->contactId;
                    }
                } else {
                    $localContactsArr = array();
                }
                //print_r($localContactsArr);

                $contact = 'https://portal.reeleezee.nl/api/v1/Customers';
                $result4 = Helper::reeleezeeAuth($contact, $username, $password);
                $customerArray = json_decode($result4);
                //print_r(json_decode($result4)); die;
                foreach ($customerArray->value as $key => $value1) {
                    if ($value1->SearchName != '') {
                        $updateId = $uniqueFrennsId . '-' . $value1->id;
                        $existId1 = in_array($value1->id, $localContactsArr);
                        //echo $existId1.'nneee';
                        if (empty($existId1)) {

                            $addClients[$key]['frenns_id'] = $usernumber;
                            $addClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $addClients[$key]['name'] = $value1->Name;
                            $addClients[$key]['type'] = 'Customer';

                            // customer address
                            $contactAddress = 'https://portal.reeleezee.nl/api/v1/Customers/' . $value1->id . '/Addresses';
                            $result5 = Helper::reeleezeeAuth($contactAddress, $username, $password);
                            $contactAddressArray = json_decode($result5);
                            //print_r($contactAddressArray->value[0]->FullAddress); die;
                            if (!empty($contactAddressArray->value[0]->FullAddress)) {
                                $addClients[$key]['address'] = isset($contactAddressArray->value[0]->Street) ? $contactAddressArray->value[0]->Street : '' . ' ' . isset($contactAddressArray->value[0]->Number) ? $contactAddressArray->value[0]->Number : '';
                                $addClients[$key]['city'] = isset($contactAddressArray->value[0]->City) ? $contactAddressArray->value[0]->City : '';
                                $addClients[$key]['postcode'] = isset($contactAddressArray->value[0]->Postcode) ? $contactAddressArray->value[0]->Postcode : '';
                            } else {
                                $addClients[$key]['address'] = '';
                                $addClients[$key]['city'] = '';
                                $addClients[$key]['postcode'] = '';
                            }
                            // customer persons
                            $contactPerson = 'https://portal.reeleezee.nl/api/v1/Customers/' . $value1->id . '/ContactPersons';
                            $result6 = Helper::reeleezeeAuth($contactPerson, $username, $password);
                            $contactPersonArray = json_decode($result6);
                            // print_r($contactPersonArray); die;
                            if (!empty($contactPersonArray->value[0]->Name)) {
                                $addClients[$key]['contact_person'] = $contactPersonArray->value[0]->Name;
                            }
                            $addClients[$key]['collection_date'] = date('Y-m-d');
                            $addClients[$key]['contactId'] = $value1->id;
                            $addClients[$key]['updateId'] = $updateId;

                            // add client
                            if (!empty($addClients)) {
                                $addSuppliers = DB::table('syncsupplier')->insert($addClients[$key]);
                                $addClients = array();
                            }
                        } else {
                            $updateClients[$key]['frenns_id'] = $usernumber;
                            $updateClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateClients[$key]['name'] = $value1->Name;
                            // customer address
                            $contactAddress = 'https://portal.reeleezee.nl/api/v1/Customers/' . $value1->id . '/Addresses';
                            $result5 = Helper::reeleezeeAuth($contactAddress, $username, $password);
                            $contactAddressArray = json_decode($result5);
                            // print_r($contactAddressArray); //die;
                            if (!empty($contactAddressArray->value[0]->FullAddress)) {
                                $updateClients[$key]['address'] = $contactAddressArray->value[0]->Street . ' ' . $contactAddressArray->value[0]->Number;
                                $updateClients[$key]['city'] = $contactAddressArray->value[0]->City;
                                $updateClients[$key]['postcode'] = $contactAddressArray->value[0]->Postcode;
                            } else {
                                $updateClients[$key]['address'] = '';
                                $updateClients[$key]['city'] = '';
                                $updateClients[$key]['postcode'] = '';
                            }
                            // customer persons
                            $contactPerson = 'https://portal.reeleezee.nl/api/v1/Customers/' . $value1->id . '/ContactPersons';
                            $result6 = Helper::reeleezeeAuth($contactPerson, $username, $password);
                            $contactPersonArray = json_decode($result6);
                            if (!empty($contactPersonArray->value[0]->Name)) {
                                $updateClients[$key]['contact_person'] = $contactPersonArray->value[0]->Name;
                            }
                            $updateClients[$key]['collection_date'] = date('Y-m-d');
                            $updateClients[$key]['contactId'] = $value1->id;
                            $updateClients[$key]['updateId'] = $updateId;
                        }
                        if (!empty($updateClients)) {
                            $updateSuppliers = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateClients[$key]);
                            $updateClients = array();
                        }
                    }
                }

                // get suppliers
                $localVendor = DB::table('syncsupplier')->select('contactId')->where('frenns_id', $usernumber)->where('type', 'Supplier')->get();
                $vendorArr = array();
                if (!empty($localVendor)) {
                    foreach ($localVendor as $key => $localContact) {
                        $vendorArr[] = $localContact->contactId;
                    }
                } else {
                    $vendorArr = array();
                }
                //print_r($localContactsArr);

                $vendor = 'https://portal.reeleezee.nl/api/v1/Vendors';
                $result7 = Helper::reeleezeeAuth($vendor, $username, $password);
                $vendorArray = json_decode($result7);
                //print_r(json_decode($result7)); die;
                foreach ($vendorArray->value as $key => $value1) {
                    if ($value1->SearchName != '') {
                        $updateId = $uniqueFrennsId . '-' . $value1->id;
                        $existId2 = in_array($value1->id, $vendorArr);
                        //echo $existId1.'nneee';
                        if (empty($existId2)) {

                            $addClients[$key]['frenns_id'] = $usernumber;
                            $addClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $addClients[$key]['name'] = $value1->Name;
                            $addClients[$key]['type'] = 'Supplier';

                            // vendor  address
                            $venderAddress = 'https://portal.reeleezee.nl/api/v1/Vendors/' . $value1->id . '/Addresses';
                            $result8 = Helper::reeleezeeAuth($venderAddress, $username, $password);
                            $vendorAddressArray = json_decode($result8);
                            //print_r($vendorAddressArray); die;
                            if (!empty($vendorAddressArray->value[0]->FullAddress)) {
                                $addClients[$key]['address'] = isset($vendorAddressArray->value[0]->Street) ? $vendorAddressArray->value[0]->Street : '' . ' ' . isset($vendorAddressArray->value[0]->Number) ? $vendorAddressArray->value[0]->Number : '' . ' ' . isset($vendorAddressArray->value[0]->NumberExtension) ? $vendorAddressArray->value[0]->NumberExtension : '';
                                $addClients[$key]['city'] = isset($vendorAddressArray->value[0]->City) ? $vendorAddressArray->value[0]->City : '';
                                $addClients[$key]['postcode'] = isset($vendorAddressArray->value[0]->Postcode) ? $vendorAddressArray->value[0]->Postcode : '';
                            } else {
                                $addClients[$key]['address'] = '';
                                $addClients[$key]['city'] = '';
                                $addClients[$key]['postcode'] = '';
                            }
                            // vendor persons
                            $vendorPerson = 'https://portal.reeleezee.nl/api/v1/Vendors/' . $value1->id . '/ContactPersons';
                            $result9 = Helper::reeleezeeAuth($vendorPerson, $username, $password);
                            $vendorPersonArray = json_decode($result9);
                            if (!empty($vendorPersonArray->value[0]->Name)) {
                                $addClients[$key]['contact_person'] = $vendorPersonArray->value[0]->Name;
                            }
                            $addClients[$key]['collection_date'] = date('Y-m-d');
                            $addClients[$key]['contactId'] = $value1->id;
                            $addClients[$key]['updateId'] = $updateId;

                            // add client
                            if (!empty($addClients)) {
                                $addSuppliers = DB::table('syncsupplier')->insert($addClients[$key]);
                                $addClients = array();
                            }
                        } else {
                            $updateClients[$key]['frenns_id'] = $usernumber;
                            $updateClients[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateClients[$key]['name'] = $value1->Name;

                            // vendor  address
                            $venderAddress = 'https://portal.reeleezee.nl/api/v1/Vendors/' . $value1->id . '/Addresses';
                            $result8 = Helper::reeleezeeAuth($venderAddress, $username, $password);
                            $vendorAddressArray = json_decode($result8);
                            //print_r($vendorAddressArray); die;
                            if (!empty($vendorAddressArray->value[0]->FullAddress)) {
                                $updateClients[$key]['address'] = isset($vendorAddressArray->value[0]->Street) ? $vendorAddressArray->value[0]->Street : '' . ' ' . isset($vendorAddressArray->value[0]->Number) ? $vendorAddressArray->value[0]->Number : '' . ' ' . isset($vendorAddressArray->value[0]->NumberExtension) ? $vendorAddressArray->value[0]->NumberExtension : '';
                                $updateClients[$key]['city'] = isset($vendorAddressArray->value[0]->City) ? $vendorAddressArray->value[0]->City : '';
                                $updateClients[$key]['postcode'] = isset($vendorAddressArray->value[0]->Postcode) ? $vendorAddressArray->value[0]->Postcode : '';
                            } else {
                                $updateClients[$key]['address'] = '';
                                $updateClients[$key]['city'] = '';
                                $updateClients[$key]['postcode'] = '';
                            }
                            // vendor persons
                            $vendorPerson = 'https://portal.reeleezee.nl/api/v1/Vendors/' . $value1->id . '/ContactPersons';
                            $result9 = Helper::reeleezeeAuth($vendorPerson, $username, $password);
                            $vendorPersonArray = json_decode($result9);
                            if (!empty($vendorPersonArray->value[0]->Name)) {
                                $updateClients[$key]['contact_person'] = $vendorPersonArray->value[0]->Name;
                            }
                            $updateClients[$key]['collection_date'] = date('Y-m-d');
                            if (!empty($updateClients)) {
                                //echo 'up';
                                $updateSuppliers = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateClients[$key]);
                                $updateClients = array();
                            }
                        }
                    }
                }

                // p&l 
                $pl = 'https://portal.reeleezee.nl/api/v1/Financials/ProfitAndLosses';
                $result15 = Helper::reeleezeeAuth($pl, $username, $password);

                if (!empty($result15)) {
                    $getAddedPlData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    //print_r($getAddedPlData);   die;
                    if (count($getAddedPlData) > 0) {
                        $updateData['pl_data'] = $result15;
                        $updatePLData = DB::table('syncreport_pl')->where('unique_frenns_id', $uniqueFrennsId)->update($updateData);
                    } else {
                        $insertData[0]['frenns_id'] = $usernumber;
                        $insertData[0]['unique_frenns_id'] = $uniqueFrennsId;
                        $insertData[0]['pl_data'] = $result15;
                        $addPLData = DB::table('syncreport_pl')->insert($insertData);
                    }
                }

                //$pl = 'https://portal.reeleezee.nl/api/v1/ReeleezeeSubscriptions/Invoices/{id}/Download';
                //$result15 = Helper::reeleezeeAuth($pl, $username, $password);
                ////$vendorArray = json_decode($result7);
                //print_r(json_decode($result15));
            }
            echo "All information has been stored successfully!!";
        } else {
            echo "No user available in database.";
        }
    }

    ################################ Reeleezee end ###################################
    ################################ FreeAgent start ###################################

    function freeagentGetTokens() {

        $sandbox = true;

        require(app_path('Services/OAuth2/') . 'Client.php');
        require(app_path('Services/OAuth2/') . 'GrantType/IGrantType.php');
        require(app_path('Services/OAuth2/') . 'GrantType/AuthorizationCode.php');

        $clinetId = '2yW_dZPcpT6jKYtuRuW62A';
        $clientSecret = 'cHepT64B-AoGsmiR_oP9nA';

        $redirectUri = 'http://localhost/tpa/freeagentGetTokens';
        $authorizationEndpoint = 'https://api.freeagent.com/v2/approve_app';
        $tokenEndpoint = 'https://api.freeagent.com/v2/token_endpoint';
        $apiUrl = 'https://api.freeagent.com/v2';

        if ($sandbox == true) {
            //$authorizationEndpoint = preg_replace('/api/', 'api', $authorizationEndpoint);
            //$tokenEndpoint = preg_replace('/api/', 'api', $tokenEndpoint);
            //$apiUrl = preg_replace('/api/', 'api', $apiUrl);
        }

        //$client = Helper::freeAgentOauth($clinetId, $clientSecret);
        $client = new OAuth2\Client($clinetId, $clientSecret);
        //echo "<prE>"; print_r($client); echo "<pre>";
        //get code
        $code = 'https://api.freeagent.com/v2/approve_app?response_type=code&client_id=2yW_dZPcpT6jKYtuRuW62A&redirect_uri=http%3A%2F%2Flocalhost%2Ftpa%2FfreeagentGetTokens';

        if (!isset($_GET['code']) && !isset($_GET['token'])) {
            $auth_url = $client->getAuthenticationUrl($authorizationEndpoint, $redirectUri);
            //die;
            header('Location: ' . $auth_url);
            die('Redirect');
        } elseif (isset($_GET['code'])) {
            $params = array('code' => $_GET['code'], 'redirect_uri' => $redirectUri);
            $response = $client->getAccessToken($tokenEndpoint, 'authorization_code', $params);
            // echo "<pre>";
            // print_r($response);
            // echo "<pre>";
            $res['access_token'] = $response['result']['access_token'];
            $res['refresh_token'] = $response['result']['refresh_token'];
            $res['token_type'] = $response['result']['token_type'];
            $res['expires_in'] = $response['result']['expires_in'];

            return view('freeagentTokens', ['response' => $res]);

            die('Your tokens are here.');
            $access = $response['result']['access_token'];
            // get access token from refresh token            
            $refresh_token = $response['result']['refresh_token'];

            //$params1 = array('refresh_token' => $refresh_token);
            //$response1 = $client->getAccessToken($tokenEndpoint, 'refresh_token', $params1);
            //echo "<pre>";
            //print_r($response1);
            // $client->setAccessToken($access);
            // $contacts = $client->fetch($apiUrl . '/contacts', array(), 'GET', array('Authorization' => 'Bearer ' . $access, 'User-Agent' => 'App name'));
            // echo "<pre>";
            // print_r($contacts);
        }
    }

    function freeagentData() {

        $accounting_system = 'freeagent';
        if (isset($_REQUEST['usernumber'])) {
            $userNumber = $_REQUEST['usernumber'];
        } else {
            $userNumber = '';
        }

        $detail = $getdetail = DB::table('appDetail')->where('accounting_system', $accounting_system)->get();
        //print_r($detail); die;
        $clinetId = $detail[0]->clientId;
        $clientSecret = $detail[0]->clientSecret;
        $redirectUri = $detail[0]->callbackUrl;

        if ($userNumber == '') {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->get();
        } else {
            $getdetail = DB::table('synccredential')->where('accounting_system', $accounting_system)->where('usernumber', $userNumber)->get();
        }
        echo "<pre>";
        if (!empty($getdetail)) {
            $response = array();
            $addSupplierDetailList = array();
            $updateSupplierDetailList = array();

            foreach ($getdetail as $user) {

                $usernumber = $user->usernumber;
                $code = $user->code;
                $access_token_db = $user->access_token;
                $refresh_token_db = $user->refresh_token;
                $uniqueId = $user->usernumber;
                $uniqueFrennsId = $user->accounting_system . "-" . $user->usernumber;

                $authorizationEndpoint = 'https://api.freeagent.com/v2/approve_app';
                $tokenEndpoint = 'https://api.freeagent.com/v2/token_endpoint';
                $apiUrl = 'https://api.freeagent.com/v2';

                $client = new OAuth2\Client($clinetId, $clientSecret);
                //print_r($client); 
                //Get New Access Token Using Refresh Token
                $paramsRefresh = array('refresh_token' => $refresh_token_db);
                $responseRefresh = $client->getAccessToken($tokenEndpoint, 'refresh_token', $paramsRefresh);
                //Set New Access Token                
                $access_token_new = $responseRefresh['result']['access_token'];
                $client->setAccessToken($access_token_new);
                //Update New Access Token Into Database
                $updateToken['access_token'] = $access_token_new;
                $updateTokenResult = DB::table('synccredential')->where('usernumber', $uniqueId)->update($updateToken);

                //get P&L

                $pl = $client->fetch($apiUrl . '/accounting/profit_and_loss/summary', array(), 'GET', array('Authorization' => 'Bearer ' . $access_token_new, 'User-Agent' => 'App name'));
                //echo "<pre>";  print_r($pl); print_r(json_encode($pl['result']['profit_and_loss_summary'])); echo "</pre>"; //die;
                if ($pl['code'] == 200) {
                    $getdetail = DB::table('syncreport_pl')->select('syncreport_pl.frenns_id')->where('unique_frenns_id', $uniqueFrennsId)->get();
                    if (count($getdetail) > 0) {
                        $plData['pl_data'] = json_encode($pl['result']['profit_and_loss_summary']);
                        $updatePLData = DB::table('syncreport_pl')->where('frenns_id', $uniqueId)->update($plData);
                    } else {
                        $plData['frenns_id'] = $uniqueId;
                        $plData['unique_frenns_id'] = $uniqueFrennsId;
                        $plData['pl_data'] = json_encode($pl['result']['profit_and_loss_summary']);
                        $insertPLData = DB::table('syncreport_pl')->insert($plData);
                    }
                } else {
                    ## oauth_problem ## token_expired ##                      
                    echo "Token Expire for user " . $uniqueId . ".Please contact to administrator.";
                    exit;
                }


                // max time for

                $getLastUpdatedContact = DB::table('syncsupplier')->select('last_update')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_update', 'desc')->limit(1)->get();
                if (count($getLastUpdatedContact) > 0) {
                    $ContactlastUpdatedTime = $getLastUpdatedContact[0]->last_update;
                } else {
                    $ContactlastUpdatedTime = '';
                }

                $idArray = array();
                $getTodayInsertedContact = DB::table('syncsupplier')->select('updateId')->where('unique_frenns_id', $uniqueFrennsId)->where('last_update', 'like', '%' . $ContactlastUpdatedTime . '%')->get();
                //print_r($getTodayInsertedContact); die;
                if (!empty($getTodayInsertedContact)) {
                    foreach ($getTodayInsertedContact as $dbData) {
                        $exp = explode("-", $dbData->updateId);
                        $idArray[] = $exp[2];
                    }
                } else {
                    $idArray[] = array();
                }

                //get customers  

                $contacts = $client->fetch($apiUrl . '/contacts', array(), 'GET', array('Authorization' => 'Bearer ' . $access_token_new, 'User-Agent' => 'App name'));
                //echo "<pre>"; print_r($contacts); die('Freeagent Contacts 4997');
                if (!empty($contacts)) {
                    foreach ($contacts['result']['contacts'] as $key => $value1) {
                        $value1 = (object) $value1;
                        $created_at = $value1->created_at;
                        $updated_at = $value1->updated_at;
                        $contactId = substr($value1->url, strrpos($value1->url, '/') + 1);
                        $updateId = $uniqueFrennsId . '-' . $contactId;

                        if ($created_at >= $ContactlastUpdatedTime || empty($ContactlastUpdatedTime)) {
                            $existId = in_array($contactId, $idArray);
                            if (!empty($existId)) {
                                $updateContacts[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateContacts[$key]['company_name'] = isset($value1->organisation_name) ? $value1->organisation_name : '';
                                $updateContacts[$key]['collection_date'] = date('Y-m-d');
                                $updateContacts[$key]['last_update'] = $value1->updated_at;
                                //$addContacts['contact'][$key]['type'] = $contactType;
                                $updateContacts[$key]['vat_registration'] = isset($value1->sales_tax_registration_number) ? $value1->sales_tax_registration_number : '';
                                $updateContacts[$key]['address'] = isset($value1->address1) ? $value1->address1 : '';
                                $updateContacts[$key]['postcode'] = isset($value1->postcode) ? $value1->postcode : '';
                                $updateContacts[$key]['city'] = isset($value1->town) ? $value1->town : '';
                                $updateContacts[$key]['country'] = isset($value1->country) ? $value1->country : '';
                                // contact person detail
                                $updateContacts[$key]['contact_person'] = isset($value1->first_name) ? $value1->first_name : '' . ' ' . isset($value1->last_name) ? $value1->last_name : '';
                                $updateContacts[$key]['phone_number'] = isset($value1->mobile) ? $value1->mobile : '';
                                $updateContacts[$key]['email'] = isset($value1->email) ? $value1->email : '';
                                $updateContacts[$key]['contactId'] = $contactId;
                                $updateContacts[$key]['updateId'] = $updateId;
                            } else {
                                $addContacts[$key]['frenns_id'] = $uniqueId;
                                $addContacts[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $addContacts[$key]['company_name'] = isset($value1->organisation_name) ? $value1->organisation_name : '';

                                $addContacts[$key]['collection_date'] = date('Y-m-d');
                                $addContacts[$key]['last_update'] = $value1->updated_at;
                                //$addContacts['contact'][$key]['type'] = $contactType;
                                $addContacts[$key]['vat_registration'] = isset($value1->sales_tax_registration_number) ? $value1->sales_tax_registration_number : '';
                                $addContacts[$key]['address'] = isset($value1->address1) ? $value1->address1 : '';
                                $addContacts[$key]['postcode'] = isset($value1->postcode) ? $value1->postcode : '';
                                $addContacts[$key]['city'] = isset($value1->town) ? $value1->town : '';
                                $addContacts[$key]['country'] = isset($value1->country) ? $value1->country : '';

                                // contact person detail
                                $addContacts[$key]['contact_person'] = isset($value1->first_name) ? $value1->first_name : '' . ' ' . isset($value1->last_name) ? $value1->last_name : '';
                                $addContacts[$key]['phone_number'] = isset($value1->mobile) ? $value1->mobile : '';
                                $addContacts[$key]['email'] = isset($value1->email) ? $value1->email : '';
                                $addContacts[$key]['contactId'] = $contactId;
                                $addContacts[$key]['updateId'] = $updateId;
                                if (!empty($addContacts)) {
                                    $addContacts = DB::table('syncsupplier')->insert($addContacts[$key]);
                                    $addContacts = array();
                                }
                            }
                        } else if ($updated_at > $ContactlastUpdatedTime) {
                            $updateContacts[$key]['frenns_id'] = $uniqueId;
                            $updateContacts[$key]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateContacts[$key]['company_name'] = isset($value1->organisation_name) ? $value1->organisation_name : '';

                            $updateContacts[$key]['collection_date'] = date('Y-m-d');
                            $updateContacts[$key]['last_update'] = $value1->updated_at;
                            //$addContacts['contact'][$key]['type'] = $contactType;

                            $updateContacts[$key]['vat_registration'] = isset($value1->sales_tax_registration_number) ? $value1->sales_tax_registration_number : '';

                            $updateContacts[$key]['address'] = isset($value1->address1) ? $value1->address1 : '';
                            $updateContacts[$key]['postcode'] = isset($value1->postcode) ? $value1->postcode : '';
                            $updateContacts[$key]['city'] = isset($value1->town) ? $value1->town : '';
                            $updateContacts[$key]['country'] = isset($value1->country) ? $value1->country : '';

                            // contact person detail
                            $updateContacts[$key]['contact_person'] = isset($value1->first_name) ? $value1->first_name : '' . ' ' . isset($value1->last_name) ? $value1->last_name : '';
                            $updateContacts[$key]['phone_number'] = isset($value1->mobile) ? $value1->mobile : '';
                            $updateContacts[$key]['email'] = isset($value1->email) ? $value1->email : '';
                            $updateContacts[$key]['contactId'] = $contactId;
                            $updateContacts[$key]['updateId'] = $updateId;
                        }
                        if (!empty($updateContacts)) {
                            $updateSuppliers = DB::table('syncsupplier')->where('updateId', $updateId)->update($updateContacts[$key]);
                            $updateContacts = array();
                        }
                    }
                }

                //invoice 
                // max time for invoice
                $getLastUpdatedInvoice = DB::table('syncinvoice')->select('last_updated')->where('unique_frenns_id', $uniqueFrennsId)->orderBy('last_updated', 'desc')->limit(1)->get();
                if (count($getLastUpdatedInvoice) > 0) {
                    $invoicelastUpdatedTime = $getLastUpdatedInvoice[0]->last_updated;
                } else {
                    $invoicelastUpdatedTime = '';
                }

                $idArray1 = array();
                $getTodayInsertedInvoice = DB::table('syncinvoice')->select('updateId')->where('unique_frenns_id', $uniqueFrennsId)->where('last_updated', 'like', '%' . $invoicelastUpdatedTime . '%')->get();
                //print_r($getTodayInsertedContact); die;
                if (!empty($getTodayInsertedInvoice)) {
                    foreach ($getTodayInsertedInvoice as $dbData) {
                        $exp = explode("-", $dbData->updateId);
                        $idArray1[] = $exp[2];
                    }
                } else {
                    $idArray1[] = array();
                }

                $invoices = $client->fetch($apiUrl . '/invoices?nested_invoice_items=true', array(), 'GET', array('Authorization' => 'Bearer ' . $access_token_new, 'User-Agent' => 'App name'));
                //echo "<prE>";print_r($invoices);die('FreeAgent Invoices 5102');            

                if (!empty($invoices)) {
                    foreach ($invoices['result']['invoices'] as $key2 => $value2) {
                        $value2 = (object) $value2;
                        $created_at1 = $value2->created_at;
                        $updated_at1 = $value2->updated_at;
                        $invoiceId = substr($value2->url, strrpos($value2->url, '/') + 1);
                        $contactId = substr($value2->contact, strrpos($value2->contact, '/') + 1);
                        $updateId = $uniqueFrennsId . '-' . $invoiceId;
                        if ($value2->status == 'Paid') {
                            $paid = 'true';
                            $paid_on = $value2->paid_on;
                        } else {
                            $paid = 'false';
                            $paid_on = '';
                        }

                        if ($created_at1 >= $invoicelastUpdatedTime || empty($invoicelastUpdatedTime)) {
                            $existId = in_array($invoiceId, $idArray1);
                            if (!empty($existId)) {
                                $updateInvoice[$key2]['frenns_id'] = $uniqueId;
                                $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $updateInvoice[$key2]['creation_date'] = $created_at1;
                                $updateInvoice[$key2]['last_updated'] = $updated_at1;
                                $updateInvoice[$key2]['invoice_number'] = $value2->reference;
                                $updateInvoice[$key2]['amount'] = $value2->total_value;
                                $updateInvoice[$key2]['issue_date'] = $created_at1;
                                $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                                $updateInvoice[$key2]['outstanding_amount'] = $value2->due_value;
                                $updateInvoice[$key2]['currency'] = $value2->currency;
                                $updateInvoice[$key2]['paid'] = $paid;
                                $updateInvoice[$key2]['pay_date'] = $paid_on;
                                $updateInvoice[$key2]['due_date'] = $value2->due_on;

                                //get contact detail
                                $contact = $client->fetch($value2->contact, array(), 'GET', array('Authorization' => 'Bearer ' . $access_token_new, 'User-Agent' => 'App name'));
                                //print_r($contact); die;
                                if ($contact['code'] == '200') {
                                    $contact['result']['contact'] = (object) $contact['result']['contact'];
                                    $updateInvoice[$key2]['contact_person'] = $contact['result']['contact']->organisation_name;
                                    $updateInvoice[$key2]['name'] = $contact['result']['contact']->first_name . ' ' . $contact['result']['contact']->last_name;
                                    $updateInvoice[$key2]['phone_no'] = $contact['result']['contact']->mobile;
                                    $updateInvoice[$key2]['email'] = $contact['result']['contact']->email;
                                    $updateInvoice[$key2]['address'] = isset($contact['result']['contact']->address1) ? $contact['result']['contact']->address1 : '';
                                    $updateInvoice[$key2]['postcode'] = isset($contact['result']['contact']->postcode) ? $contact['result']['contact']->postcode : '';
                                    $updateInvoice[$key2]['city'] = isset($contact['result']['contact']->town) ? $contact['result']['contact']->town : '';
                                    $updateInvoice[$key2]['country'] = isset($contact['result']['contact']->country) ? $contact['result']['contact']->country : '';
                                    $updateInvoice[$key2]['vat_registration_number'] = isset($contact['result']['contact']->sales_tax_registration_number) ? $contact['result']['contact']->sales_tax_registration_number : '';
                                }

                                $updateInvoice[$key2]['type'] = 'Sales Invoice';
                                $updateInvoice[$key2]['payment_terms'] = $value2->payment_terms_in_days;
                                $updateInvoice[$key2]['invoiceId'] = $invoiceId;
                                $updateInvoice[$key2]['updateId'] = $updateId;

                                #Update invoice $updateSuppliers = DB:: table
                                if (!empty($updateInvoice)) {
                                    $updateSuppliers = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateInvoice[$key2]);
                                    $updateContacts = array();
                                }

                                #Update Invoice item data
                                $invoice['LineItems'] = $value2->invoice_items;
                                if (isset($invoice['LineItems']) && !empty($invoice['LineItems'])) {
                                    $updateItemData = array();
                                    #Delete All Lines for this user
                                    DB::table('syncinvoice_item')->where('unique_frenns_id', $uniqueFrennsId)->where('invoice_id', $invoiceId)->delete();
                                    $itemKey = 0;
                                    foreach ($invoice['LineItems'] as $itemKey => $invoiceLine) {
                                        $itemId = substr($invoiceLine['url'], strrpos($invoiceLine['url'], '/') + 1);
                                        if (isset($itemId)) {
                                            $updateItemUpdateId = $updateId . '-' . $itemId;
                                            $updateItemData[$key2 . $itemKey]['frenns_id'] = $usernumber;
                                            $updateItemData[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                            //$updateItemData[$key2 . $itemKey]['invoice_number'] = isset($invoice['InvoiceNumber']) ? $invoice['InvoiceNumber'] : '';
                                            $updateItemData[$key2 . $itemKey]['line_number'] = $itemId;
                                            //$updateItemData[$key2 . $itemKey]['product_code'] = isset($invoiceLine['ItemCode']) ? $invoiceLine['ItemCode'] : '';
                                            $updateItemData[$key2 . $itemKey]['description'] = isset($invoiceLine['description']) ? $invoiceLine['description'] : '';
                                            $updateItemData[$key2 . $itemKey]['qty'] = isset($invoiceLine['quantity']) ? $invoiceLine['quantity'] : '';
                                            $updateItemData[$key2 . $itemKey]['rate'] = isset($invoiceLine['price']) ? $invoiceLine['price'] : '';
                                            $updateItemData[$key2 . $itemKey]['amount_total'] = $invoiceLine['price'] * $invoiceLine['quantity'];
                                            $updateItemData[$key2 . $itemKey]['invoice_id'] = $invoiceId;
                                            $updateItemData[$key2 . $itemKey]['updateId'] = $updateItemUpdateId;

                                            #Update FreeAgent Invoices items 
                                            if (!empty($updateItemData)) {
                                                //$this->apidetail->addInvoiceItem($invoiceItemData);                                              
                                                //DB::table('syncinvoice_item')->where('updateId', $updateItemUpdateId)->$updateItemData($updateItemData[$key2 . $itemKey]);
                                                DB::table('syncinvoice_item')->insert($updateItemData[$key2 . $itemKey]);
                                            }
                                            $itemKey++;
                                        }
                                    }
                                }
                            } else {
                                $addInvoice[$key2]['frenns_id'] = $uniqueId;
                                $addInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                                $addInvoice[$key2]['creation_date'] = $created_at1;
                                $addInvoice[$key2]['last_updated'] = $updated_at1;
                                $addInvoice[$key2]['invoice_number'] = $value2->reference;
                                $addInvoice[$key2]['amount'] = $value2->total_value;
                                $addInvoice[$key2]['issue_date'] = $created_at1;
                                $addInvoice[$key2]['collection_date'] = date('Y-m-d');
                                //$addInvoice[$key2]['vat_amount'] = $value2->tax_amount;
                                $addInvoice[$key2]['outstanding_amount'] = $value2->due_value;
                                $addInvoice[$key2]['currency'] = $value2->currency;
                                $addInvoice[$key2]['paid'] = $paid;
                                $addInvoice[$key2]['pay_date'] = $paid_on;
                                $addInvoice[$key2]['due_date'] = $value2->due_on;

                                //get contact detail
                                $contact = $client->fetch($value2->contact, array(), 'GET', array('Authorization' => 'Bearer ' . $access_token_new, 'User-Agent' => 'App name'));
                                //print_r($contact); die;
                                if ($contact['code'] == '200') {
                                    $contact['result']['contact'] = (object) $contact['result']['contact'];
                                    $addInvoice[$key2]['contact_person'] = $contact['result']['contact']->organisation_name;
                                    $addInvoice[$key2]['name'] = $contact['result']['contact']->first_name . ' ' . $contact['result']['contact']->last_name;
                                    $addInvoice[$key2]['phone_no'] = isset($contact['result']['contact']->mobile) ? $contact['result']['contact']->mobile : '';
                                    $addInvoice[$key2]['email'] = isset($contact['result']['contact']->email) ? $contact['result']['contact']->email : '';
                                    $addInvoice[$key2]['address'] = isset($contact['result']['contact']->address1) ? $contact['result']['contact']->address1 : '';
                                    $addInvoice[$key2]['postcode'] = isset($contact['result']['contact']->postcode) ? $contact['result']['contact']->postcode : '';
                                    $addInvoice[$key2]['city'] = isset($contact['result']['contact']->town) ? $contact['result']['contact']->town : '';
                                    $addInvoice[$key2]['country'] = isset($contact['result']['contact']->country) ? $contact['result']['contact']->country : '';
                                    $addInvoice[$key2]['vat_registration_number'] = isset($contact['result']['contact']->sales_tax_registration_number) ? $contact['result']['contact']->sales_tax_registration_number : '';
                                }

                                $addInvoice[$key2]['type'] = 'Sales Invoice';
                                $addInvoice[$key2]['payment_terms'] = $value2->payment_terms_in_days;
                                $addInvoice[$key2]['invoiceId'] = $invoiceId;
                                $addInvoice[$key2]['updateId'] = $updateId;

                                #Save invoice
                                if (!empty($addInvoice)) {
                                    //echo "<pre>"; print_r($addInvoice[$key2]); die;
                                    DB::table('syncinvoice')->insert($addInvoice[$key2]);
                                    $addInvoice = '';
                                }

                                #New Invoice item data
                                $invoice['LineItems'] = $value2->invoice_items;
                                if (isset($invoice['LineItems']) && !empty($invoice['LineItems'])) {
                                    $itemKey = 0;
                                    $invoiceItemData = array();
                                    foreach ($invoice['LineItems'] as $itemKey => $invoiceLine) {
                                        $itemId = substr($invoiceLine['url'], strrpos($invoiceLine['url'], '/') + 1);
                                        if (isset($itemId)) {
                                            $invoiceItemUpdateId = $updateId . '-' . $itemId;
                                            $invoiceItemData[$itemKey]['frenns_id'] = $usernumber;
                                            $invoiceItemData[$itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                            //$invoiceItemData[$itemKey]['invoice_number'] = isset($invoice['InvoiceNumber']) ? $invoice['InvoiceNumber'] : '';
                                            $invoiceItemData[$itemKey]['line_number'] = $itemId;
                                            //$invoiceItemData[$key2 . $itemKey]['product_code'] = isset($invoiceLine['ItemCode']) ? $invoiceLine['ItemCode'] : '';
                                            $invoiceItemData[$itemKey]['description'] = isset($invoiceLine['description']) ? $invoiceLine['description'] : '';
                                            $invoiceItemData[$itemKey]['qty'] = isset($invoiceLine['quantity']) ? $invoiceLine['quantity'] : '';
                                            $invoiceItemData[$itemKey]['rate'] = isset($invoiceLine['price']) ? $invoiceLine['price'] : '';
                                            $invoiceItemData[$itemKey]['amount_total'] = $invoiceLine['price'] * $invoiceLine['quantity'];
                                            $invoiceItemData[$itemKey]['invoice_id'] = $invoiceId;
                                            $invoiceItemData[$itemKey]['updateId'] = $invoiceItemUpdateId;

                                            #Save FreeAgent Invoices items 
                                            if (!empty($invoiceItemData)) {
                                                //$this->apidetail->addInvoiceItem($invoiceItemData);                                              
                                                DB::table('syncinvoice_item')->insert($invoiceItemData[$itemKey]);
                                            }
                                            $itemKey++;
                                        }
                                    }
                                }
                            }
                        } else if ($updated_at1 > $invoicelastUpdatedTime) {
                            $updateInvoice[$key2]['frenns_id'] = $uniqueId;
                            $updateInvoice[$key2]['unique_frenns_id'] = $uniqueFrennsId;
                            $updateInvoice[$key2]['last_updated'] = $updated_at1;
                            $updateInvoice[$key2]['invoice_number'] = $value2->reference;
                            $updateInvoice[$key2]['amount'] = $value2->total_value;
                            $updateInvoice[$key2]['issue_date'] = $created_at1;
                            $updateInvoice[$key2]['collection_date'] = date('Y-m-d');
                            //$addInvoice[$key2]['vat_amount'] = $value2->tax_amount;
                            $updateInvoice[$key2]['outstanding_amount'] = $value2->due_value;
                            $updateInvoice[$key2]['currency'] = $value2->currency;
                            $updateInvoice[$key2]['paid'] = $paid;
                            $updateInvoice[$key2]['pay_date'] = $paid_on;
                            $updateInvoice[$key2]['due_date'] = $value2->due_on;

                            //get contact detail
                            $contact = $client->fetch($value2->contact, array(), 'GET', array('Authorization' => 'Bearer ' . $access_token_db, 'User-Agent' => 'App name'));
                            //print_r($contact); die;
                            if ($contact['code'] == '200') {
                                $contact['result']['contact'] = (object) $contact['result']['contact'];
                                $updateInvoice[$key2]['contact_person'] = $contact['result']['contact']->organisation_name;
                                $updateInvoice[$key2]['name'] = $contact['result']['contact']->first_name . ' ' . $contact['result']['contact']->last_name;
                                $updateInvoice[$key2]['phone_no'] = isset($contact['result']['contact']->mobile) ? $contact['result']['contact']->mobile : '';
                                $updateInvoice[$key2]['email'] = isset($contact['result']['contact']->email) ? $contact['result']['contact']->email : '';
                                $updateInvoice[$key2]['address'] = isset($contact['result']['contact']->address1) ? $contact['result']['contact']->address1 : '';
                                $updateInvoice[$key2]['postcode'] = isset($contact['result']['contact']->postcode) ? $contact['result']['contact']->postcode : '';
                                $updateInvoice[$key2]['city'] = isset($contact['result']['contact']->town) ? $contact['result']['contact']->town : '';
                                $updateInvoice[$key2]['country'] = isset($contact['result']['contact']->country) ? $contact['result']['contact']->country : '';
                                $updateInvoice[$key2]['vat_registration_number'] = isset($contact['result']['contact']->sales_tax_registration_number) ? $contact['result']['contact']->sales_tax_registration_number : '';
                            }

                            $updateInvoice[$key2]['type'] = 'Sales Invoice';
                            $updateInvoice[$key2]['payment_terms'] = $value2->payment_terms_in_days;
                            $updateInvoice[$key2]['invoiceId'] = $invoiceId;
                            $updateInvoice[$key2]['updateId'] = $updateId;

                            if (!empty($updateInvoice)) {
                                $updateInvoiceRes = DB::table('syncinvoice')->where('updateId', $updateId)->update($updateInvoice[$key2]);
                                $updateInvoice = array();
                            }

                            #Update Invoice item data
                            $invoice['LineItems'] = $value2->invoice_items;
                            if (isset($invoice['LineItems']) && !empty($invoice['LineItems'])) {
                                $updateItemData = array();
                                #Delete All Lines for this user
                                DB::table('syncinvoice_item')->where('unique_frenns_id', $uniqueFrennsId)->where('invoice_id', $invoiceId)->delete();
                                $itemKey = 0;
                                foreach ($invoice['LineItems'] as $itemKey => $invoiceLine) {
                                    $itemId = substr($invoiceLine['url'], strrpos($invoiceLine['url'], '/') + 1);
                                    //if (isset($itemId)) {
                                    $updateItemUpdateId = $updateId . '-' . $itemId;
                                    $updateItemData[$itemKey]['frenns_id'] = $usernumber;
                                    $updateItemData[$itemKey]['unique_frenns_id'] = $uniqueFrennsId;
                                    //$updateItemData[$itemKey]['invoice_number'] = isset($invoice['InvoiceNumber']) ? $invoice['InvoiceNumber'] : '';
                                    $updateItemData[$itemKey]['line_number'] = $itemId;
                                    //$updateItemData[$itemKey]['product_code'] = isset($invoiceLine['ItemCode']) ? $invoiceLine['ItemCode'] : '';
                                    $updateItemData[$itemKey]['description'] = isset($invoiceLine['description']) ? $invoiceLine['description'] : '';
                                    $updateItemData[$itemKey]['qty'] = isset($invoiceLine['quantity']) ? $invoiceLine['quantity'] : '';
                                    $updateItemData[$itemKey]['rate'] = isset($invoiceLine['price']) ? $invoiceLine['price'] : '';
                                    $updateItemData[$itemKey]['amount_total'] = $invoiceLine['price'] * $invoiceLine['quantity'];
                                    $updateItemData[$itemKey]['invoice_id'] = $invoiceId;
                                    $updateItemData[$itemKey]['updateId'] = $updateItemUpdateId;

                                    #Update FreeAgent Invoices items 
                                    if (!empty($updateItemData)) {
                                        DB::table('syncinvoice_item')->insert($updateItemData[$itemKey]);
                                    }
                                    $itemKey++;
                                    //}
                                }
                            }
                        }
                    }
                }
            }
            echo "All information has been stored successfully!!";
            exit;
        } else {
            echo "No user available in database.";
            exit;
        }
    }

    ################################ FreeAgent end ###################################
}
