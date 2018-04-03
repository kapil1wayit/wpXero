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
     * @param  int  $id
     * @return Response
     */

    
 
    ############################### Xero API Start Here #############################

	public function xeroWebhooks() {   
		
		/*
		$payload = file_get_contents("php://input");
		$yourHash = base64_encode(hash_hmac('sha256', $payload, 'FfkDRfZOHo8qSR8HBu/yDDQIGxK97AyHNBsqO/VddTRoZ71TYnCB+q87Ikerp8/wNL3zcsh/AooCL1bIWa4GkA=='));
		if ($yourHash === $_SERVER['x-xero-signature']) {
			echo 
		}
		*/
		
		$rawPayload;
		$key = 'FfkDRfZOHo8qSR8HBu/yDDQIGxK97AyHNBsqO/VddTRoZ71TYnCB+q87Ikerp8/wNL3zcsh/AooCL1bIWa4GkA==';

		$rawPayload = file_get_contents("php://input");
		
		$signature = base64_encode(hash_hmac('sha256', $rawPayload, $key, true));

		if($signature == $_SERVER['HTTP_X_XERO_SIGNATURE'])
		{
			$payload = '{}';
			header("HTTP/1.1 200");
			echo $payload;
		}   
		else
		{
			$payload = '{}';
			header("HTTP/1.1 401");
			echo $payload;
		}
		
    }
	
    public function redirectToXero() {
        unset($_SESSION['access_token']);
        unset($_SESSION['oauth_token_secret']);
        unset($_SESSION['session_handle']);
        require_once(app_path('third_party/xerophp/') . 'private.php');
        //redirect(url('/') . 'redirectToXero?authenticate=1');
        ///return Redirect::to(url('/') . '/redirectToXero?authenticate=1');
    }

    public function welcomeToXero() {
        require_once(app_path('third_party/xerophp/') . 'private.php');
        return Redirect::to(url('/') . '/xeroData');
    }

    public function xeroData() {
        #Fetch all quickbook user details
        require_once(app_path('third_party/xerophp/') . 'private.php');

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

                ####################################### Customer Section Start ###################################################

                $response = $XeroOAuth->request('GET', $XeroOAuth->url('Contacts', 'core'), array(), 'xml', '');
                //echo '<pre>';print_r($response); die(' All Contacts Response 3135');
                if ($XeroOAuth->response['code'] == 200) {
                    $contacts = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                    $contacts = json_decode(json_encode($contacts), true);
                    //echo '<pre>';print_r($contacts['Contacts']['Contact']); //die(' All Contacts');

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

				
                    if(count($contacts['Contacts']['Contact']) == 1){
                        foreach ($contacts['Contacts'] as $customer) {
                            //echo '<pre>';print_r($customer);  die(' Single Contact'); 
                            $cresponse = $XeroOAuth->request('GET', $XeroOAuth->url('Contacts/'.$customer['ContactID'], 'core'), array(), 'xml', '');
                          $invoiceContactInfo = $XeroOAuth->parseResponse($cresponse['response'], $XeroOAuth->response['format']);
                          $invoiceContactInfo = json_decode(json_encode($invoiceContactInfo), true);
                          $customer = $invoiceContactInfo['Contacts']['Contact'];
                          //echo '<pre>';print_r($customer);  //die(' Single Contact');
                            $contactId = $customer['ContactID'];
                            $uniqueUpdateId = $uniqueFrennsId . '-' . $contactId;
                            if ($customer['IsSupplier'] == 'true') {
                                $contactType = "Supplier";
                            } else if ($customer['IsCustomer'] == 'true') {
                                $contactType = "Customer";
                            } else {
                                $contactType = '';
                            }

                            if($customer['Addresses']['Address'][0]['AddressType'] == 'POBOX'){
                                $customerAddress = $customer['Addresses']['Address'][0];
                            }else if($customer['Addresses']['Address'][1]['AddressType'] == 'POBOX'){
                                $customerAddress = $customer['Addresses']['Address'][1];
                            }else{
                                $customerAddress = array();
                            }

                            if(isset($customer['Phones']['Phone'][1]['PhoneNumber'])){
                                $customerPhone = $customer['Phones']['Phone'][1]['PhoneNumber'];
                            }else if(isset($customer['Phones']['Phone'][2]['PhoneNumber'])){
                                $customerPhone = $customer['Phones']['Phone'][2]['PhoneNumber'];
                            }else if(isset($customer['Phones']['Phone'][3]['PhoneNumber'])){
                                $customerPhone = $customer['Phones']['Phone'][3]['PhoneNumber'];
                            }else{
                                $customerPhone = '';
                            }

                            if(isset($customer['BatchPayments'])){
                              $accountDetails = $customer['BatchPayments'];
                            }else{
                              $accountDetails = '';
                            }


                            
                                $customerData[$key]['frenns_id'] = $uniqueId;
                                $customerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $customerData[$key]['company_name'] = isset($customer['CompanyName']) ? $customer['CompanyName'] : '';
                                $customerData[$key]['company_account_number'] = isset($customer['CompanyAccountNumber']) ? $customer['CompanyAccountNumber'] : '';
                                $customerData[$key]['company_number'] = isset($customer['CompanyNumber']) ? $customer['CompanyNumber'] : '';
                                $customerData[$key]['Type'] = $contactType;
                                $customerData[$key]['cust_supp_company'] = '';
                                $customerData[$key]['custsupp_companynumber'] = '';
                                $customerData[$key]['account_number'] = isset($accountDetails['BankAccountNumber']) ? $accountDetails['BankAccountNumber'] : '';
                                $customerData[$key]['name'] = isset($customer['Name']) ? $customer['Name'] : '';
                                $customerData[$key]['Address'] = isset($customerAddress['AddressLine1']) ? $customerAddress['AddressLine1'] : '';
                                $customerData[$key]['Postcode'] = isset($customerAddress['PostalCode']) ? $customerAddress['PostalCode'] : '';
                                $customerData[$key]['City'] = isset($customerAddress['City']) ? $customerAddress['City'] : '';
                                $customerData[$key]['country'] = isset($customerAddress['Country']) ? $customerAddress['Country'] : '';
                                $customerData[$key]['contact_person'] = isset($customer['Name']) ? $customer['Name'] : '';
                                $customerData[$key]['phone_number'] = $customerPhone;
                                $customerData[$key]['Email'] = isset($customer['EmailAddress']) ? $customer['EmailAddress'] : '';
                                $customerData[$key]['collection_date'] = date('Y-m-d');
                                $customerData[$key]['last_update'] = isset($customer['UpdatedDateUTC']) ? $customer['UpdatedDateUTC'] : '';
                                $customerData[$key]['contactId'] = $contactId;
                                
                               
                        }
                    }else{
                       foreach ($contacts['Contacts']['Contact'] as $key => $customer) {
                            //echo '<pre>';print_r($customer);  //die(' Single Contact');
                            $cresponse = $XeroOAuth->request('GET', $XeroOAuth->url('Contacts/'.$customer['ContactID'], 'core'), array(), 'xml', '');
                          $invoiceContactInfo = $XeroOAuth->parseResponse($cresponse['response'], $XeroOAuth->response['format']);
                          $invoiceContactInfo = json_decode(json_encode($invoiceContactInfo), true);
                          $customer = $invoiceContactInfo['Contacts']['Contact'];
                          //echo '<pre>';print_r($customer);  //die(' Single Contact');
                            $contactId = $customer['ContactID'];
                            $uniqueUpdateId = $uniqueFrennsId . '-' . $contactId;
                            if ($customer['IsSupplier'] == 'true') {
                                $contactType = "Supplier";
                            } else if ($customer['IsCustomer'] == 'true') {
                                $contactType = "Customer";
                            } else {
                                $contactType = '';
                            }

                            if($customer['Addresses']['Address'][0]['AddressType'] == 'POBOX'){
                                $customerAddress = $customer['Addresses']['Address'][0];
                            }else if($customer['Addresses']['Address'][1]['AddressType'] == 'POBOX'){
                                $customerAddress = $customer['Addresses']['Address'][1];
                            }else{
                                $customerAddress = array();
                            }

                            if(isset($customer['Phones']['Phone'][1]['PhoneNumber'])){
                                $customerPhone = $customer['Phones']['Phone'][1]['PhoneNumber'];
                            }else if(isset($customer['Phones']['Phone'][2]['PhoneNumber'])){
                                $customerPhone = $customer['Phones']['Phone'][2]['PhoneNumber'];
                            }else if(isset($customer['Phones']['Phone'][3]['PhoneNumber'])){
                                $customerPhone = $customer['Phones']['Phone'][3]['PhoneNumber'];
                            }else{
                                $customerPhone = '';
                            }

                            if(isset($customer['BatchPayments'])){
                              $accountDetails = $customer['BatchPayments'];
                            }else{
                              $accountDetails = '';
                            }

                                $customerData[$key]['frenns_id'] = $uniqueId;
                                $customerData[$key]['unique_frenns_id'] = $uniqueFrennsId;
                                $customerData[$key]['company_name'] = isset($customer['CompanyName']) ? $customer['CompanyName'] : '';
                                $customerData[$key]['company_account_number'] = isset($customer['CompanyAccountNumber']) ? $customer['CompanyAccountNumber'] : '';
                                $customerData[$key]['company_number'] = isset($customer['CompanyNumber']) ? $customer['CompanyNumber'] : '';
                                $customerData[$key]['Type'] = $contactType;
                                $customerData[$key]['cust_supp_company'] = '';
                                $customerData[$key]['custsupp_companynumber'] = '';
                                $customerData[$key]['account_number'] = isset($accountDetails['BankAccountNumber']) ? $accountDetails['BankAccountNumber'] : '';
                                $customerData[$key]['name'] = isset($customer['Name']) ? $customer['Name'] : '';
                                $customerData[$key]['Address'] = isset($customerAddress['AddressLine1']) ? $customerAddress['AddressLine1'] : '';
                                $customerData[$key]['Postcode'] = isset($customerAddress['PostalCode']) ? $customerAddress['PostalCode'] : '';
                                $customerData[$key]['City'] = isset($customerAddress['City']) ? $customerAddress['City'] : '';
                                $customerData[$key]['country'] = isset($customerAddress['Country']) ? $customerAddress['Country'] : '';
                                $customerData[$key]['contact_person'] = isset($customer['Name']) ? $customer['Name'] : '';
                                $customerData[$key]['phone_number'] = $customerPhone;
                                $customerData[$key]['Email'] = isset($customer['EmailAddress']) ? $customer['EmailAddress'] : '';
                                $customerData[$key]['collection_date'] = date('Y-m-d');
                                $customerData[$key]['last_update'] = isset($customer['UpdatedDateUTC']) ? $customer['UpdatedDateUTC'] : '';
                                $customerData[$key]['contactId'] = $contactId;
                                
								
								
                        } 
                    }

                     return view('contacts', ['data' => $customerData]);
					
					
                } else {
                    ## oauth_problem ## token_expired ## redirect to xero
                    echo "Token Expire for user " . $uniqueId . ".Please contact to administrator.";
                    exit;
                }

                ####################################### Customer Section End ########################################################
          
            }
            //echo 'All information saved successfully!!';
            return Redirect::to('https://frenns.com/apiwork/xr-lib/public.php?success=1');
            //return Redirect::to('https://development.frenns.com/apiwork/xr-lib/public.php?success=1');
            //return Redirect::to(url('/') . '/newPage?success=1');
            //return Redirect::to('http://localhost/tpa/newPage?success=1');
            exit;
        } else {
            echo "No user available in database.";
            exit;
        }
    }

   
}
