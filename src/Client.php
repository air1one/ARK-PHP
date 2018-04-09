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

namespace BrianFaust\Ark;

use BrianFaust\Ark\Builder\TransactionBuilder;
use BrianFaust\Http\Http;
use NumberFormatter;

class Client
{
    /** @var \BrianFaust\Ark\Config */
    public $config;

    /** @var int */
    public $version = 1;

    /**
     * Create a new Ark client instance.
     *
     * @param \BrianFaust\Ark\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->transactionBuilder = new TransactionBuilder($config->network);
    }

    /**
     * @param int $version
     */
    public function withVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return \BrianFaust\Ark\API\AbstractAPI
     */
    public function api(string $name): API\AbstractAPI
    {
        $client = Http::withBaseUri($this->config->host)->withHeaders([
            'nethash'     => $this->config->nethash,
            'version'     => $this->config->version,
            'port'        => 1,
            'API-Version' => $this->version,
        ]);

        $class = $this->buildClassName($name);

        return new $class($this, $client);
    }

    private function buildClassName(string $name): string
    {
        $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
        $version = ucfirst($formatter->format($this->version));

        return "BrianFaust\\Ark\\API\\{$version}\\{$name}";
    }
}
