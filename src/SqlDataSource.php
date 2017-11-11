<?php

namespace LianzhSQL;

use PDO;

/**
 * SQL 数据源类
 *
 * 配置信息说明
 *
 * 1. type = mysql/mariadb 
 * {
 * 		dbpath: mysql:host=${host};port=${port};dbname=${database}
 * 		initcmd: [
 * 			SET NAMES '${charset}',
 * 		]
 * }
 * 
 * 2. type = pgsql 
 * {
 * 		dbpath: pgsql:host=${host};port=${port};dbname=${database}
 * 		initcmd: [
 * 			SET NAMES '${charset}',
 * 		]
 * }
 *
 * 3. type = sybase 
 * {
 * 		dbpath: sybase:host=${host};port=${port};dbname=${database}
 * 		initcmd: [
 * 			SET NAMES '${charset}',
 * 		]
 * }
 *
 * 4. type = sqlite 
 * {
 * 		dbpath: sqlite:${file}
 * 		initcmd: [
 * 			
 * 		]
 * }
 *
 * 5. type = mssql 
 * {
 * 		Windows:
 * 		dbpath: sqlsrv:server=${host};port=${port};database=${database}
 *
 * 		Linux:
 * 		dbpath: dblib:host=${host};port=${port};dbname=${database}
 * 		
 * 		initcmd: [
 * 			SET QUOTED_IDENTIFIER ON,
 * 			SET NAMES '${charset}',
 * 		]
 * }
 *
 * 如果要使用持久连接,可以配置 attr 参数
 *
 * attr: [
 * 		\PDO::ATTR_PERSISTENT => TRUE,
 * ]
 *
 * 类内置使用的 \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC 不能被改变
 */
class SqlDataSource
{
    
    /**
     * @var int
     */
    private $query_count = 0;

    /**
     * @var PDO
     */
    private $db;
    
    /**
     * @var int
     */
    private $affected_rows = 0;

    public function __construct(array $dsn)
    {
    	$this->dsn = $dsn;
        $this->connected = false;
        $dsn = null;
    }

    /**
     * 解析 DSN 配置信息并返回标识ID
     * 
     * @param  array  $dsn
     * @return string
     */
    public static function dsn(array $dsn)
    {
    	foreach (array('type','dbpath','login','password') as $key)
        {
        	if ( empty($dsn[$key]) )
        	{
        		throw new SqlError("db config invalid: {$key}");
        	}
        }

        if ( empty( $dsn['attr'] ) || !is_array( $dsn['attr'] ) )
    	{
    		$dsn['attr'] = [];
    	}

    	# force use ASSOC array
    	$dsn['attr'][PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

    	if ( empty( $dsn['initcmd'] ) || !is_array( $dsn['initcmd'] ) )
    	{
    		$dsn['initcmd'] = [];
    	}
    	
    	# sql monitor
    	$dsn['monitor'] = empty( $dsn['monitor'] ) || !is_callable( $dsn['monitor'] ) ? false : $dsn['monitor'];

    	$dsn['id'] = "{$dsn['dbpath']}@{$dsn['login']}";

        return $dsn;
    }
    
	public function id()
	{
		return $this->dsn['id'];
	}
	
	public function connect()
    {
        if ($this->connected) return;
        
        $this->db = new PDO($this->dsn['dbpath'], $this->dsn['login'], $this->dsn['password'], $this->dsn['attr']);
        
        if ($this->db === FALSE)
        {
        	throw new SqlError("db connect failed: " . $this->dsn['id']);
        }
        foreach ($this->dsn['initcmd'] as $cmd)
        {
	        $result = $this->db->exec($cmd);
	        if ($result === false)
	        {
	        	$error = $this->db->errorInfo();
	        	throw new SqlError("db query failed: " . print_r([ $this->dsn['id'], $cmd, $error ], true) );
	        }
        }
        $this->connected = true;
    }
    
    public function close()
    {
        if (empty($this->dsn['attr'][PDO::ATTR_PERSISTENT]) && !is_null($this->db))
        {
            $this->db = null;
            $this->connected = false;
            $this->query_count = 0;
        }
    }

    public function begin()
    {
    	if (!$this->connected) $this->connect();
        $this->db->beginTransaction();
    }

    public function commit()
    {
    	if ($this->connected) return $this->db->commit();
    	throw new SqlError("db connected lost: {$this->dsn['id']}");        
    }

    public function rollback()
    {
    	if ($this->connected) return $this->db->rollBack();
    	throw new SqlError("db connected lost: {$this->dsn['id']}");
    }
	
    public function qstr($value)
	{
		if (is_int($value) || is_float($value)) { return $value; }
		if (is_bool($value)) { return $value ? 1 : 0; }
		if (is_null($value)) { return 'NULL'; }
		
		if (!$this->connected) $this->connect();
		return $this->db->quote($value);
	}
	
	public function insert_id()
	{
		if ($this->connected) return $this->db->lastInsertId();
    	throw new SqlError("db connected lost: {$this->dsn['id']}");
	}
	
    public function affected_rows()
    {
    	return $this->affected_rows;
    }

    private function monitor($sql)
    {
    	if ( $this->dsn['monitor'] ){
        	call_user_func_array($this->dsn['monitor'], [$sql, $this->dsn['id']]);
        }
    }
	
    public function execute($sql, array $args = null)
    {
    	$this->affected_rows = 0;
    	
       	if (!empty($args)) {
       		$sql = SqlHelper::bind($this, $sql, $args);
		}

        if (!$this->connected) $this->connect();
        
        $result = $this->db->exec($sql);
        $this->monitor($sql);
        $this->query_count++;

        if ($result === false)
        {
        	$error = $this->db->errorInfo();
        	throw new SqlError("db query failed: " . print_r([$this->dsn['id'], $sql, $error],true));
        }
        $this->affected_rows = $result;    	
    }
    
    /**
     * @return \PDOStatement
     */
    private function query($sql)
    {    	
    	if (!$this->connected) $this->connect();
    	
    	$statement = $this->db->query($sql);
        $this->monitor($sql);
        $this->query_count++;
        
        if ($statement !== false) return $statement;
        
    	$error = $this->db->errorInfo();
    	throw new SqlError("db query failed: " . print_r([$this->dsn['id'], $sql, $error],true));
    }
    
	public function all($sql)
    {
        $res = $this->query($sql);
        /* @var $res \PDOStatement */
        
        $val = $res->fetchAll(PDO::FETCH_ASSOC);
        $res = null;
        return $val;
    }
	
    public function one($sql)
    {
    	$res = $this->query($sql);
        /* @var $res \PDOStatement */
    	
    	$val = $res->fetchColumn(0);
    	$res = null;
        return $val;
    }
    
    public function row($sql)
    {
    	$res = $this->query($sql);
        /* @var $res \PDOStatement */
    	
    	$val = $res->fetch(PDO::FETCH_ASSOC);
    	
        $res = null;
        return $val;
    }
	
    public function col($sql, $col=0)
    {
        $res = $this->query($sql);
        /* @var $res \PDOStatement */
        
        $val = $res->fetchAll(PDO::FETCH_COLUMN,$col);
        $res = null;
        
        return $val;
    }

	public function count($sql)
	{
		return (int) $this->one("SELECT COUNT(*) FROM ( $sql ) AS t");
	}
	
	public function sql_limit($sql, $limit)
	{
		if (empty($limit)) return $sql;

		if (is_array($limit))
		{
			list($skip, $l) = $limit;
	        $skip = intval($skip);
          	$limit = intval($l);
	    }
	    else
	    {
	      	$skip = 0;
	       	$limit = intval($limit);
	    }
		
	    switch ( $this->dsn['type'] )
    	{
    		case 'sqlite':
    		case 'mariadb':
            case 'mysql':
    			return "{$sql} LIMIT {$skip}, {$limit}";
    		case 'pgsql':
    			return "{$sql} LIMIT {$limit} OFFSET {$skip}";
    		case 'sybase':
    		case 'mssql':
    			return $sql;
    	}
	}
	
}
