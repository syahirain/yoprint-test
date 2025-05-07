<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use App\Models\File;

class FileMergerJob implements ShouldQueue
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

        try{
            $finalPath = "uploads/{$file->uuid}.{$file->extension}";
            Storage::put($finalPath, '');  // Create an empty file first if needed
            
            // Sort and merge chunks
            for ($i = 0; $i < $file->total_chunks; $i++) {
                $chunkFile = "chunks/{$file->uuid}/chunk_{$i}";
                
                // Check if the chunk exists before merging
                if (Storage::exists($chunkFile)) {
                    // Append each chunk directly to the final file
                    $chunkData = Storage::get($chunkFile);
                    Storage::append($finalPath, $chunkData);  // This appends the chunk to the final file
                } else {
                    throw new \Exception("Missing chunk: {$chunkFile}");
                }
            }

            $file->status = "ready";
            $file->save();

            // Optionally clean up
            Storage::deleteDirectory("chunks/{$file->uuid}");
        } catch(\Exception $e) {
            \Log::error("[FAIL MERGE][ID:$file->id] ".$e->getMessage());

            $file->status = "error";
            $file->save();
        }
    }

    public function tags()
    {
        return ['merge']; // Example tags
    }
}
