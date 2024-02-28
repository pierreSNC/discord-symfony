<?php

namespace App\Model;

class SearchData
{

    /** @var string **/
    public string $q = '';

    public function __toString()
    {
        return $this->q;
    }
}