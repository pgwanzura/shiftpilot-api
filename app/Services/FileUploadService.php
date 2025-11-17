<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function uploadMessageAttachment(UploadedFile $file, int $userId): array
    {
        $path = $file->store("messages/{$userId}", 's3');

        return [
            'name' => $file->getClientOriginalName(),
            'url' => Storage::url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }
}
