<?php

/**
 * Created by PhpStorm.
 * User: zhq
 * Date: 2017/4/28
 * Time: 17:28
 */
 
class DB
{
    private static $instance = null;

   /* private function __construct(array $config)
    {
        $db_host = isset($config['host']) ? $config['host'] : "localhost";
        $db_name = isset($config['dbname']) ? $config['dbname'] : "default";
        $db_user = isset($config['username']) ? $config['username'] : "root";
        $db_pass = isset($config['password']) ? $config['password'] : "";
        $dsn = "mysql:host={$db_host};dbname={$db_name}";
        try {
            parent::__construct($dsn, $db_user, $db_pass);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->query("SET NAMES 'UTF8'");
        } catch (PDOException $e) {
            echo "ERROR: ".$e->getMessage()." <br />";
            return false;
        }
    }*/

    public static  function getInstance(array $config)
    {
        if (!(self::$instance instanceof self)) {
            $db_host = isset($config['host']) ? $config['host'] : "localhost";
            $db_name = isset($config['dbname']) ? $config['dbname'] : "default";
            $db_user = isset($config['username']) ? $config['username'] : "root";
            $db_pass = isset($config['password']) ? $config['password'] : "";
            $dsn = "mysql:host={$db_host};dbname={$db_name}";
            try {
                self::$instance = new PDO($dsn, $db_user, $db_pass);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->query("SET NAMES 'UTF8'");
            } catch (PDOException $e) {
                echo "ERROR: ".$e->getMessage()." <br />";
                return false;
            }
        }
        return self::$instance;
    }

    /**
     * PDO statement
     *
     * @param $sql
     * @param array $params
     * @return PDOStatement
     */
    private static function query($sql, $params = array())
    {
        $smt = self::$instance->prepare($sql);
        if (is_array($params) && !empty($params)) {
            foreach ($params as $k => $v) {
                $smt->bindValue($k, $v);
            }
        }
        $smt->execute();
        return $smt;
    }

    /**
     * PDO fetchAll
     *
     * @param $sql
     * @param array $params
     * @return array
     */
    public static function getAll($sql, $params = array())
    {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PDO fetch
     *
     * @param $sql
     * @param array $params
     * @return mixed
     */
    public static function getRow($sql, $params = array())
    {
        return self::query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public static function getOne($sql, $params = array())
    {
        return self::query($sql, $params)->fetch(PDO::FETCH_NUM);
    }

    /**
     * 单条插入 | 多条插入
     * @param $table
     * @param array $params
     * @param array $columns
     * @return mixed
     */
    public static function insert($table, $params = array(), $columns = array())
    {
        if (empty($columns)) {
            if (count($params) == count($params, 1)) {
                foreach ($params as $col => $val) {
                    $cols[] = $col;
                    $vals[] = "?";
                }
            } else {
                foreach ($params[0] as $col => $val) {
                    $cols[] = $col;
                    $vals[] = "?";
                }
            }
        } else {
            $cols = $columns;
        }
        //构造sql语句
        if (count($params) == count($params, 1)) {
            $sql = "insert into {$table} (" . implode(",", $cols) . ") values (" . implode(",", $vals) . ")";
            $smt = self::$instance->prepare($sql);
            $smt->execute(array_values($params));
        } else {
            $sql = "insert into {$table} (" . implode(",", $cols) . ") values  ";
            $in_array = array();
            $i = 0;
            foreach ($params as $k => $v) {
                if ($i > 0) {
                    $sql .= " ,(" .implode(",", $vals) . ")";
                } else {
                    $sql .= " (" .implode(",", $vals) . ")";
                }
                foreach ($v as $m => $n) {
                    $in_array[] = $n;
                }
                $i++;
            }
            unset($i);
            $smt = self::$instance->prepare($sql);
            $smt->execute($in_array);
        }
        return self::$instance->lastinsertId();
    }

    /**
     * 修改
     *
     * @param $sql
     * @param array $params
     * @return int
     */
    public static function update($sql, $params = array())
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * 删除
     *
     * @param $sql
     * @param array $params
     * @return int
     */
    public static function delete($sql, $params = array())
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * quote
     *
     * @param $string
     * @return string
     */
    public static function qstr($string)
    {
        return self::$instance->quote($string);
    }

    /**
     * PDO transaction
     *
     * @return bool
     */
    public static function beginTransactions()
    {
        try {
            self::$instance->beginTransaction();
        } catch (PDOException $e) {
            echo "ERROR: ".$e->getMessage()." <br />";
            return false;
        }
    }

    /**
     * PDO rollBack
     *
     * @return bool
     */
    public static function rollBacks()
    {
        try {
            self::$instance->rollBack();
        } catch (PDOException $e) {
            echo "ERROR: ".$e->getMessage()." <br />";
            return false;
        }
    }

    /**
     * PDO commit
     *
     * @return bool
     */
    public static function commits()
    {
        try {
            self::$instance->commit();
        } catch (PDOException $e) {
            echo "ERROR: ".$e->getMessage()." <br />";
            return false;
        }
    }


}