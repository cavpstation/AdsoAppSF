<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabasePostgresSchemaBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasTable()
    {
        $tables = [
            ['name' => 'prefix_table', 'schema' => 'public'],
        ];

        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $processor = m::mock(PostgresProcessor::class);
        $processor->shouldReceive('processTables')->andReturn($tables);

        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getConfig')->with('database')->andReturn('db');
        $connection->shouldReceive('getConfig')->with('schema')->andReturn('schema');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $builder = new PostgresBuilder($connection);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn($tables);

        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testGetColumnListing()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('db');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('getConfig')->with('database')->andReturn('db');
        $connection->shouldReceive('getConfig')->with('schema')->andReturn('schema');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar->shouldReceive('compileColumns')->with('db', 'public', 'prefix_table')->once()->andReturn('sql');
        $processor->shouldReceive('processColumns')->once()->andReturn([['name' => 'column']]);
        $builder = new PostgresBuilder($connection);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql')->andReturn([['name' => 'column']]);

        $this->assertEquals(['column'], $builder->getColumnListing('table'));
    }
}
