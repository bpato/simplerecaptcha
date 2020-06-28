<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Brais Pato <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/*
 * Run 'composer install' if the 'vendor' folder doesn't exist
 */
require_once( __DIR__  . '/vendor/autoload.php');

use PhpParser\Node\Stmt\Break_;
use ReCaptcha\ReCaptcha;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class SimpleReCaptcha extends Module implements WidgetInterface
{

    /** @var string Unique name */
    public $name = 'simplerecaptcha';

    /** @var string Admin tab corresponding to the module */
    public $tab = 'back_office_features';

    /** @var string Version */
    public $version = '1.0.2';

    /** @var string author of the module */
    public $author = 'Brais Pato';

    /** @var int need_instance */
    public $need_instance = 0;

    /** @var array filled with known compliant PS versions */
    public $ps_versions_compliancy = array(
        'min' => '1.7.6.0',
        'max' => '1.7.9.99'
    );

    /** Name of ModuleAdminController used for configuration */
    const MODULE_ADMIN_CONTROLLER = 'AdminSimpleReCaptcha';

    /** Configuration variable names */
    const CONF_API_KEYS = 'RECAPTCHA_API_KEYS';
    const CONF_FORMS_CONFIG = 'RECAPTCHA_FORMS_CONFIG';

    /** @var array Hooks used */
    public $hooks = array(
        'actionFrontControllerSetMedia',
        'actionSubmitAccountBefore',
        'displayCustomerAccountForm',
        'displayBeforeBodyClosingTag',
        'displayGDPRConsent',
    );

    /** @var array compatible modules and controllers */
    protected $instances = array(
        'modules' => array(
            'ps_emailsubscription', // v2.3.0
            'contactform',
        ),
        'controllers' => array(
            'AuthController',
            'PasswordController',
        )
    );

    /**
     * Constructor of module
     */
    public function __construct()
    {
        parent::__construct();

        $this->displayName = $this->trans('PS Simple Google ReCaptcha', array(), 'Modules.Simplerecaptcha.Admin');
        $this->description = $this->trans('Add Google ReCaptcha to your Prestashop', array(), 'Modules.Simplerecaptcha.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', array(), 'Modules.Simplerecaptcha.Admin');

        /**
         * TODO warnings
         * if (!Configuration::get('MYMODULE_NAME')) {
         *  $this->warning = $this->l('No name provided');
         * }
         */
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() 
            && $this->registerHook($this->hooks)
            && $this->installTab()
            && $this->installConfiguration();
    }

    /**
     * Save default values
     * @return bool
     */
    public function installConfiguration()
    {
        for($i = 0; $i <= 1; $i++) {
            $default_api_keys_values['RECAPTCHA_API_KEY_'.$i] = '';
            $default_api_keys_values['RECAPTCHA_SECRET_API_KEY_'.$i] = '';
        }
        $default_api_keys_values['RECAPTCHA_COUNTRY'] = (int) Configuration::get('PS_COUNTRY_DEFAULT');

        foreach ($this->getAvailableInstances() as $name) {
            $default_form_config_values['RECAPTCHA_ENABLE_' . strtoupper($name)] = 0;
            $default_form_config_values[$name.'[widget_type]'] = 0; // 0: checkbox, 1: invisible
            $default_form_config_values[$name.'[size]'] = 'normal'; // normal, compact 
            $default_form_config_values[$name.'[theme]'] = 'light'; // light, dark 
        }

        return (bool) Configuration::updateValue(static::CONF_API_KEYS, serialize($default_api_keys_values))
            && (bool) Configuration::updateValue(static::CONF_FORMS_CONFIG, serialize($default_form_config_values));
    }

    /**
     * @return bool
     */
    public function installTab()
    {
        $tab = new Tab();
        
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->name
        );
        $tab->active = false;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminModulesManage');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs()
            && $this->uninstallConfiguration();
    }

    /**
     * @return bool
     */
    public function uninstallConfiguration()
    {
        return (bool) Configuration::deleteByName(static::CONF_API_KEYS)
            && (bool) Configuration::deleteByName(static::CONF_FORMS_CONFIG);
    }

    /**
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }

    public function hookActionFrontControllerSetMedia($params)
    {

        $instances = $this->getAvailableInstances();
        $values = $this->getConfigFieldsValues();

        $load = false;
        foreach ($instances as $name) {
            if ($load === false && $values['RECAPTCHA_ENABLE_' . strtoupper($name)]) {
                $load = true;
                $this->context->controller->registerJavascript(
                    'g-recaptcha', // Unique ID
                    'https://www.google.com/recaptcha/api.js?onload=onloadRender&hl='. strtolower(Country::getIsoById($values['RECAPTCHA_COUNTRY'])), // JS path
                    array(
                        'server' => 'remote', 
                        'position' => 'bottom', 
                        'priority' => 1000
                    ) // Arguments
                );
            }

            if ($this->context->controller instanceof $name && $values['RECAPTCHA_ENABLE_' . strtoupper($name)]) {

                $filename = '/modules/' . $this->name . '/views/js/simplerecaptcha-' . strtolower($name) . '.js';

                if (file_exists(_PS_ROOT_DIR_ . $filename)) {
                    $this->context->controller->registerJavascript(
                        'simplerecaptcha-'.strtolower($name), // Unique ID
                        $filename, // JS path
                        array(
                            'server' => 'remote', 
                            'position' => 'bottom', 
                            'priority' => 1000
                        ) // Arguments
                    );
                }
            }
        }
    }

    public function hookDisplayBeforeBodyClosingTag($params) {
        $instances = $this->getAvailableInstances();
        $values = $this->getConfigFieldsValues();

        $instances_vars = array();
        foreach ($instances as $name) {
            if ($values['RECAPTCHA_ENABLE_' . strtoupper($name)] && $values[$name.'[widget_type]'] == 1) {
                $instances_vars[] = $name;
            }
        }

        $data = $this->context->smarty->createData();
        $data->assign('simplerecaptcha_js', $instances_vars );

        $index = get_class(Context::getContext()->controller);
        if ( array_key_exists($index, $instances) ) {
            $tpl_vars = array();
            switch ($index) {
                case 'AuthController':
                    $tpl_vars['widget_id'] = "#authentication #login-form";
                break;
                case 'PasswordController':
                    $tpl_vars['widget_id'] = "#password .forgotten-password";
                break;
            }
            
            if ($values['RECAPTCHA_ENABLE_' . strtoupper($index)]) {
                $tpl_vars['name'] = $index;
                $tpl_vars['widget_type'] = $values[$index.'[widget_type]'];
                $tpl_vars['size'] = $values[$index.'[size]'];
                $tpl_vars['theme'] = $values[$index.'[theme]'];
                $tpl_vars['RECAPTCHA_API_KEY'] = $values['RECAPTCHA_API_KEY_' . $tpl_vars['widget_type']];
                $data->assign('simplerecaptcha', $tpl_vars);
            }
        }
        
        return $this->fetch('module:simplerecaptcha/views/templates/hook/displayBeforeBodyClosingTag.tpl', $data);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $instances = $this->getAvailableInstances();
        $values = $this->getConfigFieldsValues();

        if (isset($configuration['id_module'])) {
            // Integer Module id
            $index = (int)$configuration['id_module'];
        } else {
            // String Controller filename
            $index = get_class(Context::getContext()->controller);
        }

        if ( array_key_exists($index, $instances) ) {
            $instance = $instances[$index];

            $tpl_vars['name'] = $instance;
            $tpl_vars['widget_type'] = $values[$instance.'[widget_type]'];
            $tpl_vars['size'] = $values[$instance.'[size]'];
            $tpl_vars['theme'] = $values[$instance.'[theme]'];
            $tpl_vars['RECAPTCHA_API_KEY'] = $values['RECAPTCHA_API_KEY_' . $tpl_vars['widget_type']];

            switch ($hookName) {
                case null:
                case 'actionFormRecaptchaSubmitBefore':
                case 'actionSubmitAccountBefore':
                    if ( $values['RECAPTCHA_ENABLE_' . strtoupper($instance)] ) {
                        // Get api secret key
                        $configuration['secret'] = $values['RECAPTCHA_SECRET_API_KEY_' . $tpl_vars['widget_type']];
                        return $this->getWidgetVariables($hookName, $configuration);
                    }
                    break;
                case 'displayRecaptcha':
                case 'displayGDPRConsent':
                    if ( !isset($tpl_vars) || !$values['RECAPTCHA_ENABLE_' . strtoupper($instance)]) {
                        return;
                    }
                default:
                    $this->context->smarty->assign('simplerecaptcha', $tpl_vars);
                    return $this->fetch('module:simplerecaptcha/views/templates/hook/simplerecaptcha.tpl');
                    break;
            }
        }
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {

        if (!isset($configuration['secret'])) {
            Context::getContext()->controller->errors[] = $this->trans('Undefined secret key', array(), 'Modules.Simplerecaptcha.Admin');
            return false;
        }

        $secret = $configuration['secret'];
        $gRecaptchaResponse = Tools::getValue('g-recaptcha-response');
        $remoteIp = Tools::getRemoteAddr();
        
        /** @var ReCaptcha $recaptcha */
        $recaptcha = new ReCaptcha($secret);

        $resp = $recaptcha->setExpectedHostname(Configuration::get('PS_SHOP_DOMAIN'))
                          ->verify($gRecaptchaResponse, $remoteIp);

        if ($resp->isSuccess()) {
            return true;
        } else {
            $errors = $resp->getErrorCodes();
            Context::getContext()->controller->errors[] = $this->trans('Incorrect captcha, %s. Please try again.', $errors, 'Modules.Simplerecaptcha.Admin');
            
            return false;
        }

    }

    public function hookActionSubmitAccountBefore($params)
    {
        return $this->renderWidget('actionSubmitAccountBefore', $params);
    }

    /**
     * Get content of module admin configuration page
     * @deprecated No longer use this ! Please use a ModuleAdminController for Configuration use with HelperOption, 
     * for ObjectModel use with HelperForm
     * @return string
     */
    public function getContent()
    {
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink(self::MODULE_ADMIN_CONTROLLER));
        // Recommended to redirect user to your ModuleAdminController who manage Configuration
        return null;
    }

    /**
     * @return array
     */
    public function getConfigFieldsValues()
    {
        $api_keys = unserialize(Configuration::get(static::CONF_API_KEYS));
        $config = unserialize(Configuration::get(static::CONF_FORMS_CONFIG));

        if (is_array($api_keys) === false) {
            $api_keys = array();
        }

        if (is_array($config) === false) {
            $config = array();
        }

        return array_merge($api_keys, $config);
    }

    /**
     * @return array
     */
    public function getAvailableInstances()
    {
        $available_instances = array();

        // check modules
        foreach($this->instances['modules'] as $name) {
            if (Module::isEnabled($name)) {
                $module = Module::getInstanceByName($name);
                $available_instances[(int)$module->id] = $module->name;
            }
        }

        // check controllers
        $controllers = Dispatcher::getControllers(_PS_FRONT_CONTROLLER_DIR_);
        foreach($this->instances['controllers'] as $name) {
            if (in_array($name, $controllers, true)) {
                $available_instances[$name] = $name;
            }
        }

        return $available_instances;
    }

}