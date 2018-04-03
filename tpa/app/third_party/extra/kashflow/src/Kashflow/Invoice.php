<?php

/*
 * PHP wrapper for Kashflow SOAP API
 *
 * Copyright (c) 2015 Agency Core.
 * Free to use under the MIT licence, for full details view the LICENCE file.
 *
 */

namespace Kashflow;

use Accounting\Abstracts\Invoice as AccountingInvoice;
use Accounting\Traits\Dated;

class Invoice extends AccountingInvoice
{
    use Dated;

	public $number     = 0;
	public $paid       = 0;
	public $netAmount  = 0;
	public $vatAmount  = 0;
	public $amountPaid = 0;

    /*
     * Convert model to API compatible format
     *
     */
    public function encode()
    {
    	$invoice = array(
        	'InvoiceDBID'   => 0,
        	'InvoiceNumber' => $this->number,
        	'DueDate'       => self::apiDate( $this->due ),
        	'InvoiceDate'   => self::apiDate( $this->issued ),
        	'CurrencyCode'  => $this->currency,
        	'Reference'     => $this->po ? 'PO: ' . $this->po : $this->notes,
        	'CustomerID'    => isset( $this->customer->id ) ? $this->customer->id : $this->customerId,
        	'Paid'          => $this->paid,
        	'SuppressTotal' => 0,
        	'ProjectID'     => 0,
        	'ExchangeRate'  => 0,
        	'NetAmount'     => $this->netAmount,
        	'VATAmount'     => $this->vatAmount,
        	'AmountPaid'    => $this->amountPaid,
        	'Lines'         => array(),
        	'UseCustomDeliveryAddress' => 0,
    	);
        foreach ( $this->items as $item )
        {
			$line = array
			(
				'LineID'      => 0,
				'Quantity'    => $item->quantity,
				'Description' => $item->description,
				'Rate'        => $item->price,
				'ChargeType'  => 0,
				'VatAmount'   => $item->vatAmount ? $item->vatAmount : ( $item->price * $item->quantity ) * ( $this->tax / 100 ),
				'VatRate'     => $item->vatRate ? $item->vatRate : $this->tax,
				'ProjID'      => 0,
				'ProductID'   => 0,
				'Sort'        => 0,
			);
			// Lines is an array of InvoiceLine as anyType.
			$invoice['Lines'][] = new \SoapVar( $line, 0, 'InvoiceLine', 'KashFlow' );
        }
    	return array( 'Inv' => $invoice );
    }
    
    /*
     * Convert API response back to model
     *
     */
    public static function decode( $data )
    {
        $decode = array();
        $single = false;
        // get correct data
        if ( isset($data->GetInvoices_RecentResult) )
        {
            $data = $data->GetInvoices_RecentResult->Invoice;
            $data = is_array( $data ) ? $data :  array( $data );
        }
        else if ( isset($data->GetInvoiceByIDResult) )
        {
            $data = array( $data->GetInvoiceByIDResult );
            $single = true;
        }
        else
        {
            return false;
        }
        // decode
        foreach ( $data as $object )
        {
            $class = __CLASS__;
            $invoice = new $class;
            $invoice->id         = $object->InvoiceDBID;
            $invoice->number     = $object->InvoiceNumber;
            $invoice->due        = $object->DueDate;
            $invoice->issued     = $object->InvoiceDate;
            $invoice->paid       = $object->Paid;
            $invoice->status     = $object->Paid ? 'PAID' : 'APPROVED';
            $invoice->vatAmount  = $object->VATAmount;
            $invoice->amountPaid = $object->AmountPaid;
            $invoice->total      = $object->NetAmount;
            $invoice->currency   = isset($object->CurrencyCode) ? $object->CurrencyCode : null;
            $invoice->notes      = isset( $object->Reference ) ? $object->Reference : null;
            $invoice->customerId = $object->CustomerID;
            $lines = is_array($object->Lines->anyType) ? $object->Lines->anyType : [ $object->Lines->anyType ];
            foreach ( $lines as $line )
            {
                $item = new Item;
                $item->description = $line->enc_value->Description;
                $item->quantity    = $line->enc_value->Quantity;
                $item->price       = $line->enc_value->Rate;
                $item->vatAmount   = $line->enc_value->VatAmount;
                $item->vatRate     = $line->enc_value->VatRate;
                $item->chargeType  = $line->enc_value->ChargeType;
                $invoice->items[] = $item;
            }
            $decode[] = $invoice;
        }
        return $single ? $decode[0] : $decode;
    }
    
    /*
     * Get unique id after object creationg
     *
     */
    public static function uid( \Accounting\Interfaces\Model $model, $data )
    {
        if ( isset($data->InsertInvoiceResult) ) $model->id = $data->InsertInvoiceResult;
        return $model;
    }
}