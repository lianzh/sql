<?php

namespace LianzhSQL;

/**
 * SQL 数据库操作类
 */
class Sql
{

	/**
	 * 读 记录集
	 */
	const MODE_READ_GETALL = 1;
	
	/**
	 * 读 第一条记录
	 */
	const MODE_READ_GETROW = 2;
	
	/**
	 * 读 第一条记录的第一个字段
	 */
	const MODE_READ_GETONE = 3;
	
	/**
	 * 读 记录集的指定列
	 */
	const MODE_READ_GETCOL = 4;
	
	/**
	 * 读 记录集的总个数
	 */
	const MODE_READ_ALLCOUNT = 5;
	
	/**
	 * 写 (插入) 操作
	 */
	const MODE_WRITE_INSERT = 11;
	
	/**
	 * 写 (更新) 操作
	 */
	const MODE_WRITE_UPDATE = 12;
	
	/**
	 * 写 (删除) 操作
	 */
	const MODE_WRITE_DELETE = 13;

	/**
	 * 获取 SqlDataSource 对象
	 * 
	 * @param  array  $dsn
	 * 
	 * @return \LianzhSQL\SqlDataSource
	 */
	public static function ds(array $dsn)
	{
		static $list = [];
		$dsn = SqlDataSource::dsn($dsn);
		$id = $dsn['id'];
		if ( empty( $list[$id] ) )
		{
			$list[$id] = new SqlDataSource($dsn);
		}
		return $list[$id];
	}

	/**
	 * 获取 SqlAssistant 对象
	 * 
	 * @param  SqlDataSource  $ds
	 * 
	 * @return \LianzhSQL\SqlAssistant
	 */
	public static function assistant(SqlDataSource $ds)
	{
		return SqlAssistant::instance($ds);
	}

	/**
	 * 执行 读 操作
	 * 
	 * @param SqlDataSource $ds
	 * @param string $mode 模式 [MODE_READ_GETALL,MODE_READ_GETROW,MODE_READ_GETONE,MODE_READ_GETCOL]
	 * @param mixed $args 参数[不同模式参数不同,缺省为sql字符串]
	 * @param callback $cb 查询记录集的回调处理函数
	 * 
	 * @return mixed
	 */
	public static function read(SqlDataSource $ds, $mode, $args, $cb=NULL)
	{
		$args = (array) $args;
		$sql = array_shift($args);// 缺省第一个参数是sql字符串
		
		switch ($mode)
		{
			case self::MODE_READ_GETALL: // array(sql,limit,counted),如果sql里面带了limit则不能使用counted
				$limit = array_shift($args);
				$counted = array_shift($args);
				
				$result = null;
				if ($counted)
				{
					$result = array(
						'total' => $ds->count($sql),
					);
				}
				if ($limit) $sql = $ds->sql_limit($sql, $limit);
				
				if (is_array($result))
				{
					$result['rows'] = ($result['total'] == 0) ? [] : $ds->all($sql);
				}
				else
				{
					$result = $ds->all($sql);
				}
				break;
			case self::MODE_READ_GETCOL:// array(sql,col,limit,counted) col 下标从 0开始 为第一列
				$col = (int) array_shift($args);
				$limit = array_shift($args);
				$counted = array_shift($args);
				
				$result = null;
				if ($counted)
				{
					$result = array(
						'total' => $ds->count($sql),
					);
				}
				if ($limit) $sql = $ds->sql_limit($sql, $limit);
				if (is_array($result))
				{
					$result['rows'] = ($result['total'] == 0) ? [] : $ds->col($sql,$col);
				}
				else
				{
					$result = $ds->col($sql,$col);
				}
				break;
			case self::MODE_READ_GETROW:
				$result = $ds->row($sql);
				break;
			case self::MODE_READ_GETONE:
				$result = $ds->one($sql);
				break;			
			case self::MODE_READ_ALLCOUNT:
				$result = $ds->count($sql);
				break;
			default:
				throw new SqlError("invalid read mode: {$mode}");
		}
		
		return (empty($cb) || !is_callable($cb)) ? $result : call_user_func_array($cb,array($result));
	}
	
	/**
	 * 执行 更新/删除 操作
	 *
	 * @param SqlDataSource $ds
	 * @param string $mode 模式 [MODE_WRITE_INSERT,MODE_WRITE_UPDATE,MODE_WRITE_DELETE]
	 * @param mixed $args 参数[不同模式参数不同,缺省为sql字符串]
	 * @param callback $cb 查询结果集的回调处理函数
	 * 
	 * @return mixed
	 */
	public static function write(SqlDataSource $ds, $mode, $args, $cb=NULL)
	{
		$args = (array) $args;		
		$sql = array_shift($args);// 缺省第一个参数是sql字符串
		
		$ds->execute($sql);
		
		switch ($mode)
		{			
			case self::MODE_WRITE_INSERT: // 插入操作可选 得到主键标识
				$id = array_shift($args);
				$result = $id ? $ds->insert_id() : $ds->affected_rows();
				break;
			case self::MODE_WRITE_UPDATE:
			case self::MODE_WRITE_DELETE:
				$result = $ds->affected_rows();
				break;
			default:
				throw new SqlError("invalid write mode: {$mode}");
		}
		
		return (empty($cb) || !is_callable($cb)) ? $result : call_user_func_array($cb,array($result));
	}

}
