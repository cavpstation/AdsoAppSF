<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class RedisStoreTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function testItCanStoreInfinite()
    {
        Cache::store('redis')->clear();

        $result = Cache::store('redis')->put('foo', INF);
        $this->assertTrue($result);
        $this->assertSame(INF, Cache::store('redis')->get('foo'));

        $result = Cache::store('redis')->put('bar', -INF);
        $this->assertTrue($result);
        $this->assertSame(-INF, Cache::store('redis')->get('bar'));
    }

    public function testItCanStoreNan()
    {
        Cache::store('redis')->clear();

        $result = Cache::store('redis')->put('foo', NAN);
        $this->assertTrue($result);
        $this->assertNan(Cache::store('redis')->get('foo'));
    }

    public function testTagsCanBeAccessed()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('name', 'Sally', 5);
        Cache::store('redis')->tags(['people', 'author'])->put('age', 30, 5);

        $this->assertEquals('Sally', Cache::store('redis')->tags(['people', 'author'])->get('name'));
        $this->assertEquals(30, Cache::store('redis')->tags(['people', 'author'])->get('age'));

        Cache::store('redis')->tags(['people', 'author'])->flush();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
    }

    public function testTagEntriesCanBeStoredForever()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->forever('name', 'Sally');
        Cache::store('redis')->tags(['people', 'author'])->forever('age', 30);

        $this->assertEquals('Sally', Cache::store('redis')->tags(['people', 'author'])->get('name'));
        $this->assertEquals(30, Cache::store('redis')->tags(['people', 'author'])->get('age'));

        Cache::store('redis')->tags(['people', 'author'])->flush();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
    }

    public function testTagEntriesCanBeIncremented()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->put('person-1', 0, 5);
        Cache::store('redis')->tags(['votes'])->increment('person-1');
        Cache::store('redis')->tags(['votes'])->increment('person-1');

        $this->assertEquals(2, Cache::store('redis')->tags(['votes'])->get('person-1'));

        Cache::store('redis')->tags(['votes'])->decrement('person-1');
        Cache::store('redis')->tags(['votes'])->decrement('person-1');

        $this->assertEquals(0, Cache::store('redis')->tags(['votes'])->get('person-1'));
    }

    public function testIncrementedTagEntriesProperlyTurnStale()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->add('person-1', 0, $seconds = 1);
        Cache::store('redis')->tags(['votes'])->increment('person-1');
        Cache::store('redis')->tags(['votes'])->increment('person-1');

        sleep(2);

        Cache::store('redis')->tags(['votes'])->flushStale();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
    }

    public function testTagsCanBeFlushedBySingleKey()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('person-1', 'Sally', 5);
        Cache::store('redis')->tags(['people', 'artist'])->put('person-2', 'John', 5);

        Cache::store('redis')->tags(['artist'])->flush();

        $this->assertEquals('Sally', Cache::store('redis')->tags(['people', 'author'])->get('person-1'));
        $this->assertNull(Cache::store('redis')->tags(['people', 'artist'])->get('person-2'));

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(3, count($keyCount)); // Sets for people, authors, and actual entry for Sally
    }

    public function testStaleEntriesCanBeFlushed()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('person-1', 'Sally', 1);
        Cache::store('redis')->tags(['people', 'artist'])->put('person-2', 'John', 1);

        sleep(2);

        // Add a non-stale entry to people...
        Cache::store('redis')->tags(['people', 'author'])->put('person-3', 'Jennifer', 5);

        Cache::store('redis')->tags(['people'])->flushStale();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(4, count($keyCount)); // Sets for people, authors, and artists + individual entry for Jennifer
    }
}
