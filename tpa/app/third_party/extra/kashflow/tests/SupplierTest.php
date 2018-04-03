<?php

use Kashflow\Supplier;

class SupplierTest extends TestCase
{    
    public function testCreate()
    {
        $supplier = new Supplier;
        $supplier->name     = 'Langarth';
        $supplier->contact  = 'Matt Davies';
        $supplier->email    = 'md@langarth.com';
        $supplier->phone    = '1234567890';
        $supplier->website  = 'http://langarth.com';
        $supplier->address  = '3 Tannery House';
        $supplier->town     = 'Woking';
        $supplier->postcode = 'GU23 7EF';
        $supplier->country  = 'United Kingdom';
        $supplier->source   = 'Referral';
        
        $supplier = $this->api->save( $supplier );
        
        $this->assertTrue( is_numeric($supplier->id) );
    }
    
    public function testFind()
    {
        $suppliers = $this->api->find( 'supplier' );
        $this->assertTrue( count($suppliers) > 0 );
    }
    
    public function testFindOne()
    {
        $suppliers = $this->api->find( 'supplier' );
        $supplier = $this->api->find( 'supplier', $suppliers[0]->id );
        $this->assertTrue( is_a($supplier, '\Accounting\Interfaces\Model') );
    }
    
    public function testSearch()
    {
        $suppliers = $this->api->find( 'supplier' );
        $suppliers = $this->api->search( 'supplier', $suppliers[0]->name );
        $this->assertTrue( count($suppliers) > 0 );
    }
    
    public function testUpdate()
    {
        $suppliers = $this->api->find( 'supplier' );
        $supplier  = $suppliers[0];
        $supplier->name = $name = 'Not Langarth Ltd';
        $this->api->save( $supplier );
        $supplier = $this->api->find( 'supplier', $supplier->id );
        $this->assertTrue( $supplier->name == $name );
    }
    
    public function testDelete()
    {
        $suppliers = $this->api->find( 'supplier' );
        $supplier = $this->api->delete( $suppliers[0] );
        $this->assertTrue( $supplier );
    }
}