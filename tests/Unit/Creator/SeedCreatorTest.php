<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Creator;

use Amp\PHPUnit\AsyncTestCase;
use League\Flysystem\FilesystemInterface;
use Spacetab\Rdb\Creator\SQL\SeedCreator;
use Spacetab\Rdb\Notifier\NotifierInterface;

class SeedCreatorTest extends AsyncTestCase
{
    public function testFileCreationForSqlSeeds()
    {
        $filesystem = $this->createMock(FilesystemInterface::class);
        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->once())
            ->method('note')
            ->with($this->matchesRegularExpression('/Seed.*created/'));

        $creator = new SeedCreator();
        $creator->create($filesystem, $notifier, 'test');
    }

    public function testFileCreationForExistingSqlSeed()
    {
        $filesystem = $this->createMock(FilesystemInterface::class);
        $filesystem
            ->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->once())
            ->method('note')
            ->with($this->matchesRegularExpression('/Seed with name.*already exist./'));

        $creator = new SeedCreator();
        $creator->create($filesystem, $notifier, 'test');
    }
}
