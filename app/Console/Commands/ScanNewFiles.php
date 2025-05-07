<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\File;
use App\Jobs\FileMergerJob;
use App\Jobs\FileProcessJob;

class ScanNewFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scan-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan all new files and run FileMergerJob to merge chunks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = File::where('status', 'new')->get();

        foreach ($files as $file) {
            $file->status = 'pending';
            $file->save();
            
            FileMergerJob::dispatch($file->id);
        }

        $files = File::where('status', 'ready')->get();

        foreach ($files as $file) {
            $file->status = 'processing';
            $file->save();
            
            FileProcessJob::dispatch($file->id);
        }
    }
}
