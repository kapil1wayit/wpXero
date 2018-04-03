<?php

use Kashflow\Customer;

class CustomerTest extends TestCase
{    
    public function testCreate()
    {
        $customer = new Customer;
        $customer->name     = 'Langarth';
        $customer->contact  = 'Matt Davies';
        $customer->email    = 'md@langarth.com';
        $customer->phone    = '1234567890';
        $customer->website  = 'http://langarth.com';
        $customer->address  = '3 Tannery House';
        $customer->town     = 'Woking';
        $customer->postcode = 'GU23 7EF';
        $customer->country  = 'United Kingdom';
        $customer->source   = 'Referral';
        
        $customer = $this->api->save( $customer );
        
        $this->assertTrue( is_numeric($customer->id) );
    }
    
    public function testFind()
    {
        $customers = $this->api->find( 'customer' );
        $this->assertTrue( count($customers) > 0 );
    }
    
    public function testFindOne()
    {
        $customers = $this->api->find( 'customer' );
        $customer = $this->api->find( 'customer', $customers[0]->id );
        $this->assertTrue( is_a($customer, '\Accounting\Interfaces\Model') );
    }
    
    public function testSearch()
    {
        $customers = $this->api->find( 'customer' );
        $customers = $this->api->search( 'customer', $customers[0]->name );
        $this->assertTrue( count($customers) > 0 );
    }
    
    public function testUpdate()
    {
        $customers = $this->api->find( 'customer' );
        $customer  = $customers[0];
        $customer->name = $name = 'Not Langarth Ltd';
        $this->api->save( $customer );
        $customer = $this->api->find( 'customer', $customer->id );
        $this->assertTrue( $customer->name == $name );
    }
    
    public function testDelete()
    {
        $customers = $this->api->find( 'customer' );
        $customer = $this->api->delete( $customers[0] );
        $this->assertTrue( $customer );
    }
}