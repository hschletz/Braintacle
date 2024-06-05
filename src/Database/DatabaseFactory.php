<?php

namespace Braintacle\Database;

use Laminas\Db\Adapter\Adapter;
use Nada\Column\AbstractColumn as Column;
use Nada\Database\AbstractDatabase;
use Nada\Factory;

class DatabaseFactory
{
    public function __construct(private Factory $factory, private Adapter $adapter)
    {
    }

    public function __invoke(): AbstractDatabase
    {
        $database = ($this->factory)($this->adapter);
        $database->setTimezone();
        if ($database->isMysql()) {
            $database->emulatedDatatypes = [Column::TYPE_BOOL];
        } elseif ($database->isSqlite()) {
            $database->emulatedDatatypes = [
                Column::TYPE_BOOL,
                Column::TYPE_DATE,
                Column::TYPE_DECIMAL,
                Column::TYPE_TIMESTAMP,
            ];
        }

        return $database;
    }
}
