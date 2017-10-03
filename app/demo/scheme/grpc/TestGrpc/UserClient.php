<?php
// GENERATED CODE -- DO NOT EDIT!

namespace TestGrpc;

/**
 */
class UserClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \TestGrpc\UserGetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function get(\TestGrpc\UserGetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/TestGrpc.User/get',
        $argument,
        ['\TestGrpc\UserGetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \TestGrpc\UserCreateRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function create(\TestGrpc\UserCreateRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/TestGrpc.User/create',
        $argument,
        ['\TestGrpc\UserCreateResponse', 'decode'],
        $metadata, $options);
    }

}
