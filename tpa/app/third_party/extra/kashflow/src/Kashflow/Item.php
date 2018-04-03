<?php

/*
 * PHP wrapper for Kashflow SOAP API
 *
 * Copyright (c) 2015 Agency Core.
 * Free to use under the MIT licence, for full details view the LICENCE file.
 *
 */

namespace Kashflow;

use Accounting\Abstracts\Item as AccountingItem;

class Item extends AccountingItem
{
    public $vatAmount;
    public $vatRate;
    public $chargeType;
}