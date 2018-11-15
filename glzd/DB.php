<?php
/**
 * File DB.php
 *
 * Class for mySQL and SQLite
 *
 * check() - gets available drivers
 * create() - creates a new table
 * removeTable() - drops a table
 * clear() - truncates a table's data
 * insert() - inserts new row of data
 * update() - updates row with new data by a condition
 * delete() - deletes a specific row by a condition
 * all() - gets all
 * get() - gets data by a condition
 * row() - gets a row by its number
 *
 * PHP version 7
 *
 * Copyright © «2018» «Ilya Hlazdouski»
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the “Software”),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom
 * the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author    Ilya Hlazdouski <glsd86@gmail.com>
 * @copyright Ilya Hlazdouski, 2018
 * @license   https://opensource.org/licenses/MIT
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/PackageName
 */

namespace Glzd;

use \PDO;
use \PDOException;
use \Exception;

class DB extends PDO
{

    private $_linesArray;
    private $_databaseType;
    private $_isDebug = null;

    const SQLITE = 0;
    const MYSQL = 1;

    static function dd($a)
    {
        print_r($a);
    }
    static function vd($a)
    {
        var_dump($a);
    }
    static function e($a)
    {
        echo $a;
    }

    function __construct($settingsFile)
    {
        $this->_linesArray = [];
        try{
            $f = fopen(
                $_SERVER['DOCUMENT_ROOT'].'/'.$settingsFile, 'rb'
            );
            while ($line = fgets($f)) {
                if(false === strpos($line, ';')) {
                    $tmp = explode('=', $line);
                    $this->_linesArray[$tmp[0]] = trim($tmp[1]);
                }
            }
            fclose($f);
        }
        catch(Exception $e){
            echo $e->getMessage().PHP_EOL;
            echo $e->getFile().PHP_EOL;
            echo $e->getLine().PHP_EOL;
        }

        $dsn = null;
        $username = null;
        $passwd = null;
        $options = null;

        switch ($this->_linesArray['DEBUG']){
        case 'true':
            $this->_isDebug = 1;
            break;
        default:
            $this->_isDebug = null;
        }

        switch ($this->_linesArray['DB_TYPE']){
        case 'sqlite' :
            if(!file_exists($_SERVER['DOCUMENT_ROOT'] . '/' .
                $this->_linesArray['DB_NAME'])){
                $dbFile = fopen($_SERVER['DOCUMENT_ROOT'] . '/' .
                    $this->_linesArray['DB_NAME'],'wb+');
                fclose($dbFile);
            }
            $dsn = 'sqlite:'.$_SERVER['DOCUMENT_ROOT'] . '/' .
                $this->_linesArray['DB_NAME'];
            $this->_databaseType = self::SQLITE;
            break;
        case 'mysql' :
            $dsn = 'mysql:host='.$this->_linesArray['DB_HOST'].';dbname='.
                $this->_linesArray['DB_NAME'];
            $username = $this->_linesArray['DB_USER'];
            $passwd = $this->_linesArray['DB_PASSWORD'];
            $this->_databaseType = self::MYSQL;
            break;
        default :
            if($this->_linesArray['DEBUG']==='true') {
                throw new Exception('Check .settings file');
            }
        }

        try{
            parent::__construct($dsn, $username, $passwd, $options);
            if($this->_linesArray['DEBUG']==='true') {
                $this->setAttribute(
                    self::ATTR_ERRMODE,
                    self::ERRMODE_EXCEPTION
                );
            }
        }
        catch (PDOException $e){
            if($this->_linesArray['DEBUG']==='true') {
                echo $e->getMessage().PHP_EOL;
                echo $e->getFile().PHP_EOL;
                echo $e->getLine().PHP_EOL;
            }
        }
    }

    /**
     * Gets available drivers
     */
    public function check()
    {
        self::dd(PDO::getAvailableDrivers());
    }

    /**
     * Creates a table
     *
     * @param $tableName string
     * @param $primaryKey string[null]
     * @param $fieldsArray array Array of arrays of this type: field name,
     * field type, ['nn'] - not null, [default value] (mixed), [comments]
     *
     * @return void
     */
    public function create($tableName, $primaryKey, array $fieldsArray)
    {
        $queryString = null;
        $count = 0;
        $quotes = null;
        $mysqlQuery = null;
        $comments = null;

        switch ($this->_databaseType){
        case self::SQLITE:
            $quotes = '';
            break;
        case self::MYSQL:
            $quotes = '`';
            break;
        }

        foreach ($fieldsArray as $fieldArray){

            $queryString .= $quotes . $fieldArray[1] . $quotes .' ';
            $queryString .= $fieldArray[0];

            if($fieldArray[2] === 'nn' && $fieldArray[2] !== null) {
                $queryString .= ' NOT NULL ';
            }

            if(!empty($fieldArray[3])) {
                $value = null;
                if (gettype($fieldArray[3]) === 'integer') {
                    $value = (int)$fieldArray[3];
                }
                if(gettype($fieldArray[3]) === 'double') {
                    $value = (double)$fieldArray[3];
                }
                if(gettype($fieldArray[3]) === 'string') {
                    $value = $this->quote($fieldArray[3]);
                }
                $queryString .= 'DEFAULT '.$value;
            }

            if(!empty($fieldArray[4]) && $this->_databaseType === self::MYSQL) {
                $comments = ' COMMENT '.$this->quote($fieldArray[4]);
                $queryString .= $comments;
            }

            $count++;

            if(count($fieldsArray)>1) {
                if($count!==count($fieldsArray)) {
                    $queryString .= ',';
                }
            }
        }

        if($primaryKey === null) {
            $mysqlQuery = "CREATE TABLE IF NOT EXISTS `$tableName`(
                $queryString
            )";
        }
        else{
            $mysqlQuery = "CREATE TABLE IF NOT EXISTS `$tableName`(
               `$primaryKey` MEDIUMINT NOT NULL AUTO_INCREMENT,
                $queryString,
                PRIMARY KEY(`$primaryKey`)
            )";
        }

        $sqliteQuery = "CREATE TABLE IF NOT EXISTS 
          $tableName($primaryKey INTEGER PRIMARY KEY, $queryString)";

        switch ($this->_databaseType){
        case self::SQLITE:
            $this->exec($sqliteQuery);
            break;
        case self::MYSQL:
            $this->exec($mysqlQuery);
            break;
        }
    }

    /**
     * Removes a table permanently
     *
     * @param  $tableName string
     * @return void
     */
    public function removeTable($tableName)
    {
        $query = "DROP TABLE IF EXISTS $tableName";
        $this->query($query);
    }

    /**
     * Removes all the content in a table
     *
     * @param  $tableName string
     * @return void
     */
    public function clear($tableName)
    {
        switch ($this->_databaseType){
        case self::SQLITE:
            $this->query("DELETE FROM $tableName");
            $this->query("VACUUM");
            break;
        case self::MYSQL:
            $this->query("SET FOREIGN_KEY_CHECKS = 0");
            $this->query("TRUNCATE TABLE $tableName");
            $this->query("SET FOREIGN_KEY_CHECKS = 1");
            break;
        }
    }

    /**
     * Inserts data into the database
     *
     * @param  $tableName string
     * @param  $values array
     * @return void
     */
    public function insert($tableName,array $values)
    {
        if($this->_databaseType === self::SQLITE) {
            $query = $this->query("pragma table_info($tableName)");
            $result = $this->toArray($query);
            $columnsNames = null;
            if(count($result)>1) {
                array_shift($result);
                $lastElement = end($result);
                reset($result);
            }

            foreach ($result as $k){
                $columnsNames .= $k['name'];
                if ($k !== $lastElement) {
                    $columnsNames .= ',';
                }
            }

        }

        if ($this->_databaseType === self::MYSQL) {
            $query = $this->query("SHOW columns FROM $tableName");
            $result = $this->toArray($query);
            $columnsNames = null;
            array_shift($result);
            $lastElement = end($result);
            reset($result);

            foreach ($result as $k){
                $columnsNames .= $k['Field'];
                if ($k !== $lastElement) {
                    $columnsNames .= ',';
                }
            }
        }

        $valuesForInserting = null;
        $lastElementOfValuesForInserting = end($values);
        reset($values);
        foreach($values as $k){
            if ($k !== $lastElementOfValuesForInserting) {
                $valuesForInserting .= $this->prep($k).',';
            }
            else{
                $valuesForInserting .= $this->prep($k);
            }
        }

        $result = $this->exec(
            "INSERT INTO $tableName ($columnsNames)
            VALUES ($valuesForInserting)"
        );

        if(!empty($this->_isDebug)) {
            self::vd($result);
        }
    }

    /**
     * Updates data in the database
     *
     * @param  $tableName string
     * @param  $columnsAndValues array
     * @param  $conditions array
     * @return void
     */
    public function update(
        $tableName,
        array $columnsAndValues,
        array $conditions
    ) {
        $lastElementOfColumns = end(array_keys($columnsAndValues));
        reset($columnsAndValues);

        $pairs = null;
        foreach ($columnsAndValues as $k=>$v){
            if($k !== $lastElementOfColumns) {
                $pairs .= $k.'='.$this->prep($v).',';
            }
            else{
                $pairs .= $k.'='.$this->prep($v);
            }
        }

        if(count($conditions)<=3) {
            $query = "UPDATE $tableName SET $pairs WHERE " .
                $this->prepareConditionsString($conditions);
            $result = $this->exec($query);

            if($this->_linesArray['DEBUG'] === 'true') {
                self::vd($result);
            }
        }
        else{
            //TODO implement multiple conditions for updating
        }
    }

    /**
     * Deletes a specific row
     */
    public function delete($tableName, array $conditions)
    {
        $tableName = trim($tableName);
        if(count($conditions)<=3) {
            $query = "DELETE FROM $tableName WHERE " .
                $this->prepareConditionsString($conditions);
            $result = $this->exec($query);

            if($this->_linesArray['DEBUG'] === 'true') {
                self::vd($result);
            }
        }
        else{
            //TODO implement multiple conditions for deleting
        }
    }

    /**
     * Gets all the data from the table
     *
     * @param  $tableName string
     * @param  $resultType string
     * @param  $debug bool
     * @return mixed
     */
    public function all($tableName, $resultType='obj', $debug=false)
    {
        $sql = $this->query("SELECT * FROM $tableName");

        if($debug === true) {
            self::vd($sql->fetchAll(self::FETCH_ASSOC));
        }

        if($resultType === 'obj') {
            return $sql->fetchAll(self::FETCH_OBJ);
        }
        elseif ($resultType === 'arr') {
            return $sql->fetchAll(self::FETCH_ASSOC);
        }
        else{
            //TODO implement another types of fetching
            return false;
        }
    }

    /**
     * Gets data by a specific condition
     *
     * @param  $tableName string
     * @param  $conditions array
     * @param  $resultType string
     * @param  $debug bool
     * @return mixed
     */
    public function get(
        $tableName,
        array $conditions,
        $resultType='obj',
        $debug = false
    ) {
        if(count($conditions)<=3) {
            $sql = $this->query(
                "SELECT * FROM $tableName WHERE " .
                    $this->prepareConditionsString($conditions)
            );
        }
        else{
            return false;
        }

        if($debug === true) {
            self::dd($sql->fetchAll(self::FETCH_ASSOC));
        }

        if($resultType === 'obj') {
            return $sql->fetchAll(self::FETCH_OBJ);
        }
        elseif ($resultType === 'arr') {
            return $sql->fetchAll(self::FETCH_ASSOC);
        }
        else{
            //TODO implement another types of fetching
            return false;
        }
    }

    /**
     * Gets a row by its number (The first row starts with 1)
     *
     * @param  $tableName string
     * @param  $rowNumber integer
     * @param  $resultType string
     * @return mixed
     */
    public function row(
        $tableName,
        $rowNumber,
        $resultType = 'obj',
        $debug = false
    ) {
        $rowNumber--;
        $sql = $this->query(
            "SELECT * FROM $tableName LIMIT $rowNumber,1"
        );

        if($debug === true) {
            self::dd($sql->fetchAll(self::FETCH_ASSOC));
        }

        if($resultType === 'obj') {
            return $sql->fetchAll(self::FETCH_OBJ);
        }
        elseif ($resultType === 'arr') {
            return $sql->fetchAll(self::FETCH_ASSOC);
        }
        else{
            //TODO implement another types of fetching
            return false;
        }
    }

    /**
     * Prepares item for inserting
     *
     * @return mixed
     */
    private function prep($item)
    {
        switch (gettype($item)){
        case 'string' : 
            return trim($this->quote($item));
            break;
        case 'integer' : 
            return (int)$item;
            break;
        case 'double' : 
            return (double)$item;
            break;
        default : 
            throw new Exception('Illegal parameter');
        }
    }

    /**
     * Resolves a condition
     *
     * @param  $conditions array mixed
     * @return string
     */
    private function prepareConditionsString(array $conditions)
    {
        $signsRE = '/[<>=]/';
        $notRE = '/not|<>/i';

        if(preg_match($signsRE, $conditions[1])) {
            $name = trim($conditions[0]);
            $sign = trim($conditions[1]);
            $val = trim($this->prep($conditions[2]));
        }
        elseif (preg_match($notRE, $conditions[1])) {
            $name = trim($conditions[0]) . ' ';
            $sign =  'NOT IN';
            $val = '('.$this->prep($conditions[2]).')';
        }
        else{
            $name = trim($conditions[0]);
            $sign = '=';
            $val = $this->prep($conditions[1]);
        }

        return $name.$sign.$val;
    }

    /**
     * Fetches statement to array
     *
     * @param string $view = assoc|num|both|lazy|obj
     */
    private function toArray($query,$view='assoc')
    {
        $result = [];
        $type = null;
        switch($view){
        case "assoc" : $type = self::FETCH_ASSOC;
            break;
        case "num" : $type = self::FETCH_NUM;
            break;
        case "both" : $type = self::FETCH_BOTH;
            break;
        case "lazy" : $type = self::FETCH_LAZY;
            break;
        case "obj" : $type = self::FETCH_OBJ;
            break;
        case "std" : $type = self::FETCH_CLASS|self::FETCH_CLASSTYPE;
            break;
        default :
            return "Wrong 2nd parameter";
        }
        while ($table = $query->fetch($type)) {
            array_push($result, $table);
        }
        return $result;
    }
}










