<?php
/**
 * IDE: PhpStorm.
 * License: The MIT License (MIT) - Copyright (c) 2016 YummyLayers
 * Date: 27.01.2016
 */

namespace YumLay\Caching;

use YumLay\Caching\CacheProviders\FilesCacheProvider;
use Closure;

class Cache {

    /**
     * The array of instances cache stores
     *
     * @var AbstractCacheProvider[]
     */
    private static $stores = array();

    /**
     * Instance active cache store
     *
     * @var AbstractCacheProvider
     */
    private static $activeStore;


    /**
     * Indicates if cache disabled
     *
     * @var bool
     */
    private static $disabled = false;


    /**
     * Add a new cache store
     *
     * @param string $storeName
     * @param string $storeProviderName
     *
     * @return AbstractCacheProvider
     */
    public static function addStore($storeName, $storeProviderName = null){
        if(!empty($storeProviderName)) $storage = new $storeProviderName($storeName);
        else $storage = new FilesCacheProvider($storeName);

        if($storage instanceof AbstractCacheProvider){
            self::$stores[ $storeName ] = $storage;

            if(count(self::$stores) == 1) $storage->setActive();

            return $storage;
        }

        return self::$activeStore;
    }

    /**
     * Get all the stores
     *
     * @return AbstractCacheProvider[]
     */
    public static function getStores(){
        self::getActiveStore();

        return self::$stores;
    }

    /**
     * Get cache store by name
     *
     * @param string $name
     *
     * @return AbstractCacheProvider
     */
    public static function getStore($name){
        self::getActiveStore();

        return self::$stores[ $name ];
    }

    /**
     * Get active store
     *
     * @return AbstractCacheProvider
     */
    public static function getActiveStore(){

        if(empty(self::$activeStore)){

            $defaultStore = new FilesCacheProvider('Default');

            self::$stores['Default'] = $defaultStore;
            self::$activeStore = $defaultStore;

        }

        return self::$activeStore;
    }

    /**
     * Set the active cache store
     *
     * @param string $name
     *
     * @return AbstractCacheProvider
     */
    public static function setActiveStore($name){
        if(self::$stores[ $name ]){
            self::$activeStore = self::$stores[ $name ];

            return self::$activeStore;
        } else return false;
    }


    /**
     * Set the new item or rewrite item in the active cache store
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $secondsLife
     *
     * @return boolean
     */
    public static function set($key, $value, $secondsLife = 300){
        if(self::$disabled) $secondsLife = 1;

        return self::getActiveStore()->set($key, $value, $secondsLife);
    }

    /**
     * Get the value of an active cache store
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, $default = null){
        return self::getActiveStore()->get($key, $default);
    }

    /**
     * Call the anonymous function and saves, gives the result
     * if the cache is not present in the repository, or it has expired
     *
     * @param Closure $callback
     * @param int     $secondsLife
     * @param string  $key
     *
     * @return mixed
     */
    public static function call(Closure $callback, $secondsLife = 300, $key = null){
        if(self::$disabled) $secondsLife = 1;

        return self::getActiveStore()->call($callback, $secondsLife, $key);
    }

    /**
     * Determine if an item exists in the active cache store
     *
     * @param string $key
     *
     * @return boolean
     */
    public static function has($key){
        return self::getActiveStore()->has($key);
    }

    /**
     * Determine if an item expired in the active cache store
     *
     * @param string $key
     *
     * @return boolean
     */
    public static function expired($key){
        return self::getActiveStore()->expired($key);
    }

    /**
     * Remove item in the active cache store
     *
     * @param string $key
     *
     * @return boolean
     */
    public static function remove($key){
        return self::getActiveStore()->remove($key);
    }


    /**
     * Disable caching
     *
     * @param boolean $boolean
     */
    public static function disable($boolean){
        self::$disabled = $boolean;
    }

    /**
     * Determine whether caching is disable
     *
     * @return boolean
     */
    public static function isDisabled(){
        return self::$disabled;
    }

}