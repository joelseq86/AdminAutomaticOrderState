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

class AutomaticOrderStateRule extends ObjectModel
{

    /**
     * @var integer Automatic Group Rule ID
     */
    public $id;

    /**
     * @var integer Automatic Group ID
     */
    public $id_automatic_order_state;

    /**
     * @var integer Type
     */
    public $type;

    /**
     * @var string Object value
     */
    public $value;

    /**
     * @var string Object creation date
     */
    public $date_add;

    /**
     * @var string Object last modification date
     */
    public $date_upd;

    /**
     *
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'automatic_order_state_rule',
        'primary' => 'id_automatic_order_state_rule',
        'fields' => array(
            'id_automatic_order_state' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'type' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'value' => array(
                'type' => self::TYPE_HTML,
                'validate' => 'isSerializedArray',
                'required' => true
            ),
            'date_add' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
            'date_upd' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            )
        )
    );

    /**
     * Remove all rules associated to automatic group
     *
     * @param int $id_automatic_order_state
     *            return bool
     */
    public static function afterRemoveAutomaticOrderState($id_automatic_order_state)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'automatic_order_state_rule`
                                WHERE `id_automatic_order_state` = ' . (int)$id_automatic_order_state;
        return Db::getInstance()->execute($sql);
    }

    /**
     * Get all automatic group rules by automatic group
     *
     * @param AutomaticOrderState $automatic_order_state
     * @return AutomaticOrderStateRule[] $automatic_order_state_rules
     */
    public static function getByAutomaticOrderState($automatic_order_state)
    {
        $sql = 'SELECT
                  `id_automatic_order_state_rule`,
                  `id_automatic_order_state`,
                  `type`,
                  `value`,
                  `date_add`,
                  `date_upd`
                FROM `' . _DB_PREFIX_ . 'automatic_order_state_rule`
                WHERE `id_automatic_order_state` = ' . (int) $automatic_order_state->id;
        $result = Db::getInstance()->executeS($sql);
        $automaticOrderStateRules = \ObjectModel::hydrateCollection('AutomaticOrderStateRule', $result);

        return $automaticOrderStateRules;
    }

    /**
     * Check if order is valid for automatic group rule
     *
     * @param Order $order
     * @return bool
     */
    public function isOrderValid($order)
    {
        $valueArr = unserialize($this->value);

        switch ($this->type) {
            case 1: // Current order state
                if ($valueArr['cos_id_order_state_condition'] == 0
                    && $order->current_state == (int)$valueArr['cos_id_order_state']
                ) {
                    return true;
                }

                if ($valueArr['cos_id_order_state_condition'] == 1
                    && $order->current_state != (int)$valueArr['cos_id_order_state']
                ) {
                    return true;
                }

                break;
            case 2: // Current order state date
                $sql = 'SELECT MAX(`date_add`)
                        FROM `' . _DB_PREFIX_ . 'order_history`
                        WHERE `id_order` = ' . (int)$order->id . '
                        GROUP BY `id_order`';
                $result = Db::getInstance()->getValue($sql);

                if ($result !== false) {
                    $checkDate = time() - ((float)$valueArr['cosd_value'] * 86400);
                    $orderDate = strtotime($result);

                    if ($valueArr['cosd_condition'] == '>' && $orderDate > $checkDate) {
                        if ($order->current_state == (int)$valueArr['cosd_id_order_state']) {
                            return true;
                        }
                    }
                    if ($valueArr['cosd_condition'] == '<' && $orderDate < $checkDate) {
                        if ($order->current_state == (int)$valueArr['cosd_id_order_state']) {
                            return true;
                        }
                    }
                    if ($valueArr['cosd_condition'] == '='
                        && date('Y-m-d', $orderDate) == date('Y-m-d', $checkDate)
                    ) {
                        if ($order->current_state == (int)$valueArr['cosd_id_order_state']) {
                            return true;
                        }
                    }
                }

                break;
            case 3: // Payment method
                if ($order->module == $valueArr['pm_name']) {
                    return true;
                }

                break;
            case 4: // Carrier method
                $carrier = new Carrier($order->id_carrier);
                if ($carrier->id_reference == $valueArr['cm_id_reference']) {
                    return true;
                }

                break;
            case 5: // Previous order state
                $orderHistories = $order->getHistory(ContextCore::getContext()->language->id);

                if (isset($orderHistories[1])) {
                    if ($valueArr['pos_id_order_state_condition'] == 0
                        && $orderHistories[1]['id_order_state'] == (int)$valueArr['pos_id_order_state']
                    ) {
                        return true;
                    }

                    if ($valueArr['pos_id_order_state_condition'] == 1
                        && $orderHistories[1]['id_order_state'] != (int)$valueArr['pos_id_order_state']
                    ) {
                        return true;
                    }
                }

                break;
            case 6: // Historical order state
                $orderHistories = $order->getHistory(ContextCore::getContext()->language->id);
                $orderStateFound = false;
                unset($orderHistories[0]);
                foreach ($orderHistories as $orderHistory) {
                    if ($orderHistory['id_order_state'] == (int)$valueArr['hos_id_order_state']) {
                        $orderStateFound = true;
                        break;
                    }
                }

                if ($valueArr['hos_id_order_state_condition'] == 0 && $orderStateFound) {
                    return true;
                }

                if ($valueArr['hos_id_order_state_condition'] == 1 && !$orderStateFound) {
                    return true;
                }

                break;
            case 7: // Current order state date
                $sql = 'SELECT MAX(`date_add`)
                        FROM `' . _DB_PREFIX_ . 'order_history`
                        WHERE `id_order` = ' . (int)$order->id . '
                        AND `id_order_state` = ' . (int)$valueArr['hosd_id_order_state'] . '
                        GROUP BY `id_order`';
                $result = Db::getInstance()->getValue($sql);

                if ($result !== false) {
                    $checkDate = time() - ((float)$valueArr['hosd_value'] * 86400);
                    $orderDate = strtotime($result);

                    if ($valueArr['hosd_condition'] == '>' && $orderDate > $checkDate) {
                        return true;
                    }
                    if ($valueArr['hosd_condition'] == '<' && $orderDate < $checkDate) {
                        return true;
                    }
                    if ($valueArr['hosd_condition'] == '='
                        && date('Y-m-d', $orderDate) == date('Y-m-d', $checkDate)
                    ) {
                        return true;
                    }
                }

                break;
            case 8: // Order product
                $sql = 'SELECT COUNT(*) AS `count`
                        FROM `' . _DB_PREFIX_ . 'order_detail`
                        WHERE `id_order` = ' . (int)$order->id . '
                        AND `product_id` = ' . (int)$valueArr['op_id_product'];
                $count = Db::getInstance()->getValue($sql);

                if ($valueArr['op_condition'] == 0) {
                    if ($count > 0) {
                        return true;
                    }
                    if ($count == 0) {
                        return false;
                    }
                }

                if ($valueArr['op_condition'] == 1) {
                    if ($count > 0) {
                        return false;
                    }
                    if ($count == 0) {
                        return true;
                    }
                }

                break;
        }

        return false;
    }

    /**
     * Get sql condition to select affected orders
     */
    public function getSqlCondition()
    {
        $sql = array();

        $valueArr = unserialize($this->value);

        switch ($this->type) {
            case 1: // Current order state
                $condition = '=';
                if ($valueArr['cos_id_order_state_condition'] == 1) {
                    $condition = '!=';
                }
                $sql['where'] = 'o.`current_state` ' . $condition . ' ' . (int)$valueArr['cos_id_order_state'];

                break;
            case 2: // Current order state date
                $checkDate = date('Y-m-d H:i:s', time() - ((float)$valueArr['cosd_value'] * 86400));

                $sql['join'] = 'LEFT JOIN `' . _DB_PREFIX_ . 'order_history` oh ON o.`id_order` = oh.`id_order`';
                $sql['where'] = 'oh.date_add = (
                    SELECT MAX(`date_add`)
                    FROM `' . _DB_PREFIX_ . 'order_history`
                    WHERE `id_order` = o.`id_order`
                    GROUP BY `id_order`
                )
                AND oh.`date_add` ' . pSQL($valueArr['cosd_condition'], true) . ' \'' . pSQL($checkDate) . '\'
                AND o.`current_state` = ' . (int)$valueArr['cosd_id_order_state'];

                break;
            case 3: // Payment method
                $sql['where'] = 'o.`module` = \'' . pSQL($valueArr['pm_name']) . '\'';

                break;
            case 4: // Carrier method
                $sql['join'] = 'LEFT JOIN `' . _DB_PREFIX_ . 'carrier` c ON o.`id_carrier` = c.`id_carrier`';
                $sql['where'] = 'c.`id_reference` = ' . (int)$valueArr['cm_id_reference'];

                break;
            case 5: // Previous order state
                $condition = '=';
                if ($valueArr['pos_id_order_state_condition'] == 1) {
                    $condition = '!=';
                }

                $sql['join'] = 'JOIN (
                    SELECT oh1.`id_order`, oh1.`id_order_state`, COUNT(*) 
                    FROM `' . _DB_PREFIX_ . 'order_history` oh1 
                    JOIN `' . _DB_PREFIX_ . 'order_history` oh2 ON oh1.`id_order` = oh2.`id_order` 
                        AND oh1.`id_order_history` <= oh2.`id_order_history`
                    GROUP BY oh1.`id_order`, oh1.`id_order_history`
                    HAVING COUNT(*) = 2
                ) pos ON o.id_order = pos.id_order';
                $sql['where'] = 'pos.`id_order_state` ' . $condition . ' ' . (int)$valueArr['pos_id_order_state'];

                break;
            case 6: // Historical order state
                $condition = 'IS NOT NULL';
                if ($valueArr['hos_id_order_state_condition'] == 1) {
                    $condition = 'IS NULL';
                }

                $sql['join'] = 'LEFT JOIN (
                    SELECT oh2.`id_order`, oh2.`id_order_state`
                    FROM (
                        SELECT oh1.`id_order`, oh1.`id_order_state`, COUNT(*) 
                        FROM `' . _DB_PREFIX_ . 'order_history` oh1 
                        JOIN `' . _DB_PREFIX_ . 'order_history` oh2 ON oh1.`id_order` = oh2.`id_order` 
                        AND oh1.`id_order_history` <= oh2.`id_order_history`
                        GROUP BY oh1.`id_order`, oh1.`id_order_history`
                        HAVING COUNT(*) >= 2
                    ) oh2
                    WHERE oh2.`id_order_state` = ' . (int)$valueArr['hos_id_order_state'] . '
                ) pos ON o.id_order = pos.id_order';
                $sql['where'] = 'pos.`id_order_state` ' . $condition;
                break;
            case 7: // Historical order state date
                $checkDate = date('Y-m-d H:i:s', time() - ((float)$valueArr['hosd_value'] * 86400));

                $sql['join'] = 'LEFT JOIN `' . _DB_PREFIX_ . 'order_history` oh ON o.`id_order` = oh.`id_order`';
                $sql['where'] = 'oh.date_add = (
                    SELECT MAX(`date_add`)
                    FROM `' . _DB_PREFIX_ . 'order_history`
                    WHERE `id_order` = o.`id_order`
                    AND `id_order_state` = ' . (int)$valueArr['hosd_id_order_state'] . '
                    GROUP BY `id_order`
                )
                AND oh.`date_add` ' . pSQL($valueArr['hosd_condition'], true) . ' \'' . pSQL($checkDate) . '\'';

                break;
            case 8: // Order product
                $condition = '=';
                if ($valueArr['op_condition'] == 1) {
                    $condition = '!=';
                }

                $sql['where'] = 'o.`id_order` IN (
                    SELECT id_order
                    FROM `' . _DB_PREFIX_ . 'order_detail`
                    WHERE `product_id` ' . pSQL($condition, true) . ' ' . (int)$valueArr['op_id_product'] . '
                    GROUP BY `id_order`
                )';

                break;
        }

        return $sql;
    }
}
