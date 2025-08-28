<?php
/**
 * 2020 PrestaWach
 *
 * @author    PrestaWach <info@prestawach.eu>
 * @copyright 2020 PrestaWach
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'automaticorderstates/classes/AutomaticOrderState.php';
require_once _PS_MODULE_DIR_ . 'automaticorderstates/classes/AutomaticOrderStateRule.php';

class AdminAutomaticOrderStateController extends ModuleAdminController
{

    protected $position_identifier = 'id_automatic_order_state';
    protected $statusesArray = array();

    public function __construct()
    {
        $this->table = 'automatic_order_state';
        $this->className = 'AutomaticOrderState';
        $this->lang = false;
        $this->bootstrap = true;
        $this->list_id = 'automatic_order_states';
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $this->meta_title = $this->l('Automatic Order Status');
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=') === true) {
            $this->tabAccess = Profile::getProfileAccess(
                $this->context->employee->id_profile,
                Tab::getIdFromClassName('AdminAutomaticOrderStates')
            );
        }

        $statuses = OrderState::getOrderStates((int)Context::getContext()->language->id);
        foreach ($statuses as $status) {
            $this->statusesArray[$status['id_order_state']] = $status['name'];
        }

        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
            $this->fields_list = array(
                'id_automatic_order_state' => array(
                    'title' => $this->l('ID'),
                    'align' => 'center',
                    'class' => 'fixed-width-xs'
                ),
                'name' => array(
                    'title' => $this->l('Name'),
                    'filter_key' => 'a!name',
                ),
                'id_rules' => array(
                    'title' => $this->l('Rules'),
                    'callback' => 'printRulesValue',
                    'orderby' => false,
                    'search' => false
                ),
                'order_state' => array(
                    'title' => $this->l('Order status'),
                    'type' => 'select',
                    'color' => 'color',
                    'list' => $this->statusesArray,
                    'filter_key' => 'a!id_order_state',
                    'filter_type' => 'int',
                    'order_key' => 'order_state'
                ),
                'position' => array(
                    'title' => $this->l('Position'),
                    'filter_key' => 'aoss!position',
                    'position' => 'position',
                    'align' => 'center'
                ),
                'active' => array(
                    'title' => $this->l('Active'),
                    'active' => 'status',
                    'type' => 'bool',
                    'class' => 'fixed-width-xs',
                    'align' => 'center',
                    'orderby' => false
                )
            );
        } else {
            $this->fields_list = array(
                'id_automatic_order_state' => array(
                    'title' => $this->l('ID'),
                    'align' => 'center',
                    'width' => 25
                ),
                'name' => array(
                    'title' => $this->l('Name'),
                    'filter_key' => 'a!name',
                ),
                'id_rules' => array(
                    'title' => $this->l('Rules'),
                    'callback' => 'printRulesValue',
                    'orderby' => false,
                    'search' => false
                ),
                'order_state' => array(
                    'title' => $this->l('Order status'),
                    'type' => 'select',
                    'color' => 'color',
                    'list' => $this->statusesArray,
                    'filter_key' => 'a!id_order_state',
                    'filter_type' => 'int',
                    'order_key' => 'order_state'
                ),
                'position' => array(
                    'title' => $this->l('Position'),
                    'width' => 70,
                    'filter_key' => 'aoss!position',
                    'align' => 'center',
                    'position' => 'position'
                ),
                'active' => array(
                    'title' => $this->l('Active'),
                    'width' => 70,
                    'active' => 'status',
                    'align' => 'center',
                    'type' => 'bool',
                    'filter_key' => 'active',
                    'orderby' => false
                )
            );
        }

        $this->bulk_actions = array();


        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $this->_select = 'aoss.`position` AS `position`,
                GROUP_CONCAT(aosr.`id_automatic_order_state_rule`) AS `id_rules`,
                osl.`name` AS `order_state`, os.`color`';
        } else {
            $this->_select = 'aosr.`id_automatic_order_state_rule` AS `position`,
                GROUP_CONCAT(aosr.`id_automatic_order_state_rule`) AS `id_rules`,
                osl.`name` AS `order_state`, os.`color`';
        }

        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'automatic_order_state_rule` aosr ON
            (a.`id_automatic_order_state` = aosr.`id_automatic_order_state`)';
        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON
            (a.`id_order_state` = os.`id_order_state`)';
        $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON
            (a.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = ' . (int)$this->context->language->id . ')';
        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $this->_join .= ' LEFT JOIN `' . _DB_PREFIX_ . 'automatic_order_state_shop` aoss ON (
                a.`id_automatic_order_state` = aoss.`id_automatic_order_state`
                AND aoss.id_shop = ' . (int)$this->context->shop->id . '
            )';
        }

        // we add restriction for shop
        if (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive()) {
            $this->_where = ' AND aoss.`id_shop` = ' . (int)Context::getContext()->shop->id;
        }

        // if we are not in a shop context, we remove the position column
        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            unset($this->fields_list['position']);
        }

        $this->_group = 'GROUP BY a.id_automatic_order_state';

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryPlugin(array(
            'autocomplete',
            'fancybox',
            'typewatch'
        ));

        $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/automaticorderstates.js');
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
            $this->addCss(_MODULE_DIR_ . $this->module->name . '/views/css/automaticorderstates.css');
            $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/automaticorderstates16.js');
        } else {
            $this->addJs(_MODULE_DIR_ . $this->module->name . '/views/js/automaticorderstates15.js');
        }
    }

    /**
     * Change object type to autmatic group rule
     */
    protected function setTypeAutomaticOrderStateRule()
    {
        $this->table = 'automatic_order_state_rule';
        $this->className = 'AutomaticOrderStateRule';
        $this->identifier = 'id_automatic_order_state_rule';
    }

    /**
     * Change object type to automatic group
     */
    protected function setTypeAutomaticOrderState()
    {
        $this->table = 'automatic_order_state';
        $this->className = 'AutomaticOrderState';
        $this->identifier = 'id_automatic_order_state';
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_automatic_order_state'] = array(
                'href' => self::$currentIndex . '&addautomatic_order_state&token=' . $this->token,
                'desc' => $this->l('Add new automatic order status', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        if ($this->display == 'view') {
            $this->page_header_toolbar_btn['new_automatic_order_state_rule'] = array(
                'href' => self::$currentIndex . '&addautomatic_order_state_rule&id_automatic_order_state=' .
                    (int)Tools::getValue('id_automatic_order_state') . '&token=' . $this->token,
                'desc' => $this->l('Add new rule', null, null, false),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * AdminController::initToolbar() override
     *
     * @see AdminController::initToolbar()
     */
    public function initToolbar()
    {
        switch ($this->display) {
            case 'view':
                $this->toolbar_btn['newAutomaticOrderStateRule'] = array(
                    'href' => self::$currentIndex . '&addautomatic_order_state_rule&id_automatic_order_state=' .
                        (int)Tools::getValue('id_automatic_order_state') . '&token=' . $this->token,
                    'desc' => $this->l('Add new rule'),
                    'class' => 'toolbar-new'
                );
                $this->toolbar_btn['back'] = array(
                    'href' => self::$currentIndex . '&token=' . $this->token,
                    'desc' => $this->l('Back to the list')
                );
                break;

            case 'editAutomaticOrderStateRule':
                $this->toolbar_btn['save'] = array(
                    'href' => '#',
                    'desc' => $this->l('Save')
                );
                $this->toolbar_btn['cancel'] = array(
                    'href' => self::$currentIndex . '&token=' . $this->token . '&id_automatic_order_state=' .
                        (int)Tools::getValue('id_automatic_order_state') . '&viewautomatic_order_state',
                    'desc' => $this->l('Cancel')
                );
                break;
            default:
                parent::initToolbar();
        }
    }

    public function initToolbarTitle()
    {
        $bread_extended = $this->breadcrumbs;

        switch ($this->display) {
            case 'edit':
                $bread_extended[] = $this->l('Edit Automatic Order Status');
                break;

            case 'add':
                $bread_extended[] = $this->l('Add New Automatic Order Status');
                break;

            case 'view':
                if (isset($this->automatic_order_state_name)) {
                    $bread_extended[] = $this->automatic_order_state_name;
                }
                break;

            case 'editAutomaticOrderStateRule':
                if (($id_automatic_order_state_rule = Tools::getValue('id_automatic_order_state_rule'))) {
                    if (($id = Tools::getValue('id_automatic_order_state'))) {
                        if (Validate::isLoadedObject($obj = new AutomaticOrderState((int)$id))) {
                            $bread_extended[] = '<a href="' .
                                Context::getContext()->link->getAdminLink('AdminAutomaticOrderState') .
                                '&id_automatic_order_state=' . $id . '&viewautomatic_order_state">' .
                                $obj->name . '</a>';
                        }

                        if (Validate::isLoadedObject(
                            $obj = new AutomaticOrderStateRule((int)Tools::getValue('id_automatic_order_state_rule'))
                        )) {
                            $bread_extended[] = sprintf($this->l('Edit: #%d'), $id_automatic_order_state_rule);
                        }
                    } else {
                        $bread_extended[] = $this->l('Edit Rule');
                    }
                } else {
                    $bread_extended[] = $this->l('Add New Rule');
                }

                break;
        }

        $this->meta_title = $bread_extended;
        $this->toolbar_title = $bread_extended;
    }

    /**
     * AdminController::initContent() override
     *
     * @see AdminController::initContent()
     */
    public function initContent()
    {
        if (!$this->viewAccess()) {
            $this->errors[] = Tools::displayError('You do not have permission to view this.');
            return;
        }

        $this->getLanguages();
        $this->initToolbar();
        $this->initTabModuleList();
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
            $this->initPageHeaderToolbar();
        }

        if ($this->display == 'edit' || $this->display == 'add') {
            if (!$this->loadObject(true)) {
                return;
            }

            $this->content .= $this->renderForm();
        } elseif ($this->display == 'view') {
            // Some controllers use the view action without an object
            if ($this->className) {
                $this->loadObject(true);
            }
            $this->content .= $this->renderView();
        } elseif ($this->display == 'details') {
            $this->content .= $this->renderDetails();
        } elseif ($this->display == 'editAutomaticOrderStateRule') {
            if (!$this->object = new AutomaticOrderState((int)Tools::getValue('id_automatic_order_state'))) {
                return;
            }

            if (!Validate::isLoadedObject($this->object)) {
                $this->errors[] = Tools::displayError('An error occurred while loading view for an object.') . ' ' .
                    Tools::displayError('(cannot load object)');
                return;
            }

            $this->content .= $this->renderFormRule();
        } elseif (!$this->ajax) {
            $this->content .= $this->renderModulesList();
            if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
                $this->content .= $this->renderKpis();
            }
            $this->content .= $this->renderList();
            $this->content .= $this->renderOptions();

            // if we have to display the required fields form
            if ($this->required_database) {
                $this->content .= $this->displayRequiredFields();
            }
        }

        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
            $this->context->smarty->assign(array(
                'content' => $this->content,
                'lite_display' => $this->lite_display,
                'url_post' => self::$currentIndex . '&token=' . $this->token,
                'show_page_header_toolbar' => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'title' => $this->page_header_toolbar_title,
                'toolbar_btn' => $this->page_header_toolbar_btn,
                'page_header_toolbar_btn' => $this->page_header_toolbar_btn
            ));
        } else {
            $this->context->smarty->assign(array(
                'content' => $this->content,
                'url_post' => self::$currentIndex . '&token=' . $this->token
            ));
        }
    }

    public function initProcess()
    {
        if (Tools::getValue('id_automatic_order_state_rule')
            || Tools::isSubmit('deleteautomatic_order_state_rule')
            || Tools::isSubmit('submitAddautomatic_order_state_rule')
            || Tools::isSubmit('addautomatic_order_state_rule')
            || Tools::isSubmit('updateautomatic_order_state_rule')
            || Tools::isSubmit('submitBulkdeleteautomatic_order_state_rule')
        ) {
            $this->setTypeAutomaticOrderStateRule();
        }

        if (Tools::getIsset('viewautomatic_order_state')) {
            $this->list_id = 'automatic_order_state_rule';
            $this->_defaultOrderBy = 'date_upd';
            $this->_defaultOrderWay = 'ASC';

            if (Tools::isSubmit('submitReset' . $this->list_id)) {
                $this->processResetFilters();
            }
        } else {
            $this->list_id = 'automatic_order_state';
            $this->_defaultOrderBy = 'position';
            $this->_defaultOrderWay = 'ASC';
        }

        parent::initProcess();
    }

    public function postProcess()
    {
        parent::postProcess();

        if ($this->table == 'automatic_order_state_rule' && ($this->display == 'edit' || $this->display == 'add')) {
            $this->display = 'editAutomaticOrderStateRule';
        }
        if ($this->table == 'automatic_order_state_rule' && is_null($this->display)) {
            $this->display = 'view';
        }
    }

    protected function afterAdd($obj)
    {
        if (is_a($obj, 'AutomaticOrderStateRule')) {
            $this->redirect_after = self::$currentIndex . '&id_automatic_order_state=' .
                Tools::getValue('id_automatic_order_state') . '&viewautomatic_order_state&conf=3&token=' . $this->token;
        }

        return parent::afterAdd($obj);
    }

    protected function afterUpdate($obj)
    {
        if (is_a($obj, 'AutomaticOrderStateRule')) {
            $this->redirect_after = self::$currentIndex . '&id_automatic_order_state=' .
                Tools::getValue('id_automatic_order_state') . '&viewautomatic_order_state&conf=4&token=' . $this->token;
        }

        return parent::afterUpdate($obj);
    }

    protected function copyFromPost(&$object, $table)
    {
        parent::copyFromPost($object, $table);

        if ($table == 'automatic_order_state_rule') {
            $this->prepareAOSRBeforeSave($object);
        }
    }

    public function processDelete()
    {
        $obj = parent::processDelete();

        if (is_a($obj, 'AutomaticOrderStateRule')) {
            $this->redirect_after = self::$currentIndex . '&id_automatic_order_state=' .
                Tools::getValue('id_automatic_order_state') . '&viewautomatic_order_state&conf=1&token=' . $this->token;
        }

        return $obj;
    }

    public function processBulkDelete()
    {
        $return = parent::processBulkDelete();

        if ($this->table == 'automatic_order_state_rule') {
            $this->redirect_after = self::$currentIndex . '&id_automatic_order_state=' .
                Tools::getValue('id_automatic_order_state') . '&viewautomatic_order_state&conf=1&token=' . $this->token;
        }

        return $return;
    }

    public function renderView()
    {
        if (($id = Tools::getValue('id_automatic_order_state'))) {
            $this->setTypeAutomaticOrderStateRule();
            $this->list_id = 'automatic_order_state_rule';

            // Action for list
            $this->addRowAction('edit');
            $this->addRowAction('delete');

            if (!Validate::isLoadedObject($obj = new AutomaticOrderState((int)$id))) {
                $this->errors[] = Tools::displayError('An error occurred while loading view for an object.') . ' ' .
                    Tools::displayError('(cannot load object)');
                return;
            }

            $this->automatic_order_state_name = $obj->name;
            $this->toolbar_title = $this->automatic_order_state_name;

            if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
                $this->fields_list = array(
                    'id_automatic_order_state_rule' => array(
                        'title' => $this->l('ID'),
                        'align' => 'center',
                        'class' => 'fixed-width-xs',
                        'filter_key' => 'id_automatic_order_state_rule'
                    ),
                    'type' => array(
                        'title' => $this->l('Type'),
                        'type' => 'select',
                        'list' => $this->getAvailableTypes(),
                        'callback' => 'printRuleType',
                        'filter_key' => 'type'
                    ),
                    'value' => array(
                        'title' => $this->l('Value'),
                        'filter' => false,
                        'callback' => 'printRuleValue',
                        'filter_key' => 'value'
                    )
                );
            } else {
                $this->fields_list = array(
                    'id_automatic_order_state_rule' => array(
                        'title' => $this->l('ID'),
                        'align' => 'center',
                        'width' => 25,
                        'filter_key' => 'id_automatic_order_state_rule'
                    ),
                    'type' => array(
                        'title' => $this->l('Type'),
                        'type' => 'select',
                        'list' => $this->getAvailableTypes(),
                        'callback' => 'printRuleType',
                        'filter_key' => 'type',
                        'filter_type' => 'int',
                        'width' => 250
                    ),
                    'value' => array(
                        'title' => $this->l('Value'),
                        'filter' => false,
                        'callback' => 'printRuleValue',
                        'filter_key' => 'value'
                    )
                );
            }

            unset($this->_select);
            unset($this->_join);

            $this->_where = 'AND a.`id_automatic_order_state` = ' . (int)$id;

            unset($this->_group);

            self::$currentIndex = self::$currentIndex . '&id_automatic_order_state=' .
                (int)$id . '&viewautomatic_order_state';

            $this->processFilter();
            return parent::renderList();
        }
    }

    public function printRuleType($type, $tr)
    {
        unset($tr);

        $type_arr = $this->getAvailableTypes();
        if (isset($type_arr[$type])) {
            return $type_arr[$type];
        } else {
            return '-';
        }
    }

    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('add');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $module_url = Tools::getProtocol(Tools::usingSecureMode()) . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ .
            'automaticorderstates/';
        $this->tpl_list_vars['cron_url'] = $module_url . 'cron.php?token=' .
            Tools::substr(Tools::encrypt('automaticorderstates'), 0, 10);

        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
            $this->tpl_list_vars['ps_version'] = '1.6';
        } else {
            $this->tpl_list_vars['ps_version'] = '1.5';
        }

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->initToolbar();

        if (!$this->loadObject(true)) {
            return;
        }

        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
            $this->fields_form = array(
                'legend' => array(
                    'title' => $this->l('Automatic Order Status'),
                    'icon' => 'icon-tags'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Ordre status'),
                        'name' => 'id_order_state',
                        'desc' => $this->l(
                            'Target order status applied after validate order against all defined rules'
                        ),
                        'required' => true,
                        'col' => '4',
                        /*'default_value' => 0,*/
                        'options' => array(
                            'query' => OrderState::getOrderStates($this->context->language->id),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Name'),
                        'name' => 'name',
                        'lang' => false,
                        'required' => true,
                        'hint' => $this->l('Invalid characters:') . ' <>;=#{}'
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'active',
                        'required' => false,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'submitAdd' . $this->table
                )
            );
        } else {
            $this->fields_form = array(
                'legend' => array(
                    'title' => $this->l('Automatic Order Status'),
                    'icon' => 'icon-tags'
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Order status'),
                        'name' => 'id_order_state',
                        'required' => true,
                        'col' => '4',
                        /*'default_value' => (int)Configuration::get('PS_GUEST_GROUP'),*/
                        'options' => array(
                            'query' => OrderState::getOrderStates($this->context->language->id),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Name'),
                        'name' => 'name',
                        'lang' => false,
                        'required' => true,
                        'size' => 33
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Active'),
                        'name' => 'active',
                        'required' => false,
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'submitAdd' . $this->table
                )
            );
        }

        // Display this field only if multistore option is enabled AND there are several stores configured
        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso'
            );
        }

        if (!$this->loadObject(true)) {
            return;
        }

        return parent::renderForm();
    }

    public function ajaxProcessUpdatePositions()
    {
        $id_to_move = (int)Tools::getValue('id');
        $way = (int)Tools::getValue('way');
        $positions = Tools::getValue('automatic_order_state');
        if (is_array($positions)) {
            foreach ($positions as $key => $value) {
                $pos = explode('_', $value);
                if ((isset($pos[1]) && isset($pos[2])) && ($pos[2] == $id_to_move)) {
                    $position = $key + 1;
                    break;
                }
            }
        }

        $automatic_order_state = new AutomaticOrderState($id_to_move);
        if (Validate::isLoadedObject($automatic_order_state)) {
            if (isset($position) && $automatic_order_state->updatePosition($way, $position)) {
                die(true);
            } else {
                die('{"hasError" : true, errors : "Can not update automatic order staus position"}');
            }
        } else {
            die('{"hasError" : true, "errors" : "This auomatic order status can not be loaded"}');
        }
    }

    /**
     * Function to return available types for select format
     */
    public function getAvailableTypesForSelect()
    {
        $type_arr_select = array(
            array(
                'id' => 0,
                'name' => ''
            )
        );
        foreach ($this->getAvailableTypes() as $key => $val) {
            $type_arr_select[] = array(
                'id' => $key,
                'name' => $val
            );
        }

        usort($type_arr_select, function ($a, $b) {
            if ($a['name'] == $b['name']) {
                return 0;
            }

            return ($a['name'] < $b['name']) ? (- 1) : (1);
        });

        return $type_arr_select;
    }

    /**
     * AdminController::renderForm() override
     *
     * @see AdminController::renderForm()
     */
    public function renderFormRule()
    {
        $this->setTypeAutomaticOrderStateRule();

        $this->initToolbar();

        if (!$this->loadObject(true)) {
            return;
        }

        $this->initFieldsetType();
        $this->initFieldsetCurrentOrderState();
        $this->initFieldsetCurrentOrderStateDate();
        $this->initFieldsetPaymentMethod();
        $this->initFieldsetCarrierMethod();
        $this->initFieldsetPreviousOrderState();
        $this->initFieldsetHistoricalOrderState();
        $this->initFieldsetHistoricalOrderStateDate();
        $this->initFieldsetOrderProduct();

        $this->multiple_fieldsets = true;

        if (!$this->loadObject(true)) {
            return;
        }

            // start classes/controller/admin/AdminController.php renderForm()
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
            if (!$this->default_form_language) {
                $this->getLanguages();
            }

            if (Tools::getValue('submitFormAjax')) {
                $this->content .= $this->context->smarty->fetch('form_submit_ajax.tpl');
            }

            if ($this->fields_form && is_array($this->fields_form)) {
                if (!$this->multiple_fieldsets) {
                    $this->fields_form = array(
                        array(
                            'form' => $this->fields_form
                        )
                    );
                }

                    // For add a fields via an override of $fields_form, use $fields_form_override
                if (is_array($this->fields_form_override) && !empty($this->fields_form_override)) {
                    $this->fields_form[0]['form']['input'] = array_merge(
                        $this->fields_form[0]['form']['input'],
                        $this->fields_form_override
                    );
                }

                    // start modified part of the classes/controller/admin/AdminController.php renderForm()
                $automatic_order_state_rule = new AutomaticOrderStateRule(
                    Tools::getValue('id_automatic_order_state_rule')
                );
                $fields_value = $this->getFieldsValue($automatic_order_state_rule);
                $fields_value['id_automatic_order_state'] = (int)Tools::getValue('id_automatic_order_state');
                $this->updateAutomaticOrderStateValues($fields_value, $automatic_order_state_rule);

                if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
                    $this->tpl_form_vars['ps_version'] = '1.6';
                } else {
                    $this->tpl_form_vars['ps_version'] = '1.5';
                }
                // end modified part of the classes/controller/admin/AdminController.php renderForm()

                Hook::exec('action' . $this->controller_name . 'FormModifier', array(
                    'fields' => &$this->fields_form,
                    'fields_value' => &$fields_value,
                    'form_vars' => &$this->tpl_form_vars
                ));

                $helper = new HelperForm($this);
                $this->setHelperDisplay($helper);
                $helper->id = $automatic_order_state_rule->id;
                $helper->fields_value = $fields_value;
                $helper->submit_action = $this->submit_action;
                $helper->tpl_vars = $this->tpl_form_vars;
                $helper->show_cancel_button = (isset($this->show_form_cancel_button)) ?
                    $this->show_form_cancel_button :
                    ($this->display == 'add' ||
                    $this->display == 'edit' ||
                    $this->display == 'editAutomaticOrderStateRule');

                $back = Tools::safeOutput(Tools::getValue('back', ''));
                if (empty($back)) {
                    $back = self::$currentIndex . '&token=' . $this->token .
                        '&id_automatic_order_state=' . (int)Tools::getValue('id_automatic_order_state') .
                        '&viewautomatic_order_state';
                }
                if (!Validate::isCleanHtml($back)) {
                    die(Tools::displayError());
                }

                $helper->back_url = $back;
                !is_null($this->base_tpl_form) ? $helper->base_tpl = $this->base_tpl_form : '';
                if ($this->tabAccess['view']) {
                    if (Tools::getValue('back')) {
                        $helper->tpl_vars['back'] = Tools::safeOutput(Tools::getValue('back'));
                    } else {
                        $helper->tpl_vars['back'] = Tools::safeOutput(
                            Tools::getValue(self::$currentIndex . '&token=' . $this->token)
                        );
                    }
                }

                return $helper->generateForm($this->fields_form);
            }
        } else {
            if (!$this->default_form_language) {
                $this->getLanguages();
            }

            if (Tools::getValue('submitFormAjax')) {
                $this->content .= $this->context->smarty->fetch('form_submit_ajax.tpl');
            }
            if ($this->fields_form && is_array($this->fields_form)) {
                if (!$this->multiple_fieldsets) {
                    $this->fields_form = array(
                        array(
                            'form' => $this->fields_form
                        )
                    );
                }

                // For add a fields via an override of $fields_form, use $fields_form_override
                if (is_array($this->fields_form_override) && !empty($this->fields_form_override)) {
                    $this->fields_form[0]['form']['input'][] = $this->fields_form_override;
                }

                $helper = new HelperForm($this);
                $this->setHelperDisplay($helper);

                // start modified part of the classes/controller/admin/AdminController.php renderForm()
                $automatic_order_state_rule = new AutomaticOrderStateRule(
                    (int)Tools::getValue('id_automatic_order_state_rule')
                );
                $helper->fields_value = $this->getFieldsValue($automatic_order_state_rule);
                $helper->fields_value['id_automatic_order_state'] = (int)Tools::getValue('id_automatic_order_state');
                $this->updateAutomaticOrderStateValues($helper->fields_value, $automatic_order_state_rule);

                $helper->id = $automatic_order_state_rule->id;

                if (version_compare(_PS_VERSION_, '1.6.0.0', '>=') === true) {
                    $this->tpl_form_vars['ps_version'] = '1.6';
                } else {
                    $this->tpl_form_vars['ps_version'] = '1.5';
                }
                // end modified part of the classes/controller/admin/AdminController.php renderForm()

                $helper->tpl_vars = $this->tpl_form_vars;
                !is_null($this->base_tpl_form) ? $helper->base_tpl = $this->base_tpl_form : '';
                if ($this->tabAccess['view']) {
                    if (Tools::getValue('back')) {
                        $helper->tpl_vars['back'] = Tools::safeOutput(Tools::getValue('back'));
                    } else {
                        $helper->tpl_vars['back'] = Tools::safeOutput(
                            Tools::getValue(self::$currentIndex . '&token=' . $this->token)
                        );
                    }
                }

                $form = $helper->generateForm($this->fields_form);

                return $form;
            }
        }
        // end classes/controller/admin/AdminController.php renderForm()

        return '';
    }

    private function initFieldsetType()
    {
        $this->fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Rule type'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_automatic_order_state',
                    'required' => true
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Type'),
                    'name' => 'type',
                    'required' => true,
                    'col' => '4',
                    'default_value' => 0,
                    'options' => array(
                        'query' => $this->getAvailableTypesForSelect(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                )
            )
        );
    }

    private function initFieldsetCurrentOrderState()
    {
        // get order statuses list
        $statuses = OrderState::getOrderStates((int)Context::getContext()->language->id);

        $this->fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Current order status'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Order status condition'),
                    'name' => 'cos_id_order_state_condition',
                    'col' => '4',
                    'default_value' => 0,
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => $this->l('equal')
                            ),
                            array(
                                'id' => 1,
                                'name' => $this->l('not equal')
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Order status'),
                    'name' => 'cos_id_order_state',
                    'required' => true,
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    private function initFieldsetHistoricalOrderState()
    {
        // get order statuses list
        $statuses = OrderState::getOrderStates((int)Context::getContext()->language->id);

        $this->fields_form[6]['form'] = array(
            'legend' => array(
                'title' => $this->l('Historical order status'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Historical order status condition'),
                    'name' => 'hos_id_order_state_condition',
                    'col' => '4',
                    'default_value' => 0,
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => $this->l('equal')
                            ),
                            array(
                                'id' => 1,
                                'name' => $this->l('not equal')
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Historical order status'),
                    'name' => 'hos_id_order_state',
                    'required' => true,
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    private function initFieldsetCurrentOrderStateDate()
    {
        // get order statuses list
        $statuses = OrderState::getOrderStates((int)Context::getContext()->language->id);

        $this->fields_form[2]['form'] = array(
            'legend' => array(
                'title' => $this->l('Current order status with date'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Order status'),
                    'name' => 'cosd_id_order_state',
                    'required' => true,
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Condition'),
                    'name' => 'cosd_condition',
                    'col' => '4',
                    'default_value' => 0,
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => ''
                            ),
                            array(
                                'id' => '<',
                                'name' => $this->l('more than')
                            ),
                            array(
                                'id' => '>',
                                'name' => $this->l('less than')
                            ),
                            array(
                                'id' => '=',
                                'name' => $this->l('equal')
                            )
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Value'),
                    'name' => 'cosd_value',
                    'required' => true,
                    'size' => 33,
                    'desc' => $this->l('Number of days ago (fractions allowed)')
                )
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    private function initFieldsetHistoricalOrderStateDate()
    {
        // get order statuses list
        $statuses = OrderState::getOrderStates((int)Context::getContext()->language->id);

        $this->fields_form[7]['form'] = array(
            'legend' => array(
                'title' => $this->l('Historical order status with date'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Order status'),
                    'name' => 'hosd_id_order_state',
                    'required' => true,
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Condition'),
                    'name' => 'hosd_condition',
                    'col' => '4',
                    'default_value' => 0,
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => ''
                            ),
                            array(
                                'id' => '<',
                                'name' => $this->l('more than')
                            ),
                            array(
                                'id' => '>',
                                'name' => $this->l('less than')
                            ),
                            array(
                                'id' => '=',
                                'name' => $this->l('equal')
                            )
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Value'),
                    'name' => 'hosd_value',
                    'required' => true,
                    'size' => 33,
                    'desc' => $this->l('Number of days ago (fractions allowed)')
                )
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    private function initFieldsetOrderProduct()
    {
        $this->fields_form[8]['form'] = array(
            'legend' => array(
                'title' => $this->l('Order product'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Search product'),
                    'name' => 'op_product',
                    'required' => true,
                    'size' => 33
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Product condition'),
                    'name' => 'op_condition',
                    'col' => '4',
                    'default_value' => 0,
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => $this->l('contain')
                            ),
                            array(
                                'id' => 1,
                                'name' => $this->l('not contain')
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    private function initFieldsetPreviousOrderState()
    {
        // get order statuses list
        $statuses = OrderState::getOrderStates((int)Context::getContext()->language->id);

        $this->fields_form[5]['form'] = array(
            'legend' => array(
                'title' => $this->l('Previous order status'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Previous order status condition'),
                    'name' => 'pos_id_order_state_condition',
                    'col' => '4',
                    'default_value' => 0,
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => $this->l('equal')
                            ),
                            array(
                                'id' => 1,
                                'name' => $this->l('not equal')
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Previous order status'),
                    'name' => 'pos_id_order_state',
                    'required' => true,
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    private function initFieldsetPaymentMethod()
    {
        // get payments list
        $payments = PaymentModule::getInstalledPaymentModules();
        $paymentList = array();
        foreach ($payments as $payment) {
            $paymentList[] = array(
                'id' => $payment['name'],
                'name' => Tools::ucfirst($payment['name']),
            );
        }

        $this->fields_form[3]['form'] = array(
            'legend' => array(
                'title' => $this->l('Payment method'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Payment'),
                    'name' => 'pm_name',
                    'required' => true,
                    'options' => array(
                        'query' => $paymentList,
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    private function initFieldsetCarrierMethod()
    {
        // get carriers list
        $carriers = Carrier::getCarriers(
            (int)Context::getContext()->language->id,
            true,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );

        $this->fields_form[4]['form'] = array(
            'legend' => array(
                'title' => $this->l('Carrier method'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Carrier'),
                    'name' => 'cm_id_reference',
                    'required' => true,
                    'options' => array(
                        'query' => $carriers,
                        'id' => 'id_reference',
                        'name' => 'name'
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save')
            )
        );
    }

    /**
     * Function to return available types
     */
    public function getAvailableTypes()
    {
        return array(
            1 => $this->l('Current order status'),
            2 => $this->l('Current order status with date'),
            3 => $this->l('Payment method'),
            4 => $this->l('Carrier method'),
            5 => $this->l('Previous order status'),
            6 => $this->l('Historical order status'),
            7 => $this->l('Historical order status with date'),
            8 => $this->l('Order product'),
        );
    }

    public function printRuleValue($value, $tr)
    {
        $return = '';

        $value_arr = unserialize($value);

        switch ($tr['type']) {
            case 1:
                if ($value_arr['cos_id_order_state_condition'] == 0) {
                    $condition = $this->l('Equal');
                } else {
                    $condition = $this->l('Not equal');
                }

                $orderState = new OrderState($value_arr['cos_id_order_state'], Context::getContext()->language->id);
                $return .= ' ' . $condition . ' "' . $orderState->name . '"';
                break;
            case 2:
                $orderState = new OrderState($value_arr['cosd_id_order_state'], Context::getContext()->language->id);
                $return .= ' "' . $orderState->name . '"';

                switch ($value_arr['cosd_condition']) {
                    case '<':
                        $return .= ' ' . $this->l('more than');
                        break;
                    case '>':
                        $return .= ' ' . $this->l('less than');
                        break;
                    case '=':
                        $return .= ' ' . $this->l('equal');
                        break;
                }

                $return .= ' ' . $value_arr['cosd_value'] . ' ' . $this->l('days ago');
                break;
            case 3:
                $return .= ' "' . Tools::ucfirst($value_arr['pm_name']) . '"';
                break;
            case 4:
                $carrier = Carrier::getCarrierByReference((int)$value_arr['cm_id_reference']);
                $return .= ' "' . $carrier->name . '"';
                break;
            case 5:
                if ($value_arr['pos_id_order_state_condition'] == 0) {
                    $condition = $this->l('Equal');
                } else {
                    $condition = $this->l('Not equal');
                }

                $orderState = new OrderState($value_arr['pos_id_order_state'], Context::getContext()->language->id);
                $return .= ' ' . $condition . ' "' . $orderState->name . '"';
                break;
            case 6:
                if ($value_arr['hos_id_order_state_condition'] == 0) {
                    $condition = $this->l('Equal');
                } else {
                    $condition = $this->l('Not equal');
                }

                $orderState = new OrderState($value_arr['hos_id_order_state'], Context::getContext()->language->id);
                $return .= ' ' . $condition . ' "' . $orderState->name . '"';
                break;
            case 7:
                $orderState = new OrderState($value_arr['hosd_id_order_state'], Context::getContext()->language->id);
                $return .= ' "' . $orderState->name . '"';

                switch ($value_arr['hosd_condition']) {
                    case '<':
                        $return .= ' ' . $this->l('more than');
                        break;
                    case '>':
                        $return .= ' ' . $this->l('less than');
                        break;
                    case '=':
                        $return .= ' ' . $this->l('equal');
                        break;
                }

                $return .= ' ' . $value_arr['hosd_value'] . ' ' . $this->l('days ago');
                break;
            case 8:
                switch ((int)$value_arr['op_condition']) {
                    case 0:
                        $return .= ' ' . $this->l('Contain');
                        break;
                    case 1:
                        $return .= ' ' . $this->l('Not contain');
                        break;
                }
                $product = new Product($value_arr['op_id_product'], false, Context::getContext()->language->id);
                $return .= ' "' . $product->name . '" (id: ' . $value_arr['op_id_product'] . ')';

                break;
        }

        return $return;
    }

    public function printRulesValue($value, $tr)
    {
        unset($tr);

        $return = '';
        $return_arr = array();

        $id_rules = explode(',', $value);
        if (!empty($id_rules)) {
            $rule_types = $this->getAvailableTypes();
            foreach ($id_rules as $key => $id_rule) {
                $rule = new AutomaticOrderStateRule($id_rule);
                $return_one = ($key + 1) . '. ' . $rule_types[$rule->type];

                $value_arr = unserialize($rule->value);

                switch ($rule->type) {
                    case 1:
                        if ($value_arr['cos_id_order_state_condition'] == 0) {
                            $condition = $this->l('equal');
                        } else {
                            $condition = $this->l('not equal');
                        }

                        $orderState = new OrderState(
                            $value_arr['cos_id_order_state'],
                            Context::getContext()->language->id
                        );
                        $return_one .= ' ' . $condition . ' "' . $orderState->name . '"';
                        break;
                    case 2:
                        $orderState = new OrderState(
                            $value_arr['cosd_id_order_state'],
                            Context::getContext()->language->id
                        );
                        $return_one .= ' "' . $orderState->name . '"';

                        switch ($value_arr['cosd_condition']) {
                            case '<':
                                $return_one .= ' ' . $this->l('more than');
                                break;
                            case '>':
                                $return_one .= ' ' . $this->l('less than');
                                break;
                            case '=':
                                $return_one .= ' ' . $this->l('equal');
                                break;
                        }

                        $return_one .= ' ' . $value_arr['cosd_value'] . ' ' . $this->l('days ago');
                        break;
                    case 3:
                        $return_one .= ' "' . Tools::ucfirst($value_arr['pm_name']) . '"';
                        break;
                    case 4:
                        $carrier = Carrier::getCarrierByReference((int)$value_arr['cm_id_reference']);
                        $return_one .= ' "' . $carrier->name . '"';
                        break;
                    case 5:
                        if ($value_arr['pos_id_order_state_condition'] == 0) {
                            $condition = $this->l('equal');
                        } else {
                            $condition = $this->l('not equal');
                        }

                        $orderState = new OrderState(
                            $value_arr['pos_id_order_state'],
                            Context::getContext()->language->id
                        );
                        $return_one .= ' ' . $condition . ' "' . $orderState->name . '"';
                        break;
                    case 6:
                        if ($value_arr['hos_id_order_state_condition'] == 0) {
                            $condition = $this->l('equal');
                        } else {
                            $condition = $this->l('not equal');
                        }

                        $orderState = new OrderState(
                            $value_arr['hos_id_order_state'],
                            Context::getContext()->language->id
                        );
                        $return_one .= ' ' . $condition . ' "' . $orderState->name . '"';
                        break;
                    case 7:
                        $orderState = new OrderState(
                            $value_arr['hosd_id_order_state'],
                            Context::getContext()->language->id
                        );
                        $return_one .= ' "' . $orderState->name . '"';

                        switch ($value_arr['hosd_condition']) {
                            case '<':
                                $return_one .= ' ' . $this->l('more than');
                                break;
                            case '>':
                                $return_one .= ' ' . $this->l('less than');
                                break;
                            case '=':
                                $return_one .= ' ' . $this->l('equal');
                                break;
                        }

                        $return_one .= ' ' . $value_arr['hosd_value'] . ' ' . $this->l('days ago');
                        break;
                    case 8:
                        switch ((int)$value_arr['op_condition']) {
                            case 0:
                                $return_one .= ' ' . $this->l('contain');
                                break;
                            case 1:
                                $return_one .= ' ' . $this->l('not contain');
                                break;
                        }
                        $product = new Product($value_arr['op_id_product'], false, Context::getContext()->language->id);
                        $return_one .= ' "' . $product->name . '" (id: ' . $value_arr['op_id_product'] . ')';
                }

                $return_arr[] = $return_one;
            }

            $return = implode('<br />', $return_arr);
        }

        return $return;
    }

    private function prepareAOSRBeforeSave(&$obj)
    {
        switch ($obj->type) {
            case 1:
                $obj->value = serialize(array(
                    'cos_id_order_state_condition' => Tools::getValue('cos_id_order_state_condition'),
                    'cos_id_order_state' => Tools::getValue('cos_id_order_state'),
                ));
                break;
            case 2:
                $obj->value = serialize(array(
                    'cosd_id_order_state' => Tools::getValue('cosd_id_order_state'),
                    'cosd_condition' => Tools::getValue('cosd_condition'),
                    'cosd_value' => str_replace(',', '.', Tools::getValue('cosd_value'))
                ));
                break;
            case 3:
                $obj->value = serialize(array(
                    'pm_name' => Tools::getValue('pm_name')
                ));
                break;
            case 4:
                $obj->value = serialize(array(
                    'cm_id_reference' => Tools::getValue('cm_id_reference')
                ));
                break;
            case 5:
                $obj->value = serialize(array(
                    'pos_id_order_state_condition' => Tools::getValue('pos_id_order_state_condition'),
                    'pos_id_order_state' => Tools::getValue('pos_id_order_state'),
                ));
                break;
            case 6:
                $obj->value = serialize(array(
                    'hos_id_order_state_condition' => Tools::getValue('hos_id_order_state_condition'),
                    'hos_id_order_state' => Tools::getValue('hos_id_order_state'),
                ));
                break;
            case 7:
                $obj->value = serialize(array(
                    'hosd_id_order_state' => Tools::getValue('hosd_id_order_state'),
                    'hosd_condition' => Tools::getValue('hosd_condition'),
                    'hosd_value' => str_replace(',', '.', Tools::getValue('hosd_value'))
                ));
                break;
            case 8:
                $obj->value = serialize(array(
                    'op_id_product' => (int)Tools::getValue('input_op_product'),
                    'op_condition' => (int)Tools::getValue('op_condition'),
                ));
                break;
        }
    }

    public function ajaxProcessGetAutocompleteProducts()
    {
        $query = Tools::getValue('q', false);
        if (!$query || $query == '' || Tools::strlen($query) < 1) {
            die();
        }

        $excludeIds = Tools::getValue('excludeIds', false);
        if ($excludeIds && $excludeIds != 'NaN') {
            $excludeIds = implode(',', array_map('intval', explode(',', $excludeIds)));
        } else {
            $excludeIds = '';
        }

        $context = Context::getContext();

        $sql = 'SELECT 
                  p.`id_product`, 
                  pl.`link_rewrite`, 
                  p.`reference`, 
                  pl.`name`, 
                  p.`cache_default_attribute`
                FROM `' . _DB_PREFIX_ . 'product` p
                ' . Shop::addSqlAssociation('product', 'p') . '
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl 
                ON (
                    pl.id_product = p.id_product 
                    AND pl.id_lang = ' . (int)$context->language->id .
            Shop::addSqlRestrictionOnLang('pl') . '
                )
                WHERE (pl.name LIKE \'%' . pSQL($query) . '%\' OR p.reference LIKE \'%' . pSQL($query) . '%\')
                ' . (!empty($excludeIds) ? ' AND p.id_product NOT IN (' . $excludeIds . ') ' : ' ') . '
                GROUP BY p.id_product';

        $items = Db::getInstance()->executeS($sql);

        $results = array();
        if ($items && $excludeIds) {
            foreach ($items as $item) {
                $item['name'] = str_replace('|', '&#124;', $item['name']);
                $results[] = trim($item['name']) .
                    (!empty($item['reference']) ? ' (ref: '.$item['reference'].')' : '') .
                    '|' .
                    (int)($item['id_product']);
            }
        }

        echo implode("\n", $results);
    }

    private function updateAutomaticOrderStateValues(&$fields_value, $automatic_order_state_rule)
    {
        $value_arr = unserialize($automatic_order_state_rule->value);
        if (!empty($value_arr)) {
            foreach ($value_arr as $key => $val) {
                $fields_value[$key] = $val;
            }
        }

        if ($automatic_order_state_rule->type == 8) {
            $this->tpl_form_vars['op_products'] = $this->resolveOpProductsFieldsValue($value_arr['op_id_product']);
        }
    }

    private function resolveOpProductsFieldsValue($id_product)
    {
        $idProducts = array((int)$id_product);

        $sql = 'SELECT p.`id_product`, pl.`name`, p.`reference`
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON (pl.`id_product` = p.`id_product`' . \Shop::addSqlRestrictionOnLang('pl') . ')
                WHERE pl.`id_lang` = ' . (int)\Context::getContext()->language->id . '
                AND p.`id_product` IN (' . implode(',', $idProducts) . ')';
        $products = \Db::getInstance()->executeS($sql);

        return $products;
    }
}
