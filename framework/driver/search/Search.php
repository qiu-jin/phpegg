<?php
namespace framework\driver\search;

use framework\core\http\Client;

abstract class Search
{
    public function __get($index)
    {
        return new Query($this, $index);
    }
    
    public function index($index)
    {
        return new Query($this, $index);
    }
}
