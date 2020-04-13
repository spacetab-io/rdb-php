<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Generic;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use League\Flysystem\AdapterInterface;
use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Exception\RdbException;
use Spacetab\Rdb\Generic\Seeder;
use Spacetab\Rdb\Notifier\NotifierInterface;
use Spacetab\Rdb\Repository\SeederRepositoryInterface;

class SeederTest extends AsyncTestCase
{
    public function testDefaultConstantsIsPublicAvailable()
    {
        $this->assertIsString(Seeder::DEFAULT_SEED_PATH);
    }

    public function testConstructorHasOneRequiredArgument()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);

        $seeder = new Seeder($creator, null, null, $adapter);

        $this->assertInstanceOf(Seeder::class, $seeder);
    }

    public function testCreateSeedsAsFileWorksWithoutConnectToDatabase()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);

        $creator
            ->expects($this->once())
            ->method('create');

        $seeder = new Seeder($creator, null, null, $adapter);
        $seeder->create('test');
    }

    public function testSeederRepositoryExistsCheckWorksWithSeedRunMethod()
    {
        $this->expectException(RdbException::class);
        $this->expectExceptionMessage('SeederRepository must be initialized to run this command.');

        $creator = $this->createMock(CreatorInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);

        $seeder = new Seeder($creator, null, null, $adapter);
        $seeder->run();
    }

    public function testSeedRunning()
    {
        $creator = $this->createMock(CreatorInterface::class);

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->method('note')
            ->with($this->matchesRegularExpression('/Seed.*executed/'));

        $repository = $this->createMock(SeederRepositoryInterface::class);
        $repository
            ->method('transaction')
            ->willReturn(new Success());

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([
                ['filename' => 'seed1', 'path' => 'seed1.sql'],
                ['filename' => 'seed2', 'path' => 'seed2.sql'],
            ]);

        $adapter
            ->method('has')
            ->willReturn(true);

        $adapter
            ->method('read')
            ->with($this->logicalOr(
                $this->equalTo('seed1.sql'),
                $this->equalTo('seed2.sql'),
            ))
            ->willReturn(['contents' => 'select 1']);

        $seeder = new Seeder($creator, $repository, $notifier, $adapter);

        yield $seeder->run();
    }

    public function testSeedRunningIfNothingExists()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $repository = $this->createMock(SeederRepositoryInterface::class);
        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->method('note')
            ->with($this->matchesRegularExpression('/Seed files not found/'));

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([]);

        $seeder = new Seeder($creator, $repository, $notifier, $adapter);

        yield $seeder->run();
    }

    public function testSeedRunningIfFilenameProvided()
    {
        $creator = $this->createMock(CreatorInterface::class);
        $notifier = $this->createMock(NotifierInterface::class);

        $repository = $this->createMock(SeederRepositoryInterface::class);
        $repository
            ->method('transaction')
            ->willReturn(new Success());

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects($this->once())
            ->method('listContents')
            ->willReturn([
                ['filename' => 'file', 'path' => 'file.sql'],
            ]);

        $adapter
            ->method('has')
            ->willReturn(true);

        $adapter
            ->method('read')
            ->with('file.sql')
            ->willReturn(['contents' => 'select 1']);

        $seeder = new Seeder($creator, $repository, $notifier, $adapter);

        yield $seeder->run('file');
    }
}
