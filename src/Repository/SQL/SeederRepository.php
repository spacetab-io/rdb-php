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
//            $transaction = yield $this->pool->beginTransaction();
//            try {
//                yield $transaction->query($sql);
//                yield $transaction->commit();
//            } catch (\Throwable $e) {
//                yield $transaction->rollback();
//                throw $e;
//            }
            yield $this->pool->query($sql);
        });
    }
}
