<?php

use Kashflow\Customer;
use Kashflow\Invoice;
use Kashflow\Item;

class InvoiceTest extends TestCase
{    
    public function testCreate()
    {
        $customers = $this->api->find( 'customer' );
        
        $invoice = new Invoice;
        $invoice->issued   = '2015-07-02 22:44:00';
        $invoice->due      = '2015-08-02 22:44:00';
        $invoice->terms    = 30;
        $invoice->customer = $customers[0];
        $invoice->currency = 'GBP';
        $invoice->notes    = 'Project #P123';
        
        $item = new Item;
        $item->description = 'Example';
        $item->quantity    = 10;
        $item->price       = 100;
        
        $invoice->items[] = $item;
        
        $invoice = $this->api->save( $invoice );
        
        $this->assertTrue( is_numeric($invoice->id) );
    }
    
    public function testFind()
    {
        $invoices = $this->api->find( 'invoice' );
        $this->assertTrue( count($invoices) > 0 );
    }
    
    public function testFindOne()
    {
        $invoices = $this->api->find( 'invoice' );
        $invoice  = $this->api->find( 'invoice', $invoices[0]->id );
        $this->assertTrue( is_a($invoice, '\Accounting\Interfaces\Model') );
    }
    
    public function testSearch()
    {
        $invoices = $this->api->find( 'invoice' );
        $invoices = $this->api->search( 'invoice', $invoices[0]->number );
        $this->assertTrue( count($invoices) > 0 );
    }
    
    public function testUpdate()
    {
        $invoices = $this->api->find( 'invoice' );
        $invoice  = $invoices[0];
        $invoice->due = $due = '2015-07-20 22:44:00';
        $this->api->save( $invoice );
        $invoice = $this->api->find( 'invoice', $invoice->id );
        $this->assertTrue( $invoice->due == '2015-07-20T00:00:00' );
    }
    
    public function testDelete()
    {
        $invoices = $this->api->find( 'invoice' );
        $invoice  = $this->api->delete( $invoices[0] );
        $this->assertTrue( $invoice );
    }
}