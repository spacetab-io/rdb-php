<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Generic;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use League\Flysystem\AdapterInterface;
use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Exception\RdbException;
use Spacetab\Rdb\Generic\Migrator;
use Spacetab\Rdb\Notifier\NotifierInterface;
use Spacetab\Rdb\Repository\MigrationRepositoryInterface;

class MigratorTest extends AsyncTestCase
{
    public function testDefaultConstantsIsPublicAvailable()
    {
        $this->assertIsString(Migrator::DEFAULT_MIGRATION_PATH);
        $this->assertIsString(Migrator::FILE_TYPE_UP);
        $this->assertIsString(Migrator::FILE_TYPE_DOWN);
    }

    public function testConstructorHasOneRequiredArgument()
    {
        $migrator = new Migrator($this->createMock(CreatorInterface::class), null, null, $this->createMock(AdapterInterface::class));
        $this->assertInstanceOf(Migrator::class, $migrator);
    }

    public function testCreateMigrationAsFileWorksWithoutConnectToDatabase()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $creator
            ->expects($this->once())
            ->method('create');

        $migrator = new Migrator($creator, null, null, $this->createMock(AdapterInterface::class));
        $migrator->create('test');
    }

    public function migrationRepositoryExistsCheckProvider()
    {
        return [
            ['exists'],
            ['reset'],
            ['migrate'],
            ['install'],
            ['status'],
            ['rollback'],
        ];
    }

    /**
     * @param string $methodName
     * @dataProvider migrationRepositoryExistsCheckProvider
     */
    public function testMigrationRepositoryExistsCheckWorksWithSetOfMethods(string $methodName)
    {
        $this->expectException(RdbException::class);
        $this->expectExceptionMessage('MigrationRepository must be initialized to run this command.');

        $creator = $this->createMock(CreatorInterface::class);
        $migrator = new Migrator($creator, null, null, $this->createMock(AdapterInterface::class));
        $migrator->{$methodName}();
    }

    public function testHowToExistsMethodReturnsPromiseWithBooleanValue()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('repositoryExists')
            ->willReturn(new Success(true));

        $migrator = new Migrator($creator, $repository, null, $this->createMock(AdapterInterface::class));

        $this->assertTrue(yield $migrator->exists());
    }

    public function testHowToMigrationCanBeInstalledSuccessfully()
    {
        $creator = $this->createMock(CreatorInterface::class);

        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('repositoryExists')
            ->willReturn(new Success(false));
        $repository
            ->expects($this->once())
            ->method('createRepository')
            ->willReturn(new Success());

        $migrator = new Migrator($creator, $repository, null, $this->createMock(AdapterInterface::class));

        yield $migrator->install();
    }

    public function testHowToMigrationCanBeInstalledOverExistingMigration()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $notifier = $this->createMock(NotifierInterface::class);

        $repository
            ->expects($this->once())
            ->method('repositoryExists')
            ->willReturn(new Success(true));

        $notifier
            ->expects($this->once())
            ->method('note');

        $migrator = new Migrator($creator, $repository, $notifier, $this->createMock(AdapterInterface::class));

        yield $migrator->install();
    }

    public function testHowToRunningPendingMigrations()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getRan')
            ->willReturn(new Success(['completed_migration1', 'completed_migration2']));

        $repository
            ->expects($this->once())
            ->method('getNextBatchNumber')
            ->willReturn(new Success(2));

        $repository
            ->expects($this->exactly(2))
            ->method('transaction')
            ->willReturn(new Success());

        $repository
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturn(new Success());

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->any())
            ->method('note');

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([
                ['filename' => 'new_migration1.up', 'path' => 'new_migration1.up.sql'],
                ['filename' => 'new_migration2.up', 'path' => 'new_migration2.up.sql'],
            ]);

        $adapter
            ->method('has')
            ->willReturn(true);

        $adapter
            ->expects($this->exactly(2))
            ->method('read')
            ->with($this->logicalOr(
                $this->equalTo('new_migration1.up.sql'),
                $this->equalTo('new_migration2.up.sql'),
            ))
            ->willReturn(['contents' => 'select 1']);

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->migrate(['step' => 2]);
    }

    public function testHowToRunningPendingMigrationsIfNothingExist()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getRan')
            ->willReturn(new Success(['completed_migration1', 'completed_migration2']));

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->once())
            ->method('note')
            ->with($this->matchesRegularExpression('/Nothing to migrate/'));

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([]);

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->migrate();
    }

    public function testHowToRunningDefaultRollback()
    {
        $creator    = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getLast')
            ->willReturn(new Success(['migration1', 'migration2']));

        $repository
            ->expects($this->exactly(2))
            ->method('transaction')
            ->willReturn(new Success());

        $repository
            ->expects($this->exactly(2))
            ->method('delete')
            ->with($this->logicalOr(
                $this->equalTo('migration1'),
                $this->equalTo('migration2')
            ))
            ->willReturn(new Success());

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([
                ['filename' => 'migration1.down', 'path' => 'migration1.down.sql'],
                ['filename' => 'migration2.down', 'path' => 'migration2.down.sql'],
            ]);

        $adapter
            ->method('has')
            ->willReturn(true);

        $adapter
            ->expects($this->exactly(2))
            ->method('read')
            ->with($this->logicalOr(
                $this->equalTo('migration1.down.sql'),
                $this->equalTo('migration2.down.sql'),
            ))
            ->willReturn(['contents' => 'select 1']);

        $notifier = $this->createMock(NotifierInterface::class);

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->rollback();
    }

    public function testHowToRunningRollbackIfNothingExists()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getMigrations')
            ->willReturn(new Success([]));

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->once())
            ->method('note')
            ->with($this->matchesRegularExpression('/Nothing to rollback/'));

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->rollback(['step' => 1]);
    }

    public function testHowToRunningRollbackIfMigrationNotFoundInTable()
    {
        $creator    = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getMigrations')
            ->willReturn(new Success(['migration1']));

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([]);

        $adapter
            ->method('has')
            ->willReturn(true);

        $adapter
            ->method('read')
            ->with('migration1.down.sql')
            ->willReturn(['contents' => 'select 1']);

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->method('note')
            ->with($this->matchesRegularExpression('/Migration not found/'));

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->rollback(['step' => 1]);
    }

    public function testMigrationReset()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getRan')
            ->willReturn(new Success(['completed_migration1', 'completed_migration2']));

        $notifier = $this->createMock(NotifierInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([]);

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->reset();
    }

    public function testMigrationResetIfNothingExists()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);

        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getRan')
            ->willReturn(new Success([]));

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->once())
            ->method('note')
            ->with($this->matchesRegularExpression('/Nothing to rollback/'));

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->reset();
    }

    public function testMigrationStatusOutput()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $notifier = $this->createMock(NotifierInterface::class);

        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getRan')
            ->willReturn(new Success(['migration1']));

        $repository
            ->expects($this->once())
            ->method('getMigrationBatches')
            ->willReturn(new Success(['migration1' => 1]));

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([
                ['filename' => 'migration1.up', 'path' => 'migration1.up.sql'],
                ['filename' => 'migration2.up', 'path' => 'migration2.up.sql'],
            ]);

        $adapter
            ->method('has')
            ->willReturn(true);

        $adapter
            ->method('read')
            ->with($this->logicalOr(
                $this->equalTo('migration1.up.sql'),
                $this->equalTo('migration2.up.sql'),
            ))
            ->willReturn(['contents' => 'select 1']);

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        $truth = [
            ['status' => true, 'ran' => 'migration1', 'batches' => 1],
            ['status' => false, 'ran' => 'migration2', 'batches' => null],
        ];

        $this->assertSame($truth, yield $migrator->status());
    }

    public function testMigrationStatusOutputIfMigrationsDoesNotExists()
    {
        $creator = $this->createMock(CreatorInterface::class);

        $repository = $this->createMock(MigrationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getRan')
            ->willReturn(new Success([]));

        $repository
            ->expects($this->once())
            ->method('getMigrationBatches')
            ->willReturn(new Success([]));

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([]);

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->once())
            ->method('note')
            ->with($this->matchesRegularExpression('/No migrations found/'));

        $migrator = new Migrator($creator, $repository, $notifier, $adapter);

        yield $migrator->status();
    }
}
