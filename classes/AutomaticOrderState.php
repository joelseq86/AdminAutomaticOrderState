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

class AutomaticOrderState extends ObjectModel
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
     * @var integer Name
     */
    public $name;

    /**
     * @var boolean Status
     */
    public $active = true;

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
        'table' => 'automatic_order_state',
        'primary' => 'id_automatic_order_state',
        'fields' => array(
            'id_order_state' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ),
            'name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isMessage',
                'required' => true
            ),
            'active' => array(
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'copy_post' => false
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

    private static function afterRemoveAutomatcOrderState($id_automatic_order_state)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'automatic_order_state_shop`
                WHERE `id_automatic_order_state` = ' . (int)$id_automatic_order_state;
        return Db::getInstance()->execute($sql);
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     * @return bool
     */
    public function add($autodate = true, $nullValues = false)
    {
        $ret = parent::add($autodate, $nullValues);
        if (Tools::isSubmit('checkBoxShopAsso_automatic_order_state')) {
            foreach (Tools::getValue('checkBoxShopAsso_automatic_order_state') as $idShop => $value) {
                unset($value);

                $position = self::getLastPosition($idShop) + 1;
                $this->addPosition($position, $idShop);
            }
        } else {
            foreach (Shop::getShops(true) as $shop) {
                $position = self::getLastPosition($shop['id_shop']) + 1;
                $this->addPosition($position, $shop['id_shop']);
            }
        }

        return $ret;
    }

    /**
     * this function return the number of automatic order state.
     *
     * @param int $idShop
     * @return int
     */
    public static function getLastPosition($idShop)
    {
        return Db::getInstance()->getValue('
            SELECT MAX(aoss.`position`)
            FROM `' . _DB_PREFIX_ . 'automatic_order_state` aos
            LEFT JOIN `' . _DB_PREFIX_ . 'automatic_order_state_shop` aoss ON (
                aos.`id_automatic_order_state` = aoss.`id_automatic_order_state`
                AND aoss.`id_shop` = ' . (int)$idShop . '
            )');
    }

    public function addPosition($position, $idShop = null)
    {
        $return = true;
        if (is_null($idShop)) {
            if (Shop::getContext() != Shop::CONTEXT_SHOP) {
                foreach (Shop::getContextListShopID() as $idShop) {
                    $return &= Db::getInstance()->execute('
                        INSERT INTO `' . _DB_PREFIX_ . 'automatic_order_state_shop` (
                            `id_automatic_order_state`,
                            `id_shop`,
                            `position`
                        ) VALUES (
                            ' . (int)$this->id . ',
                            ' . (int)$idShop . ',
                            ' . (int)$position . '
                        )
                        ON DUPLICATE KEY UPDATE `position` = ' . (int)$position);
                }
            } else {
                $id = Context::getContext()->shop->id;
                $idShop = $id ? $id : \Configuration::get('PS_SHOP_DEFAULT');
                $return &= Db::getInstance()->execute('
                    INSERT INTO `' . _DB_PREFIX_ . 'automatic_order_state_shop` (
                        `id_automatic_order_state`,
                        `id_shop`,
                        `position`
                    ) VALUES (
                        ' . (int)$this->id . ',
                        ' . (int)$idShop . ',
                        ' . (int)$position . '
                    )
                    ON DUPLICATE KEY UPDATE `position` = ' . (int)$position);
            }
        } else {
            $return &= Db::getInstance()->execute('
                INSERT INTO `' . _DB_PREFIX_ . 'automatic_order_state_shop` (
                    `id_automatic_order_state`,
                    `id_shop`,
                    `position`
                ) VALUES (
                    ' . (int)$this->id . ',
                    ' . (int)$idShop . ',
                    ' . (int)$position . '
                )
                ON DUPLICATE KEY UPDATE `position` = ' . (int)$position);
        }

        return $return;
    }

    public function updatePosition($way, $position)
    {
        Shop::addTableAssociation('automatic_order_state', array(
            'type' => 'shop'
        ));

        if (!$res = Db::getInstance()->executeS('
            SELECT aos.`id_automatic_order_state`, automatic_order_state_shop.`position`
            FROM `' . _DB_PREFIX_ . 'automatic_order_state` aos
            ' . Shop::addSqlAssociation('automatic_order_state', 'aos') . '
            ORDER BY automatic_order_state_shop.`position` ASC')
        ) {
            return false;
        }

        $movedAutomaticOrderState = false;
        foreach ($res as $automaticOrderState) {
            if ((int)$automaticOrderState['id_automatic_order_state'] == (int)$this->id) {
                $movedAutomaticOrderState = $automaticOrderState;
            }
        }

        if ($movedAutomaticOrderState === false || !$position) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $result = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'automatic_order_state` aos
            ' . Shop::addSqlAssociation('automatic_order_state', 'aos') . '
            SET automatic_order_state_shop.`position` =
                automatic_order_state_shop.`position` ' . ($way ? '- 1' : '+ 1') . '
            WHERE automatic_order_state_shop.`position`
            ' . (
                $way ?
               '> ' . (int)$movedAutomaticOrderState['position'] . '
                AND automatic_order_state_shop.`position` <= ' . (int)$position :
                '< ' . (int)$movedAutomaticOrderState['position'] . '
                AND automatic_order_state_shop.`position` >= ' . (int)$position
            )
        )
        && Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'automatic_order_state` aos ' .
            Shop::addSqlAssociation('automatic_order_state', 'aos') . '
            SET automatic_order_state_shop.`position` = ' . (int)$position . '
            WHERE aos.`id_automatic_order_state`=' . (int)$movedAutomaticOrderState['id_automatic_order_state']
        );

        return $result;
    }

    public static function cleanPositions()
    {
        Shop::addTableAssociation('automatic_order_state', array(
            'type' => 'shop'
        ));

        $return = true;
        $result = Db::getInstance()->executeS('
            SELECT aos.`id_automatic_order_state`
            FROM `' . _DB_PREFIX_ . 'automatic_order_state` aos
            ' . Shop::addSqlAssociation('automatic_order_state', 'aos') . '
            ORDER BY automatic_order_state_shop.`position`');
        $count = count($result);
        for ($i = 0; $i < $count; $i ++) {
            $return &= Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'automatic_order_state` aos
                ' . Shop::addSqlAssociation('automatic_order_state', 'aos') . '
                SET automatic_order_state_shop.`position` = ' . ($i + 1) . '
                WHERE aos.`id_automatic_order_state` = ' . (int)$result[$i]['id_automatic_order_state']);
        }

        return $return;
    }

    public function delete()
    {
        if (parent::delete()) {
            AutomaticOrderStateRule::afterRemoveAutomaticOrderState($this->id);
            AutomaticOrderState::afterRemoveAutomatcOrderState($this->id);
            self::cleanPositions();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Function get affected order ids
     *
     * @return int[] $id_orders
     */
    public function getAffectedIdOrders()
    {
        $idOrders = array();

        $automaticOrderStateRules = AutomaticOrderStateRule::getByAutomaticOrderState($this->id);
        foreach ($automaticOrderStateRules as $automaticOrderStateRule) {
            $automaticOrderStateRule->getSqlCondition();
        }


        return $idOrders;
    }

    /**
     * Get all automatic group states
     *
     * @param bool $active
     * @return AutomaticOrderState[] $automaticOrderStates
     */
    public static function getAllAutomaticOrderStates($active = true)
    {
        $sql = 'SELECT
                    aos.`id_automatic_order_state`,
                    aos.`id_order_state`,
                    aos.`name`,
                    aos.`active`,
                    aos.`date_add`,
                    aos.`date_upd`
                FROM `' . _DB_PREFIX_ . 'automatic_order_state` aos
                ' . (($active) ? ('WHERE aos.`active` = 1') : (''));
        $result = Db::getInstance()->executeS($sql);
        $automaticOrderStates = \ObjectModel::hydrateCollection('AutomaticOrderState', $result);

        return $automaticOrderStates;
    }

    public static function getAutomatciGroupsByOrder($order)
    {
        $sql = 'SELECT
                    aos.`id_automatic_order_state`,
                    aos.`id_order_state`,
                    aos.`name`,
                    aos.`active`,
                    aos.`date_add`,
                    aos.`date_upd`
                FROM `' . _DB_PREFIX_ . 'automatic_order_state` aos
                JOIN `' . _DB_PREFIX_ . 'automatic_order_state_shop` aoss ON (
                    aos.`id_automatic_order_state` = aoss.`id_automatic_order_state`
                    AND aoss.`id_shop` = ' . (int)$order->id_shop . '
                )
                WHERE aos.`active` = 1
                ORDER BY aoss.`position` ASC';
        $result = Db::getInstance()->executeS($sql);
        $automaticOrderStates = \ObjectModel::hydrateCollection('AutomaticOrderState', $result);

        return $automaticOrderStates;
    }

    /**
     * Check if order is valid for automatic group insert
     *
     * @param Order $order
     * @return bool
     */
    public function isOrderValid($order)
    {
        $automaticOrderStateRules = AutomaticOrderStateRule::getByAutomaticOrderState($this);
        foreach ($automaticOrderStateRules as $automaticOrderStateRule) {
            if (!$automaticOrderStateRule->isOrderValid($order)) {
                return false;
            }
        }

        return true;
    }
}
