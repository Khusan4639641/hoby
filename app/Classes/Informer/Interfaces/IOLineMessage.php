<?php

namespace App\Classes\Informer\Interfaces;

interface IOLineMessage
{

    public function info($string);

    public function line($string);

    public function comment($string);

    public function question($string);

    public function error($string);

    public function warn($string);

    public function alert($string);

}
