<?php

namespace Illuminate\Database\DBAL;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Types\PhpDateTimeMappingType;
use Doctrine\DBAL\Types\Type;

class TimestampType extends Type implements PhpDateTimeMappingType
{
    /**
     * {@inheritdoc}
     *
     * @throws DBALException
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return match (get_class($platform)) {
            MySQLPlatform::class,
            MySQL80Platform::class,
            MariaDBPlatform::class => $this->getMySqlPlatformSQLDeclaration($column),
            PostgreSQLPlatform::class => $this->getPostgresPlatformSQLDeclaration($column),
            SQLServerPlatform::class => $this->getSqlServerPlatformSQLDeclaration($column),
            SqlitePlatform::class => 'DATETIME',
            default => throw new DBALException('Invalid platform: '. substr(strrchr(get_class($platform), '\\'), 1)),
        };
    }

    /**
     * Get the SQL declaration for MySQL.
     *
     * @param  array  $column
     * @return string
     */
    protected function getMySqlPlatformSQLDeclaration(array $column): string
    {
        $columnType = 'TIMESTAMP';

        if ($column['precision']) {
            $columnType = 'TIMESTAMP('.min((int) $column['precision'], 6).')';
        }

        $notNull = $column['notnull'] ?? false;

        if (! $notNull) {
            return $columnType.' NULL';
        }

        return $columnType;
    }

    /**
     * Get the SQL declaration for PostgreSQL.
     *
     * @param  array  $column
     * @return string
     */
    protected function getPostgresPlatformSQLDeclaration(array $column): string
    {
        return 'TIMESTAMP('.min((int) $column['precision'], 6).')';
    }

    /**
     * Get the SQL declaration for SQL Server.
     *
     * @param  array  $column
     * @return string
     */
    protected function getSqlServerPlatformSQLDeclaration(array $column): string
    {
        return $column['precision'] ?? false
            ? 'DATETIME2('.min((int) $column['precision'], 7).')'
            : 'DATETIME';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'timestamp';
    }
}
