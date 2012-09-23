<?php

include 'includes/Request.php';

$request = new Request();

class Marg {
    private static $setUp;
    private static $tearDown;

    public static function addSetUp($func) {
        self::$setUp = $func;
    }

    public static function addTearDown($func) {
        self::$tearDown = $func;
    }

    public static function run($routes) {
        if (isset(self::$setUp)) {
            call_user_func(self::$setUp);
        }

        global $request;
        $controller_name = '';
        $matches = array();
        foreach ($routes as $pattern => $controller) {
            if (preg_match($pattern, $request->uri, $match)) {
                $controller_name = $controller;
                $matches = $match;
                break;
            }
        }

        if (is_array($controller_name)) {
            // By default GET
            $methods = array('GET');

            if (isset($controller_name['controller'])) {
                if (isset($controller_name['methods'])) {
                    $methods = $controller_name['methods'];
                }
                $controller_name = $controller_name['controller'];
            } else {
                if (isset($controller_name[1]) && is_array($controller_name[1])) {
                    $methods = $controller_name[1];
                }
                $controller_name = $controller_name[0];
            }

            if (in_array(strtoupper($request->verb), $methods)) {
                call_user_func_array($controller_name, $matches);
            }
        } elseif (class_exists($controller_name)) {
            $controller = new $controller_name($matches);

            call_user_func_if_exists(array($controller, 'setUp'));
            $method = strtolower($request->verb);
            if (method_exists($controller, $method)) {
                call_user_func_array(array($controller, $method), $matches);
            }
            call_user_func_if_exists(array($controller, 'tearDown'));
        } elseif (function_exists($controller_name)) {
            call_user_func_array($controller_name, $matches);
        } else {
            raise('404');
        }

        if (isset(self::$tearDown)) {
            call_user_func(self::$tearDown);
        }
    }
};

function raise($code) {
    $args = func_get_args();
    $args = array_slice($args, 1);
    call_user_func_if_exists('raise_' . $code, $args);
}

function call_user_func_if_exists($func, $args = array()) {
    $retval = null;
    if (is_array($func)) {
        if (method_exists($func[0], $func[1])) {
            $retval = call_user_func_array(array($func[0], $func[1]), $args);
        }
    } else {
        $retval = call_user_func_array($func, $args);
    }
    return $retval;
}

?>
