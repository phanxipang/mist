<?php

namespace Jenky\Concurrency\Tests;

use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    public function test_pool(): void
    {
        $pool = new NullPool();

        $responses = $pool->send([
            new DummyRequest(),
            new DummyRequest(),
        ]);

        $this->assertCount(0, $responses);
    }

    public function test_concurrent(): void
    {
        $pool = new NullPool();

        $pool->concurrent(10);

        $this->assertNotSame($pool, $pool->concurrent(10), 'Pool is immutable');

        $this->expectException(\ValueError::class);

        $pool->concurrent(-1);
    }
}
