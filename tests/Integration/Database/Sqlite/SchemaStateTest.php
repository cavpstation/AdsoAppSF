<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

class SchemaStateTest extends DatabaseTestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'database/schema',
    ];

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testSchemaDumpOnSqlite()
    {
        if ($this->driver !== 'sqlite') {
            $this->markTestSkipped('Test requires a SQLite connection.');
        }

        $connection = DB::connection('sqlite');
        $connection->getSchemaBuilder()->createDatabase($connection->getConfig('database'));

        $connection->statement('CREATE TABLE users(id integer primary key autoincrement not null, email varchar not null, name varchar not null);');
        $connection->statement('CREATE UNIQUE INDEX users_email_unique on users (email);');
        $connection->statement('INSERT INTO users (email, name) VALUES ("taylor@laravel.com", "Taylor Otwell");');

        $connection->statement('PRAGMA optimize;');

        $this->assertTrue($connection->table('sqlite_stat1')->exists());

        $this->app['files']->ensureDirectoryExists(database_path('schema'));

        $connection->getSchemaState()->dump($connection, database_path('schema/sqlite-schema.sql'));

        $this->assertFileContains([
            'CREATE TABLE users',
        ], 'database/schema/sqlite-schema.sql');
        $this->assertFileDoesNotContains([
            'sqlite_sequence',
            'sqlite_stat1',
            'sqlite_stat4',
        ], 'database/schema/sqlite-schema.sql');
    }
}
