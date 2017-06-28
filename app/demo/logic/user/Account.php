<?php
namespace app\logic;

use framework\core\Container;

class Account extends Container
{
    public function getNameById($id)
    {
        return $this->db->select('name')->get($id);
    }
}

