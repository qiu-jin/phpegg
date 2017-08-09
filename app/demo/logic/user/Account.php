<?php
namespace app\logic;

class Account
{
    use \framework\core\Getter;
    
    protected $container = [
        'zhihu' => 'db.zhihu',
        'test' => []
    ];
    
    public function getNameById($id)
    {
        return $this->db->select('name')->get($id);
    }
}

