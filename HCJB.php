<?php

// Copyright (C) 2014  Yevhen Matasar
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
 * @version    2014081600
 */

/**
 * HC JSON Bridge
 * Static only
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
        if (isset($_REQUEST['f'])) {
        if (isset($_REQUEST['a'])) {
                $args = str_replace('\"', '"', str_replace('\\\\', '\\', $_REQUEST['a']));
                $args = json_decode($args);
            } else {
                $args = array();
            }
            if (self::$config['secured']) {
                if (isset($_REQUEST['p'])) {
                    if (self::$config['passkey'] === $_REQUEST['p']) {
                        echo json_encode(HCJB::exec($_REQUEST['f'], $args));
                    } else {
                        $a = new stdClass;
                        $a->err = 'Wrong passkey!';
                        echo json_encode($a);
                    }
                } else {
                   throw new Exception("Passkey needed!", 1);
                }
            } else {
                echo json_encode(HCJB::exec($_REQUEST['f'], $args));
            }
        } else {
            echo null;
        }
        exit();
    }
    
    /**
     * Set configuration
     * 
     * @param array Configuration
     */
    public static function config($config = null) {
        !is_array($config) && $config = (array)simplexml_load_file(__DIR__ . '/hcjb.xml');
        $required = array('secured', 'passkey');
        foreach ($required as $value) {
            if (isset($config[$value])) {
                self::$config[$value] = $config[$value];
            } else {
                throw new Exception("{$value} : not found!", 1);
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
     * Send query
     * 
     * @param string Handler url
     * @param string Function name
     * @param array Arguments
     * @param string Passkey (if security is enabled)
     */
    public static function get($url, $function, $args = array(), $passkey = null) {
        !self::$config && self::config();
        self::$config['secured'] && $url .= '?p=' . urlencode(self::$config['passkey']);
        $url .= "&f={$function}";
        $args && $url .= '&a=' . urlencode(json_encode($args));
        if ($a = json_decode(file_get_contents($url))) {
            if (isset($a->err)) {
                throw new Exception($a->err, 1);
            }
            return $a;
        } else {
            return null;
        }
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
            throw new Exception('Value is not callable!', 1);
        }
    }
}

//Adds function 'info' to the script handler
HCJB::addFunction('info', function() {
    $a = new stdClass();
    $a->name = 'HC JSON Bridge';
    $a->version = 2014081600;
    return $a;
});