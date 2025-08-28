<?php
/**
 * 2020 PrestaWach
 *
 * @author    PrestaWach <info@prestawach.eu>
 * @copyright 2020 PrestaWach
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/automaticorderstates.php');

if (Tools::substr(Tools::encrypt('automaticorderstates'), 0, 10) != Tools::getValue('token')
    || !Module::isInstalled('automaticorderstates')
) {
    die('Bad token');
}

if (!Tools::getValue('ajax')) {
    if (Tools::getValue('return_message') !== false) {
        echo '1';
        die();
    }

    if (Tools::usingSecureMode()) {
        $domain = Tools::getShopDomainSsl(true);
    } else {
        $domain = Tools::getShopDomain(true);
    }

    // Uncomment this code if You are using external cron service
    header(
        'Location: '.
        $domain . __PS_BASE_URI__ .
        'modules/automaticorderstates/cron.php?token=' . Tools::getValue('token') .
        '&return_message=' . (int) Tools::getValue('cursor')
    );


    // Comment this code if You are using external cron service
    /*
    Tools::redirect(
        $domain . __PS_BASE_URI__ .
        'modules/automaticorderstates/cron.php?token=' . Tools::getValue('token') .
        '&return_message=' . (int) Tools::getValue('cursor')
    );
    */

    flush();
}

/** @var AutomaticOrderStates $automaticGroups */
$automaticGroups = ModuleCore::getInstanceByName('automaticorderstates');
echo $automaticGroups->cronProcess((int)Tools::getValue('cursor'), (int)Tools::getValue('ajax'));
