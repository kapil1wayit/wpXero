<?php

/*
 * PHP wrapper for Kashflow SOAP API
 *
 * Copyright (c) 2015 Agency Core.
 * Free to use under the MIT licence, for full details view the LICENCE file.
 *
 */

namespace Kashflow;

use Accounting\Abstracts\Api as AccountingApi;
use Accounting\Traits\AccessTokens;
use Accounting\Traits\Debugger;
use Accounting\Traits\Inflect;
use Accounting\Traits\StandardResponse;

class Api extends AccountingApi
{
    /*
     * Register traits
     *
     */
    use StandardResponse, Debugger, Inflect;

    /*
     * API variables
     *
     */
    public $api = 'https://securedwebapp.com/api/service.asmx?WSDL';
    public $endpoints = [
        'customer' => [
            'create'  => 'InsertCustomer',
            'update'  => 'UpdateCustomer',
            'findAll' => 'GetCustomers',
            'findOne' => 'GetCustomerByID',
            'delete'  => [ 'DeleteCustomer', 'CustomerID' ],
        ],
        'supplier' => [
            'create'  => 'InsertSupplier',
            'update'  => 'UpdateSupplier',
            'findAll' => 'GetSuppliers',
            'findOne' => 'GetSupplierByID',
            'delete'  => [ 'DeleteSupplier', 'SupplierID' ],
        ],
        'purchase' => [
            'create'  => 'InsertReceipt',
            'update'  => 'UpdateReceipt',
            'findAll' => 'GetReceipts_Recent',
            'findOne' => 'GetReceipt',
            'delete'  => [ 'DeleteReceipt', 'ReceiptNumber' ],
        ],
        'invoice' => [
            'create'  => 'InsertInvoice',
            'update'  => 'UpdateInvoice',
            'findAll' => 'GetInvoices_Recent',
            'findOne' => 'GetInvoiceByID',
            'delete'  => [ 'DeleteInvoice', 'InvoiceNumber' ],
        ],
        'credit' => [
            'create'  => 'InsertInvoice',
            'update'  => 'UpdateInvoice',
            'findAll' => 'GetInvoices_Recent',
            'findOne' => 'GetInvoiceByID',
            'delete'  => [ 'DeleteInvoice', 'InvoiceNumber' ],
        ]
    ];
    
    /*
     * Private tokens
     *
     */
    private $_username;
    private $_password;

    /*
     * Fetch the API and set the refresh token
     *
     */
    public function fetch( $refresh = null )
    {
        return $this;
    }
    
    /*
     * Set up the wrapper
     *
     */
    public function setup( array $tokens = array() )
    {
        if ( isset($tokens['username']) ) $this->_username = $tokens['username'];
        if ( isset($tokens['password']) ) $this->_password = $tokens['password'];
    }

    /*
     * Configure the wrapper
     *
     */
    public function auxiliary( array $params = array() )
    {
        // currently not in use
        return null;
    }

    /*
     * Locate this class
     *
     */
    protected function locate()
    {
        return __NAMESPACE__;
    }

    /*
     * Handle raw requests
     *
     */
    public function request( $method, array $params = array() )
    {
		// authenticate request
		$params['UserName'] = $this->_username;
		$params['Password'] = $this->_password;
		// make request
		$client  = new \SoapClient( $this->api, array( 'exceptions' => false, 'trace' => true ) );
		$request = $client->$method( $params );
		// handle any errors
		if ( is_soap_fault( $request ) )
		{
            $this->debugger( $method, $request->getCode(), $client->__getLastRequest(), $request, true );
            $this->error( $request->getMessage(), 500 );
		}
        else if ( $request->Status == 'NO' )
        {
            $this->debugger( $method, $request->Status, $params, $request );
            $this->error( $request->StatusDetail, 500 );
        }
        // wrap up response
        return $this->response( $request, $request->Status );
    }
    
    /*
     * Internal front end to request function
     *
     */
    public function call( $endpoint, $verb, array $data = array() )
    {
        // this is SOAP not REST so lose $verb and just take $data
        if ( is_array($verb) && empty($data) )
            $data = $verb;
        // make request
        return $this->request( $endpoint, $data );
    }
    
    /**
     * Find one or more models
     *
     */
    public function find( $type, $id = null )
    {
        // prepare method
        $endpoint = $this->endpoints[$type];
        $method   = $id ? $endpoint['findOne'] : $endpoint['findAll'];
        $params   = array();
        if ( $id ) $params[ ucfirst($type) . 'ID' ] = $id;
        // get invoices, get receipts requires additional params
        if ( $method == 'GetInvoices_Recent' ) $params['NumberOfInvoices'] = 20;
        if ( $method == 'GetReceipts_Recent' ) $params['NumberOfReceipts'] = 20;
        if ( $id && $method == 'GetReceipt' )
        {
            unset( $params[ ucfirst($type) . 'ID' ] );
            $params['ReceiptNumber'] = $id;
        }
        if ( $method == 'GetInvoiceByID' )
        {
            unset( $params[ ucfirst($type) . 'ID' ] );
            $params['InvoiceID'] = $id;
        }
        // attempt request
        $response = $this->call( $method, $params );
        // prepare response
        $class = $this->inflect( $type );
        return $class::decode( $response->data );
    }

    /**
     * Find qualified resource location
     *
     */
    public function resource( \Accounting\Interfaces\Model $model )
    {
        if ( $model->id )
        {
            $endpoint = $this->detector( $model );
            return $this->url( $endpoint . '/' . $model->id );
        }
        return false;
    }

    /**
     * Search for models
     *
     */
    public function search( $type, $name )
    {
        $data  = $this->find( $type );
        $fetch = array();
        foreach ( $data as $object )
        {
            $search = ( $type == 'invoice' || $type == 'purchase' || $type == 'credit' ) ? $object->number : $object->name;
            if ( preg_match( "/$name/i", $search ) )
            {
                $fetch[] = $object;
            }
        }
        return empty( $fetch ) ? false : $fetch;
    }

    /**
     * Save a model
     *
     */
    public function save( \Accounting\Interfaces\Model $model )
    {
        // prepare endpoint and select method
        $endpoint = $this->detector( $model );
        $method   = $model->id ? $endpoint['update'] : $endpoint['create'];
        // make request
        $response = $this->call( $method, $model->encode() );
        // grab id
        $class = $this->inflect( $model );
        $model = $class::uid( $model, $response->data );
        return $model;
    }

    /**
     * Delete a model
     *
     */
    public function delete( \Accounting\Interfaces\Model $model )
    {
        if ( $model->id )
        {
            $endpoint = $this->detector( $model );
            $method   = $endpoint['delete'];
            if ( $method[1] == 'InvoiceNumber' || $method[1] == 'ReceiptNumber' )
                $params = array( $method[1] => $model->number );
            else
                $params = array( $method[1] => $model->id );
            $response = $this->call( $method[0], $params );
            return true;
        }
        return false;
    }
}