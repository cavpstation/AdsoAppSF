<?php

use Illuminate\Support\NamespacedItemResolver;

class SupportNamespacedItemResolverTest extends PHPUnit_Framework_TestCase {

	public function testResolution()
	{
		$r = new NamespacedItemResolver;

		$this->assertEquals(['foo', 'bar', 'baz'], $r->parseKey('foo::bar.baz'));
		$this->assertEquals(['foo', 'bar', null], $r->parseKey('foo::bar'));
		$this->assertEquals([null, 'bar', 'baz'], $r->parseKey('bar.baz'));
		$this->assertEquals([null, 'bar', null], $r->parseKey('bar'));
	}


	public function testParsedItemsAreCached()
	{
		$r = $this->getMock('Illuminate\Support\NamespacedItemResolver', ['parseBasicSegments', 'parseNamespacedSegments']);
		$r->setParsedKey('foo.bar', ['foo']);
		$r->expects($this->never())->method('parseBasicSegments');
		$r->expects($this->never())->method('parseNamespacedSegments');

		$this->assertEquals(['foo'], $r->parseKey('foo.bar'));
	}

}
