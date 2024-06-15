<?php

namespace App\Console\Commands;

use App\Models\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\text;

class TenancyCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenancy:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with a specific database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = text('What is the name of your tenancy?');
        if (!$name) {
            $this->error('Name is required.');
            return;
        }
        $domain = text("What is the domain of your tenancy? Enter to use " . strtolower($name) . ".jetete.test");

        if (!$domain) {
            $domain = strtolower($name) . '.jetete.test';
        }

        $database = 'jetete_' . explode('.', $domain)[0];

        // Conectando ao banco de dados padrão
        $defaultDatabase = Config::get('database.connections.mysql.database');
        Config::set('database.connections.mysql.database', 'information_schema');
        DB::purge('mysql'); // Limpar a conexão para garantir que a nova configuração seja usada

        try {
            // Verificando se o banco de dados existe
            $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$database]);

            if (!empty($databaseExists)) {
                $this->info('The database already exists.');
            } else {
                $this->info('Creating the database.');

                // Iniciando a transação no banco de dados principal
                DB::beginTransaction();

                try {
                    // Criando o banco de dados
                    DB::statement("CREATE DATABASE `$database`");
                    $this->info("Database '$database' created successfully.");

                    // Mudando a configuração para o novo banco de dados
                    Config::set('database.connections.mysql.database', $database);
                    DB::purge('mysql'); // Limpar a conexão para garantir que a nova configuração seja usada

                    $this->info("Running migrations.");
                    Artisan::call('migrate', ['--database' => 'mysql']);
                    $this->info("Migrations completed.");

                    $this->info("Adding the record to the 'tenancies' table.");
                    // Usando a conexão tenancy para criar a entrada no banco de dados principal tenancy
                    Tenancy::on('tenancy')->create([
                        'name' => $name,
                        'domain' => $domain,
                        'database' => $database
                    ]);
                    $this->info("Record in the 'tenancies' table completed.");

                    // Commit da transação no banco de dados principal
                    DB::commit();
                } catch (\Exception $e) {
                    // Rollback da transação no banco de dados principal
                    DB::rollBack();
                    // Drop the newly created tenant database in case of failure
                    DB::statement("DROP DATABASE IF EXISTS `$database`");
                    $this->error('Sorry, an error occurred: ' . $e->getMessage());
                    $this->error("Database '$database' has been deleted.");
                }
            }
        } catch (\Exception $e) {
            $this->error('An error occurred while checking the database: ' . $e->getMessage());
        } finally {
            // Restaurando a configuração original do banco de dados
            Config::set('database.connections.mysql.database', $defaultDatabase);
            DB::purge('tenancy'); // Limpar a conexão para garantir que a configuração original seja usada
        }
    }
}
