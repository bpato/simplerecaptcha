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
        'displayBeforeBodyClosingTag',
        'displayGDPRConsent',
    );

    /** @var array modules compatible */
    protected $compatible_modules = array(
        'ps_emailsubscription', // v2.3.0
        'contactform',
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

        foreach ($this->getAvailableModuleForms() as $name) {
            $default_form_config_values['RECAPTCHA_ENABLE_' . strtoupper($name)] = 0;
            $default_form_config_values[$name.'[widget_type]'] = 0;
            $default_form_config_values[$name.'[country]'] = (int) Configuration::get('PS_COUNTRY_DEFAULT');
            $default_form_config_values[$name.'[size]'] = 'normal';
            $default_form_config_values[$name.'[theme]'] = 'light';
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

        $modules = $this->getAvailableModuleForms();
        $values = $this->getConfigFieldsValues();

        $load = false;
        foreach ($modules as $name) {
            if ($load) {
                continue;
            }

            if ($values['RECAPTCHA_ENABLE_' . strtoupper($name)]) {
                $load = true;
                $this->context->controller->registerJavascript(
                    'g-recaptcha', // Unique ID
                    'https://www.google.com/recaptcha/api.js', // JS path
                    array(
                        'server' => 'remote', 
                        'position' => 'bottom', 
                        'priority' => 1000
                    ) // Arguments
                );
            }
        }
    }

    public function hookDisplayBeforeBodyClosingTag($params) {
        $modules = $this->getAvailableModuleForms();
        $values = $this->getConfigFieldsValues();

        $tpl_vars = array();
        foreach ($modules as $name) {
            if ($values['RECAPTCHA_ENABLE_' . strtoupper($name)] && $values[$name.'[widget_type]'] == 1) {
                $tpl_vars[] = $name;
            }
        }

        $this->context->smarty->assign('simplerecaptcha_js', $tpl_vars );
        return $this->fetch('module:simplerecaptcha/views/templates/hook/displayBeforeBodyClosingTag.tpl');
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (isset($configuration['id_module'])) {
            // Retrieves the data for this module
            $id_module = (int)$configuration['id_module'];
            
            $modules = $this->getAvailableModuleForms();
            $values = $this->getConfigFieldsValues();

            if ( array_key_exists($id_module, $modules) ) {
                $tpl_vars['name'] = $modules[$id_module];
                $tpl_vars['widget_type'] = $values[$modules[$id_module].'[widget_type]'];
                $tpl_vars['country'] = strtolower(Country::getIsoById($values[$modules[$id_module].'[country]']));
                $tpl_vars['size'] = $values[$modules[$id_module].'[size]'];
                $tpl_vars['theme'] = $values[$modules[$id_module].'[theme]'];

                $tpl_vars['RECAPTCHA_API_KEY'] = $values['RECAPTCHA_API_KEY_' . $tpl_vars['widget_type']];
                $configuration['secret'] = $values['RECAPTCHA_SECRET_API_KEY_' . $tpl_vars['widget_type']];
            }
        }

        switch ($hookName) {
            case null:
            case 'actionFormSubmitBefore':
                return $this->getWidgetVariables($hookName, $configuration);
                break;
            case 'displayRecaptcha':
            case 'displayGDPRConsent':
                if ( !isset($tpl_vars) || !$values['RECAPTCHA_ENABLE_' . strtoupper($modules[$id_module])]) {
                    return;
                }
            default:
                $this->context->smarty->assign('simplerecaptcha', $tpl_vars);
                return $this->fetch('module:simplerecaptcha/views/templates/hook/simplerecaptcha.tpl');
                break;
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

    /**
     * Get content of module admin configuration page
     * @deprecated No longer use this ! Please use a ModuleAdminController for Configuration use with HelperOption, 
     * for ObjectModel use with HelperForm
     * @return string
     */
    public function getContent() {
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
    public function getAvailableModuleForms()
    {
        $available_modules = array();

        foreach($this->compatible_modules as $name) {
            if (Module::isEnabled($name)) {
                $module = Module::getInstanceByName($name);
                $available_modules[$module->id] = $module->name;
            }
        }

        return $available_modules;
    }

}