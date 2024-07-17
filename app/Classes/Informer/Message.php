<?php

namespace App\Classes\Informer;

class Message
{

    protected $title;
    protected $text;
    protected $tags;

    public function __construct($text, $title = '', $tags = [])
    {
        $this->title = $title;
        $this->text = $text;
        $this->tags = $tags;
    }

}
