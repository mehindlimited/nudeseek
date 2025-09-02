<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function index()
    {
        // Create some sample content
        $content = "Test file created on " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "This is a test file to verify FTP storage is working.";

        // Generate a unique filename
        $filename = 'test_' . time() . '.txt';

        // Save file to FTP disk
        Storage::disk('ftp')->put($filename, $content);

        // Verify the file was created
        if (Storage::disk('ftp')->exists($filename)) {
            return "File '{$filename}' successfully saved to FTP storage!";
        }

        return "Failed to save file to FTP storage.";
    }
}
