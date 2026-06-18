<?php

namespace App\Modules\Admin\File\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileService
{
    public function fileManager()
    {
        $dirs = Storage::disk("products")->directories();
        $files = Storage::disk("products")->files();
        return [
            "directories" => $dirs,
            "files" => $files
        ];
    }

    public function upload(Request $request)
    {
        $file = Storage::disk('products')
            ->putFileAs('/', $request->file('file'), $request->file('file')->getClientOriginalName());
        return Storage::url($file);
    }
}
