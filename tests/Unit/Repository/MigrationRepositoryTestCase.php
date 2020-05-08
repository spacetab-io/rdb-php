<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Repository;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use Amp\Sql\Pool;
use Amp\Sql\ResultSet;
use Amp\Sql\Statement;
use Amp\Sql\Transaction;
use Amp\Success;
use Spacetab\Rdb\Repository\MigrationRepositoryInterface;
use function Amp\call;

abstract class MigrationRepositoryTestCase extends AsyncTestCase
{
    protected function simpleTransaction(string $repository): Promise
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction
            ->expects($this->any())
            ->method('query')
            ->willReturn(new Success());

        $transaction
            ->expects($this->once())
            ->method('commit')
            ->willReturn(new Success());

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(new Success($transaction));

        /** @var \Spacetab\Rdb\Repository\SeederRepositoryInterface|\Spacetab\Rdb\Repository\MigrationRepositoryInterface $repo */
        $repo = new $repository($pool);

        return call(fn() => yield $repo->transaction('select 1'));
    }

    protected function throwableTransaction(string $repository): Promise
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__METHOD__);

        $transaction = $this->createMock(Transaction::class);
        $transaction
            ->expects($this->any())
            ->method('query')
            ->willReturn(new Success());

        $transaction
            ->expects($this->once())
            ->method('commit')
            ->willThrowException(new \Exception(__METHOD__));

        $transaction
            ->expects($this->once())
            ->method('rollback')
            ->willReturn(new Success());

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(new Success($transaction));

        /** @var \Spacetab\Rdb\Repository\SeederRepositoryInterface|\Spacetab\Rdb\Repository\MigrationRepositoryInterface $repo */
        $repo = new $repository($pool);

        return call(fn() => yield $repo->transaction('select 1'));
    }

    protected function repositoryExists(string $repository): Promise
    {
        $result = $this->createMock(ResultSet::class);
        $result->method('advance')
               ->willReturn(new Success(true));

        $result->method('getCurrent')
               ->willReturnOnConsecutiveCalls(['count' => 2]);

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('execute')
            ->with(['tableName' => MigrationRepositoryInterface::DEFAULT_TABLE])
            ->willReturn(new Success($result));

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('prepare')
            ->willReturn(new Success($statement));

        /** @var \Spacetab\Rdb\Repository\SeederRepositoryInterface|\Spacetab\Rdb\Repository\MigrationRepositoryInterface $repo */
        $repo = new $repository($pool);

        return call(fn() => yield $repo->repositoryExists());
    }
}
