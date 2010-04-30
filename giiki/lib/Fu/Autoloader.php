<?php
/**
 * This class handles the auto class loading.
 *
 * If a class name is used with undersbss in it, it will take that to
 * mean sub-directories, e.g.:
 *
 * Geocoder_Yahoo
 *
 * will search the include path for
 *
 * {path}/Geocoder/Yahoo.php
 */
class Fu_AutoloaderException extends Exception { }

class Fu_Autoloader {

    public static function load($className) {
        $path = (strstr($className, '_')) ? str_replace('_', '/', $className) : $className;
        $path.= '.php';

        $paths_to_search = explode(":", ini_get('include_path'));

        foreach ($paths_to_search as $p) {
            if (file_exists($p.'/'.$path)) {
                require_once($p.'/'.$path);
                return;
            }
        }

        throw new Fu_AutoloaderException("Class $className not found");
    }

}

spl_autoload_register(array('Fu_Autoloader', 'load'));