<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 29.01.2016
 * Time: 13:24
 */

namespace Caching\CacheProviders;

use Caching\AbstractCacheProvider;

class FileCacheProvider extends AbstractCacheProvider {

    private $dir = 'cache';

    private $mime = '.fc';

    private $data = array();

    public function __construct($name){
        parent::__construct($name);

        if($res = @file_get_contents($this->getDir() . '/' . $this->name . 'Cache' . $this->mime)){
            $this->data = unserialize($res);
        }
    }


    public function set($key, $value, $secondsLife = 300){
        $this->data[ $key ][0] = time() + $secondsLife;
        $this->data[ $key ][1] = $value;

        return $this->save();
    }

    public function has($key){
        if(!empty($this->data[ $key ])) return true;
        else return false;
    }

    public function get($key, $default = null){

        if(!empty($this->data[ $key ])){
            if($this->data[ $key ][0] == 0 || $this->data[ $key ][0] > time()) return $this->data[ $key ][1];
            else {
                $this->remove($key);

                return $default;
            }
        } else return $default;

    }

    public function expired($key){
        if(!empty($this->data[ $key ])){
            if($this->data[ $key ][0] == 0 || $this->data[ $key ][0] > time()) return false;
            else return true;
        } else return true;
    }

    public function remove($key){
        unset($this->data[ $key ]);

        return $this->save();
    }

    public function removeAll(){
        unset($this->data);

        return $this->save();
    }

    public function removeAllExpired(){
        foreach($this->data as $key => $value){
            if($value[0] != 0 && $value[0] > time()) unset($this->data[ $key ]);
        }

        return $this->save();
    }

    public function setDir($dir){
        $this->dir = $dir;
    }

    private function getDir(){
        $path = $this->dir;
        if(!is_dir($path)) mkdir($path, 0700, true);

        return $path;
    }

    private function save(){
        return @file_put_contents($this->getDir() . '/' . $this->name . 'Cache' . $this->mime, serialize($this->data));
    }

}