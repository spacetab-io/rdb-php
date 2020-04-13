<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Repository;

use Amp\Sql\Pool;
use Amp\Sql\ResultSet;
use Amp\Sql\Statement;
use Amp\Success;
use Spacetab\Rdb\Repository\MigrationRepositoryInterface;
use Spacetab\Rdb\Repository\SQL\PostgresMigrationRepository;
use Spacetab\Rdb\Repository\SQL\SeederRepository;

class PostgresMigrationRepositoryTest extends MigrationRepositoryTestCase
{
    public function testGetRanMethod()
    {
        $result = $this->createMock(ResultSet::class);
        $result->method('advance')
               ->willReturnOnConsecutiveCalls(
                   new Success(true),
                   new Success(true),
                   new Success(false)
               );

        $result->method('getCurrent')
               ->willReturnOnConsecutiveCalls(['migration' => 1], ['migration' => 3]);

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('query')
            ->willReturn(new Success($result));

        $repository = new PostgresMigrationRepository($pool);

        $this->assertSame(4, array_sum(yield $repository->getRan()));
    }

    public function testGetMigrationsMethod()
    {
        $steps = 5;

        $result = $this->createMock(ResultSet::class);
        $result->method('advance')
               ->willReturnOnConsecutiveCalls(
                   new Success(true),
                   new Success(true),
                   new Success(false)
               );

        $result->method('getCurrent')
               ->willReturnOnConsecutiveCalls(['migration' => 1], ['migration' => 3]);

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('execute')
            ->with(['limit' => $steps])
            ->willReturn(new Success($result));

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('prepare')
            ->willReturn(new Success($statement));

        $repository = new PostgresMigrationRepository($pool);

        $this->assertSame(4, array_sum(yield $repository->getMigrations($steps)));
    }

    public function testGetLastMethod()
    {
        $result = $this->createMock(ResultSet::class);
        $result->method('advance')
               ->willReturnOnConsecutiveCalls(
                   new Success(true),
                   new Success(true),
                   new Success(false)
               );

        $result->method('getCurrent')
               ->willReturnOnConsecutiveCalls(['migration' => 1], ['migration' => 3]);

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(new Success($result));

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('prepare')
            ->willReturn(new Success($statement));

        $repository = new PostgresMigrationRepository($pool);

        $this->assertSame(4, array_sum(yield $repository->getLast()));
    }

    public function testGetMigrationBatchesMethod()
    {
        $result = $this->createMock(ResultSet::class);
        $result->method('advance')
               ->willReturnOnConsecutiveCalls(
                   new Success(true),
                   new Success(true),
                   new Success(false)
               );

        $result->method('getCurrent')
               ->willReturnOnConsecutiveCalls(['migration' => 'm1', 'batch' => 1], ['migration' => 'm2', 'batch' => 2]);

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(new Success($result));

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('prepare')
            ->willReturn(new Success($statement));

        $repository = new PostgresMigrationRepository($pool);

        $expected = [
            'm1' => 1,
            'm2' => 2,
        ];

        $this->assertSame($expected, yield $repository->getMigrationBatches());
    }

    public function testLogMethod()
    {
        $file = 'file.sql';
        $batch = 2;

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('execute')
            ->with(compact('file', 'batch'))
            ->willReturn(new Success());

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('prepare')
            ->willReturn(new Success($statement));

        $repository = new PostgresMigrationRepository($pool);

        yield $repository->log($file, $batch);
    }

    public function testDeleteMethod()
    {
        $migration = 'file.sql';

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('execute')
            ->with(compact('migration'))
            ->willReturn(new Success());

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('prepare')
            ->willReturn(new Success($statement));

        $repository = new PostgresMigrationRepository($pool);

        yield $repository->delete($migration);
    }

    public function testGetNextBatchNumberMethod()
    {
        $repository = $this->getBatchNumber();

        $this->assertSame(6, yield $repository->getNextBatchNumber());
    }

    public function testCreateRepositoryMethod()
    {
        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('execute')
            ->willReturn(new Success());

        $repository = new PostgresMigrationRepository($pool);
        yield $repository->createRepository();
    }

    public function testSqlRunInTransaction()
    {
        yield $this->simpleTransaction(PostgresMigrationRepository::class);
    }

    public function testSqlRunInTransactionWithError()
    {
        yield $this->throwableTransaction(PostgresMigrationRepository::class);
    }

    public function testPostgresRepositoryExistsMethod()
    {
        yield $this->repositoryExists(PostgresMigrationRepository::class);
    }

    protected function getBatchNumber(): MigrationRepositoryInterface
    {
        $result = $this->createMock(ResultSet::class);
        $result->method('advance')
               ->willReturn(new Success(true));

        $result->method('getCurrent')
               ->willReturn(['max' => '5']);

        $pool = $this->createMock(Pool::class);
        $pool
            ->expects($this->once())
            ->method('query')
            ->willReturn(new Success($result));

        return new PostgresMigrationRepository($pool);
    }
}
