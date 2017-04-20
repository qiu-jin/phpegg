<?php
namespace framework\core;

class Job
{
    public function delay(callable $job, array $params = [])
    {
        Hook::add('close', $job, $params);
    }
}
