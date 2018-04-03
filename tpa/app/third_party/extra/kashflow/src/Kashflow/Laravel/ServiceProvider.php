<?php

/*
 * Kashflow SOAP API Service Provider for Laravel
 *
 * Copyright (c) 2015 Agency Core.
 * Free to use under the MIT licence, for full details view the LICENCE file.
 *
 */

namespace Kashflow\Laravel;

use Accounting\Abstracts\ServiceProvider as AccountingServiceProvider;

class ServiceProvider extends AccountingServiceProvider
{
    public $root    = __DIR__;
    public $service = 'kashflow';
    public $library = 'Kashflow\Api';
}
