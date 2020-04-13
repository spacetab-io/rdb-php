<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Repository;

use Amp\Promise;

interface SeederRepositoryInterface
{
    /**
     * @param string $sql
     * @return \Amp\Promise<void>
     */
    public function transaction(string $sql): Promise;
}
