<?php

namespace Illuminate\Tests\Cache;

use ArrayIterator;
use BadMethodCallException;
use Closure;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Cache\TaggableStore;
use Illuminate\Cache\TaggedCache;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse($this->getTestDate()));
    }

    protected function tearDown(): void
    {
        m::close();

        Carbon::setTestNow(null);
    }

    public function testGetReturnsValueFromCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn('bar');
        $this->assertSame('bar', $repo->get('foo'));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArray()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => 'bar', 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $repo->get(['foo', 'bar']));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArrayWithDefaultValues()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => null, 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'default', 'bar' => 'baz'], $repo->get(['foo' => 'default', 'bar']));
    }

    public function testDefaultValueIsReturned()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->times(2)->andReturn(null);
        $this->assertSame('bar', $repo->get('foo', 'bar'));
        $this->assertSame('baz', $repo->get('boom', function () {
            return 'baz';
        }));
    }

    public function testSettingDefaultCacheTime()
    {
        $repo = $this->getRepository();
        $repo->setDefaultCacheTime(10);
        $this->assertEquals(10, $repo->getDefaultCacheTime());
    }

    public function testHasMethod()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->once()->with('bar')->andReturn('bar');
        $repo->getStore()->shouldReceive('get')->once()->with('baz')->andReturn(false);

        $this->assertTrue($repo->has('bar'));
        $this->assertFalse($repo->has('foo'));
        $this->assertTrue($repo->has('baz'));
    }

    public function testMissingMethod()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->once()->with('bar')->andReturn('bar');

        $this->assertTrue($repo->missing('foo'));
        $this->assertFalse($repo->missing('bar'));
    }

    public function testRememberMethodCallsPutAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 10);
        $result = $repo->remember('foo', 10, function () {
            return 'bar';
        });
        $this->assertSame('bar', $result);

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->times(2)->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 602);
        $repo->getStore()->shouldReceive('put')->once()->with('baz', 'qux', 598);
        $result = $repo->remember('foo', Carbon::now()->addMinutes(10)->addSeconds(2), function () {
            return 'bar';
        });
        $this->assertSame('bar', $result);
        $result = $repo->remember('baz', Carbon::now()->addMinutes(10)->subSeconds(2), function () {
            return 'qux';
        });
        $this->assertSame('qux', $result);

        /*
         * Use a callable...
         */
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 10);
        $result = $repo->remember('foo', function () {
            return 10;
        }, function () {
            return 'bar';
        });
        $this->assertSame('bar', $result);
    }

    public function testRememberForeverMethodCallsForeverAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar');
        $result = $repo->rememberForever('foo', function () {
            return 'bar';
        });
        $this->assertSame('bar', $result);
    }

    public function testPuttingMultipleItemsInCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1);
        $repo->put(['foo' => 'bar', 'bar' => 'baz'], 1);
    }

    public function testSettingMultipleItemsInCacheArray()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1)->andReturn(true);
        $result = $repo->setMultiple(['foo' => 'bar', 'bar' => 'baz'], 1);
        $this->assertTrue($result);
    }

    public function testSettingMultipleItemsInCacheIterator()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1)->andReturn(true);
        $result = $repo->setMultiple(new ArrayIterator(['foo' => 'bar', 'bar' => 'baz']), 1);
        $this->assertTrue($result);
    }

    public function testPutWithNullTTLRemembersItemForever()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar')->andReturn(true);
        $this->assertTrue($repo->put('foo', 'bar'));
    }

    public function testPutWithDatetimeInPastOrZeroSecondsRemovesOldItem()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->never();
        $repo->getStore()->shouldReceive('forget')->twice()->andReturn(true);
        $result = $repo->put('foo', 'bar', Carbon::now()->subMinutes(10));
        $this->assertTrue($result);
        $result = $repo->put('foo', 'bar', Carbon::now());
        $this->assertTrue($result);
    }

    public function testRefreshFailsIfRepositoryNotLockProvider()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('This cache store does not support atomic locks');

        $this->getRepository()->refresh('foo', function ($item) {
            return $item ?? 'new';
        });
    }

    public function testRefreshPutsNonExistingItemForeverByDefault()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->with('foo', 'new');

        $result = $repo->refresh('foo', function ($item) {
            return $item ?? 'new';
        });

        $this->assertSame('new', $result);
    }

    public function testRefreshUsesCustomTtl()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('put')->with('foo', 'new', 90);

        $repo->refresh('foo', function ($item, $expire) {
            $this->assertSame(90, $expire->at);

            return $item ?? 'new';
        }, 90);
    }

    public function testRefreshUsesComputedTtl()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('put')->with('foo', 'new', 60);

        $repo->refresh('foo', function ($item, $expire) {
            $expire->at(60);

            return $item ?? 'new';
        }, 90);
    }

    public function testRefreshUsesComputedNeverTtl()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->with('foo', 'new');

        $repo->refresh('foo')->put(function ($item, $expire) {
            $expire->never();

            return $item ?? 'new';
        });
    }

    public function testRefreshDoesntCallCacheIfNothingToRefresh()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldNotReceive('forget');
        $repo->getStore()->shouldNotReceive('put');
        $repo->getStore()->shouldNotReceive('forever');

        $repo->refresh('foo')->put(function () {
            return null;
        });
    }

    public function testRefreshForgetsItemIfExistsAndExpireIsNow()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('foo');
        $repo->getStore()->shouldReceive('forget')->with('foo');

        $repo->refresh('foo')->put(function ($item, $expire) {
            $expire->now();

            return null;
        });
    }

    public function testRefreshDoesntCallCacheIfNothingToForget()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldNotReceive('forget');
        $repo->getStore()->shouldNotReceive('put');
        $repo->getStore()->shouldNotReceive('forever');

        $result = $repo->refresh('foo', function ($item, $expire) {
            $expire->now();

            return null;
        });

        $this->assertNull($result);
    }

    public function testRefreshRemovesItemFromCacheStoreWithExpirationNow()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $repo->getStore()->shouldReceive('forget')->with('foo')->andReturnTrue();
        $repo->getStore()->shouldNotReceive('put');
        $repo->getStore()->shouldNotReceive('forever');

        $result = $repo->refresh('foo', function ($item, $expire) {
            $expire->now();

            return $item ?? 'bar';
        });

        $this->assertSame('bar', $result);
    }

    public function testRefreshUpdatesExistingItem()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('foo:refresh', 0, null)->andReturn($this->getMockedLock());
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $repo->getStore()->shouldReceive('forever')->with('foo', 'bar');

        $result = $repo->refresh('foo', function ($item) {
            $this->assertSame('bar', $item);

            return $item ?? 'new';
        });

        $this->assertSame('bar', $result);
    }

    public function testRefreshUsesLockConfig()
    {
        $repo = $this->getRepositoryWithLockProvider();

        $repo->getStore()->shouldReceive('lock')->with('test_lock', 20, 'test_owner')->andReturn($this->getMockedLock(30));
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $repo->getStore()->shouldReceive('forever')->with('foo', 'bar');

        $result = $repo->refresh('foo')
            ->lock('test_lock', 20, 'test_owner')
            ->waitFor(30)
            ->put(function ($item) {
                return $item ?? 'new';
            });

        $this->assertSame('bar', $result);
    }

    public function testPutManyWithNullTTLRemembersItemsForever()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forever')->with('foo', 'bar')->andReturn(true);
        $repo->getStore()->shouldReceive('forever')->with('bar', 'baz')->andReturn(true);
        $this->assertTrue($repo->putMany(['foo' => 'bar', 'bar' => 'baz']));
    }

    public function testAddWithStoreFailureReturnsFalse()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('add')->never();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $repo->getStore()->shouldReceive('put')->andReturn(false);
        $this->assertFalse($repo->add('foo', 'bar', 60));
    }

    public function testCacheAddCallsRedisStoreAdd()
    {
        $store = m::mock(RedisStore::class);
        $store->shouldReceive('add')->once()->with('k', 'v', 60)->andReturn(true);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 60));
    }

    public function testAddMethodCanAcceptDateIntervals()
    {
        $storeWithAdd = m::mock(RedisStore::class);
        $storeWithAdd->shouldReceive('add')->once()->with('k', 'v', 61)->andReturn(true);
        $repository = new Repository($storeWithAdd);
        $this->assertTrue($repository->add('k', 'v', DateInterval::createFromDateString('61 seconds')));

        $storeWithoutAdd = m::mock(ArrayStore::class);
        $this->assertFalse(method_exists(ArrayStore::class, 'add'), 'This store should not have add method on it.');
        $storeWithoutAdd->shouldReceive('get')->once()->with('k')->andReturn(null);
        $storeWithoutAdd->shouldReceive('put')->once()->with('k', 'v', 60)->andReturn(true);
        $repository = new Repository($storeWithoutAdd);
        $this->assertTrue($repository->add('k', 'v', DateInterval::createFromDateString('60 seconds')));
    }

    public function testAddMethodCanAcceptDateTimeInterface()
    {
        $withAddStore = m::mock(RedisStore::class);
        $withAddStore->shouldReceive('add')->once()->with('k', 'v', 61)->andReturn(true);
        $repository = new Repository($withAddStore);
        $this->assertTrue($repository->add('k', 'v', Carbon::now()->addSeconds(61)));

        $noAddStore = m::mock(ArrayStore::class);
        $this->assertFalse(method_exists(ArrayStore::class, 'add'), 'This store should not have add method on it.');
        $noAddStore->shouldReceive('get')->once()->with('k')->andReturn(null);
        $noAddStore->shouldReceive('put')->once()->with('k', 'v', 62)->andReturn(true);
        $repository = new Repository($noAddStore);
        $this->assertTrue($repository->add('k', 'v', Carbon::now()->addSeconds(62)));
    }

    public function testAddWithNullTTLRemembersItemForever()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar')->andReturn(true);
        $this->assertTrue($repo->add('foo', 'bar'));
    }

    public function testAddWithDatetimeInPastOrZeroSecondsReturnsImmediately()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('add', 'get', 'put')->never();
        $result = $repo->add('foo', 'bar', Carbon::now()->subMinutes(10));
        $this->assertFalse($result);
        $result = $repo->add('foo', 'bar', Carbon::now());
        $this->assertFalse($result);
        $result = $repo->add('foo', 'bar', -1);
        $this->assertFalse($result);
    }

    public function dataProviderTestGetSeconds()
    {
        return [
            [Carbon::parse($this->getTestDate())->addMinutes(5)],
            [(new DateTime($this->getTestDate()))->modify('+5 minutes')],
            [(new DateTimeImmutable($this->getTestDate()))->modify('+5 minutes')],
            [new DateInterval('PT5M')],
            [300],
        ];
    }

    /**
     * @dataProvider dataProviderTestGetSeconds
     *
     * @param  mixed  $duration
     */
    public function testGetSeconds($duration)
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->once()->with($key = 'foo', $value = 'bar', 300);
        $repo->put($key, $value, $duration);
    }

    public function testRegisterMacroWithNonStaticCall()
    {
        $repo = $this->getRepository();
        $repo::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertSame('Taylor', $repo->{__CLASS__}());
    }

    public function testForgettingCacheKey()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->forget('a-key');
    }

    public function testRemovingCacheKey()
    {
        // Alias of Forget
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->delete('a-key');
    }

    public function testSettingCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->with($key = 'foo', $value = 'bar', 1)->andReturn(true);
        $result = $repo->set($key, $value, 1);
        $this->assertTrue($result);
    }

    public function testClearingWholeCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('flush')->andReturn(true);
        $repo->clear();
    }

    public function testGettingMultipleValuesFromCache()
    {
        $keys = ['key1', 'key2', 'key3'];
        $default = 5;

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['key1', 'key2', 'key3'])->andReturn(['key1' => 1, 'key2' => null, 'key3' => null]);
        $this->assertEquals(['key1' => 1, 'key2' => 5, 'key3' => 5], $repo->getMultiple($keys, $default));
    }

    public function testRemovingMultipleKeys()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->getStore()->shouldReceive('forget')->once()->with('a-second-key')->andReturn(true);

        $this->assertTrue($repo->deleteMultiple(['a-key', 'a-second-key']));
    }

    public function testRemovingMultipleKeysFailsIfOneFails()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->getStore()->shouldReceive('forget')->once()->with('a-second-key')->andReturn(false);

        $this->assertFalse($repo->deleteMultiple(['a-key', 'a-second-key']));
    }

    public function testAllTagsArePassedToTaggableStore()
    {
        $store = m::mock(ArrayStore::class);
        $repo = new Repository($store);

        $taggedCache = m::mock();
        $taggedCache->shouldReceive('setDefaultCacheTime');
        $store->shouldReceive('tags')->once()->with(['foo', 'bar', 'baz'])->andReturn($taggedCache);
        $repo->tags('foo', 'bar', 'baz');
    }

    public function testItThrowsExceptionWhenStoreDoesNotSupportTags()
    {
        $this->expectException(BadMethodCallException::class);

        $store = new FileStore(new Filesystem, '/usr');
        $this->assertFalse(method_exists($store, 'tags'), 'Store should not support tagging.');
        (new Repository($store))->tags('foo');
    }

    public function testTagMethodReturnsTaggedCache()
    {
        $store = (new Repository(new ArrayStore()))->tags('foo');

        $this->assertInstanceOf(TaggedCache::class, $store);
    }

    public function testPossibleInputTypesToTags()
    {
        $repo = new Repository(new ArrayStore());

        $store = $repo->tags('foo');
        $this->assertEquals(['foo'], $store->getTags()->getNames());

        $store = $repo->tags(['foo!', 'Kangaroo']);
        $this->assertEquals(['foo!', 'Kangaroo'], $store->getTags()->getNames());

        $store = $repo->tags('r1', 'r2', 'r3');
        $this->assertEquals(['r1', 'r2', 'r3'], $store->getTags()->getNames());
    }

    public function testEventDispatcherIsPassedToStoreFromRepository()
    {
        $repo = new Repository(new ArrayStore());
        $repo->setEventDispatcher(new Dispatcher());

        $store = $repo->tags('foo');

        $this->assertSame($store->getEventDispatcher(), $repo->getEventDispatcher());
    }

    public function testDefaultCacheLifeTimeIsSetOnTaggableStore()
    {
        $repo = new Repository(new ArrayStore());
        $repo->setDefaultCacheTime(random_int(1, 100));

        $store = $repo->tags('foo');

        $this->assertSame($store->getDefaultCacheTime(), $repo->getDefaultCacheTime());
    }

    public function testTaggableRepositoriesSupportTags()
    {
        $taggable = m::mock(TaggableStore::class);
        $taggableRepo = new Repository($taggable);

        $this->assertTrue($taggableRepo->supportsTags());
    }

    public function testNonTaggableRepositoryDoesNotSupportTags()
    {
        $nonTaggable = m::mock(FileStore::class);
        $nonTaggableRepo = new Repository($nonTaggable);

        $this->assertFalse($nonTaggableRepo->supportsTags());
    }

    protected function getRepository()
    {
        $dispatcher = new Dispatcher(m::mock(Container::class));
        $repository = new Repository(m::mock(Store::class));

        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }

    protected function getRepositoryWithLockProvider()
    {
        $dispatcher = new Dispatcher(m::mock(Container::class));
        $repository = new Repository(m::mock(Store::class, LockProvider::class));

        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }

    protected function getMockedLock($block = 10)
    {
        $lock = m::mock(Lock::class);

        $lock->shouldReceive('block')
            ->with($block, m::type(Closure::class))
            ->andReturnUsing(function ($lock, $callback) {
                return $callback();
            });

        return $lock;
    }

    protected function getTestDate()
    {
        return '2030-07-25 12:13:14 UTC';
    }
}
