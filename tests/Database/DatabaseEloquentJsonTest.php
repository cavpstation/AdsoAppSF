<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Orchestra\Testbench\TestCase;

class DatabaseEloquentJsonTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->schema()->drop('json_tests');
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('json_tests', function ($table) {
            $table->increments('id');
            $table->json('sample_data');
        });
    }

    public function testJsonUpdateOnNonChangedJsonData()
    {
        $sample_data = [
            'aa' => 1,
            'b' => 2,
        ];

        $model = new JsonTest();
        $model->sample_data = $sample_data;
        $model->save();

        $newModel = JsonTest::find(1);
        $newModel->sample_data = $sample_data;
        $newModel->save();
        $this->assertEmpty($newModel->getChanges());
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

class JsonTest extends Model
{
    public $timestamps = false;

    protected $casts = [
        'sample_data' => 'array',
    ];
}