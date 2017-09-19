<?php
namespace framework\driver\sms;

abstract class Sms
{
    protected $acckey;
    protected $seckey;
    protected $signname;
    protected $template;
    
    /* 
     * 发送短信
     * $to 接受短信手机号
     * $$template 短信模版id
     * $$data 短信内容变量
     */
    abstract public function send($to, $template, $data);
    
    public function __construct(array $config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->signname = $config['signname'];
        $this->template = $config['template'];
    }
}
