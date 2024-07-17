<?php

namespace App\Jobs\MkoJobs;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class MkoZip
{
    private ?ZipArchive $archiver = null;
    private string      $zippedFileName;
    private string      $savePath;

    public function __construct(string $path)
    {
        $this->zippedFileName = md5(time() . random_int(1000, 9999)) . '.zip';
        $this->savePath       = $path . '/' . $this->zippedFileName;
        $this->archiver       = new ZipArchive();
    }

    public function saveFile(string $path): void
    {
        if (app()->environment('local')) {
            $disk = Storage::disk('local');
        } else {
            $disk = Storage::disk('sftp');
        }
        $disk->putFileAs($path, storage_path('app/temp.zip'), $this->zippedFileName);
        Storage::disk('local')->delete('temp.zip');
    }

    public function getArchiver(): ZipArchive
    {
        return $this->archiver;
    }

    public function getSavePath(): string
    {
        return $this->savePath;
    }
}
