<?php

declare(strict_types=1);

/*
 * This file is part of ARK PHP.
 *
 * (c) Brian Faust <hello@brianfaust.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrianFaust\Tests\Ark\API\One;

use BrianFaust\Tests\Ark\TestCase;

/**
 * @coversNothing
 */
class TransactionTest extends TestCase
{
    /** @test */
    public function can_get_transaction()
    {
        // Arrange...
        $id = 'dfa5a992f392daf01e3db43e49799010ef13b107c592e9044ced99f7df3f81c9';

        // Act...
        $response = $this->getClient()->api('Transaction')->transaction($id);

        // Assert...
        $this->assertInstanceOf('Illuminate\Support\Collection', $response);
    }

    /** @test */
    public function can_get_transactions()
    {
        // Act...
        $response = $this->getClient()->api('Transaction')->transactions();

        // Assert...
        $this->assertInstanceOf('Illuminate\Support\Collection', $response);
    }

    /** @test */
    public function can_get_unconfirmed_transaction()
    {
        // Skip...
        $this->markTestSkipped('This is unreliable to test because of how fast the unconfirmed transactions disappear.');

        // Arrange...
        $id = '52cb2975b2dec5cd21beac470055a254a84169e51b1a72387757a340509a5049';

        // Act...
        $response = $this->getClient()->api('Transaction')->unconfirmedTransaction($id);

        // Assert...
        $this->assertInstanceOf('Illuminate\Support\Collection', $response);
    }

    /** @test */
    public function can_get_unconfirmed_transactions()
    {
        // Act...
        $response = $this->getClient()->api('Transaction')->unconfirmedTransactions();

        // Assert...
        $this->assertInstanceOf('Illuminate\Support\Collection', $response);
    }

    /** @test */
    public function can_add_transactions()
    {
        // Skip...
        $this->markTestSkipped('This requires secrets and will only be tested on local machines.');

        // Arrange...
        $secret = env('ARK_TESTING_SECRET');
        $amount = rand();
        $recipientId = 'DARiJqhogp2Lu6bxufUFQQMuMyZbxjCydN';

        // Act...
        $response = $this->getClient()->api('Transaction')->create($secret, $amount, $recipientId);

        // Assert...
        $this->assertInstanceOf('Illuminate\Support\Collection', $response);
    }
}
