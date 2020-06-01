<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class SimpleReCaptcha extends Module implements WidgetInterface
{

    public $_displayIn = array(
        'contactform',
        'ps_emailsubscription',
    );

    public function __construct()
    {
        $this->name = 'simplerecaptcha';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Brais Pato';


        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('PS Simple Google ReCaptcha', array(), 'Modules.Simplerecaptcha.Admin');
        $this->description = $this->trans('Add Google ReCaptcha to your Prestashop', array(), 'Modules.Simplerecaptcha.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', array(), 'Modules.Simplerecaptcha.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);        
    }

    public function install()
    {

        return (parent::install() 
                && $this->registerHook(
                    array(
                        'displayGDPRConsent',
                    )
            ));
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
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

    /* Get values */
    public function getSubmitReCaptchaKey() {
        
        $values = array();

        for($i = 0; $i <= 1; $i++) {
            $values['RECAPTCHA_API_KEY_'.$i] = strval(Tools::getValue('RECAPTCHA_API_KEY_'.$i));
            $values['RECAPTCHA_SECRET_API_KEY_'.$i] = strval(Tools::getValue('RECAPTCHA_SECRET_API_KEY_'.$i));
        }

        return $values;
    }

    public function getSubmitSimpleReCaptcha() {

        $values = array();

        foreach ($this->getAvailableModuleForms() as $name => $displayName) {
            $values['RECAPTCHA_ENABLE_' . strtoupper($name)] = (int)Tools::getValue('RECAPTCHA_ENABLE_' . strtoupper($name));
            
            $default_values = array(
                '\'widget_type\''   => 0,
                '\'country\''       => (int) Configuration::get('PS_COUNTRY_DEFAULT'),
                '\'size\''          => 'normal',
                '\'theme\''         => 'light',
            );

            $options = Tools::getValue($name, $default_values);

            $values[$name.'[\'widget_type\']'] = (int) $options['\'widget_type\''];
            $values[$name.'[\'country\']'] = (int) $options['\'country\''];
            $values[$name.'[\'size\']'] = strval($options['\'size\'']);
            $values[$name.'[\'theme\']'] = strval($options['\'theme\'']);
        }

        return $values;
    }

    public function getConfigFieldsValues()
    {
        if ( ! $values = unserialize(Configuration::get('RECAPTCHA_API_KEYS')) ) {
            $values = $this->getSubmitReCaptchaKey();
        }

        if ( ! $config = unserialize(Configuration::get('RECAPTCHA_CONFIG')) ) {
            $config = $this->getSubmitSimpleReCaptcha();
        }

        return array_merge($config, $values);
    }

    public function getAvailableModuleForms() {

        $available_modules = array();

        foreach($this->_displayIn as $name) {
            if ($module = Module::getInstanceByName($name)) {
                $available_modules[$module->name] = $module->displayName;
            }
        }

        return $available_modules;
    }

    /* Configuration Page */
    public function getContent()
    {
        $this->_html = null;

        $submit_values = array();
        
        if (Tools::isSubmit('submitRecaptchaKey')) {
            $key = 'RECAPTCHA_API_KEYS';
            $submit_values = $this->getSubmitReCaptchaKey();
        } elseif (Tools::isSubmit('submitSimpleRecaptcha')) {
            $key = 'RECAPTCHA_CONFIG';
            $submit_values = $this->getSubmitSimpleReCaptcha(); 
        }

        if ( Tools::isSubmit('submitRecaptchaKey') || Tools::isSubmit('submitSimpleRecaptcha') ) {
            if ( Configuration::updateValue($key, serialize($submit_values)) ) {
                $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Modules.Simplerecaptcha.Admin'));
            } else {
                $this->_html .= $this->displayError($this->trans('Invalid Configuration value', array(), 'Modules.Simplerecaptcha.Admin'));
            }
        }

        $this->_html .= $this->displayForm();
        return $this->_html;
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Fields to store recaptcha api keys
        $fieldsForm[0]['form'] = array(
            'tabs' => array(
                'visible' => $this->trans('reCAPTCHA v2 - I am not a robot', array(), 'Modules.Simplerecaptcha.Admin'),
                'invisible' => $this->trans('reCAPTCHA v2 - Invisible', array(), 'Modules.Simplerecaptcha.Admin'),
            ),
            'legend' => array(
                'title' => $this->trans('reCAPTCHA API key pair', array(), 'Modules.Simplerecaptcha.Admin'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->trans('Google API Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'name' => 'RECAPTCHA_API_KEY_0',
                    'col' => '6',
                    'size' => 20,
                    'tab' => 'visible',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Google API Secret Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'name' => 'RECAPTCHA_SECRET_API_KEY_0',
                    'col' => '6',
                    'size' => 20,
                    'tab' => 'visible',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Google API Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'name' => 'RECAPTCHA_API_KEY_1',
                    'col' => '6',
                    'size' => 20,
                    'tab' => 'invisible',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Google API Secret Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'name' => 'RECAPTCHA_SECRET_API_KEY_1',
                    'col' => '6',
                    'size' => 20,
                    'tab' => 'invisible',
                )
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
                'class' => 'btn btn-default pull-right',
                'name'  => 'submitRecaptchaKey'
            )
        );

        $cache_id = 'module:simplerecaptcha_displayForm_select_array_' . pSQL($defaultLang);
        
        if (!Cache::isStored($cache_id)) {
            $select_array = array(
                'country' => array(),
                'size' => array(
                    array('id' => 'normal', 'name' => $this->trans('Normal', array(), 'Modules.Simplerecaptcha.Admin')),
                    array('id' => 'compact', 'name' => $this->trans('Compact', array(), 'Modules.Simplerecaptcha.Admin')),
                ),
                'theme' => array(
                    array('id' => 'light', 'name' => $this->trans('Light', array(), 'Modules.Simplerecaptcha.Admin')),
                    array('id' => 'dark', 'name' => $this->trans('Dark', array(), 'Modules.Simplerecaptcha.Admin')),
                )
            );
        
        
            $countries = Country::getCountries($defaultLang);
            foreach ($countries as $country) {
                $select_array['country'][] = array(
                    'id' => $country['id_country'],
                    'name' => $country['name'] . ' (' . $country['iso_code'] . ')',
                );
            }

            Cache::store($cache_id, $select_array);
        }

        $select_array = Cache::retrieve($cache_id);

        $include_forms = $this->getAvailableModuleForms();

        $input_config_forms = array();

        foreach ($include_forms as $name => $displayName) {
            $new_input = array(
                array(
                    'type' => 'switch',
                    'label' => $this->trans('Enable reCaptcha for "%name%"', array('%name%'=>$name), 'Modules.Simplerecaptcha.Admin'),
                    'name' => 'RECAPTCHA_ENABLE_' . strtoupper($name),
                    'required' => true,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'enable_on',
                            'value' => 1,
                            'label' => $this->trans('Enabled', array(), 'Admin.Global'),
                        ),
                        array(
                            'id' => 'enable_off',
                            'value' => 0,
                            'label' => $this->trans('Disabled', array(), 'Admin.Global'),
                        ),
                    ),
                    'tab' => $name,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('reCAPTCHA v2 widget type', array(), 'Modules.Simplerecaptcha.Admin'),
                    'name' => $name.'[\'widget_type\']',
                    'required' => false,
                    'default_value' => 0,
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => 0,
                                'name' => $this->trans('I am not a robot box', array(), 'Modules.Simplerecaptcha.Admin'),
                            ),
                            array(
                                'id' => 1,
                                'name' => $this->trans('Invisible', array(), 'Modules.Simplerecaptcha.Admin'),
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'tab' => $name,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('Language code', array(), 'Modules.Simplerecaptcha.Admin'),
                    'name' => $name.'[\'country\']',
                    'required' => false,
                    'default_value' => (int) Configuration::get('PS_COUNTRY_DEFAULT'),
                    'options' => array(
                        'query' => $select_array['country'],
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'tab' => $name,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('Size', array(), 'Modules.Simplerecaptcha.Admin'),
                    'required' => false,
                    'name' => $name.'[\'size\']',
                    'required' => false,
                    'default_value' => (int) $select_array['size'][0],
                    'options' => array(
                        'query' => $select_array['size'],
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'tab' => $name,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->trans('Theme', array(), 'Modules.Simplerecaptcha.Admin'),
                    'required' => false,
                    'name' => $name.'[\'theme\']',
                    'required' => false,
                    'default_value' => (int) $select_array['theme'][0],
                    'options' => array(
                        'query' => $select_array['theme'],
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'tab' => $name,
                ),
            );
            $input_config_forms = array_merge($input_config_forms, $new_input);
        }

        // Fields to store forms configuration
        $fieldsForm[1]['form'] = array(
            'tabs' => $include_forms,
            'legend' => array(
                'title' => $this->trans('Settings', array(), 'Admin.Global'),
                'icon' => 'icon-cogs'
            ),
            'input' => $input_config_forms,
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
                'class' => 'btn btn-default pull-right',
                'name'  => 'submitSimpleRecaptcha'
            )
        );

        $helper = new HelperForm();

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;        // false -> remove toolbar
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->identifier = $this->identifier;

        // Load current value
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $defaultLang,
        );

        return $helper->generateForm($fieldsForm);
    }

}