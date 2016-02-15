<?php
/**
 * IDE: PhpStorm.
 * License: The MIT License (MIT) - Copyright (c) 2016 YummyLayers
 * Date: 27.01.2016
 */

namespace YumLay\Caching;

use Closure;
use ReflectionFunction;

abstract class AbstractCacheProvider {

    /**
     * Store name
     *
     * @var string
     */
    protected $name;


    /**
     * Create a store
     *
     * @param string $name
     */
    public function __construct($name){
        $this->name = $name;
    }


    /**
     * Set the new item or rewrite item in the cache store
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $secondsLife
     *
     * @return boolean
     */
    abstract public function set($key, $value, $secondsLife = 300);

    /**
     * Get the value of an cache store
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    abstract public function get($key, $default = null);

    /**
     * Determine if an item exists in the cache store
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract public function has($key);

    /**
     * Determine if an item expired in the cache store
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract public function expired($key);

    /**
     * Remove item in the cache store
     *
     * @param string $key
     * @return boolean
     */
    abstract public function remove($key);

    /**
     * Remove all items in the cache store
     *
     * @return boolean
     */
    abstract public function removeAll();

    /**
     * Remove all expired items in the cache store
     *
     * @return boolean
     */
    abstract public function removeAllExpired();


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
    public function call(Closure $callback, $secondsLife = 300, $key = null){

        if(empty($key)){
            $refCallback = new ReflectionFunction($callback);
            $key = md5($refCallback->getStartLine() . $refCallback->getFileName() . $refCallback->getEndLine());
        }

        if(!$this->has($key) || $this->expired($key)){

            $value = $callback();
            $this->set($key, $value, $secondsLife);

            return $value;

        } else return $this->get($key);

    }

    /**
     * Set the active cache store
     *
     * @return AbstractCacheProvider
     */
    public function setActive(){
        Cache::setActiveStore($this->name);

        return $this;
    }

}