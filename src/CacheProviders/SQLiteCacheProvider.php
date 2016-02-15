<?php
/**
 * IDE: PhpStorm.
 * License: The MIT License (MIT) - Copyright (c) 2016 YummyLayers
 * Date: 28.01.2016
 */

namespace YumLay\Caching\CacheProviders;

use YumLay\Caching\AbstractCacheProvider;
use PDO;
use PDOException;

class SQLiteCacheProvider extends AbstractCacheProvider {

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
    private $mime = '.db';

    /**
     * The database connection instance.
     *
     * @var PDO
     */
    private $db;


    /**
     * Data from the last query
     *
     * @var array
     */
    private $row;

    /**
     * Indicates whether you need to update the database
     *
     * @var bool
     */
    private $dbUpdated = false;

    /**
     * @inheritdoc
     */
    public function __construct($name){
        parent::__construct($name);

        try {

            $this->db = new PDO('sqlite:' . $this->getDir() . '/' . $name . 'Cache' . $this->mime);

            $this->db->query('CREATE TABLE IF NOT EXISTS `cache`(
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `key` TEXT NOT NULL,
              `value` TEXT,
              `expires` INTEGER
            )');

        } catch(PDOException $e){
            echo 'SQLiteCacheProvider: ' . $e->getMessage();
            die();
        }
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $secondsLife = 300){

        $keyQuote = $this->db->quote($key);
        $value = $this->db->quote(json_encode($value));
        $expires = time() + (int)$secondsLife;

        if(!$this->has($key)){
            $res = $this->db->query('
                INSERT INTO `cache` (`key`, `value`, `expires`)
                VALUES (' . $keyQuote . ', ' . $value . ', ' . $expires . ')'
            );
        } else {
            $res = $this->db->query('
                UPDATE `cache`
                SET `value`=' . $value . ', `expires`=' . $expires . '
                WHERE `key`=' . $keyQuote
            );
        }

        $this->dbUpdated = true;

        return $res;
    }

    /**
     * @inheritdoc
     */
    public function has($key){
        if(!$this->select($key)) return false;
        else return true;
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null){
        $result = $this->select($key);

        if($result){
            if($result['expires'] == 0 || $result['expires'] > time()){
                $answer = json_decode($result['value']);
            } else {
                $answer = $default;
                $this->remove($key);
            }
        } else $answer = $default;

        return $answer;
    }

    /**
     * @inheritdoc
     */
    public function expired($key){

        $result = $this->select($key);

        if($result){
            if((int)$result['expires'] != 0 && time() >= (int)$result['expires']) $answer = true;
            else $answer = false;
        } else $answer = true;

        return $answer;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function removeAll(){

        $res = $this->db->query('
                DELETE FROM `cache`
        ');

        $this->dbUpdated = true;

        return $res;
    }

    /**
     * @inheritdoc
     */
    public function removeAllExpired(){
        $res = $this->db->query('
                DELETE FROM `cache`
                WHERE `expires` <> 0 AND `expires` < ' . time()
        );

        $this->dbUpdated = true;

        if($res) return true;

        return false;
    }

    /**
     * @inheritdoc
     */
    private function select($key){

        if($this->row && $this->row['key'] == $key && !$this->dbUpdated){
            $answer = $this->row;
        } else {
            $key = $this->db->quote($key);
            $res = $this->db->query('SELECT *  FROM `cache` WHERE `key`=' . $key);

            if($res){
                $result = $res->fetch(PDO::FETCH_ASSOC);

                if($result) $answer = $result;
                else $answer = false;

            } else $answer = false;

            $this->dbUpdated = false;
        }

        $this->row = $answer;

        return $answer;
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
}