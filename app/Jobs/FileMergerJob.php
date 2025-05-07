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
            
            // Sort and merge chunks
            for ($i = 0; $i < $file->total_chunks; $i++) {
                $chunkFile = "chunks/{$file->uuid}/chunk_{$i}";
                
                // Check if the chunk exists before merging
                if (Storage::exists($chunkFile)) {
                    $chunkData = Storage::get($chunkFile);

                    if($i === 0){
                        Storage::put($finalPath, $chunkData);
                    }else{
                        // Append each chunk directly to the final file
                        Storage::append($finalPath, $chunkData);  // This appends the chunk to the final file
                    }
                } else {
                    throw new \Exception("Missing chunk: {$chunkFile}");
                }
            }
            
            // Clean up any non-UTF-8 characters
            $originalContent = Storage::get($finalPath);
            $convertedContent = mb_convert_encoding($originalContent, 'UTF-8', 'UTF-8');
            Storage::put($finalPath, $convertedContent);

            $file->status = "ready";
            $file->save();

            // Delete chunk files permanently
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
