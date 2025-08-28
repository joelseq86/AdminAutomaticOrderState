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

require_once _PS_MODULE_DIR_ . 'automaticorderstates/classes/AutomaticOrderState.php';
require_once _PS_MODULE_DIR_ . 'automaticorderstates/classes/AutomaticOrderStateRule.php';
require_once _PS_MODULE_DIR_ . 'automaticorderstates/classes/AutomaticOrderStateCron.php';

class AutomaticOrderStates extends Module
{
    public function __construct()
    {
        $this->name = 'automaticorderstates';
        $this->tab = 'administration';
        $this->version = '1.0.13';
        $this->author = 'PrestaWach';
        $this->need_instance = 0;
        $this->module_key = '39c987652c8243d56b0d3bff37954eba';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Automatic Order Status');
        $this->description = $this->l('Automatic order status to generate rules
            that automaticly change order status.');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->installTab()
            && $this->installDb();
    }

    /**
     * @return bool
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminAutomaticOrderState';
        $tab->module = $this->name;
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Automatic Order Status';
        }
        $tab->id_parent = $this->getTabParentId();
        $tab->active = 1;
        if (!$tab->add()) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    private function getTabParentId()
    {
        $idParent = 10;

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=') === true) {
            $idParent = 3;
        }

        return $idParent;
    }

    /**
     * @return boolean
     */
    public function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'automatic_order_state` (
                `id_automatic_order_state` int(11) NOT NULL AUTO_INCREMENT,
                `id_order_state` int(10) unsigned NOT NULL,
                `name` varchar(128) NOT NULL,
                `active` tinyint(1) NOT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                PRIMARY KEY (`id_automatic_order_state`),
                KEY `id_order_state` (`id_order_state`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';
        if (Db::getInstance()->execute($sql)) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'automatic_order_state_shop` (
                    `id_automatic_order_state` int(11) unsigned NOT NULL,
                    `id_shop` int(11) unsigned NOT NULL,
                    `position` INT(10) UNSIGNED NOT NULL,
                    PRIMARY KEY (`id_automatic_order_state`,`id_shop`),
                    KEY `id_shop` (`id_shop`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';
            if (Db::getInstance()->execute($sql)) {
                $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'automatic_order_state_rule` (
                        `id_automatic_order_state_rule` int(11) NOT NULL AUTO_INCREMENT,
                        `id_automatic_order_state` int(11) unsigned NOT NULL,
                        `type` int(10) unsigned NOT NULL,
                        `value` text NOT NULL,
                        `date_add` datetime NOT NULL,
                        `date_upd` datetime NOT NULL,
                        PRIMARY KEY (`id_automatic_order_state_rule`),
                        KEY `id_automatic_order_state` (`id_automatic_order_state`)
                        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';
                if (Db::getInstance()->execute($sql)) {
                    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'automatic_order_state_cron` (
                                `id_automatic_order_state_cron` int(11) NOT NULL AUTO_INCREMENT,
                                `id_order` int(11) NOT NULL,
                                PRIMARY KEY (`id_automatic_order_state_cron`),
                                UNIQUE KEY `id_order` (`id_order`)
                            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';
                    if (Db::getInstance()->execute($sql)) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTab()
            && $this->uninstallDb();
    }

    /**
     * @return bool
     */
    private function uninstallTab()
    {
        $idTab = Tab::getIdFromClassName('AdminAutomaticOrderState');
        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $tab->delete();

            return true;
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function uninstallDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'automatic_order_state`';
        if (Db::getInstance()->execute($sql)) {
            $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'automatic_order_state_shop`';
            if (Db::getInstance()->execute($sql)) {
                $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'automatic_order_state_rule`';
                if (Db::getInstance()->execute($sql)) {
                    $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'automatic_order_state_cron`';
                    if (Db::getInstance()->execute($sql)) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param null $cursor
     * @param bool $ajax
     * @param bool $smart
     * @return int|null|string
     */
    public function cronProcess($cursor = null, $ajax = false, $smart = false)
    {
        if (version_compare(_PS_VERSION_, '1.7.6.0', '>=') === true) {
            global $kernel;
            if (!$kernel) {
                require_once _PS_ROOT_DIR_ . '/app/AppKernel.php';
                $kernel = new \AppKernel('prod', false);
                $kernel->boot();
            }
        }

        // start build id order list for cron process
        if (empty($cursor)) {
            AutomaticOrderStateCron::init();
        }
        // end build id order list for cron process

        $nbOrders = (int)Db::getInstance()->getValue('
            SELECT COUNT(`id_automatic_order_state_cron`)
            FROM `' . _DB_PREFIX_ . 'automatic_order_state_cron`
        ');

        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime > 5 || $maxExecutionTime <= 0) {
            $maxExecutionTime = 5;
        }

        $startTime = microtime(true);

        if (function_exists('memory_get_peak_usage')) {
            do {
                $cursor = (int)$this->applyOrderStateUnbreakable((int)$cursor, $smart);
                $timeElapsed = microtime(true) - $startTime;
            } while ($cursor < $nbOrders
                && Tools::getMemoryLimit() > memory_get_peak_usage()
                && $timeElapsed < $maxExecutionTime
            );
        } else {
            do {
                $cursor = (int)$this->applyOrderStateUnbreakable((int)$cursor, $smart);
                $timeElapsed = microtime(true) - $startTime;
            } while ($cursor < $nbOrders && $timeElapsed < $maxExecutionTime);
        }

        if (!$ajax && $nbOrders > 0 && $cursor < $nbOrders) {
            $token = Tools::substr(Tools::encrypt('automaticorderstates'), 0, 10);
            if (Tools::usingSecureMode()) {
                $domain = Tools::getShopDomainSsl(true);
            } else {
                $domain = Tools::getShopDomain(true);
            }

            Tools::file_get_contents(
                $domain . __PS_BASE_URI__ .
                'modules/automaticorderstates/cron.php?token=' . $token .
                '&cursor=' . (int)$cursor
            );

            return $cursor;
        }

        if ($ajax && $nbOrders > 0 && $cursor < $nbOrders) {
            return '{"cursor": ' . $cursor . ', "count": ' . ($nbOrders - $cursor) . '}';
        } else {
            if ($ajax) {
                return '{"result": "ok"}';
            } else {
                return - 1;
            }
        }
    }

    private static function applyOrderStateUnbreakable($cursor, $smart = false)
    {
        static $length = 25; // Nb of automatic groups to handle

        if (is_null($cursor)) {
            $cursor = 0;
        }

        $sql = 'SELECT `id_order`
                FROM `' . _DB_PREFIX_ . 'automatic_order_state_cron`
                LIMIT ' . (int)$cursor . ',' . (int)$length;
        $result = Db::getInstance()->executeS($sql);
        foreach ($result as $row) {
            self::applyOrderState((int)$row['id_order'], $smart);
        }

        return ($cursor + $length);
    }

    public static function applyOrderState($idOrder, $smart = true)
    {
        unset($smart);

        $order = new Order($idOrder);
        $automaticOrderStates = AutomaticOrderState::getAutomatciGroupsByOrder($order);

        foreach ($automaticOrderStates as $automaticOrderState) {
            if ($automaticOrderState->isOrderValid($order)) {
                if ($order->current_state != $automaticOrderState->id_order_state) {
                    $newHistory = new OrderHistory();
                    $newHistory->id_order = (int)$order->id;
                    $newHistory->changeIdOrderState((int)$automaticOrderState->id_order_state, $order, true);
                    $newHistory->addWithemail(true, array());

                    $new_os = new OrderState((int)$automaticOrderState->id_order_state, $order->id_lang);
                    Hook::exec(
                        'updateOrderStatus',
                        array('newOrderStatus' => $new_os, 'id_order' => (int)($order->id))
                    );
                }
            }
        }
    }
}
