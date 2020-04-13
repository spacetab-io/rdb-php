<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Repository\SQL;

use Amp\Promise;

class MysqlMigrationRepository extends AbstractMigrationRepository
{
    /**
     * @inheritDoc
     */
    public function repositoryExists(): Promise
    {
        $sql = 'select count(*) from information_schema.tables 
                where table_schema = database() 
                and table_name = :tableName';

        return $this->checkIfRepositoryExists($sql);
    }
}
