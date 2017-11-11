<?php

namespace LianzhSQL;

/**
 * 从库 仅仅承载着 "读" 数据的功能
 */
class SqlSlaver
{

	/**
	 * 构造函数
	 * 
	 * @param SqlDataSource $ds
	 */
	public function __construct(SqlDataSource $ds)
	{
		$this->ds = $ds;
	}

	/**
	 * 返回数据源对象
	 * 
	 * @return SqlDataSource
	 */
	public function getDataSource()
	{
		return $this->ds;
	}

	## 自己封装一些 读操作
	
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
    	return Sql::assistant( $this->ds )->select_row($table, $cond, $fields,  $sort);
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
		return Sql::assistant( $this->ds )->select($table, $cond, $fields, $sort, $limit, $calc);
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
    	return Sql::assistant( $this->ds )->count($table, $cond, $fields, $distinct);
    }

    /**
	 * 执行 读 操作
	 * 
	 * @param string $mode 模式 [MODE_READ_GETALL,MODE_READ_GETROW,MODE_READ_GETONE,MODE_READ_GETCOL]
	 * @param mixed $args 参数[不同模式参数不同,缺省为sql字符串]
	 * @param callback $cb 查询记录集的回调处理函数
	 * 
	 * @return mixed
	 */
	public function read($mode, $args, $cb=NULL)
	{
		return Sql::read( $this->ds, $mode, $args, $cb);
	}

}
