<?php
if (file_exists(ROOT . 'env.php')) {
    include_once(ROOT . 'env.php');
    include_once(ROOT . 'classes/vendor/autoload.php');
    if (function_exists('Sentry\init')) {
        Sentry\init(['dsn' => $sentryDsn]);
        define('SENTRY_EXISTS',true);
    } else {
        define('SENTRY_EXISTS',false);
    }
}
