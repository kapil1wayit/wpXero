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

class TFcollector extends Controller {

    /**
     * Fetch Data from APIs.
     *
     * @param  int  $id
     * @return Response
     */
    ############################## Twinfield API Start Here #######################
    /*
    public function twinfieldDataOld() {

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
                    
                      // echo '<pre>';
                      // print_r($result);
                      // echo '</pre>';
                    
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
                     echo '<pre>';
                      print_r($header);
                      echo '</pre>'; 


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
    */
    ####################################### Twinfield API End Here #######################################################
    

    ####################################### Twinfield Test Data #########################################
    public function tfData() {
        $params = array(
            'user' => 'API000611',
            'password' => 'Twinfield@12345',
            'organisation' => 'TWF-SAAS');
        //logon
        try {
            $session = new SoapClient("https://login.twinfield.com/webservices/session.asmx?wsdl", array('trace' => 1));
            $result = $session->logon($params);
            
               // echo '<pre>';
               // print_r($result);
               // echo '</pre>';
               // die('=========');
             
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
              // echo '<pre>';
              // print_r($header);
              // echo '</pre>'; 
            
            
            try {
                // Function
                echo '<br /><br />Resultaat ProcessXmlString<br /><br />';
               
                $xml = "<read>
                        <type>browse</type>
                        <office>NLA002821</office>
                        <code>100</code>
                        </read>";
                        
                $xml = '<?xml version="1.0"?>
                        <columns code="100">
                        <column id="1">
                        <field>fin.trs.head.yearperiod</field>
                        <label>Periode</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>between</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="2">
                        <field>fin.trs.head.code</field>
                        <label>Dagboek</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>equal</operator>
                        <from></from>
                        <to></to>
                        <finderparam>hidden=1</finderparam>
                        </column>
                        <column id="3">
                        <field>fin.trs.head.shortname</field>
                        <label>Naam</label>
                        <visible>true</visible>
                        <ask>false</ask>
                        <operator>none</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="4">
                        <field>fin.trs.head.number</field>
                        <label>Boekst.nr.</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>between</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="5">
                        <field>fin.trs.head.status</field>
                        <label>Status</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>equal</operator>
                        <from>normal</from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="6">
                        <field>fin.trs.line.dim2</field>
                        <label>Debiteur</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>between</operator>
                        <from></from>
                        <to></to>
                        <finderparam>dimtype=DEB</finderparam>
                        </column>
                        <column id="7">
                        <field>fin.trs.line.dim2name</field>
                        <label>Naam</label>
                        <visible>true</visible>
                        <ask>false</ask>
                        <operator>none</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="8">
                        <field>fin.trs.head.curcode</field>
                        <label>Valuta</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>equal</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="9">
                        <field>fin.trs.line.valuesigned</field>
                        <label>Bedrag</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>between</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="10">
                        <field>fin.trs.line.basevaluesigned</field>
                        <label>Euro</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>between</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="11">
                        <field>fin.trs.line.repvaluesigned</field>
                        <label></label>
                        <visible>false</visible>
                        <ask>false</ask>
                        <operator>between</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="12">
                        <field>fin.trs.line.openbasevaluesigned</field>
                        <label>Openstaand</label>
                        <visible>true</visible>
                        <ask>false</ask>
                        <operator>none</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="13">
                        <field>fin.trs.line.invnumber</field>
                        <label>Factuurnr.</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>equal</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="14">
                        <field>fin.trs.line.datedue</field>
                        <label>Vervaldatum</label>
                        <visible>true</visible>
                        <ask>false</ask>
                        <operator>none</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="15">
                        <field>fin.trs.line.matchstatus</field>
                        <label>Betaalstatus</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>none</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="16">
                        <field>fin.trs.line.matchnumber</field>
                        <label>Betaalnr.</label>
                        <visible>true</visible>
                        <ask>false</ask>
                        <operator>none</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        <column id="17">
                        <field>fin.trs.line.matchdate</field>
                        <label>Betaaldatum</label>
                        <visible>true</visible>
                        <ask>true</ask>
                        <operator>between</operator>
                        <from></from>
                        <to></to>
                        <finderparam></finderparam>
                        </column>
                        </columns>';
                
                $result = $client->__soapCall('ProcessXmlString', array(array('xmlRequest' => $xml)), null, $header);
                //echo '<xmp>';
                //print_r($result->ProcessXmlStringResult);
                //echo '</xmp>';

                $xml = simplexml_load_string($result->ProcessXmlStringResult, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                echo '<pre>';
                print_r($array);
                echo '</pre>';

                die('================');
            } catch (SoapFault $e) {
                echo $e->getMessage();
            }
            

            /*
            try {
                // Function
                echo '<br /><br />Resultaat ProcessXmlString<br /><br />';
                
                $xml = "<salesinvoices>
                            <salesinvoice>
                                    <header>
                                            <invoicetype>FACTUUR</invoicetype>
                                            <customer>1004</customer>
                                    </header>
                            </salesinvoices>
                        </salesinvoice>";

                $xml = '<columns code="040_1">
                  <column id="1">
                     <field>fin.trs.head.office</field>
                     <label>Company</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="2">
                     <field>fin.trs.head.officename</field>
                     <label>Company name</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="3">
                     <field>fin.trs.head.year</field>
                     <label>Year</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="4">
                     <field>fin.trs.head.period</field>
                     <label>Period</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="5">
                     <field>fin.trs.head.yearperiod</field>
                     <label>Year/period (YYYY/PP)</label>
                     <visible>false</visible>
                     <ask>true</ask>
                     <operator>between</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="6">
                     <field>fin.trs.head.status</field>
                     <label>Status</label>
                     <visible>true</visible>
                     <ask>true</ask>
                     <operator>equal</operator>
                     <from>normal</from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="7">
                     <field>fin.trs.line.dim1</field>
                     <label>General ledger acct.</label>
                     <visible>true</visible>
                     <ask>true</ask>
                     <operator>between</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="8">
                     <field>fin.trs.line.dim1name</field>
                     <label>General ledger acct. name</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="9">
                     <field>fin.trs.line.dim1type</field>
                     <label>Dimension type 1</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="10">
                     <field>fin.trs.line.dim2</field>
                     <label>Ccr. /Cust./supp.</label>
                     <visible>true</visible>
                     <ask>true</ask>
                     <operator>between</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="11">
                     <field>fin.trs.line.dim2name</field>
                     <label>Ccr. /Cust./supp. name</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="12">
                     <field>fin.trs.line.dim2type</field>
                     <label>Dimension type 2</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="13">
                     <field>fin.trs.line.dim3</field>
                     <label>Asset/proj.</label>
                     <visible>true</visible>
                     <ask>true</ask>
                     <operator>between</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="14">
                     <field>fin.trs.line.dim3name</field>
                     <label>Asset/proj.name</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="15">
                     <field>fin.trs.line.dim3type</field>
                     <label>Dimension type 3</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="16">
                     <field>fin.trs.line.basevaluesigned</field>
                     <label>Base value</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="17">
                     <field>fin.trs.line.repvaluesigned</field>
                     <label>Reporting amount</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="18">
                     <field>fin.trs.line.debitcredit</field>
                     <label>D/C</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="19">
                     <field>fin.trs.line.quantity</field>
                     <label>Quantity</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="20">
                     <field>fin.trs.line.dim1group1</field>
                     <label>Group 1</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="21">
                     <field>fin.trs.line.dim1group1name</field>
                     <label>Group name 1</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="22">
                     <field>fin.trs.line.dim1group2</field>
                     <label>Group 2</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="23">
                     <field>fin.trs.line.dim1group2name</field>
                     <label>Group name 2</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="24">
                     <field>fin.trs.line.dim1group3</field>
                     <label>Group 3</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
                  <column id="25">
                     <field>fin.trs.line.dim1group3name</field>
                     <label>Group name 3</label>
                     <visible>true</visible>
                     <ask>false</ask>
                     <operator>none</operator>
                     <from></from>
                     <to></to>
                     <finderparam></finderparam>
                  </column>
               </columns>';


                $result = $client->__soapCall('ProcessXmlString', array(array('xmlRequest' => $xml)), null, $header);
                //echo '<xmp>';
                //print_r($result->ProcessXmlStringResult);
                //echo '</xmp>';

                $xml = simplexml_load_string($result->ProcessXmlStringResult, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                echo '<pre>';
                print_r($array);
                echo '</pre>';

                die('================');
            } catch (SoapFault $e) {
                echo $e->getMessage();
            }

            */

            try {
                // Function
                echo '<br /><br />Resultaat ProcessXmlString<br /><br />';
                
                $xml = "<columns code='100'>
       <column>
          <field>fin.trs.head.code</field>
          <label>Transaction type</label>
          <visible>false</visible>
          <ask>true</ask>
          <operator>equal</operator>
          <from>VRK</from>
       </column>
       <column>
          <field>fin.trs.head.number</field>
          <label>Trans. no.</label>
          <visible>true</visible>
       </column>
       <column>
          <field>fin.trs.head.status</field>
          <label>Status</label>
          <visible>true</visible>
          <ask>true</ask>
          <operator>equal</operator>
          <from>normal</from>
          <to></to>
       </column>
       <column>
          <field>fin.trs.line.dim2</field>
          <label>Customer</label>
          <visible>true</visible>
          <ask>true</ask>
          <operator>between</operator>
          <from></from>
          <to></to>
       </column>
       <column>
          <field>fin.trs.line.valuesigned</field>
          <label>Value</label>
          <visible>true</visible>
       </column>
       <column>
          <field>fin.trs.line.openvaluesigned</field>
          <label>Open amount</label>
          <visible>true</visible>
       </column>
       <column>
          <field>fin.trs.line.matchstatus</field>
          <label>Payment status</label>
          <visible>false</visible>
          <ask>true</ask>
          <operator>equal</operator>
          <from>available</from>
       </column>
    </columns>";


                $result = $client->__soapCall('ProcessXmlString', array(array('xmlRequest' => $xml)), null, $header);
                //echo '<xmp>';
                //print_r($result->ProcessXmlStringResult);
                //echo '</xmp>';

                $xml = simplexml_load_string($result->ProcessXmlStringResult, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                echo '<pre>';
                print_r($array);
                echo '</pre>';

                die('================');
            } catch (SoapFault $e) {
                echo $e->getMessage();
            }

            ################## Invoice Data End Here ######################
        } catch (SoapFault $e) {
            echo $e->getMessage();
        }
    }


}
