<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class DropTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'droptables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forcibly drops all tables in the database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $db = env('DB_DATABASE');
        $colname = sprintf('Tables_in_%s', $db);

        $tables = DB::select('SHOW TABLES');
        $droplist = [];
        foreach ($tables as $table) {
            $droplist[] = $table->$colname;
        }

        if (empty($droplist)) {
            return;
        }

        $droplist = implode(',', $droplist);

        DB::beginTransaction();
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::statement(sprintf('DROP TABLE %s', $droplist));
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        DB::commit();
    }
}
