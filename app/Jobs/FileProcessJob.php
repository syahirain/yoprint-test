<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
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
            if (($handle = fopen($fullPath, 'r')) !== false) {
                $header = fgetcsv($handle); // First row is the header
            
                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($header, $row);
                    //print_r($data);

                    Product::updateOrCreate([
                        ['key' => $data['UNIQUE_KEY']], 
                        [
                            'title' => $data['PRODUCT_TITLE'], 
                            'description' => $data['PRODUCT_DESCRIPTION'],
                            'style' => $data['STYLE#'], 
                            'sanmar_mainframe_color' => $data['SANMAR_MAINFRAME_COLOR'],
                            'size' => $data['SIZE'], 
                            'color_name' => $data['COLOR_NAME'],
                            'piece_price' => $data['PIECE_PRICE']
                        ]
                    ]);
                }
            
                fclose($handle);
            } else {
                throw new \Exception("Failed to open the file.");
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
