<?php

namespace App\Http\Controllers;

class DownloadController extends Controller
{
    public function index()
    {
        $apkPath = public_path('downloads/agrilens-field-technician-app.apk');
        $exists = file_exists($apkPath);
        $sizeMb = $exists ? round(filesize($apkPath) / 1024 / 1024, 1) : null;

        return view('download-app', compact('exists', 'sizeMb'));
    }
}
