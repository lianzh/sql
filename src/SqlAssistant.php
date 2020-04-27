<?php

namespace LianzhSQL;

class SqlAssistant
{

    /**
     * @var SqlDataSource
     */
    private $ds = null;

    private function __construct()
    {
    }

    /**
     * 获取 SqlAssistant 对象
     *
     * @param  SqlDataSource  $ds
     *
     * @return \LianzhSQL\SqlAssistant
     */
    public static function instance(SqlDataSource $ds)
    {
        static $obj = null;
        if (!$obj) {
            $obj = new self();
        }
        $obj->ds = $ds;
        return $obj;
    }

    private function ds()
    {
        if (empty($this->ds)) {
            throw new SqlError("invalid ds obj");
        }
        $ds = $this->ds;
        $this->ds = null;
        return $ds;
    }

    /**
     * 解析查询条件
     *
     * @param  mixed  $cond
     * @param  boolean  $dash
     *
     * @return string
     */
    public function cond($cond, $dash=false)
    {
        $ds = $this->ds();
        return SqlHelper::parse_cond($ds, $cond, $dash);
    }
    
    /**
     * 从表中检索符合条件的一条记录
     *
     * @param string $table
     * @param mixed $cond
     * @param string $fields
     * @param string $sort
     *
     * @return array
     */
    public function select_row($table, $cond=null, $fields='*', $sort=null)
    {
        $ds = $this->ds();
        $cond = SqlHelper::parse_cond($ds, $cond);
        if ($cond) {
            $cond = "WHERE {$cond}";
        }
        if ($sort) {
            $sort = "ORDER BY {$sort}";
        }
        
        $qfields = SqlHelper::qfields($fields, $table);
        
        return Sql::read($ds, Sql::MODE_READ_GETROW, [
                "SELECT {$qfields} FROM {$table} {$cond} {$sort}"
            ]);
    }
    
    /**
     * 从表中检索符合条件的多条记录
     *
     * @param string $table
     * @param mixed $cond
     * @param string $fields
     * @param string $sort
     * @param int|array $limit 数组的话遵循格式 ( offset,length )
     * @param bool $calc 计算总个数
     *
     * @return array
     */
    public function select($table, $cond=null, $fields='*', $sort=null, $limit=null, $calc=false)
    {
        $ds = $this->ds();
        $cond = SqlHelper::parse_cond($ds, $cond);
        if ($cond) {
            $cond = "WHERE {$cond}";
        }
        if ($sort) {
            $sort = "ORDER BY {$sort}";
        }
        
        $qfields = SqlHelper::qfields($fields, $table);
        $table = SqlHelper::qtable($table);
        
        return Sql::read($ds, Sql::MODE_READ_GETALL, [
                "SELECT {$qfields} FROM {$table} {$cond} {$sort}",
                empty($limit) ? false : $limit,
                $calc
            ]);
    }

    /**
     * 统计符合条件的记录的总数
     *
     * @param string $table
     * @param mixed $cond
     * @param string|array $fields
     * @param boolean $distinct
     *
     * @return int
     */
    public function count($table, $cond=null, $fields='*', $distinct=false)
    {
        $ds = $this->ds();
        if ($distinct) {
            $distinct = 'DISTINCT ';
        }
        
        $cond = SqlHelper::parse_cond($ds, $cond);
        if ($cond) {
            $cond = "WHERE {$cond}";
        }
        
        if (is_null($fields) || trim($fields) == '*') {
            $fields = '*';
        } else {
            $fields = SqlHelper::qfields($fields, $table);
        }
        
        $table = SqlHelper::qtable($table);
        
        return (int) Sql::read($ds, Sql::MODE_READ_GETONE, [
                "SELECT COUNT({$distinct}{$fields}) FROM {$table} {$cond}"
            ]);
    }

    /**
     * 插入一条记录
     *
     * @param string $table
     * @param array $row
     * @param bool $pkval 是否获取插入的主键值
     *
     * @return mixed
     */
    public function insert($table, array $row, $pkval=false)
    {
        $ds = $this->ds();
        list($holders, $values) = SqlHelper::placeholder($row);
        $holders = implode(',', $holders);
        
        $fields = SqlHelper::qfields(array_keys($values));
        $table = SqlHelper::qtable($table);
        
        return Sql::write($ds, Sql::MODE_WRITE_INSERT, [
                SqlHelper::bind($ds, "INSERT INTO {$table} ({$fields}) VALUES ({$holders})", $row),
                $pkval
            ]);
    }

    /**
     * 更新表中记录
     *
     * @param string $table
     * @param array $row
     * @param mixed $cond 条件
     *
     * @return int
     */
    public function update($table, array $row, $cond=null)
    {
        $ds = $this->ds();
        if (empty($row)) {
            return false;
        }
        
        list($pairs, $values) = SqlHelper::placeholder_pair($row);
        $pairs = implode(',', $pairs);
        
        $table = SqlHelper::qtable($table);
        
        $sql = SqlHelper::bind($ds, "UPDATE {$table} SET {$pairs}", $row);
        
        $cond = SqlHelper::parse_cond($ds, $cond);
        if ($cond) {
            $sql .= " WHERE {$cond}";
        }
        
        return Sql::write($ds, Sql::MODE_WRITE_UPDATE, [
             $sql
        ]);
    }

    /**
     * 删除 表中记录
     *
     * @param string $table
     * @param mixed $cond
     *
     * @return int
     */
    public function del($table, $cond=null)
    {
        $ds = $this->ds();
        $cond = SqlHelper::parse_cond($ds, $cond);
        $table = SqlHelper::qtable($table);
        
        $sql = "DELETE FROM {$table} " . (empty($cond) ? '' : "WHERE {$cond}");
        
        return Sql::write($ds, Sql::MODE_WRITE_DELETE, [
                $sql
            ]);
    }

    /**
     * 向表中 某字段的值做 "加"运算
     *
     * @param string $table
     * @param string $field
     * @param int $incr
     * @param mixed $cond
     *
     * @return int
     */
    public function incr_field($table, $field, $incr = 1, $cond=null)
    {
        $incr = (int)$incr;
        if ($incr == 0) {
            return false;
        }
        
        $ds = $this->ds();
        $field = SqlHelper::qfield($field, $table);
        $cond = SqlHelper::parse_cond($ds, $cond);
        $sql = "UPDATE {$table} SET {$field} = {$field} + {$incr} " . (empty($cond) ? '' : "WHERE {$cond}");

        return Sql::write($ds, Sql::MODE_WRITE_UPDATE, [
                $sql
            ]);
    }
}
