# LianzhSQL
a light sql query lib for php.


## Make the best PHP database operation lib 

 * Simple and understandable source code 
 * Simple configuration, easy to use 
 * Support multiple databases based on PDO, not coupled, do not rely on third-party libraries 
 * Moderate function , Easy to integrate 
 * Support PHP5.4+ 
 * Safe anti-sql injection 

## Based on the principle of minimum interface 

Developers basically only need to use 2 convenient functions of the Sql class to operate all functions: 

```php 
\LianzhSQL\Sql::ds # get data source object 
\LianzhSQL\Sql::assistant # Sql auxiliary class object obtained 
```

## manual 

```php
class JptestApp
{

	/**
	 * @var SqlDataSource
	 */
	static $ds = null;

	static function sql_monitor($sql, $dsn_id)
	{
		if (PHP_SAPI === 'cli')
		{
			fwrite(STDOUT, "[sql]: " . print_r($sql,true) . PHP_EOL);
		}
		else
		{
			echo "<BR />[sql]: " . print_r($sql,true);
		}
	}

}

function jptest_init()
{
	$dsn = array(
			'type' => 'mysql',

			'dbpath'  => 'mysql:host=127.0.0.1;port=3306;dbname=jptest',
			'login'	=> 'root',
			'password' => '123456',

			'initcmd' => array(
					"SET NAMES 'utf8'",
				),

			'attr'	=> array(
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_PERSISTENT => false,
				),

			'monitor'	=> 'JptestApp::sql_monitor',
		);
	JptestApp::$ds = \LianzhSQL\Sql::ds($dsn);
	var_dump(JptestApp::$ds);
	
	$result = null;
	// $result = JptestApp::$ds->all('show tables');
	// 
	// $result = \LianzhSQL\Sql::assistant( JptestApp::$ds )->select_row('ixr_citys',array('island'=>array(1,'>=')),'id,name,image');
	// 
	// $result = \LianzhSQL\Sql::assistant( JptestApp::$ds )->select('ixr_citys',array('id'=>array(1,'>=')),'id,name,image');
	
	prety_printr( $result );
}

```

## Strong search condition generation

```php

use LianzhSQL\SqlHelper;

function assertEqual($var1,$var2){
	if ($var1 !== $var2)
		throw new Exception('Not Equal .');
}

$ds = JptestApp::$ds;
/* @var $ds SqlDataSource */

$cond = "author_id=123 AND bookname='仓一鼠'";
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id=123 AND bookname='仓一鼠'");

// ? Is an array
$cond = array(
	'author_id' => 123,
	'bookname' => '仓一鼠',
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id = 123 AND bookname = '仓一鼠'");

// > < != 
$cond = array(
	'author_id' => array(123, '>'),
	'bookname' => '仓一鼠',
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id > 123 AND bookname = '仓一鼠'");

$cond = array(
	'author_id' => array(123, '<'),
	'bookname' => '仓一鼠',
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id < 123 AND bookname = '仓一鼠'");

$cond = array(
	'author_id' => array(123, '!='),
	'bookname' => '仓一鼠',
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id != 123 AND bookname = '仓一鼠'");

// fuzzy query
$cond = array(
	'bookname' => array('%仓一鼠%','LIKE'),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"bookname LIKE '%仓一鼠%'");

// 'IN','NOT IN'
$cond = array(
	'author_id' => array( array(123,124,125) ),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id IN (123,124,125)");

$cond = array(
	'author_id' => array( array(123,124,125), 'IN'),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id IN (123,124,125)");

$cond = array(
	'author_id' => array( array(123,124,125), 'NOT IN'),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id NOT IN (123,124,125)");

// BETWEEN AND , NOT BETWEEN AND
$cond = array(
	'author_id' => array( array(10,25), 'BETWEEN_AND'),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id  BETWEEN 10 AND 25");

$cond = array(
	'author_id' => array( array(10,25), 'NOT_BETWEEN_AND'),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id NOT BETWEEN 10 AND 25");

// author_id > 15 OR author_id < 5 AND author_id != 32
$cond = array(
	'author_id' => array(
		array( array(15,'>','OR'),array(5,'<','AND'), array(32,'!=') ) ,
		'FIELD_GROUP'
	),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"  (author_id > 15 OR author_id < 5 AND author_id != 32)");

// OR AND connector
$cond = array(
	'author_id' => array(123, '!=' ,'AND'),
	'bookname' => array('仓一鼠', '=' ,'OR'),
	'book_price' => array(45, '<=' ,'AND'),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id != 123 AND bookname = '仓一鼠' OR book_price <= 45");

// Special characters in the value of the incoming condition will be automatically escaped by qstr
$cond = array(
	'bookname' => array("%色'色%",'LIKE'),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
// note osc 'There is a problem with symbol resolution, so 2 \
assertEqual($result,"bookname LIKE '%色\\'色%'");

// 数据表字段名比较
$cond = array(
	'author_id' => array(123, '!=' ,'AND'),
	'book_price' => array("market_parce",'>','AND',true),
);
$result = SqlHelper::parse_cond($ds,$cond,FALSE);
assertEqual($result,"author_id != 123 AND book_price > market_parce");

```

## Simple configuration and multi-database support

```php
Description of configuration information
1. type = mysql/mariadb 
{
		dbpath: mysql:host=${host};port=${port};dbname=${database}
		initcmd: [
			SET NAMES '${charset}',
		]
}

2. type = pgsql 
{
		dbpath: pgsql:host=${host};port=${port};dbname=${database}
		initcmd: [
			SET NAMES '${charset}',
		]
}

3. type = sybase 
{
		dbpath: sybase:host=${host};port=${port};dbname=${database}
		initcmd: [
			SET NAMES '${charset}',
		]
}

4. type = sqlite 
{
		dbpath: sqlite:${file}
		initcmd: [
			
		]
}

5. type = mssql 
{
		Windows:
		dbpath: sqlsrv:server=${host};port=${port};database=${database}

		Linux:
		dbpath: dblib:host=${host};port=${port};dbname=${database}
		
		initcmd: [
			SET QUOTED_IDENTIFIER ON,
			SET NAMES '${charset}',
		]
}

If you want to use persistent connections, you can configure attr parameters

attr: [
		PDO::ATTR_PERSISTENT => TRUE,
]

The PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC used by the class cannot be changed
```
