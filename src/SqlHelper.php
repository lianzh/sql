<?php

namespace LianzhSQL;

class SqlHelper
{
	
	public static function bind(SqlDataSource $ds, $sql, array $inputarr)
	{
		$arr = explode('?', $sql);
        $sql = array_shift($arr);
        foreach ($inputarr as $value) {
            if (isset($arr[0])) {
                $sql .= $ds->qstr($value) . array_shift($arr);
            }
        }
        return $sql;
	}
	
	public static function parse_cond(SqlDataSource $ds, $cond, $dash=false)
	{
		static $equal_in = array('=','IN','NOT IN');
        static $between_and = array('BETWEEN_AND','NOT_BETWEEN_AND');
        
		if (empty($cond)) return '';
 		
		// 如果是字符串，则假定为自定义条件
        if (is_string($cond)) return $cond;
	
        // 如果不是数组，说明提供的查询条件有误
        if (!is_array($cond)) return '';
        
        
 		$where = '';$expr = '';
 		
 		/**
         * 不过何种条件形式，一律为  字段名 => (值, 操作, 连接运算符, 值是否是SQL命令) 的形式
         */
 		foreach ($cond as $field => $d) 
 		{
 			
 			$expr = 'AND';
            
 			if (!is_string($field)) {
 				continue;
 			}
 			if (!is_array($d)) {
                // 字段名 => 值
            	$d = array($d);
            }
            reset($d);
            // 第一个元素是值
 			if (!isset($d[1])) { $d[1] = '='; }
            if (!isset($d[2])) { $d[2] = $expr; }
            if (!isset($d[3])) { $d[3] = false; }
			
            list($value, $op, $expr, $is_cmd) = $d;
            
            $op = strtoupper(trim($op));            
            $expr = strtoupper(trim($expr));
            
            if (is_array($value))
            {
 				
 				do {
 					if (in_array($op, $equal_in)){
 						if ($op == '=') $op = 'IN';
 						$value = '(' . implode(',',array_map([$ds, 'qstr'],$value)) . ')';
 						break;
 					} 					
 					
	 				if (in_array($op, $between_and)){	 					
	 					$between = array_shift($value);
	 					
	 					$and = array_shift($value);
	 					$value = sprintf('BETWEEN %s AND %s',$ds->qstr($between),$ds->qstr($and));
	 					$op = 'NOT_BETWEEN_AND' == $op ? 'NOT' : '';// 此处已经串在 $value 中了
	 					break;
	 				}
 					
	 				// 一个字段对应 多组条件 的实现,比如 a > 15 OR a < 5 and a != 32
	 				// 'a' => array(  array( array(15,'>','OR'),array(5,'<','AND'), array(32,'!=') ) , 'FIELD_GROUP')
 					if ($op == 'FIELD_GROUP'){
 						$kv = array();
 						foreach($value as $k => $v){
 							$kv[":+{$k}+:"] = $v;
 						}
 						$value = self::parse_cond($ds,$kv,true);
 						
 						foreach(array_keys($kv) as $k){
 							$value = str_ireplace($k,$field,$value);
 						}
 						
 						$field = $op = '';// 此处已经串在 $value 中了
	 					break;
 					}
 					
 				} while(false);
 				
 				$is_cmd = true;
 			}
 			
 			if (!$is_cmd) {
				$value = $ds->qstr($value);
			}
			$where .= "{$field} {$op} {$value} {$expr} ";
 		}
 		
        $where = substr($where, 0, - (strlen($expr) + 2));
        return $dash ? "({$where})" : $where;
	}
		
	public static function qtable($table)
	{
		return "`{$table}`";
	}
	
	public static function qfield($field, $table = null)
	{
		$field = ($field == '*') ? '*' : "`{$field}`";
		return $table != '' ? self::qtable($table) . '.' . $field : $field;
	}
		
    public static function qfields($fields, $table = null, $get_arr = false)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
            $fields = array_map('trim', $fields);
        }
        $result = [];
        foreach ($fields as $field) {
            $result[] = self::qfield($field, $table);
        }
       
        return $get_arr ? $result : implode(', ', $result);
    }
	
    public static function placeholder(&$inputarr, $fields = null)
    {
        $holders = [];
        $values = [];
        if (is_array($fields)) {
            $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
            foreach (array_keys($inputarr) as $key) {
                if (!isset($fields[strtolower($key)])) { continue; }
                $holders[] = '?';
                $values[$key] =&$inputarr[$key];
            }
        } else {
            foreach (array_keys($inputarr) as $key) {
                $holders[] = '?';
                $values[$key] =&$inputarr[$key];
            }
        }
        return array($holders, $values);
    }
    
    public static function placeholder_pair(&$inputarr, $fields = null)
    {
        $pairs = [];
        $values = [];
        if (is_array($fields)) {
            $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
            foreach (array_keys($inputarr) as $key) {
                if (!isset($fields[strtolower($key)])) { continue; }
                $qkey = self::qfield($key);
                $pairs[] = "{$qkey}=?";
                $values[$key] =&$inputarr[$key];
            }
        } else {
            foreach (array_keys($inputarr) as $key) {
                $qkey = self::qfield($key);
                $pairs[] = "{$qkey}=?";
                $values[$key] =&$inputarr[$key];
            }
        }
        return array($pairs, $values);
    }

    public static function timestamp($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
    
}
