<?php
// Copyright (C) 2015  Yevhen Matasar
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.


/**
 * HC JSON Bribge
 *
 * @package    HCJB
 * @copyright  2014 Yevhen Matasar <matasar.ei@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    2015070800
 */

 /**
 * HC JSON Bridge
 * static only
 */
class HCJB {
    private function __construct() { }
    private function __clone() { }

    /**
     * @var array Added functions
     */
    private static $functions = array();

    /**
     * @var array Script configuration
     */
    private static $config = null;

    /**
     * Query handler
     *
     * @return mixed Query result
     */
    public static function query() {
        !self::$config && self::config();
        $function = $_REQUEST['f'];
        $args = (array)json_decode(base64_decode($_REQUEST['a']));
        $passkey = base64_decode($_REQUEST['p']);

        if (self::$config['secured'] && $passkey !== md5(self::$config['passkey'])) {
            $a = new stdClass;
            $a->err = 'Wrong passkey!';
            echo json_encode($a);
            return false;
        }

        echo json_encode(self::exec($function, $args));
        return true;
    }

    /**
     * Set configuration
     *
     * @param array Configuration
     */
    public static function config(array $config = array()) {
        !$config && $config = (array)json_decode(file_get_contents(__DIR__ . '/hcjb.json'));
        $required = array('secured', 'passkey');
        foreach ($required as $value) {
            if (isset($config[$value])) {
                self::$config[$value] = $config[$value];
            } else {
                throw new \Exception("{$value} : not found!", 1);
            }
        }
    }

    /**
     * Execute query
     *
     * @param string Function name
     * @param array Arguments
     */
    private static function exec($name, $args) {
        if (array_key_exists($name, self::$functions)) {
            $func = self::$functions[$name];
            return $func($args);
        } else {
            $a = new stdClass();
            $a->err = 'Wrong function name';
            return $a;
        }
    }

    /**
     * @param string URL
     */
    private static function checkUrl($url, $strict = true) {
        $result = (bool)preg_match("/^https?:\/\/[a-z0-9.\/-]*.php$/ui", $url);
        if ($strict && !$result) {
            throw new \Exception("Wrong URL!", 1);
        }
        return $result;
    }

    /**
     * Send query
     *
     * @param string Handler url
     * @param string Function name
     * @param array Arguments
     * @param string Passkey (if security is enabled)
     */
    public static function get($handler, $function, $args = array(), $passkey = null, $strict = true) {
        !self::$config && self::config();

        //check handler URL
        self::checkUrl($handler);

        //test args
        is_object($args) && $args = (array)$args;
        if (!is_array($args)) {
            throw new InvalidArgumentException('An object or an array expected, ' . gettype($args) . ' given.');
        }

        $request = "{$handler}?f={$function}";

        if (self::$config['secured'] || $passkey) {
            !$passkey && $passkey = self::$config['passkey'];
            $request .= '&p=' . base64_encode(md5($passkey));
        }

        $request .= '&a=' . base64_encode(json_encode($args));

        $response = file_get_contents($request);

        return $response;
    }

    /**
     * Remove function from the handler
     * @param string Function name
     */
    public static function removeFunction($name) {
        if (array_key_exists($name, self::$functions)) {
            unset(self::$functions[$name]);
            return true;
        } else return false;
    }

    /**
     * Add function to the handler
     * @param string Function name
     * @param callable Anonymous function
     */
    public static function addFunction($name, $function) {
        if (is_callable($function)) {
            self::$functions[$name] = $function;
        } else {
            throw new \Exception('Value is not callable!', 1);
        }
    }
}


//Adds function 'info' to the script handler
HCJB::addFunction('info', function() {
    $a = new stdClass();
    $a->name = 'HC JSON Bridge';
    $a->version = 20150708;
    return $a;
});