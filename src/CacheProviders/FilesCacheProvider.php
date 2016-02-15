<?php
/**
 * IDE: PhpStorm.
 * License: The MIT License (MIT) - Copyright (c) 2016 YummyLayers
 * Date: 29.01.2016
 */

namespace YumLay\Caching\CacheProviders;

use YumLay\Caching\AbstractCacheProvider;

class FilesCacheProvider extends AbstractCacheProvider {

    /**
     * Files directory
     *
     * @var string
     */
    private $dir = 'cache/FilesCache';

    /**
     * File mime
     *
     * @var string
     */
    private $mime = '.fc';


    /**
     * @inheritdoc
     */
    public function set($key, $value, $secondsLife = 300){

        $inner = json_encode(array( $secondsLife, $value ));

        $result = file_put_contents($this->getDir() . '/' . $key . $this->mime, $inner);

        if($result !== false) return true;
        else return false;

    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null){

        $fileName = $this->getDir() . '/' . $key . $this->mime;

        $result = @file_get_contents($fileName);

        if($result !== false && !empty($result)){

            $result = json_decode($result);

            $fileModifyTime = filemtime($fileName);

            if((int)$result[0] == 0 || time() < $result[0] + $fileModifyTime){
                return $result[1];
            }
        }

        $this->remove($key);

        return $default;
    }

    /**
     * @inheritdoc
     */
    public function remove($key){
        return @unlink($this->getDir() . '/' . $key . $this->mime);
    }

    /**
     * @inheritdoc
     */
    public function has($key){
        return file_exists($this->getDir() . '/' . $key . $this->mime);
    }

    /**
     * @inheritdoc
     */
    public function expired($key){

        $fileName = $this->getDir() . '/' . $key . $this->mime;

        $result = @file_get_contents($fileName);

        if($result !== false && !empty($result)){

            $fileModifyTime = filemtime($fileName);
            $result = json_decode($result);

            if((int)$result[0] == 0 || time() < $result[0] + $fileModifyTime){
                return false;
            }
        }

        $this->remove($key);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function removeAll(){
        $answer = true;
        foreach(glob($this->getDir() . '/*') as $fileName){
            $answer = @unlink($fileName);
        }

        return $answer;
    }

    /**
     * @inheritdoc
     */
    public function removeAllExpired(){
        $answer = true;
        foreach(glob($this->getDir() . '/*') as $fileName){

            $result = file_get_contents($fileName);

            if(!empty($result)){

                $fileModifyTime = filemtime($fileName);
                $result = json_decode($result);

                if((int)$result[0] !== 0 || time() > (int)$result[0] + $fileModifyTime){
                    $answer = @unlink($fileName);
                }

            } else {
                $answer = @unlink($fileName);
            }

        }

        return $answer;
    }


    /**
     * Set a directory for file
     *
     * @param $dir
     */
    public function setDir($dir){
        $this->dir = $dir;
    }

    /**
     * Get a file directory
     *
     * @return string
     */
    private function getDir(){
        $path = $this->dir . $this->name;
        if(!is_dir($path)) mkdir($path, 0700, true);

        return $path;
    }
}