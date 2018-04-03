<?php

/*
 * PHP wrapper for Kashflow SOAP API
 *
 * Copyright (c) 2015 Agency Core.
 * Free to use under the MIT licence, for full details view the LICENCE file.
 *
 */

namespace Kashflow;

use SimpleXMLElement;
use Accounting\Abstracts\Supplier as AccountingSupplier;

class Supplier extends AccountingSupplier
{
    public $id           = 0;
    public $code;
    public $EC           = 1;
    public $outsideEC    = 0;
    public $terms        = 0;
    public $currencyId   = 0;
    public $source       = 0;
    public $discount     = 0;
    public $showDiscount = 0;
    public $created;
    public $updated;
    
    /*
     * Convert model to API compatible format
     *
     */
    public function encode()
    {
        // set default values
        if ( !$this->created ) $this->created = date('Y-m-d\TH:i:s');
        if ( !$this->updated ) $this->updated = date('Y-m-d\TH:i:s');
        // encode object to array
        $supplier = array(
            'SupplierID'   => $this->id,
            'Code'         => $this->code,
            'Name'         => $this->name,
            'Contact'      => $this->contact,
            'Email'        => $this->email,
            'Website'      => $this->website,
            'Address1'     => $this->address,
            'Address2'     => $this->town,
            'Postcode'     => $this->postcode,
            'Telephone'    => $this->phone,
            'Notes'        => $this->notes,
            'EC'           => $this->EC,
            'OutsideEC'    => $this->outsideEC,
            'PaymentTerms' => $this->terms,
            'CurrencyID'   => $this->currencyId,
            'Source'       => $this->source,
            'Discount'     => $this->discount,
            'ShowDiscount' => $this->showDiscount,
            'Created'      => $this->created,
            'Updated'      => $this->updated,
            // why do we have to add all of these?! ask Kashflow :|
            'CheckBox1'  => 0,
            'CheckBox2'  => 0,
            'CheckBox3'  => 0,
            'CheckBox4'  => 0,
            'CheckBox5'  => 0,
            'CheckBox6'  => 0,
            'CheckBox7'  => 0,
            'CheckBox8'  => 0,
            'CheckBox9'  => 0,
            'CheckBox10' => 0,
            'CheckBox11' => 0,
            'CheckBox12' => 0,
            'CheckBox13' => 0,
            'CheckBox14' => 0,
            'CheckBox15' => 0,
            'CheckBox16' => 0,
            'CheckBox17' => 0,
            'CheckBox18' => 0,
            'CheckBox19' => 0,
            'CheckBox20' => 0,
        );
        return $this->id ? array( 'sup' => $supplier ) : array( 'supl' => $supplier );
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
        if ( isset($data->GetSuppliersResult) )
        {
            $data = $data->GetSuppliersResult->Supplier;
            $data = is_array( $data ) ? $data :  array( $data );
        }
        else if ( isset($data->GetSupplierByIDResult) )
        {
            $data   = array( $data->GetSupplierByIDResult );
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
            $supplier = new $class;
            $supplier->id           = $object->SupplierID;
            $supplier->code         = $object->Code;
            $supplier->name         = $object->Name;
            $supplier->contact      = isset( $object->Contact )   ? $object->Contact   : null;
            $supplier->email        = isset( $object->Email )     ? $object->Email     : null;
            $supplier->phone        = isset( $object->Telephone ) ? $object->Telephone : null;
            $supplier->website      = isset( $object->Website )   ? $object->Website   : null;
            $supplier->address      = isset( $object->Address1 )  ? $object->Address1  : null;
            $supplier->town         = isset( $object->Address2 )  ? $object->Address2  : null;
            $supplier->postcode     = isset( $object->Postcode )  ? $object->Postcode  : null;
            $supplier->notes        = $object->Notes;
            $supplier->EC           = $object->EC;
            $supplier->outsideEC    = isset($object->OutsideEC) ? $object->OutsideEC : 0;
            $supplier->terms        = $object->PaymentTerms;
            $supplier->source       = isset($object->Source) ? $object->Source : null;
            $supplier->discount     = isset($object->Discount) ? $object->Discount : null;
            $supplier->showDiscount = isset($object->ShowDiscount) ? $object->ShowDiscount : null;
            $supplier->created      = $object->Created;
            $supplier->updated      = $object->Updated;
            $decode[] = $supplier;
        }
        return $single ? $decode[0] : $decode;
    }
    
    /*
     * Get unique id after object creationg
     *
     */
    public static function uid( \Accounting\Interfaces\Model $model, $data )
    {
        if ( isset($data->InsertSupplierResult) ) $model->id = $data->InsertSupplierResult;
        return $model;
    }
}