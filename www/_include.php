<?php

// nginx doesn't populate the $_SERVER['PATH_INFO'] variable which is used a lot
// in this package. The below hack populates the value to make it work.
// @see https://github.com/simplesamlphp/simplesamlphp/issues/5
if (empty($_SERVER['PATH_INFO'])) {
  $_SERVER['PATH_INFO'] = '';

  $matches = array();
  if (preg_match("/^(.+\/module\.php)(\/.+)$/", $_SERVER['REQUEST_URI'], $matches)) {
    $pathinfo = $matches[2];

    $matches = array();
    if (preg_match("/(.*)\?(.*)/", $pathinfo, $matches)) {
      $pathinfo = $matches[1];
    }

    $_SERVER['PATH_INFO'] = $pathinfo;
  }
}

// Below fixes an issue with lando edge urls being redirected with :80 appended.
// Eg: https://site:80. The below hack forces the correct port.
// @see: https://github.com/simplesamlphp/simplesamlphp/issues/450
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] = 'on') {
  $_SERVER['SERVER_PORT'] = '443';
}

// initialize the autoloader
require_once(dirname(dirname(__FILE__)).'/lib/_autoload.php');

// enable assertion handler for all pages
SimpleSAML_Error_Assertion::installHandler();

// show error page on unhandled exceptions
function SimpleSAML_exception_handler($exception)
{
    SimpleSAML\Module::callHooks('exception_handler', $exception);

    if ($exception instanceof SimpleSAML_Error_Error) {
        $exception->show();
    } elseif ($exception instanceof Exception) {
        $e = new SimpleSAML_Error_Error('UNHANDLEDEXCEPTION', $exception);
        $e->show();
    } else {
        if (class_exists('Error') && $exception instanceof Error) {
            $code = $exception->getCode();
            $errno = ($code > 0) ? $code : E_ERROR;
            $errstr = $exception->getMessage();
            $errfile = $exception->getFile();
            $errline = $exception->getLine();
            SimpleSAML_error_handler($errno, $errstr, $errfile, $errline);
        }
    }
}

set_exception_handler('SimpleSAML_exception_handler');

// log full backtrace on errors and warnings
function SimpleSAML_error_handler($errno, $errstr, $errfile = null, $errline = 0, $errcontext = null)
{
    if (!class_exists('SimpleSAML\Logger')) {
        /* We are probably logging a deprecation-warning during parsing. Unfortunately, the autoloader is disabled at
         * this point, so we should stop here.
         *
         * See PHP bug: https://bugs.php.net/bug.php?id=47987
         */
        return false;
    }

    if (SimpleSAML\Logger::isErrorMasked($errno)) {
        // masked error
        return false;
    }

    static $limit = 5;
    $limit -= 1;
    if ($limit < 0) {
        // we have reached the limit in the number of backtraces we will log
        return false;
    }

    // show an error with a full backtrace
    $e = new SimpleSAML_Error_Exception('Error '.$errno.' - '.$errstr);
    $e->logError();

    // resume normal error processing
    return false;
}

set_error_handler('SimpleSAML_error_handler');

try {
    SimpleSAML_Configuration::getInstance();
} catch (Exception $e) {
    throw new \SimpleSAML\Error\CriticalConfigurationError(
        $e->getMessage()
    );
}

// set the timezone
SimpleSAML\Utils\Time::initTimezone();
