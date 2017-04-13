<?php
namespace framework\driver\sms;

abstract class Sms
{
    abstract public function send($to, $type, $data);
}
