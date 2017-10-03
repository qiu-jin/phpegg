<?php

include '../../../framework/app.php';

framework\App::boot();

driver('captcha', 'image')->output();
