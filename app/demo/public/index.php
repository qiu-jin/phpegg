<?php

$example_host = 'example.com';

switch ($_SERVER['HTTP_HOST']) {
    case "standard.$example_host":
        include 'index_standard.php';
        break;
    case "rest.$example_host":
        include 'index_rest.php';
        break;
    case "inline.$example_host":
        include 'index_inline.php';
        break;
    case "jsonrpc.$example_host":
        include 'index_jsonrpc.php';
        break;
    case "micro.$example_host":
        include 'index_micro.php';
        break;
    case "grpc.$example_host":
        include 'index_grpc.php';
        break;
    /*case "view.$example_host":
        include 'index_view.php';
        break;*/
    /*case "graphql.$example_host":
        include 'index_graphql.php';
        break;*/
}

