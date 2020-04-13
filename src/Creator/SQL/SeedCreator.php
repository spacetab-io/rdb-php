<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Creator\SQL;

use League\Flysystem\FilesystemInterface;
use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Notifier\NotifierInterface;

final class SeedCreator implements CreatorInterface
{
    /**
     * Create a new migration.
     *
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \Spacetab\Rdb\Notifier\NotifierInterface $notifier
     * @param string $name
     * @param mixed ...$options
     * @return void
     */
    public function create(FilesystemInterface $filesystem, NotifierInterface $notifier, string $name, ...$options): void
    {
        $filename = $this->getName($name);

        if ($filesystem->has($filename)) {
            $notifier->note("<info>Seed with name</info> {$filename} <info>already exists.</info>");
            return;
        }

        $date = date('Y_m_d_His');

        $stub = <<<STUB
        -- {$filename} created at: {$date}
        
        STUB;

        $filesystem->put($filename, $stub);

        $notifier->note("<comment>Seed</comment> {$filename} <comment>created</comment>");
    }

    /**
     * @param string|null $name
     * @return string
     */
    private function getName(?string $name): string
    {
        return $name . '.sql';
    }
}
