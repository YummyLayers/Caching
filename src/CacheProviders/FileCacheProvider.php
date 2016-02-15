<?php
/**
 * IDE: PhpStorm.
 * License: The MIT License (MIT) - Copyright (c) 2016 YummyLayers
 * Date: 29.01.2016
 */

namespace YumLay\Caching\CacheProviders;

use YumLay\Caching\AbstractCacheProvider;

class FileCacheProvider extends AbstractCacheProvider {

    /**
     * Files directory
     *
     * @var string
     */
    private $dir = 'cache';

    /**
     * File mime
     *
     * @var string
     */
    private $mime = '.fc';

    /**
     * Storage data
     *
     * @var array
     */
    private $data = array();


    /**
     * @inheritdoc
     */
    public function __construct($name){
        parent::__construct($name);

        if($res = @file_get_contents($this->getDir() . '/' . $this->name . 'Cache' . $this->mime)){
            $this->data = json_decode($res, true);
        }
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $secondsLife = 300){
        $this->data[ $key ][0] = time() + $secondsLife;
        $this->data[ $key ][1] = $value;

        return $this->save();
    }

    /**
     * @inheritdoc
     */
    public function has($key){
        if(!empty($this->data[ $key ])) return true;
        else return false;
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null){

        if(!empty($this->data[ $key ])){
            if($this->data[ $key ][0] == 0 || $this->data[ $key ][0] > time()) return $this->data[ $key ][1];
            else {
                $this->remove($key);

                return $default;
            }
        } else return $default;

    }

    /**
     * @inheritdoc
     */
    public function expired($key){
        if(!empty($this->data[ $key ])){
            if($this->data[ $key ][0] == 0 || $this->data[ $key ][0] > time()) return false;
            else return true;
        } else return true;
    }

    /**
     * @inheritdoc
     */
    public function remove($key){
        unset($this->data[ $key ]);

        return $this->save();
    }

    /**
     * @inheritdoc
     */
    public function removeAll(){
        unset($this->data);

        return $this->save();
    }

    /**
     * @inheritdoc
     */
    public function removeAllExpired(){
        foreach($this->data as $key => $value){
            if($value[0] != 0 && $value[0] > time()) unset($this->data[ $key ]);
        }

        return $this->save();
    }


    /**
     * Set a directory for files
     *
     * @param $dir
     */
    public function setDir($dir){
        $this->dir = $dir;
    }

    /**
     * Get a files directory
     *
     * @return string
     */
    private function getDir(){
        $path = $this->dir;
        if(!is_dir($path)) mkdir($path, 0700, true);

        return $path;
    }

    /**
     * Save changes
     *
     * @return int
     */
    private function save(){
        return @file_put_contents($this->getDir() . '/' . $this->name . 'Cache' . $this->mime, json_encode($this->data));
    }

}