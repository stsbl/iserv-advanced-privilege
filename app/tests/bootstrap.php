<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\ErrorHandler;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

// allows iservmake tests cache clearing at any time, especially after failing previously
umask(0000);

// https://github.com/symfony/symfony/issues/53812#issuecomment-1962740145
set_exception_handler([new ErrorHandler(), 'handleException']);
