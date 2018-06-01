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

namespace ArkEcosystem\Ark\API\Two;

use ArkEcosystem\Ark\API\AbstractAPI;
use Illuminate\Support\Collection;

class Node extends AbstractAPI
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function status(): Collection
    {
        return $this->get('node/status');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function syncing(): Collection
    {
        return $this->get('node/syncing');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function configuration(): Collection
    {
        return $this->get('node/configuration');
    }
}
