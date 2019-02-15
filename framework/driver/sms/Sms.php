<?php
namespace framework\driver\sms;

abstract class Sms
{
    protected $acckey;
    protected $seckey;
    protected $signname;
    protected $template;
    
    public function __construct(array $config)
    {
        $this->acckey   = $config['acckey'];
        $this->seckey   = $config['seckey'];
        $this->template = $config['template'];
        if (isset($config['signname'])) {
            $this->signname = $config['signname'];
        }
    }
    
    /* 
     * 发送短信
     * $to 接受短信手机号
     * $template 短信模版id
     * $data 短信内容变量
     * $ignname 短信签名
     */
    public function send($to, $template, $data, $signname = null)
    {
        if (isset($this->template[$template])) {
            return $this->handle($to, $template, $data, $signname);
        }
        return error('Template not exists');
    }
    
    /* 
     * 短信发送处理
     */
    abstract protected function handle($to, $template, $data);
}
