<?php

namespace Illuminate\Tests\Integration\Generators;

class CacheTableCommandTest extends TestCase
{
    public function testCreateMakesMigration()
    {
        $this->artisan('cache:table')->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'cache\', function (Blueprint $table) {',
            'Schema::create(\'cache_locks\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'cache\');',
            'Schema::dropIfExists(\'cache_locks\');',
        ], 'create_cache_table.php');
    }
}
