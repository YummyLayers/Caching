<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 28.01.2016
 * Time: 15:58
 */

namespace YamLay\Caching\CacheProviders;

use YamLay\Caching\AbstractCacheProvider;
use PDO;

class SQLiteCacheProvider extends AbstractCacheProvider {

    private $dir = 'cache';

    private $mime = '.db';

    private $db;

    private $row;

    private $dbUpdated = false;

    public function __construct($name){
        parent::__construct($name);

        $this->db = new PDO('sqlite:' . $this->getDir() . '/' . $name . 'Cache' . $this->mime);

        $this->db->query('CREATE TABLE IF NOT EXISTS `cache`(
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `key` TEXT NOT NULL,
            `value` TEXT,
            `expires` INTEGER
        )');

        // TODO: set Exception, if database don't create
    }


    public function set($key, $value, $secondsLife = 300){

        $key = $this->db->quote($key);
        $value = $this->db->quote($value);
        $expires = time() + (int)$secondsLife;

        if(!$this->has($key)){
            $res = $this->db->query('
                INSERT INTO `cache` (`key`, `value`, `expires`)
                VALUES (' . $key . ', ' . $value . ', ' . $expires . ')'
            );
        } else {
            $res = $this->db->query('
                UPDATE `cache`
                SET `value`=' . $value . ', `expires`=' . $expires . '
                WHERE `key`=' . $key
            );
        }

        $this->dbUpdated = true;

        return $res;
    }

    public function has($key){
        if(!$this->select($key)) return false;
        else return true;
    }

    public function get($key, $default = null){
        $result = $this->select($key);

        if($result){
            if($result['expires'] == 0 || $result['expires'] > time()){
                $answer = $result['value'];
            }else{
                $answer = $default;
                $this->remove($key);
            }
        } else $answer = $default;

        return $answer;
    }

    public function expired($key){

        $result = $this->select($key);

        if($result){
            if($result['expires'] != 0 && time() >= $result['expires']) $answer = true;
            else $answer = false;
        } else $answer = true;

        return $answer;
    }

    public function remove($key){
        $key = $this->db->quote($key);
        $res = $this->db->query('
                DELETE FROM `cache`
                WHERE `key`=' . $key
        );

        $this->dbUpdated = true;

        if($res) return true;

        return false;
    }

    public function removeAll(){

        $res = $this->db->query('
                DELETE FROM `cache`
        ');

        $this->dbUpdated = true;

        return $res;
    }

    public function removeAllExpired(){
        $res = $this->db->query('
                DELETE FROM `cache`
                WHERE `expires` <> 0 AND `expires` < ' . time()
        );

        $this->dbUpdated = true;

        if($res) return true;

        return false;
    }

    private function select($key){

        if($this->row && $this->row['key'] == $key && !$this->dbUpdated){
            $answer = $this->row;
        } else {
            $key = $this->db->quote($key);
            $res = $this->db->query('SELECT *  FROM `cache` WHERE `key`=' . $key);

            if($res){
                $result = $res->fetch();

                if($result) $answer = $result;
                else $answer = false;

            } else $answer = false;

            $this->dbUpdated = false;
        }

        $this->row = $answer;

        return $answer;
    }

    public function setDir($dir){
        $this->dir = $dir;
    }

    private function getDir(){
        $path = $this->dir;
        if(!is_dir($path)) mkdir($path, 0700, true);

        return $path;
    }
}