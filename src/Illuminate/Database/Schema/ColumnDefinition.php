<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

/**
 * Class ColumnDefinition.
 * @method ColumnDefinition after(string $column) Place the column "after" another column (MySQL)
 * @method ColumnDefinition autoIncrement() Set INTEGER columns as auto-increment (primary key)
 * @method ColumnDefinition charset(string $charset) Specify a character set for the column (MySQL)
 * @method ColumnDefinition collation(string $collation) Specify a collation for the column (MySQL/SQL Server)
 * @method ColumnDefinition comment(string $comment) Add a comment to the column (MySQL)
 * @method ColumnDefinition default(mixed $value) Specify a "default" value for the column
 * @method ColumnDefinition first(string $column) Place the column "first" in the table (MySQL)
 * @method ColumnDefinition nullable($value = true) Allow NULL values to be inserted into the column
 * @method ColumnDefinition storedAs($expression) Create a stored generated column (MySQL)
 * @method ColumnDefinition unique() Add a unique index
 * @method ColumnDefinition unsigned() Set the INTEGER column as UNSIGNED (MySQL)
 * @method ColumnDefinition useCurrent() Set the TIMESTAMP column to use CURRENT_TIMESTAMP as default value
 * @method ColumnDefinition virtualAs(string $expression) Create a virtual generated column (MySQL)
 */
class ColumnDefinition extends Fluent
{
    //
}
