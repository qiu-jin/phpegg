<?php

define('APP_DEBUG', true);

$xhprof_dir = '/home/qiujin/www/xhprof/';

include "$xhprof_dir/xhprof_lib/utils/xhprof_lib.php";
include "$xhprof_dir/xhprof_lib/utils/xhprof_runs.php";


xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);


include '../../../framework/app.php';

$app = framework\App::start('Inline', [
    'controller_prefix' => 'controller/inline'
    //'param_mode'    => 2,
]);

framework\core\Hook::add('exit', function() {
    $xhprof_data = xhprof_disable();
    $xhprof_runs = new XHProfRuns_Default();
    $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_test");
    file_put_contents(APP_DIR.'storage/cache/xhprof_test', $run_id);
});

$app->run();
