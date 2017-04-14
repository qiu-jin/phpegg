<?php

include __DIR__.'/../framework/app.php';

framework\App::init('test');

load('captcha', 'image')->response();
