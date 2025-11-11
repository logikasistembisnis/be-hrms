<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\HariLiburNasionalController;

class SyncHariLiburCommand extends Command
{
    protected $signature = 'harilibur:sync';
    protected $description = 'Sinkronisasi data hari libur nasional dari API publik';

    public function handle()
    {
        $controller = new HariLiburNasionalController();
        $result = $controller->syncHariLiburData();
        $this->info($result['message']);
    }
}
