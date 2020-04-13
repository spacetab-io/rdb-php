<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Tests\Unit\Creator;

use Amp\PHPUnit\AsyncTestCase;
use League\Flysystem\FilesystemInterface;
use Spacetab\Rdb\Creator\SQL\MigrateCreator;
use Spacetab\Rdb\Notifier\NotifierInterface;

class MigrateCreatorTest extends AsyncTestCase
{
    public function testFileCreationForSqlMigrations()
    {
        $filesystem = $this->createMock(FilesystemInterface::class);
        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->exactly(2))
            ->method('note')
            ->with($this->matchesRegularExpression('/Migration.*created/'));

        $creator = new MigrateCreator();
        $creator->create($filesystem, $notifier, 'test', null, false);
    }

    public function testFileCreationForSqlMigrationsIfPassedOptions()
    {
        $filesystem = $this->createMock(FilesystemInterface::class);
        $notifier = $this->createMock(NotifierInterface::class);
        $notifier
            ->expects($this->exactly(2))
            ->method('note')
            ->with($this->matchesRegularExpression('/Migration.*created/'));

        $creator = new MigrateCreator();
        $creator->create($filesystem, $notifier, 'test', 'tableName', true);
    }
}
