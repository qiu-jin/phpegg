<?php
namespace Model\Demo;

use Core\Model;

class Test extends Model
{
    /*
    protected $resources = array(
        'userdb' => array(
            'type'      => 'db',
            'driver'    => 'pdo',
            'host'      => '127.0.0.1',
            'user'      => 'root',
            'passwd'    => '',
            'name'      => 'vidonme',
        ),
    );

	public function  get_user($user,$type = 'id')
    { 
        $type = in_array($type , ['id', 'email', 'phone', 'username']) ? $type : 'username';
        return $this->db->select('user', '*', [$type => $user]);
	}
    */
    public function  get_user($id)
    {
        return $this->db->table('test1')->get($id);
    }
    
}
?>