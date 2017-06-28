<?php

include '../../../framework/app.php';

framework\App::boot();

load('captcha', 'image')->output();
