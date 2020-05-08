<?php

declare(strict_types=1);

namespace Spacetab\Rdb\Repository\SQL;

use Amp\Promise;
use Amp\Sql\Pool as PoolInterface;
use Spacetab\Rdb\Repository\SeederRepositoryInterface;
use function Amp\call;

class SeederRepository implements SeederRepositoryInterface
{
    /**
     * @var \Amp\Sql\Pool
     */
    protected PoolInterface $pool;

    /**
     * SeederRepository constructor.
     *
     * @param \Amp\Sql\Pool $pool
     */
    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @inheritDoc
     */
    public function transaction(string $sql): Promise
    {
        return call(function () use ($sql) {
            /** @var \Amp\Sql\Transaction $transaction */
            $transaction = yield $this->pool->beginTransaction();
            try {
                // to avoid bug https://github.com/amphp/postgres/issues/27
                foreach (array_filter(explode(';', $sql), fn($x) => preg_match('/s+/', $x)) as $item) {
                    yield $transaction->query($item);
                }
                yield $transaction->commit();
            } catch (\Throwable $e) {
                yield $transaction->rollback();
                throw $e;
            }
        });
    }
}
