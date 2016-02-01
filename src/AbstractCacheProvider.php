<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 27.01.2016
 * Time: 14:46
 */
namespace Caching;

use Closure;
use ReflectionFunction;

abstract class AbstractCacheProvider {

    protected $name;


    abstract public function set($key, $value, $secondsLife = 300);

    abstract public function has($key);

    abstract public function get($key, $default = null);

    abstract public function expired($key);

    abstract public function remove($key);

    abstract public function removeAll();

    abstract public function removeAllExpired();


    public function __construct($name){
        $this->name = $name;
    }

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

    public function setDefault(){
        Cache::setActiveStorage($this->name);

        return $this;
    }

}