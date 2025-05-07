<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\File;

class SSEController extends Controller
{
    public function streamFileData()
    {
        // Set the appropriate headers for SSE
        $response = new StreamedResponse(function () {
            while (true) {
                $files = File::all();
                $data = json_encode($files);

                echo "data: $data\n\n";

                // Flush the output buffer
                ob_flush();
                flush();

                // Delay for 1 second
                sleep(3);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}