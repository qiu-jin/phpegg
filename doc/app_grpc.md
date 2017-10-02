说明
----
此应用模式目前在试验阶段，只支持简单的[grpc协议](https://grpc.io/)，另外需要配合使用nginx的ngx_http_v2_module来支持http2（虽然在php层没强制要求http2，但是要使用grpc官方提供的各语言client的话则是需要支持http2的）

实现grpc应用模式初衷是因为grpc官方没有提供php版的server，为了测试client自己模仿协议实现一个简单的grpc server（http2也是http，php实现也简单）。
但是后面考虑到grpc使用并不广泛，用php实现并没多大优势，而且http2也会提高使用门槛，在php的的一次请求响应一个进程处理模型下http2的多路复用等特性也没多大优势，甚至还可能有劣势（http2强制要求ssl），所以grpc模式考虑实现成一个兼容grpc协议但不强制要求http2的应用模式。


入口
----
```php
include '../../../framework/app.php';

framework\App::start('Grpc', [
    'service_schemes'   => [
        'prefix'    => [
            'TestGrpc' => '../scheme/grpc/TestGrpc/'
        ],
        'map'       => [
            'GPBMetadata\User' => '../scheme/grpc/GPBMetadata/User'
        ]
    ]
])->run();
```

配置
----
```php
[
    // 控制器namespace
    'controller_ns'     => 'controller',
    // 控制器类名后缀
    'controller_suffix' => null,
    /* 参数模式
     * 0 键值参数模式
     * 1 request response 参数模式
     */
    'param_mode'        => 0,
    // 服务定义文件
    'service_schemes'   => null,
    
    'request_scheme_format' => '{service}{method}Request',
    
    'response_scheme_format' => '{service}{method}Response',
]
```

调度
----
grpc应用调度规则较简单，仍以url_path来匹配控制类和方法，当grpc client发送一个请求时，它会向/namespace.namespace.Class/method形式的url_path发送一个protobuf message请求，此url_path的请求就会默认调用app\controller\namespace\namespace\Class::method()方法。

参数
----
支持2种参数模式

service_schemes配置定义grpc应用所需要服务描述文件（protoc自动生成的PHP文件）的加载方式。

> ./protoc --proto_path=./  --php_out=./ --grpc_out=./  --plugin=protoc-gen-grpc=./grpc_php_plugin ./User.proto

```php
'service_schemes'   => [
    'prefix'    => [
        'TestGrpc' => '../scheme/grpc/TestGrpc/'
    ],
    'map'       => [
        'GPBMetadata\User' => '../scheme/grpc/GPBMetadata/User'
    ]
]
```
proto文件

```
syntax = "proto3";

package TestGrpc;

service User {
  rpc get (UserGetRequest) returns (UserGetResponse) {}
  
  rpc create (UserCreateRequest) returns (UserCreateResponse) {}
}

message UserGetRequest {
  int32 id = 1;
}

message UserGetResponse {
  int32 id = 1;
  string name = 2;
  string email = 3;
  string mobile = 4;
}

message UserCreateRequest {
  string name = 1;
  string email = 2;
  string mobile = 3;
}

message UserCreateResponse {
  int32 id = 1;
}

```

1 键值参数模式

在键值参数模式下，request_scheme_format和response_scheme_format配置定义了request message scheme和response message scheme类名的格式，并将请求的protobuf数据绑定到request message类实例，然后从request message类实例抽取field值，以键值对形式传给控制器方法。

> 如request_scheme_format为{service}{method}Request时
> 
> 请求 /TestGrpc.User/get, 会将protobuf数据绑定到TestGrpc\UserGetRequest类实例
> 
> UserGetRequest中有一个field id，从中抽取id的值
> 
> 然后传给app\controller\ TestGrpc\User::get($id)方法
> 
> 方法return一个数组，处理器将数组绑定UserGetResponse类实例

```php
public function get ($id)
{
	return $this->db->user->get($id);
}
```


2 request response参数模式

在此参数模式下，控制器方法接收2个参数，参数分别为request message类实例和response message类实例，调用器会使用反射来获取request message和response message类名，然后实例化传给控制器方法，方法return返回response message类实例。

```php
public function get (\TestGrpc\UserGetRequest $request, \TestGrpc\UserGetResponse $response)
{
	$id = request->getId();
	$user = $this->db->user->get($id);
	$response->setId($id);
	$response->setName($user['name']);
	$response->setEmail($user['email']);
	$response->setMoblie($user['moblie']);
	return $response;
}
```

响应
----
设置grpc-status header头值为0（0为成功）
将response message类实例数据编码为protobuf格式二进制数据放在body发送给请求者。

> 注意grpc协议中request和response的二进制数据前5个字节用来描述protobuf数据，而不是protobuf数据本身的，这5个字节中第1个字节用来表示数据编码方式（默认为0），2-5字节用来表示protobuf数据大小（大端排序）

错误
----
按照grpc协议把错误码$code通过grpc-status header头，错误信息$message通过grpc-message header头发送给请求者，body为空。






