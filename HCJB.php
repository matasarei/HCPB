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
 * HC PHP Bribge
 * 
 * @package    HCPB
 * @copyright  2014 Yevhen Matasar <matasar.ei@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    2015081102
 */
 
 /**
 * HC PHP Bridge
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
    
    private static $debug = false;
 
    public static function debug($val) {
        self::$debug = (bool)$val;
    }
 
    /**
     * Query handler
     *
     * @return mixed Query result
     */
    public static function query() {
        !self::$config && self::config();

        //get data
        $function = $_REQUEST['f'];
        $args = (array)json_decode(gzinflate(base64_decode($_REQUEST['a'])));
        $passkey = $_REQUEST['p'];

        //debug
        if (!empty($_REQUEST['d'])) {
            var_dump($_REQUEST, $args);
        }

        //check passkey
        if (self::$config['secured'] && $passkey !== md5(self::$config['passkey'])) {
            $a = new stdClass;
            $a->err = 'Wrong passkey!';
            echo json_encode($a);
            return false;
        }

        //execute
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
        if ($strict) {
            self::checkUrl($handler);
        }
        
        //test args
        is_object($args) && $args = (array)$args;
        if (!is_array($args)) {
            throw new InvalidArgumentException('An object or an array expected, ' . gettype($args) . ' given.');
        }
        
        //prepare request
        $request = "{$handler}?f=" . $function;
        self::$debug && $request .= '&d=' . (int)self::$debug;
        
        //passkey
        if (self::$config['secured'] || $passkey) {
            !$passkey && $passkey = self::$config['passkey'];
            $request .= '&p=' . md5($passkey);
        }
        $args && $request .= '&a=' . urlencode(base64_encode(gzdeflate(json_encode($args), 9)));

        //send request
        $response = file_get_contents($request);
        
        if (self::$debug) {
            return $response;
        }
        
        //decode and return
        $response = json_decode($response);
        if (isset($response->err)) {
            throw new Exception($response->err, 1);
        }
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
    $a->name = 'HC PHP Bridge';
    $a->version = 2015081102;
    return $a;
});
