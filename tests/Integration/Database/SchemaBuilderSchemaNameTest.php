<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class SchemaBuilderSchemaNameTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if (! in_array($this->driver, ['pgsql', 'sqlsrv'])) {
            $this->markTestSkipped('Test requires a PostgreSQL or SQL Server connection.');
        }

        if ($this->driver === 'pgsql') {
            DB::connection('without-prefix')->statement('create schema if not exists my_schema');
            DB::connection('with-prefix')->statement('create schema if not exists my_schema');
        } elseif ($this->driver === 'sqlsrv') {
            DB::connection('without-prefix')->statement("if schema_id('my_schema') is null begin exec('create schema my_schema') end");
            DB::connection('with-prefix')->statement("if schema_id('my_schema') is null begin exec('create schema my_schema') end");
        }
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.pgsql.search_path', 'public,my_schema');
        $app['config']->set('database.connections.without-prefix', $app['config']->get('database.connections.'.$this->driver));
        $app['config']->set('database.connections.with-prefix', $app['config']->get('database.connections.without-prefix'));
        $app['config']->set('database.connections.with-prefix.prefix', 'example_');
    }

    #[DataProvider('connectionProvider')]
    public function testCreate($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertFalse($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testRename($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertFalse($schema->hasTable('my_schema.new_table'));
        $this->assertTrue($schema->hasTable('table'));
        $this->assertFalse($schema->hasTable('my_table'));

        $schema->rename('my_schema.table', 'new_table');
        $schema->rename('table', 'my_table');

        $this->assertTrue($schema->hasTable('my_schema.new_table'));
        $this->assertFalse($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('my_table'));
        $this->assertFalse($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testDrop($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));

        $schema->drop('my_schema.table');

        $this->assertFalse($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testDropIfExists($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));

        $schema->dropIfExists('my_schema.table');
        $schema->dropIfExists('my_schema.fake_table');
        $schema->dropIfExists('fake_schema.table');

        $this->assertFalse($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testAddColumns($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('default schema title');
        });
        $schema->create('my_table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default name');
        });

        $this->assertEquals(['id', 'title'], $schema->getColumnListing('my_schema.table'));
        $this->assertEquals(['id', 'name'], $schema->getColumnListing('my_table'));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->string('name')->default('default schema name');
            $table->integer('count');
        });
        $schema->table('my_table', function (Blueprint $table) {
            $table->integer('count');
            $table->string('title')->default('default title');
        });

        $this->assertEquals(['id', 'title', 'name', 'count'], $schema->getColumnListing('my_schema.table'));
        $this->assertEquals(['id', 'name', 'count', 'title'], $schema->getColumnListing('my_table'));
        $this->assertStringContainsString('default schema name', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'name')['default']);
        $this->assertStringContainsString('default schema title', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'title')['default']);
        $this->assertStringContainsString('default name', collect($schema->getColumns('my_table'))->firstWhere('name', 'name')['default']);
        $this->assertStringContainsString('default title', collect($schema->getColumns('my_table'))->firstWhere('name', 'title')['default']);
    }

    #[DataProvider('connectionProvider')]
    public function testRenameColumns($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('default schema title');
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default name');
        });

        $this->assertTrue($schema->hasColumn('my_schema.table', 'title'));
        $this->assertTrue($schema->hasColumn('table', 'name'));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->renameColumn('title', 'new_title');
        });
        $schema->table('table', function (Blueprint $table) {
            $table->renameColumn('name', 'new_name');
        });

        $this->assertFalse($schema->hasColumn('my_schema.table', 'title'));
        $this->assertTrue($schema->hasColumn('my_schema.table', 'new_title'));
        $this->assertFalse($schema->hasColumn('table', 'name'));
        $this->assertTrue($schema->hasColumn('table', 'new_name'));
        $this->assertStringContainsString('default schema title', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'new_title')['default']);
        $this->assertStringContainsString('default name', collect($schema->getColumns('table'))->firstWhere('name', 'new_name')['default']);
    }

    #[DataProvider('connectionProvider')]
    public function testDropColumns($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default schema name');
            $table->integer('count')->default(20);
            $table->string('title')->default('default schema title');
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default name');
            $table->integer('count')->default(10);
            $table->string('title')->default('default title');
        });

        $this->assertTrue($schema->hasColumns('my_schema.table', ['id', 'name', 'count', 'title']));
        $this->assertTrue($schema->hasColumns('table', ['id', 'name', 'count', 'title']));

        $schema->dropColumns('my_schema.table', ['name', 'count']);
        $schema->dropColumns('table', ['name', 'title']);

        $this->assertTrue($schema->hasColumns('my_schema.table', ['id', 'title']));
        $this->assertFalse($schema->hasColumn('my_schema.table', 'name'));
        $this->assertFalse($schema->hasColumn('my_schema.table', 'count'));
        $this->assertTrue($schema->hasColumns('table', ['id', 'count']));
        $this->assertFalse($schema->hasColumn('table', 'name'));
        $this->assertFalse($schema->hasColumn('table', 'title'));
        $this->assertStringContainsString('default schema title', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'title')['default']);
        $this->assertStringContainsString('10', collect($schema->getColumns('table'))->firstWhere('name', 'count')['default']);
    }

    #[DataProvider('connectionProvider')]
    public function testAddAndRenameAndDropIndexes($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('email')->unique();
            $table->integer('name')->index();
            $table->integer('title')->index();
        });
        $schema->create('my_table', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('email')->unique();
            $table->integer('name')->index();
            $table->integer('title')->index();
        });

        var_dump($schema->getIndexListing('my_schema.table'));
        var_dump($schema->getIndexListing('my_table'));

        $this->assertTrue($schema->hasIndex('my_schema.table', ['code'], 'primary'));
        $this->assertTrue($schema->hasIndex('my_schema.table', ['email'], 'unique'));
        $this->assertTrue($schema->hasIndex('my_schema.table', ['name']));
        $this->assertTrue($schema->hasIndex('my_table', ['code'], 'primary'));
        $this->assertTrue($schema->hasIndex('my_table', ['email'], 'unique'));
        $this->assertTrue($schema->hasIndex('my_table', ['name']));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->renameIndex('my_schema_table_title_index', 'my_schema_table_new_title_index');
        });
        $schema->table('my_table', function (Blueprint $table) {
            $table->renameIndex('my_table_title_index', 'my_table_new_title_index');
        });

        $this->assertTrue($schema->hasIndex('my_schema.table', 'my_schema_table_new_title_index'));
        $this->assertFalse($schema->hasIndex('my_schema.table', 'my_schema_table_title_index'));
        $this->assertTrue($schema->hasIndex('my_table', 'my_table_new_title_index'));
        $this->assertFalse($schema->hasIndex('my_table', 'my_table_title_index'));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->dropPrimary(['code']);
            $table->dropUnique(['email']);
            $table->dropIndex(['name']);
            $table->dropIndex('my_schema_table_new_title_index');
        });
        $schema->table('my_table', function (Blueprint $table) {
            $table->dropPrimary(['code']);
            $table->dropUnique(['email']);
            $table->dropIndex(['name']);
            $table->dropIndex('my_table_new_title_index');
        });

        $this->assertEmpty($schema->getIndexListing('my_schema.table'));
        $this->assertEmpty($schema->getIndexListing('my_table'));
    }

    public static function connectionProvider(): array
    {
        return [
            'without prefix' => ['without-prefix'],
            // 'with prefix' => ['with-prefix'],
        ];
    }
}
