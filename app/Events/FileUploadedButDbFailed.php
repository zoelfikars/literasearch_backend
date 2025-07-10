<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileUploadedButDbFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $filePaths;
    public function __construct(array $filePath)
    {
        $this->filePaths = $filePath;
    }
}

