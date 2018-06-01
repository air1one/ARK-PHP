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

namespace ArkEcosystem\Ark\Builder;

use ArkEcosystem\Ark\Utils\Crypto;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Buffertools\Buffer;

class TransactionBuilder
{
    /**
     * Create a new signed transaction object.
     *
     * @param string      $recipientId
     * @param int         $amount
     * @param string      $vendorField
     * @param string      $secret
     * @param null|string $secondSecret
     *
     * @return \Illuminate\Support\Collection
     */
    public function createTransfer(string $recipientId, int $amount, string $vendorField, string $secret, ?string $secondSecret = null)
    {
        $transaction = self::createEmptyTransaction();
        $transaction->recipientId = $recipientId;
        $transaction->type = TransactionType::TRANSFER;
        $transaction->amount = $amount;
        $transaction->fee = TransactionFee::TRANSFER;
        $transaction->vendorField = $vendorField;
        $transaction->timestamp = self::getTimeSinceEpoch();

        $keys = Crypto::getKeys($secret);
        $transaction->senderPublicKey = $keys->getPublicKey()->getHex();

        self::sign($transaction, $keys);

        if ($secondSecret) {
            $secondKeys = Crypto::getKeys($secondSecret);
            self::secondSign($transaction, $secondKeys);
        }

        $idBytes = self::getBytes($transaction, false, false);
        $transaction->id = Hash::sha256(new Buffer($idBytes))->getHex();

        if (!$transaction->signSignature) {
            unset($transaction->signSignature);
        }
        unset($transaction->asset);

        return $transaction;
    }

    public function createSecondSignature($secondPassphrase, $firstPassphrase)
    {
        $transaction = self::createEmptyTransaction();
        $transaction->type = TransactionType::SECOND_SIGNATURE;
        $transaction->amount = 0;
        $transaction->fee = TransactionFee::SECOND_SIGNATURE;
        $transaction->asset['signature'] = ['publicKey' => Crypto::getKeys($secondPassphrase)->getPublicKey()->getHex()];
        $transaction->timestamp = self::getTimeSinceEpoch();

        $firstPassphraseKeys = Crypto::getKeys($firstPassphrase);
        $transaction->senderPublicKey = $firstPassphraseKeys->getPublicKey()->getHex();
        self::sign($transaction, $firstPassphraseKeys);

        return $transaction;
    }

    public function createVote($votes, $secret, $secondSecret, $network)
    {
        $transaction = self::createEmptyTransaction();
        $transaction->type = TransactionType::VOTE;
        $transaction->amount = 0;
        $transaction->fee = TransactionFee::VOTE;

        $transaction->asset['votes'] = $votes;
        $transaction->recipientId = Crypto::getAddress(Crypto::getKeys($secret), $network);
        $transaction->timestamp = self::getTimeSinceEpoch();

        $keys = Crypto::getKeys($secret);
        $transaction->senderPublicKey = $keys->getPublicKey()->getHex();
        self::sign($transaction, $keys);

        if ($secondSecret) {
            $secondKeys = Crypto::getKeys($secondSecret);
            self::secondSign($transaction, $secondKeys);
        }
        $idBytes = self::getBytes($transaction, false, false);
        $transaction->id = Hash::sha256(new Buffer($idBytes))->getHex();

        return $transaction;
    }

    public function createDelegate($username, $secret, $secondSecret = null)
    {
        $transaction = self::createEmptyTransaction();
        $transaction->type = TransactionType::DELEGATE;
        $transaction->amount = 0;
        $transaction->fee = TransactionFee::DELEGATE;
        $transaction->timestamp = self::getTimeSinceEpoch();

        $keys = Crypto::getKeys($secret);
        $transaction->senderPublicKey = $keys->getPublicKey()->getHex();

        $transaction->asset['delegate'] = [
            'username'  => $username,
            'publicKey' => $transaction->senderPublicKey,
        ];

        self::sign($transaction, $keys);

        if ($secondSecret) {
            $secondKeys = Crypto::getKeys($secondSecret);
            self::secondSign($transaction, $secondKeys);
        }
        $idBytes = self::getBytes($transaction, false, false);
        $transaction->id = Hash::sha256(new Buffer($idBytes))->getHex();

        return $transaction;
    }

    public function createMultiSignature(string $secret, string $secondSecret, array $keysgroup, int $lifetime, int $min)
    {
        $transaction = self::createEmptyTransaction();
        $transaction->type = TransactionType::MULTI_SIGNATURE;
        $transaction->amount = 0;
        $transaction->fee = (count($keysgroup) + 1) * TransactionFee::MULTI_SIGNATURE;
        $transaction->timestamp = self::getTimeSinceEpoch();
        $transaction->asset['multisignature'] = [
            'min'       => $min,
            'lifetime'  => $lifetime,
            'keysgroup' => $keysgroup,
        ];

        $keys = Crypto::getKeys($secret);
        $transaction->senderPublicKey = $keys->getPublicKey()->getHex();
        self::sign($transaction, $keys);

        if ($secondSecret) {
            $secondKeys = Crypto::getKeys($secondSecret);
            self::secondSign($transaction, $secondKeys);
        }
        $idBytes = self::getBytes($transaction, false, false);
        $transaction->id = Hash::sha256(new Buffer($idBytes))->getHex();

        return $transaction;
    }

    private function createEmptyTransaction()
    {
        $out = new \stdClass();
        $out->recipientId = null;
        $out->type = null;
        $out->amount = null;
        $out->fee = null;
        $out->vendorField = null;
        $out->timestamp = null;

        $out->senderPublicKey = null;

        $out->signature = null;
        $out->signSignature = null;

        $out->id = null;
        $out->asset = [];

        return $out;
    }

    public static function getBytes($transaction, $skipSignature = true, $skipSecondSignature = true)
    {
        $out = '';
        $out .= pack('h', $transaction->type);
        $out .= pack('V', $transaction->timestamp);
        $out .= pack('H'.strlen($transaction->senderPublicKey), $transaction->senderPublicKey);

        // TODO: requester public key

        if ($transaction->recipientId) {
            $out .= \BitWasp\Bitcoin\Base58::decodeCheck($transaction->recipientId)->getBinary();
        } else {
            $out .= pack('x21');
        }

        if ($transaction->vendorField && strlen($transaction->vendorField) < 64) {
            $out .= $transaction->vendorField;
            $vendorFieldLength = strlen($transaction->vendorField);
            if ($vendorFieldLength < 64) {
                $out .= pack('x'.(64 - $vendorFieldLength));
            }
        } else {
            $out .= pack('x64');
        }

        $out .= pack('P', $transaction->amount);
        $out .= pack('P', $transaction->fee);

        if ($transaction->type == TransactionType::SECOND_SIGNATURE) { // second signature
            $assetSigPubKey = $transaction->asset['signature']['publicKey'];
            $out .= pack('H'.strlen($assetSigPubKey), $assetSigPubKey);
        } elseif ($transaction->type == TransactionType::DELEGATE) {
            $out .= $transaction->asset['delegate']['username'];
        } elseif ($transaction->type == TransactionType::VOTE) {
            $out .= implode('', $transaction->asset['votes']);
        } elseif ($transaction->type == TransactionType::MULTI_SIGNATURE) {
            $out .= pack('C', $transaction->asset['multisignature']['min']);
            $out .= pack('C', $transaction->asset['multisignature']['lifetime']);
            $out .= implode('', $transaction->asset['multisignature']['keysgroup']);
        }

        if (!$skipSignature && $transaction->signature) {
            $out .= pack('H'.strlen($transaction->signature), $transaction->signature);
        }
        if (!$skipSecondSignature && $transaction->signSignature) {
            $out .= pack('H'.strlen($transaction->signSignature), $transaction->signSignature);
        }

        return $out;
    }

    private function sign($transaction, $keys)
    {
        $txBytes = self::getBytes($transaction);
        $transaction->signature = $keys->sign(Hash::sha256(new Buffer($txBytes)))->getBuffer()->getHex();
    }

    private function secondSign($transaction, $keys)
    {
        $txBytes = self::getBytes($transaction, false);
        $transaction->signSignature = $keys->sign(Hash::sha256(new Buffer($txBytes)))->getBuffer()->getHex();
    }

    private function getTimeSinceEpoch()
    {
        return time() - strtotime('2017-03-21 13:00:00');
    }
}
