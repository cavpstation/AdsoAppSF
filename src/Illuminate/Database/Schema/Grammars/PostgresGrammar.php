<?php

namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Database\Schema\Columns\CharString;
use Illuminate\Database\Schema\Columns\Column;
use Illuminate\Database\Schema\Columns\Decimal;
use Illuminate\Database\Schema\Columns\Enum;
use Illuminate\Database\Schema\Columns\Integer as ColumnInteger;
use Illuminate\Database\Schema\Columns\Text;
use Illuminate\Database\Schema\Columns\Time;
use Illuminate\Database\Schema\Columns\Timestamp;
use RuntimeException;
use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;

class PostgresGrammar extends Grammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected $transactions = true;

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = ['Increment', 'Nullable', 'Default'];

    /**
     * The columns available as serials.
     *
     * @var array
     */
    protected $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * The commands to be executed outside of create or alter command.
     *
     * @var array
     */
    protected $fluentCommands = ['Comment'];

    /**
     * Compile the query to determine if a table exists.
     *
     * @return string
     */
    public function compileTableExists()
    {
        return 'select * from information_schema.tables where table_schema = ? and table_name = ?';
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @return string
     */
    public function compileColumnListing()
    {
        return 'select column_name from information_schema.columns where table_schema = ? and table_name = ?';
    }

    /**
     * Compile a create table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('%s table %s (%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint))
        );
    }

    /**
     * Compile a column addition command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s %s',
            $this->wrapTable($blueprint),
            implode(', ', $this->prefixArray('add column', $this->getColumns($blueprint)))
        );
    }

    /**
     * Compile a primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);

        return 'alter table '.$this->wrapTable($blueprint)." add primary key ({$columns})";
    }

    /**
     * Compile a unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('alter table %s add constraint %s unique (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a plain index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('create index %s on %s%s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $command->algorithm ? ' using '.$command->algorithm : '',
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a spatial index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        $command->algorithm = 'gist';

        return $this->compileIndex($blueprint, $command);
    }

    /**
     * Compile a foreign key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        $sql = parent::compileForeign($blueprint, $command);

        if (! is_null($command->deferrable)) {
            $sql .= $command->deferrable ? ' deferrable' : ' not deferrable';
        }

        if ($command->deferrable && ! is_null($command->initiallyImmediate)) {
            $sql .= $command->initiallyImmediate ? ' initially immediate' : ' initially deferred';
        }

        return $sql;
    }

    /**
     * Compile a drop table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table '.$this->wrapTable($blueprint);
    }

    /**
     * Compile a drop table (if exists) command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
    {
        return 'drop table if exists '.$this->wrapTable($blueprint);
    }

    /**
     * Compile the SQL needed to drop all tables.
     *
     * @param  string  $tables
     * @return string
     */
    public function compileDropAllTables($tables)
    {
        return 'drop table "'.implode('","', $tables).'" cascade';
    }

    /**
     * Compile the SQL needed to retrieve all table names.
     *
     * @param  string  $schema
     * @return string
     */
    public function compileGetAllTables($schema)
    {
        return "select tablename from pg_catalog.pg_tables where schemaname = '{$schema}'";
    }

    /**
     * Compile a drop column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->prefixArray('drop column', $this->wrapArray($command->columns));

        return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $columns);
    }

    /**
     * Compile a drop primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap("{$blueprint->getTable()}_pkey");

        return 'alter table '.$this->wrapTable($blueprint)." drop constraint {$index}";
    }

    /**
     * Compile a drop unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a drop index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        return "drop index {$this->wrap($command->index)}";
    }

    /**
     * Compile a drop spatial index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        return $this->compileDropIndex($blueprint, $command);
    }

    /**
     * Compile a drop foreign key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a rename table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        $from = $this->wrapTable($blueprint);

        return "alter table {$from} rename to ".$this->wrapTable($command->to);
    }

    /**
     * Compile the command to enable foreign key constraints.
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints()
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE;';
    }

    /**
     * Compile the command to disable foreign key constraints.
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints()
    {
        return 'SET CONSTRAINTS ALL DEFERRED;';
    }

    /**
     * Compile a comment command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileComment(Blueprint $blueprint, Fluent $command)
    {
        return sprintf('comment on column %s.%s is %s',
            $this->wrapTable($blueprint),
            $this->wrap($command->column->name),
            "'".str_replace("'", "''", $command->value)."'"
        );
    }

    /**
     * Create the column definition for a char type.
     *
     * @param  CharString  $column
     * @return string
     */
    protected function typeChar(CharString $column)
    {
        return "char({$column->length})";
    }

    /**
     * Create the column definition for a string type.
     *
     * @param  CharString  $column
     * @return string
     */
    protected function typeString(CharString $column)
    {
        return "varchar({$column->length})";
    }

    /**
     * Create the column definition for a text type.
     *
     * @param  Text  $column
     * @return string
     */
    protected function typeText(Text $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param  Text  $column
     * @return string
     */
    protected function typeMediumText(Text $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param  Text  $column
     * @return string
     */
    protected function typeLongText(Text $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for an integer type.
     *
     * @param  ColumnInteger  $column
     * @return string
     */
    protected function typeInteger(ColumnInteger $column)
    {
        return $column->autoIncrement ? 'serial' : 'integer';
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @param  ColumnInteger  $column
     * @return string
     */
    protected function typeBigInteger(ColumnInteger $column)
    {
        return $column->autoIncrement ? 'bigserial' : 'bigint';
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param  ColumnInteger  $column
     * @return string
     */
    protected function typeMediumInteger(ColumnInteger $column)
    {
        return $column->autoIncrement ? 'serial' : 'integer';
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param  ColumnInteger  $column
     * @return string
     */
    protected function typeTinyInteger(ColumnInteger $column)
    {
        return $column->autoIncrement ? 'smallserial' : 'smallint';
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param  ColumnInteger  $column
     * @return string
     */
    protected function typeSmallInteger(ColumnInteger $column)
    {
        return $column->autoIncrement ? 'smallserial' : 'smallint';
    }

    /**
     * Create the column definition for a float type.
     *
     * @param  Decimal  $column
     * @return string
     */
    protected function typeFloat(Decimal $column)
    {
        return $this->typeDouble($column);
    }

    /**
     * Create the column definition for a double type.
     *
     * @param  Decimal  $column
     * @return string
     */
    protected function typeDouble(Decimal $column)
    {
        return 'double precision';
    }

    /**
     * Create the column definition for a real type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeReal(Column $column)
    {
        return 'real';
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param  Decimal  $column
     * @return string
     */
    protected function typeDecimal(Decimal $column)
    {
        return "decimal({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeBoolean(Column $column)
    {
        return 'boolean';
    }

    /**
     * Create the column definition for an enumeration type.
     *
     * @param  Enum  $column
     * @return string
     */
    protected function typeEnum(Enum $column)
    {
        return sprintf(
            'varchar(255) check ("%s" in (%s))',
            $column->name,
            $this->quoteString($column->allowed)
        );
    }

    /**
     * Create the column definition for a json type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeJson(Column $column)
    {
        return 'json';
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeJsonb(Column $column)
    {
        return 'jsonb';
    }

    /**
     * Create the column definition for a date type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeDate(Column $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param  Time  $column
     * @return string
     */
    protected function typeDateTime(Time $column)
    {
        return "timestamp($column->precision) without time zone";
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
     *
     * @param  Time  $column
     * @return string
     */
    protected function typeDateTimeTz(Time $column)
    {
        return "timestamp($column->precision) with time zone";
    }

    /**
     * Create the column definition for a time type.
     *
     * @param  Time  $column
     * @return string
     */
    protected function typeTime(Time $column)
    {
        return "time($column->precision) without time zone";
    }

    /**
     * Create the column definition for a time (with time zone) type.
     *
     * @param  Time  $column
     * @return string
     */
    protected function typeTimeTz(Time $column)
    {
        return "time($column->precision) with time zone";
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param  Timestamp  $column
     * @return string
     */
    protected function typeTimestamp(Timestamp $column)
    {
        $columnType = "timestamp($column->precision) without time zone";

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
     *
     * @param  Timestamp  $column
     * @return string
     */
    protected function typeTimestampTz(Timestamp $column)
    {
        $columnType = "timestamp($column->precision) with time zone";

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a year type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeYear(Column $column)
    {
        return 'integer';
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeBinary(Column $column)
    {
        return 'bytea';
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeUuid(Column $column)
    {
        return 'uuid';
    }

    /**
     * Create the column definition for an IP address type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeIpAddress(Column $column)
    {
        return 'inet';
    }

    /**
     * Create the column definition for a MAC address type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeMacAddress(Column $column)
    {
        return 'macaddr';
    }

    /**
     * Create the column definition for a spatial Geometry type.
     *
     * @param  Column  $column
     * @throws \RuntimeException
     */
    protected function typeGeometry(Column $column)
    {
        throw new RuntimeException('The database driver in use does not support the Geometry spatial column type.');
    }

    /**
     * Create the column definition for a spatial Point type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typePoint(Column $column)
    {
        return $this->formatPostGisType('point');
    }

    /**
     * Create the column definition for a spatial LineString type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeLineString(Column $column)
    {
        return $this->formatPostGisType('linestring');
    }

    /**
     * Create the column definition for a spatial Polygon type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typePolygon(Column $column)
    {
        return $this->formatPostGisType('polygon');
    }

    /**
     * Create the column definition for a spatial GeometryCollection type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeGeometryCollection(Column $column)
    {
        return $this->formatPostGisType('geometrycollection');
    }

    /**
     * Create the column definition for a spatial MultiPoint type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeMultiPoint(Column $column)
    {
        return $this->formatPostGisType('multipoint');
    }

    /**
     * Create the column definition for a spatial MultiLineString type.
     *
     * @param  Column  $column
     * @return string
     */
    public function typeMultiLineString(Column $column)
    {
        return $this->formatPostGisType('multilinestring');
    }

    /**
     * Create the column definition for a spatial MultiPolygon type.
     *
     * @param  Column  $column
     * @return string
     */
    protected function typeMultiPolygon(Column $column)
    {
        return $this->formatPostGisType('multipolygon');
    }

    /**
     * Format the column definition for a PostGIS spatial type.
     *
     * @param  string  $type
     * @return string
     */
    private function formatPostGisType(string $type)
    {
        return "geography($type, 4326)";
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  Column  $column
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Column $column)
    {
        return $column->nullable ? ' null' : ' not null';
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  Column  $column
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Column $column)
    {
        if (! is_null($column->default)) {
            return ' default '.$this->getDefaultValue($column->default);
        }
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  ColumnInteger  $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, ColumnInteger $column)
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' primary key';
        }
    }
}
