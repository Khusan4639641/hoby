<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\ReportFile;
use App\Helpers\EncryptHelper;

use App\Classes\Reports\Exports\DebtorsExport;

class AppendRowsToFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $export_model;
    protected $offset;
    protected $limit;
    protected $temp_file_path;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($export_model, $offset, $limit, $temp_file_path)
    {
        $this->export_model = $export_model;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->temp_file_path = $temp_file_path;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $csv = fopen($this->temp_file_path, 'a');

        $partial_result = $this->export_model->query(false, $this->offset, $this->limit)->cursor();

        foreach ($partial_result as $row) {

            $modelAsArray = json_decode(json_encode($row), true);

            if (isset($modelAsArray['buyer_pinfl']) && !empty($modelAsArray['buyer_pinfl'])) {
                $modelAsArray['buyer_pinfl'] = EncryptHelper::decryptData($modelAsArray['buyer_pinfl']) ?: '';
            }

            if (isset($modelAsArray['buyer_passport_number']) && !empty($modelAsArray['buyer_passport_number'])) {
                $modelAsArray['buyer_passport_number'] = EncryptHelper::decryptData($modelAsArray['buyer_passport_number']) ?: '';
            }

            /*$modelAsArray['buyer_pinfl'] = 't2nrUlnSvZn7FoXULhmrFeICD4rGvvDVX7ElR8lwgWVz70BcUk2CybrflGAlEiK+tbLgKrP6Bk9wUuTSOUYoK6o3AUoP36YpatFfheE6wtno+M6BVYxl4mbDur+EdSCvqrnpGF8nuyU8vWxeE/ZhCheU7sM/U2NgaHUCE8NNDDl5VXZZWtqPmOZAM8PDdED+nTC/pb0moReimRS/W6cLX8VkYchqxzoNUHnGOSzXrda2wv9tNoDBMkyKKeCZtXhYwME7/K9obU0NiBdh1QWBvEAsiBT/R7kEruzDXaRfuIkGxZzWM6dR4t2i/kM1SGQbJAi67uguUE8BTVqtNg7MUSh6taT5EsAdggMm52IHoyBKT6GCHbFwwNzD9ZcTP7bXHgZWe8AfsAjH/r/JC8JxKKXe8bYg6vVMEEsyQ3PFmAYczckcFVMYsrpFSk+lmXvApcjUU9E1r8/cAS2DDFIJZy3NtcBS44mRukNJDyDBBXPPQ1gwX/rTKS0ISdwfyXJFpfezSw8ZHxzXL89ZIc+WC9VYCBWFreTJohzgV4fH68DvFzpUaL1n2xu9ZnXYQxNuWYlt6bFLl0yyB77NBXO8HdGX6B3dIioKtg6g26THxAiMUHv2LnRJAMIirxOEL+SVRRMERyoGuCDYyOfmX6nKczDS8pQu1HKP+SRjwpl0y1Q=';
            $modelAsArray['buyer_passport_number'] = 'tsAOWS70kPVOkAvI9AUrlDZwVsOKkyqrc5un3JHdEi094cToga5FcCJ2x5bLsMAEvAD6ddqLF1JrPs9ZrYAcaoW3QIJ0v5R03Q5ZP38IiLlrWPTqYQtcBnzz701IYPYl6j1jRr0io91hrUM+/t5Zc02ZBJogWCSipkhEOmVNSnQyHjnqD3tkwkNa6UCd5Y5DJiKb5la/GsIlppOM4uXZdcAj/SZ5nMRakotf/+OJTLLG3BhZEqwJRkG6HoXfsZN3ZyU6aYbqAebHX+0H87ICIPyDvmXyD7XhiYEHgGz2MNTwYpuCQGmWZ/VIA/Vsj6+/T5Oc6NbnGg9b2ntSISO6bs4S7pBQ/EqW/PsnryDEDxCNnb5T65UYqnHKjuW/KHr5WkufYrnnRbUt31VS6ksIh53t5Yp9y30dl3bstrFFrfJoG8WIrEsu6KgnrunghKwqAEXH+S0dK69xeya/ibrh2TT2gdiCTBLPUg0I54ruoZHuc9g6Q+EFVbKnOrPQCcxvHQ3QegenDrqIodnw3NYnGM4dzToKjmIMeeIKtkKqq9PSC8h9ZujwlYDDVAFtS3lVMZcWqKJ40Zenw5GMYsH0kIVDfMwTFjX46suVcjSf6RAlr4iBzBg5iSOtK/X36QMvDQETy+oFSPSkKFmxaK2+WcbaYM4RGmsSvgxm6bxd61A=';
            $modelAsArray['buyer_pinfl'] = EncryptHelper::decryptData($modelAsArray['buyer_pinfl']) ?: '';
            $modelAsArray['buyer_passport_number'] = EncryptHelper::decryptData($modelAsArray['buyer_passport_number']) ?: '';*/

            fputcsv($csv, $modelAsArray);
        }

        fclose($csv);
    }
}
