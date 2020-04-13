<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Repository;

use Spacetab\Rdb\Repository\SQL\SeederRepository;

class SeederRepositoryTest extends MigrationRepositoryTestCase
{
    public function testSqlRunInTransaction()
    {
        yield $this->simpleTransaction(SeederRepository::class);
    }

    public function testSqlRunInTransactionWithError()
    {
        yield $this->throwableTransaction(SeederRepository::class);
    }
}
