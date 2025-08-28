<?php
/**
 * 2020 PrestaWach
 *
 * @author    PrestaWach <info@prestawach.eu>
 * @copyright 2020 PrestaWach
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit();
}

class AutomaticOrderStateCron extends ObjectModel
{

    /**
     * @var integer Automatic Group ID
     */
    public $id;

    /**
     * @var integer Order State ID
     */
    public $id_order_state;

    /**
     * Function to initialize cron request
     */
    public static function init()
    {
        self::truncateTable();

        $automaticOrderStates = AutomaticOrderState::getAllAutomaticOrderStates(true);
        foreach ($automaticOrderStates as $automaticOrderState) {
            $automaticOrderStateRules = AutomaticOrderStateRule::getByAutomaticOrderState($automaticOrderState);

            if (!empty($automaticOrderStateRules)) {
                $sqls = array();
                foreach ($automaticOrderStateRules as $automaticOrderStateRule) {
                    $sqls[] = $automaticOrderStateRule->getSqlCondition();
                }

                $sql = self::prepareSql($sqls);
                $result = Db::getInstance()->executeS($sql);
                foreach ($result as $row) {
                    Db::getInstance()->insert('automatic_order_state_cron', $row, false, true, Db::INSERT_IGNORE);
                }
            }
        }
    }

    /**
     * Function to prepare sql statement
     *
     * @param string[] $sqls
     * @param AutomaticOrderState $automaticOrderState
     * @return string $sql
     */
    public static function prepareSql($sqls)
    {
        $sql = '';

        if (!empty($sqls)) {
            $where = array();
            $join = array();

            foreach ($sqls as $sqlArr) {
                if (isset($sqlArr['where'])) {
                    $where[] = $sqlArr['where'];
                }
                if (isset($sqlArr['join'])) {
                    $join[] = $sqlArr['join'];
                }
            }

            $where = array_unique($where);
            $join = array_unique($join);

            $whereStr = implode(' AND ', $where);
            $joinStr = implode(' ', $join);

            if (!empty($whereStr)) {
                $sql = 'SELECT DISTINCT o.`id_order`
                        FROM `' . _DB_PREFIX_ . 'orders` o
                        ' . $joinStr . '
                        WHERE ' . $whereStr;
            }
        }

        return $sql;
    }

    /**
     * Function to truncate cron table
     */
    public static function truncateTable()
    {
        $sql = 'TRUNCATE TABLE `' . _DB_PREFIX_ . 'automatic_order_state_cron`';
        Db::getInstance()->execute($sql);
    }
}
