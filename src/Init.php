<?php

namespace LHSeznamczAutocomplete;

/**
 * Description of Init
 *
 * @author Lubos Hilgert <luboshilgert@gmail.com>
 */
final class Init {
    /**
     * Seznam vsech trid
     * @return type
     */
    public static function get_services(){
        $services = [
        Pages\Admin::class,
        Base\Enqueue::class,
        Base\SettingsLink::class,
        Base\Updater::class
        ];

        // Panel v editoru formulare ma smysl jen s aktivnim Contact Form 7.
        if (defined('WPCF7_VERSION')) {
            $services[] = Pages\Cf7Panel::class;
        }

        return $services;
    }
/**
 * Loop skrze classy a inicializace
 */
    public static function register_services(){
        foreach(self::get_services() as $class){
            $service = self::instantiate($class);
            if(method_exists($service, 'register')){
                $service->register();
            }
        }
    } 
    
    private static function instantiate($class){
        $service = new $class;
        return $service;
    }
}
