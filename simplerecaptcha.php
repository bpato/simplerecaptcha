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

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class SimpleReCaptcha extends Module implements WidgetInterface
{

    /** @var string Unique name */
    public $name = 'simplerecaptcha';

    /** @var string Admin tab corresponding to the module */
    public $tab = 'back_office_features';

    /** @var string Version */
    public $version = '1.0.0';

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
        'displayGDPRConsent',
    );

    /** @var array modules compatible */
    protected $compatible_modules = array(
        'contactform',
        'ps_emailsubscription',
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
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() 
            && $this->registerHook($this->hooks)
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

        foreach ($this->getAvailableModuleForms() as $name => $displayName) {
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
    public function uninstall()
    {
        return parent::uninstall()
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

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $modules = array();
        foreach ($this->getAvailableModuleForms() as $name => $displayName) {
            $modules[Module::getModuleIdByName($name)] = $name;
        }

        $values = $this->getConfigFieldsValues();
        $tpl_vars = array();

        $id_module = $configuration['id_module'];

        switch ($hookName) {
            case null:
                return;
                break;
            case 'displayGDPRConsent':
                if (array_key_exists($id_module, $modules) && $tpl_vars['RECAPTCHA_ENABLE'] = $values['RECAPTCHA_ENABLE_' . strtoupper($modules[$id_module])]) {
                    $tpl_vars['name'] = $modules[$id_module];
                    $tpl_vars['widget_type'] = $values[$modules[$id_module].'[\'widget_type\']'];
                    $tpl_vars['RECAPTCHA_API_KEY'] = $values['RECAPTCHA_API_KEY_' . $tpl_vars['widget_type']];
                    $tpl_vars['country'] = strtolower(Country::getIsoById($values[$modules[$id_module].'[\'country\']']));
                    $tpl_vars['size'] = $values[$modules[$id_module].'[\'size\']'];
                    $tpl_vars['theme'] = $values[$modules[$id_module].'[\'theme\']'];
                } else {
                    return;
                }
            default:
                $this->context->smarty->assign('simplerecaptcha', array( $id_module => $tpl_vars ));
                return $this->fetch('module:simplerecaptcha/views/templates/hook/simplerecaptcha.tpl');
                break;
        }
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return true;
    }

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

    public function getAvailableModuleForms()
    {

        $available_modules = array();

        foreach($this->compatible_modules as $name) {
            $module = Module::getInstanceByName($name);
            if ($module->active) {
                $available_modules[$module->name] = $module->displayName;
            }
        }

        return $available_modules;
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

}