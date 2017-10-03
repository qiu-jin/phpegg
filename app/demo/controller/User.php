<?php
namespace app\controller;

class User
{
    use \Getter;

    //action路由仅standard rest下有效
    protected $routes = [
        '*/name' => 'getName($1)',
        '*/email' => 'getEmail($1)'
    ];
    
    public function index()
    {
        return $this->db->user->find();
    }
    
    /*
     * 在standard default dispatch下（url path对应控制器和方法）
     * 请求 /User/get 
     *
     * 在rest default dispatch下（HTTP get put post delete请求会自动调用url path控制器类下同名方法）
     * 请求GET /User
     *
     * 在jsonroc下（使用body中的json数据dispatch）
     * 请求POST / {"method":"User.get"}
     *
     * 在grpc下
     * 请求POST /User/get
     */
    public function get($id = 1)
    {
        return $this->db->user->get($id);
    }
    
    public function put($id)
    {
        return $this->db->user->update(\Request::post(), $id);
    }
    
    public function post()
    {
        return $this->db->user->insert([
            'name' => \Request::post('name'),
            'email' => \Request::post('email'),
            'mobile' => \Request::post('mobile')
        ]);
    }
    
    public function delete($id)
    {
        return $this->db->user->delete($id);
    }
    
    public function getName($id)
    {
        return $this->db->user->select('name')->get($id);
    }
    
    public function getNames(...$ids)
    {
        return $this->db->user->select('name')->where('id', 'IN', $ids)->find();
    }
    
    // standard模式下的的route dispatch可以调用protected方法
    protected function getEmail($id)
    {
        return $this->db->user->select('email')->get($id);
    }
}
?>