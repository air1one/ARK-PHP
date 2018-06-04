<?php

declare(strict_types=1);

/*
 * This file is part of ARK PHP.
 *
 * (c) Ark Ecosystem <info@ark.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ArkEcosystem\Tests\Ark;

use ArkEcosystem\Ark\Builder\TransactionBuilder;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $transactionBuilder;
    public function __construct()
    {
        parent::__construct();
        $this->transactionBuilder = new TransactionBuilder();   
    }
}
