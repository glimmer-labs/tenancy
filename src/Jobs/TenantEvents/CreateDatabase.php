<?php

namespace Glimmer\Tenancy\Jobs\TenantEvents;

use Glimmer\Tenancy\Jobs\Concerns\TenantEventQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CreateDatabase extends TenantEventQueue
{
    public function handle(): void
    {
        $databaseName = $this->tenant->getDatabaseName();

        $databaseConnection = $this->tenant->database_connection ?? Config::get('multitenancy.tenant_database_connection_name') ?? Config::get('database.default');

        switch (Config::get("database.connections.$databaseConnection.driver")) {
            case 'sqlite':
                $databasePath = database_path($databaseName.'.sqlite');

                if (! file_exists($databasePath)) {
                    touch($databasePath);
                }
                break;
            case 'pgsql':
                if (DB::selectOne(
                    /** @lang PostgreSQL */ 'SELECT 1 FROM pg_database WHERE datname = ?',
                    [$databaseName])
                ) {
                    break;
                }

                DB::disconnect();
                DB::connection()->statement("CREATE DATABASE $databaseName");
                break;
            case 'sqlsrv':
                DB::connection()->statement(/** @lang TSQL */ "
                    IF NOT EXISTS
                        (SELECT name FROM sys.databases WHERE name = '{$databaseName}')
                    BEGIN
                        CREATE DATABASE {$databaseName};
                    END;
                ");
                break;
            default:
                DB::connection()->statement(/** @lang MySQL */ "
                    CREATE DATABASE IF NOT EXISTS `{$databaseName}`
                ");
                break;
        }
    }
}
