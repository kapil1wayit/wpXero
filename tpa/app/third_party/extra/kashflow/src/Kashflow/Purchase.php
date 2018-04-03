<?php

/*
 * PHP wrapper for Kashflow SOAP API
 *
 * Copyright (c) 2015 Agency Core.
 * Free to use under the MIT licence, for full details view the LICENCE file.
 *
 */

namespace Kashflow;

use Accounting\Abstracts\Purchase as AccountingPurchase;
use Accounting\Traits\Dated;

class Purchase extends AccountingPurchase
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
        	'Reference'     => $this->notes,
        	'CustomerID'    => isset( $this->supplier->id ) ? $this->supplier->id : $this->supplierId,
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
    	return $this->id ? array( 'Receipt' => $invoice ) : array( 'Inv' => $invoice );
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
        if ( isset($data->GetReceipts_RecentResult) )
        {
            $data = $data->GetReceipts_RecentResult->Invoice;
            $data = is_array( $data ) ? $data :  array( $data );
        }
        else if ( isset($data->GetReceiptResult) )
        {
            $data = array( $data->GetReceiptResult );
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
            $purchase = new $class;
            $purchase->id         = $object->InvoiceDBID;
            $purchase->number     = $object->InvoiceNumber;
            $purchase->due        = $object->DueDate;
            $purchase->issued     = $object->InvoiceDate;
            $purchase->paid       = $object->Paid;
            $purchase->status     = $object->Paid ? 'PAID' : 'APPROVED';
            $purchase->vatAmount  = $object->VATAmount;
            $purchase->amountPaid = $object->AmountPaid;
            $purchase->total      = $object->NetAmount;
            $purchase->currency   = isset($object->CurrencyCode) ? $object->CurrencyCode : null;
            $purchase->notes      = isset( $object->Reference ) ? $object->Reference : null;
            $purchase->supplierId = $object->CustomerID;
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
                $purchase->items[] = $item;
            }
            $decode[] = $purchase;
        }
        return $single ? $decode[0] : $decode;
    }
    
    /*
     * Get unique id after object creationg
     *
     */
    public static function uid( \Accounting\Interfaces\Model $model, $data )
    {
        if ( isset($data->InsertReceiptResult) ) $model->id = $data->InsertReceiptResult;
        return $model;
    }
}