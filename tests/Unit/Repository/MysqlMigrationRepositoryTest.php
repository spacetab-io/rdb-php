<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Repository;

use Spacetab\Rdb\Repository\SQL\MysqlMigrationRepository;

class MysqlMigrationRepositoryTest extends MigrationRepositoryTestCase
{
    public function testMysqlRepositoryExistsMethod()
    {
        yield $this->repositoryExists(MysqlMigrationRepository::class);
    }
}
