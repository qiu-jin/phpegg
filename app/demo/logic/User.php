<?php
namespace app\logic;

class User
{
    use \Getter;

    public function getNameById($id)
    {
        return $this->db->user->select('name')->get($id);
    }
}

