<?php

namespace App\Services\Mobile;

use App\Models\User;
use App\Models\V3\OtpEnterCodeAttempts;
use Carbon\Carbon;

class OtpService
{
    private string $phone = '';
    private string $code = '';
    private $record = null;

    public function __construct($phone, $code)
    {
        $this->phone = $phone;
        $this->code = $code;
        $this->validate();
    }

    private function validate()
    {
        $this->phone = correct_phone($this->phone);
    }

    public function save_record(): void
    {
        $this->record = OtpEnterCodeAttempts::where('phone', $this->phone)->first();
        if (isset($this->record)) {
            $this->update();
        } else {
            $this->create();
        }
    }

    private function create(): void
    {
        $user = User::where('phone', $this->phone)->first();
        if (isset($user)) {
            OtpEnterCodeAttempts::create([
                'phone' => $this->phone,
                'user_id' => $user->id,
                'code' => $this->code,
                'attempts' => 0
            ]);
        } else {
            OtpEnterCodeAttempts::create([
                'phone' => $this->phone,
                'user_id' => 0,
                'code' => $this->code,
                'attempts' => 0
            ]);
        }
    }

    private function update(): void
    {
        $this->record->update([
            'code' => $this->code,
            'attempts' => 0,
            'updated_at' => Carbon::now()
        ]);
    }

}
