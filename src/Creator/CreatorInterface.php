<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Creator;

use League\Flysystem\FilesystemInterface;
use Spacetab\Rdb\Notifier\NotifierInterface;

interface CreatorInterface
{
    /**
     * Create a new file.
     *
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \Spacetab\Rdb\Notifier\NotifierInterface $notifier
     * @param string $name
     * @param mixed ...$options
     * @return void
     */
    public function create(FilesystemInterface $filesystem, NotifierInterface $notifier, string $name, ...$options): void;
}
