<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 27.01.2016
 * Time: 14:26
 */

namespace Caching;

use Caching\CacheProviders\FilesCacheProvider;
use Closure;

class Cache {

    /**
     * @var AbstractCacheProvider[]
     */
    private static $storages = array();

    /**
     * @var AbstractCacheProvider
     */
    private static $activeStorage;

    private static $disabled = false;


    /**
     * @return AbstractCacheProvider
     */
    public static function getActiveStorage(){

        if(empty(self::$activeStorage)){

            $defaultStorage = new FilesCacheProvider('Default');

            self::$storages['Default'] = $defaultStorage;
            self::$activeStorage = $defaultStorage;

        }

        return self::$activeStorage;
    }

    public static function setActiveStorage($name){
        if(self::$storages[ $name ]){
            self::$activeStorage = self::$storages[ $name ];

            return self::$activeStorage;
        } else return false;
    }

    /**
     * @param string $name
     * @param string $storageProviderName
     * @return AbstractCacheProvider
     */
    public static function addStorage($name, $storageProviderName = null){
        if(!empty($storageProviderName)) $storage = new $storageProviderName($name);
        else $storage = new FilesCacheProvider($name);

        if($storage instanceof AbstractCacheProvider){
            self::$storages[ $name ] = $storage;

            return $storage;
        }

        return self::$activeStorage;
    }

    public static function getStorage($name){
        self::getActiveStorage();

        return self::$storages[ $name ];
    }

    public static function getStorages(){
        self::getActiveStorage();

        return self::$storages;
    }


    public static function set($key, $value, $secondsLife = 300){
        if(self::$disabled) $secondsLife = 1;

        return self::getActiveStorage()->set($key, $value, $secondsLife);
    }

    public static function get($key, $default = null){
        return self::getActiveStorage()->get($key, $default);
    }

    public static function has($key){
        return self::getActiveStorage()->has($key);
    }

    public static function remove($key){
        return self::getActiveStorage()->remove($key);
    }

    public static function expired($key){
        return self::getActiveStorage()->expired($key);
    }

    public static function call(Closure $callback, $secondsLife = 300, $key = null){
        if(self::$disabled) $secondsLife = 1;

        return self::getActiveStorage()->call($callback, $secondsLife, $key);
    }


    /**
     * @param boolean $boolean
     */
    public static function disable($boolean){
        self::$disabled = $boolean;
    }

    /**
     * @return boolean
     */
    public static function isDisabled(){
        return self::$disabled;
    }

}