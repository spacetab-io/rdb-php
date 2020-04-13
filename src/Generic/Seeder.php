<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Generic;

use Amp\Promise;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Spacetab\Rdb\Creator\CreatorInterface;
use Spacetab\Rdb\Exception\RdbException;
use Spacetab\Rdb\Notifier\NotifierInterface;
use Spacetab\Rdb\Notifier\NullNotifier;
use Spacetab\Rdb\Repository\SeederRepositoryInterface;
use function Amp\call;

final class Seeder
{
    public const DEFAULT_SEED_PATH = 'database/seeds';

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private FilesystemInterface $filesystem;

    /**
     * @var \Spacetab\Rdb\Creator\CreatorInterface
     */
    private CreatorInterface $creator;

    /**
     * @var \Spacetab\Rdb\Notifier\NotifierInterface
     */
    private NotifierInterface $notifier;

    /**
     * @var \Spacetab\Rdb\Repository\SeederRepositoryInterface
     */
    private ?SeederRepositoryInterface $repository;

    /**
     * Seeder constructor.
     *
     * @param \Spacetab\Rdb\Repository\SeederRepositoryInterface $repository
     * @param \Spacetab\Rdb\Creator\CreatorInterface $creator
     * @param \Spacetab\Rdb\Notifier\NotifierInterface $notifier
     * @param \League\Flysystem\AdapterInterface $adapter
     */
    public function __construct(
        CreatorInterface $creator,
        SeederRepositoryInterface $repository = null,
        ?NotifierInterface $notifier = null,
        ?AdapterInterface $adapter = null
    )
    {
        $this->creator    = $creator;
        $this->repository = $repository;
        $this->notifier   = $notifier ?: new NullNotifier();
        $this->filesystem = new Filesystem($adapter ?: new Local(self::DEFAULT_SEED_PATH));
    }

    /**
     * @param string $filename
     */
    public function create(string $filename): void
    {
        $this->creator->create($this->filesystem, $this->notifier, $filename);
    }

    /**
     * Start seeds!
     *
     * @param string|null $filename
     * @return \Amp\Promise
     * @throws \Spacetab\Rdb\Exception\RdbException
     */
    public function run(?string $filename = null): Promise
    {
        if (!$this->repository instanceof SeederRepositoryInterface) {
            throw RdbException::forUninitializedSeedRepository();
        }

        return call(function () use ($filename) {
            $files = $this->findAll($filename);

            if (count($files) < 1) {
                $this->notifier->note("<info>Seed files not found.</info>");
                return;
            }

            foreach ($files as [$file, $content]) {
                yield $this->repository->transaction($content);
                $this->notifier->note("<comment>Seed</comment> {$file['basename']} <comment>executed</comment>");
            }
        });
    }

    /**
     * Find all seed's in the default Flysystem path.
     *
     * @param string|null $filename
     * @return array
     * @throws \League\Flysystem\FileNotFoundException
     */
    private function findAll(?string $filename): array
    {
        $array = [];
        foreach ($this->filesystem->listContents() as $file) {
            if (is_null($filename)) {
                $array[] = [$file, $this->filesystem->read($file['path'])];
            } elseif ($file['filename'] === $filename) {
                $array[] = [$file, $this->filesystem->read($file['path'])];
                break;
            }
        }

        return $array;
    }
}
