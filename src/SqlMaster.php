<?php

namespace LianzhSQL;

/**
 * 主库 原则上仅仅承载着 "写" 数据的功能
 * * 但是在同步的过程中由于网络延迟造成数据不同步,避免脏数据的产生
 * * 或者 事务处理过程中也需要 "读"
 */
class SqlMaster extends SqlSlaver
{
       
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
        return Sql::assistant($this->ds)->insert($table, $row, $pkval);
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
        return Sql::assistant($this->ds)->update($table, $row, $cond);
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
        return Sql::assistant($this->ds)->del($table, $cond);
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
        return Sql::assistant($this->ds)->incr_field($table, $field, $incr, $cond);
    }

    /**
     * 执行 更新/删除 操作
     *
     * @param string $mode 模式 [MODE_WRITE_INSERT,MODE_WRITE_UPDATE,MODE_WRITE_DELETE]
     * @param mixed $args 参数[不同模式参数不同,缺省为sql字符串]
     * @param callback $cb 查询结果集的回调处理函数
     *
     * @return mixed
     */
    public function write($mode, $args, $cb=null)
    {
        return Sql::write($this->ds, $mode, $args, $cb);
    }
}
