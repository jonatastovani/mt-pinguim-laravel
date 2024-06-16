<?php

namespace App\Tenancy;
use App\Models\Tenancy;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Connect
{

    public function __construct(
        protected readonly Tenancy $tenancy
    ) {
    }

    public function setDefault(): void
    {
        // Config::set('database.connections.tenancy', $this->tenancy->database);
        Config::set('database.connections.mysql.database', $this->tenancy->database);
        DB::purge('mysql'); // Limpar a conexão para garantir que a nova configuração seja usada
    }
}
