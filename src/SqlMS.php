<?php
namespace LianzhSQL;

/**
 * 简易的 主从 实现
 */
class SqlMS
{

    /**
     * 主库数据源
     * @var SqlDataSource
     */
    private static $ds_master;

    /**
     * 从库数据源
     * @var SqlDataSource
     */
    private static $ds_slaver;

    public static function init(array $config)
    {
        self::$ds_master = Sql::ds($config['master']);
        self::$ds_slaver = Sql::ds($config['slaver']);
    }

    /**
     * 返回主库对象
     *
     * @return \LianzhSQL\SqlMaster
     */
    public static function master()
    {
        static $master = null;
        if (is_null($master)) {
            $master = new SqlMaster(self::$ds_master);
        }
        return $master;
    }

    /**
     * 返回主库对象
     *
     * @return \LianzhSQL\SqlSlaver
     */
    public static function slaver()
    {
        static $slaver = null;
        if (is_null($slaver)) {
            $slaver = new SqlSlaver(self::$ds_slaver);
        }
        return $slaver;
    }
}
