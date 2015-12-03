<?php

namespace FARSymfony2UploadBundle\Lib;

class FileUploadWrapper
{
    protected $options;

    public function __construct($options = null)
    {
        $this->options = $options;
    }
}
