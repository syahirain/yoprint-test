<?php
namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use App\Models\File;
use App\Models\Product;

class FileProcessJob implements ShouldQueue
{
    use Queueable;

    private $fileId;

    /**
     * Create a new job instance.
     */
    public function __construct($fileId)
    {
        $this->fileId = $fileId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {   
        $file = File::find($this->fileId);
        $filePath = "uploads/{$file->uuid}.{$file->extension}";
        $fullPath = Storage::disk('local')->path($filePath);

        try{ 
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0); 
            $records = $csv->getRecords();

            foreach ($records as $record) {   
                if(!isset($record['UNIQUE_KEY'])){
                    throw new \Exception("Invalid csv format");
                } 

                Product::updateOrCreate(
                    ['key' => $record['UNIQUE_KEY']],
                    [
                        'title' => $record['PRODUCT_TITLE'],
                        'description' => $record['PRODUCT_DESCRIPTION'],
                        'style' => $record['STYLE#'],
                        'sanmar_mainframe_color' => $record['SANMAR_MAINFRAME_COLOR'],
                        'size' => $record['SIZE'],
                        'color_name' => $record['COLOR_NAME'],
                        'piece_price' => $record['PIECE_PRICE']
                    ]
                );
            }

            $file->status = "completed";
            $file->save();
        } catch(\Exception $e) {
            \Log::error("[FAIL PROCESS][ID:$file->id] ".$e->getMessage());

            $file->status = "error";
            $file->save();
        }
    }

    public function tags()
    {
        return ['process']; // Example tags
    }
}
