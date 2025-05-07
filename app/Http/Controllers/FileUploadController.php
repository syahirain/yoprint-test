<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\File;

class FileUploadController extends Controller
{
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'file_chunk' => 'required|file',
            'file_id' => 'required|string',
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
        ]);

        $fileId = $request->input('file_id');
        $chunkIndex = $request->input('chunk_index');
        $totalChunks = $request->input('total_chunks');
        $originalFileName = $request->input('file_name');
        $chunkExt = $request->input('file_ext');

        $chunk = $request->file('file_chunk');

        // Save chunk to storage (local, s3, etc.)
        Storage::disk('local')->putFileAs("chunks/{$fileId}", $chunk, "chunk_{$chunkIndex}");

        // Check if all chunks uploaded
        $chunkFiles = Storage::files("chunks/{$fileId}");
        if (count($chunkFiles) == $totalChunks) {
            File::create([
                'uuid' => $fileId,
                'name' => $originalFileName,
                'extension' => $chunkExt,
                'total_chunks' => $totalChunks,
                'status' => 'new'
            ]);

            return response()->json(['message' => 'Upload complete']);
        }

        return response()->json(['message' => 'Chunk uploaded']);
    }
}
