<?php

namespace Illuminate\Tests\Support;

use ArrayIterator;
use Illuminate\Support\Collection;
use Illuminate\Support\Json;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use RecursiveArrayIterator;
use Traversable;

class SupportJsonTest extends TestCase
{
    protected $json;

    protected function setUp(): void
    {
        parent::setUp();

        $this->json = new Json(new class
        {
            public function __construct($foo = ['foo' => 'bar'])
            {
                $this->foo = $foo;
                $this->bar = (object) ['baz' => (object) ['quz' => 'qux']];
            }
        });
    }

    public function testGet(): void
    {
        $this->assertSame(['foo' => 'bar'], $this->json->get('foo'));
        $this->assertSame('qux', $this->json->get('bar.baz.quz'));
        $this->assertSame('cougar', $this->json->get('baz', 'cougar'));
        $this->assertSame('cougar', $this->json->get('baz', fn () => 'cougar'));
        $this->assertNull($this->json->get('baz'));
    }

    public function testSet(): void
    {
        $this->json->set('quz', 'qux');

        $this->assertSame('qux', $this->json->get('quz'));

        $this->json->set('foo', 'bar');

        $this->assertSame('bar', $this->json->get('foo'));
    }

    public function testSetOnNonArrayOrObject()
    {
        $json = (new Json(null));

        $json->set('quz', 'qux');

        $this->assertSame('qux', $json->get('quz'));
    }

    public function testFill(): void
    {
        $this->json->fill('quz', 'qux');

        $this->assertSame('qux', $this->json->get('quz'));

        $this->json->fill('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $this->json->get('foo'));
    }

    public function testHas(): void
    {
        $this->json->set('quz', ['bar' => null]);

        $this->assertTrue($this->json->has('foo'));
        $this->assertTrue($this->json->has('bar.baz.quz'));
        $this->assertFalse($this->json->has('quz.bar'));
        $this->assertFalse($this->json->has('quz.baz'));
    }

    public function testMissing(): void
    {
        $this->json->set('quz', ['bar' => null]);

        $this->assertFalse($this->json->missing('foo'));
        $this->assertFalse($this->json->missing('bar.baz.quz'));
        $this->assertTrue($this->json->missing('quz.bar'));
        $this->assertTrue($this->json->missing('quz.baz'));
    }

    public function testForget(): void
    {
        $this->json->forget('foo');
        $this->assertTrue($this->json->missing('foo'));

        $this->json->forget('bar.baz.quz');
        $this->assertFalse($this->json->missing('bar'));
        $this->assertFalse($this->json->missing('bar.baz'));
        $this->asserttrue($this->json->missing('bar.baz.quz'));

        $this->json->forget('bar.baz');
        $this->assertFalse($this->json->missing('bar'));
        $this->assertTrue($this->json->missing('bar.baz'));

        $this->json->forget('bar.baz');
        $this->assertFalse($this->json->missing('bar'));
        $this->assertTrue($this->json->missing('bar.baz'));
    }

    public function testCollect(): void
    {
        $collection = $this->json->collect();

        $this->assertTrue($collection->has('foo'));
        $this->assertTrue($collection->has('bar'));

        $collection = $this->json->collect('bar');

        $this->assertTrue($collection->has('baz'));
    }

    public function testDynamicAccess(): void
    {
        $this->assertSame(['foo' => 'bar'], $this->json->foo);

        $this->json->foo = 'bar';
        $this->assertSame('bar', $this->json->foo);

        $this->assertTrue(isset($this->json->bar));
        $this->assertFalse(isset($this->json->baz));

        unset($this->json->foo);

        $this->assertTrue($this->json->missing('foo'));
    }

    public function testArrayAccess(): void
    {
        $this->assertSame(['foo' => 'bar'], $this->json['foo']);

        $this->json['foo'] = 'bar';
        $this->assertSame('bar', $this->json['foo']);

        $this->assertTrue(isset($this->json['bar']));
        $this->assertFalse(isset($this->json['baz']));

        unset($this->json['foo']);

        $this->assertTrue($this->json->missing('foo'));
    }

    public function testToStringAsJson(): void
    {
        $this->assertSame('{"foo":{"foo":"bar"},"bar":{"baz":{"quz":"qux"}}}', (string) $this->json);
    }

    public function testToJson(): void
    {
        $this->assertSame('{"foo":{"foo":"bar"},"bar":{"baz":{"quz":"qux"}}}', $this->json->toJson());
    }

    public function testToArray(): void
    {
        $this->json->set('baz', new Collection(['foo', 'bar', 'baz']));

        $this->assertEquals([
            'foo' => ['foo' => 'bar'],
            'bar' => (object) ['baz' => (object) ['quz' => 'qux']],
            'baz' => ['foo', 'bar', 'baz'],
        ],
            $this->json->toArray()
        );
    }

    public function testIterator(): void
    {
        $this->assertEquals(
            ['foo' => ['foo' => 'bar'], 'bar' => (object) ['baz' => (object) ['quz' => 'qux']]],
            iterator_to_array($this->json)
        );

        $json = new Json(['foo', 'bar', 'baz']);

        $this->assertInstanceOf(ArrayIterator::class, $json->getIterator());
        $this->assertSame(['foo', 'bar', 'baz'], iterator_to_array($json));

        $json = new Json(new class
        {
            public function __construct(public $foo = 'bar', public $baz = 'quz', public $qux = 'cougar')
            {
            }
        });

        $this->assertInstanceOf(ArrayIterator::class, $json->getIterator());
        $this->assertSame(['foo' => 'bar', 'baz' => 'quz', 'qux' => 'cougar'], iterator_to_array($json));

        $json = new Json(new class implements IteratorAggregate
        {
            public function getIterator(): Traversable
            {
                return new RecursiveArrayIterator(['foo' => ['bar', 'quz']]);
            }
        });

        $this->assertInstanceOf(RecursiveArrayIterator::class, $json->getIterator());
        $this->assertSame(['foo' => ['bar', 'quz']], iterator_to_array($json));
    }

    public function testMake(): void
    {
        $json = Json::make(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $json->data());

        $json = Json::make($object = (object) ['foo' => 'bar']);
        $this->assertSame($object, $json->data());
    }

    public function testFromString(): void
    {
        $json = Json::fromString('{"foo":{"foo":"bar"}}');
        $this->assertEquals((object) ['foo' => (object) ['foo' => 'bar']], $json->data());
    }

    public function testWrap(): void
    {
        $json = Json::fromString('{"foo":{"foo":"bar"}}');

        $this->assertSame($json, Json::wrap($json));

        $object = (object) ['foo' => (object) ['foo' => 'bar']];

        $this->assertSame($object, Json::wrap($object)->data());

        $this->assertEmpty(Json::wrap(null)->data());
    }

    public function testInstanceWithNonObjectsOrArray()
    {
        $this->assertSame('null', (new Json(null))->toJson());
        $this->assertSame('"foo"', (new Json('foo'))->toJson());
        $this->assertSame('true', (new Json(true))->toJson());
        $this->assertSame('false', (new Json(false))->toJson());
        $this->assertSame('10', (new Json(10))->toJson());
        $this->assertSame('10.1', (new Json(10.1))->toJson());
        $this->assertSame('"foo"', (new Json("\x66\x6f\x6f"))->toJson());
    }
}
