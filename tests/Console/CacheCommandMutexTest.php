<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console;

use Illuminate\Console\CacheCommandMutex;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class CacheCommandMutexTest extends TestCase
{
    /**
     * @var CacheCommandMutex
     */
    protected $mutex;

    /**
     * @var Command
     */
    protected $command;

    /**
     * @var Factory
     */
    protected $cacheFactory;

    /**
     * @var Repository
     */
    protected Repository $cacheRepository;

    protected function setUp(): void
    {
        $this->cacheFactory = m::mock(Factory::class);
        $this->cacheRepository = m::mock(Repository::class);
        $this->cacheFactory->shouldReceive('store')->andReturn($this->cacheRepository);
        $this->mutex = new CacheCommandMutex($this->cacheFactory);
        $this->command = new class extends Command {
            protected $name = 'command-name';
        };
    }

    public function testCanCreateMutex()
    {
        $this->cacheRepository->shouldReceive('add')
            ->andReturn(true)
            ->once();
        $actual = $this->mutex->create($this->command);

        $this->assertTrue($actual);
    }

    public function testCannotCreateMutexIfAlreadyExist()
    {
        $this->cacheRepository->shouldReceive('add')
            ->andReturn(false)
            ->once();
        $actual = $this->mutex->create($this->command);

        $this->assertFalse($actual);
    }

    public function testCanCreateMutexWithCustomConnection()
    {
        $this->cacheRepository->shouldReceive('getStore')
            ->with('test')
            ->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('add')
            ->andReturn(false)
            ->once();
        $this->mutex->useStore('test');

        $this->mutex->create($this->command);
    }
}
