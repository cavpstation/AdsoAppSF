<?php

class SupportPluralizerTest extends PHPUnit_Framework_TestCase {

    public function testBasicUsage()
    {
        $this->assertEquals('children', str_plural('child'));
        $this->assertEquals('tests', str_plural('test'));
        $this->assertEquals('deer', str_plural('deer'));
        $this->assertEquals('child', str_singular('children'));
        $this->assertEquals('test', str_singular('tests'));
        $this->assertEquals('deer', str_singular('deer'));
    }

    public function testCaseSensitiveUsage()
    {
        $this->assertEquals('Children', str_plural('Child'));
        $this->assertEquals('CHILDREN', str_plural('CHILD'));
        $this->assertEquals('Tests', str_plural('Test'));
        $this->assertEquals('TESTS', str_plural('TEST'));
        $this->assertEquals('tests', str_plural('test'));
        $this->assertEquals('Deer', str_plural('Deer'));
        $this->assertEquals('DEER', str_plural('DEER'));
        $this->assertEquals('Child', str_singular('Children'));
        $this->assertEquals('CHILD', str_singular('CHILDREN'));
        $this->assertEquals('Test', str_singular('Tests'));
        $this->assertEquals('TEST', str_singular('TESTS'));
        $this->assertEquals('Deer', str_singular('Deer'));
        $this->assertEquals('DEER', str_singular('DEER'));
    }

}
