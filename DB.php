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
	
	/**  不允许调用构造函数  */
    private function __construct()
    {}

    /**  不允许克隆  */
    private function __clone()
    {}

    /**  不允许serialize */
    private function __sleep()
    {}

    /**  不允许unserialize */
    private function __wakeup()
    {}
	
    public static  function getInstance(array $config)
    {
        if (!(self::$instance instanceof self)) {
            $db_host = !empty($config['host']) ? $config['host'] : "localhost";
            $db_name = !empty($config['dbname']) ? $config['dbname'] : "default";
            $db_user = !empty($config['username']) ? $config['username'] : "root";
            $db_pass = !empty($config['password']) ? $config['password'] : "";
            $dsn     = "mysql:host={$db_host};dbname={$db_name}";
            try {
                self::$instance = new PDO($dsn, $db_user, $db_pass);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				if (!empty($config['charset'])) {
					self::$instance->query("SET NAMES ".$config['charset']."");
				}            
            } catch (PDOException $e) {
                echo "ERROR: ".$e->getMessage()." \n";
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
            $params = self::makeBindArray($params);
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
     * PDO fetchRow
     *
     * @param $sql
     * @param array $params
     * @return mixed
     */
    public static function getRow($sql, $params = array())
    {
        return self::query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }
	
	/**
     * PDO fetchOne
     *
     * @param $sql
     * @param array $params
     * @return mixed
     */
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
        $cols = array();
        $vals = array();

        //判断是否是多维数组
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
        if ($columns) $cols = $columns;

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
            $smt = self::$instance->prepare($sql);
            $smt->execute($in_array);
            unset($params);
            unset($in_array);
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
    public static function update($tableName, $data = array(), $where = '', $whereParams = array())
    {
        if (empty($data)) {
            return false;
        }
        $columnArray = array();
        $column      = array_keys($data);
        $newColumn   = $column;
        array_walk($newColumn, array('DB', 'addSpecialChar'));
        foreach ($column as $key => $value) {
            $columnArray[$key] = $newColumn[$key] . '=:' . $value;
        }
        $columnStr = implode(',', $columnArray);
        $sql = "update `{$tableName}` set {$columnStr} ";
        if (!empty($where)) {
            $sql .= ' where ' . $where;
            if (is_array($whereParams)) {
                $data = array_merge($data, $whereParams);
            }
        }
        return self::query($sql, $data)->rowCount();
    }

    /**
     * 删除
     *
     * @param $sql
     * @param array $params
     * @return int
     */
    public static function delete($tableName, $where = '', $whereParams = array())
    {
        $sql = "delete from `{$tableName}` ";
        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }
        return self::query($sql, $whereParams)->rowCount();
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

    /**
     * @为字段插入特殊字符
     *
     * @param $value
     * @param $key
     * @param string $specialChar
     */
    protected static function addSpecialChar(&$value, $key, $specialChar = '`')
    {
        switch ($specialChar)
        {
            case '`':
                if($value !== '*' && strpos($value, '.') === false && strpos($value, '`') === false) {
                    $value = $specialChar . trim($value) . $specialChar;
                }
                break;
            case ':':
                if (substr($value, 0, 1) != ':') {
                    $value = $specialChar . trim($value);
                }
                break;
        }
    }

    /**
     * @构造绑定数组
     *
     * @param array $bindArray
     * @return array|bool
     */
    protected static function makeBindArray($bindArray = array())
    {
        if (empty($bindArray)) {
           return false;
        }
        $bindColumn = array_keys($bindArray);
        $bindValue  = array_values($bindArray);
        array_walk($bindColumn, array('DB', 'addSpecialChar'), ':');
        $bindArray  = array_combine($bindColumn, $bindValue);
        return $bindArray;
    }

}