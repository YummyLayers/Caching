<?php

/**
 * Created by PhpStorm.
 * User: andre
 * Date: 27.01.2016
 * Time: 14:41
 */

namespace Caching\CacheProviders;

use Caching\AbstractCacheProvider;

class FilesCacheProvider extends AbstractCacheProvider {

    private $dir = 'cache/FilesCache';

    private $mime = '.fc';

    public function set($key, $value, $secondsLife = 300){

        $inner = serialize(array( $secondsLife, $value, ));

        $result = file_put_contents($this->getDir() . '/' . $key . $this->mime, $inner);

        if($result !== false) return true;
        else return false;

    }

    public function get($key, $default = null){

        $fileName = $this->getDir() . '/' . $key . $this->mime;

        $result = @file_get_contents($fileName);

        if($result !== false && !empty($result)){

            $result = unserialize($result);

            $fileModifyTime = filemtime($fileName);

            if((int)$result[0] == 0 || time() < $result[0] + $fileModifyTime){
                return $result[1];
            }
        }

        $this->remove($key);

        return $default;
    }

    public function remove($key){
        return @unlink($this->getDir() . '/' . $key . $this->mime);
    }

    public function has($key){
        return file_exists($this->getDir() . '/' . $key . $this->mime);
    }

    public function expired($key){

        $fileName = $this->getDir() . '/' . $key . $this->mime;

        $result = @file_get_contents($fileName);

        if($result !== false && !empty($result)){

            $fileModifyTime = filemtime($fileName);
            $result = unserialize($result);

            if((int)$result[0] == 0 || time() < $result[0] + $fileModifyTime){
                return false;
            }
        }

        $this->remove($key);

        return true;
    }

    public function removeAll(){
        $answer = true;
        foreach(glob($this->getDir() . '/*') as $fileName){
            $answer = @unlink($fileName);
        }

        return $answer;
    }

    public function removeAllExpired(){
        $answer = true;
        foreach(glob($this->getDir() . '/*') as $fileName){

            $result = file_get_contents($fileName);

            if(!empty($result)){

                $fileModifyTime = filemtime($fileName);
                $result = unserialize($result);

                if((int)$result[0] !== 0 || time() > (int)$result[0] + $fileModifyTime){
                    $answer = @unlink($fileName);
                }

            } else {
                $answer = @unlink($fileName);
            }

        }

        return $answer;
    }


    public function setDir($dir){
        $this->dir = $dir;
    }

    private function getDir(){
        $path = $this->dir . $this->name;
        if(!is_dir($path)) mkdir($path, 0700, true);

        return $path;
    }
}