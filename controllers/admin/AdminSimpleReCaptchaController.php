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

class AdminSimpleReCaptchaController extends ModuleAdminController
{

    /** @var SimpleReCaptcha $module */
    public $module;

    /**
     * AdminSimpleReCaptchaController Constructor.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'configuration';
        $this->className = 'Configuration';

        parent::__construct();

        /** @var SimpleReCaptcha $module */
        $this->module = Module::getInstanceByName('simplerecaptcha');

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminDashboard'));
        }

        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $this->fields_options = array();
        $this->fields_options[0] = array(
            'title' => $this->trans('reCAPTCHA API key pair', array(), 'Modules.Simplerecaptcha.Admin'),
            'icon' => 'icon-cogs',
            'tabs' => array(
                'visible' => $this->trans('reCAPTCHA v2 - I am not a robot', array(), 'Modules.Simplerecaptcha.Admin'),
                'invisible' => $this->trans('reCAPTCHA v2 - Invisible', array(), 'Modules.Simplerecaptcha.Admin'),
            ),
            'fields' => array(
                'RECAPTCHA_API_KEY_0' => array(
                    'type' => 'text',
                    'title' => $this->trans('Google API Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'size' => 20,
                    'auto_value' => false,
                    'tab' => 'visible',
                ),
                'RECAPTCHA_SECRET_API_KEY_0' => array(
                    'type' => 'text',
                    'title' => $this->trans('Google API Secret Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'size' => 20,
                    'auto_value' => false,
                    'tab' => 'visible',
                ),
                'RECAPTCHA_API_KEY_1' => array(
                    'type' => 'text',
                    'title' => $this->trans('Google API Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'size' => 20,
                    'auto_value' => false,
                    'tab' => 'invisible',
                ),
                'RECAPTCHA_SECRET_API_KEY_1' => array(
                    'type' => 'text',
                    'title' => $this->trans('Google API Secret Key', array(), 'Modules.Simplerecaptcha.Admin'),
                    'size' => 20,
                    'auto_value' => false,
                    'tab' => 'invisible',
                )
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
                'name'  => 'submitRecaptchaKeys'
            )
        );

        if (_PS_MODE_DEV_) {
            $this->fields_options[0]['fields'][$this->module::CONF_API_KEYS] = array(
                'type' => 'textarea',
                'cols' => 10,
                'rows' => 5,
                'title' => $this->module::CONF_API_KEYS,
                'desc' => '<i class="material-icons">bug_report</i>'.$this->trans('Your shop is in debug mode.', array(), 'Admin.Navigation.Notification')
            );
        }

        $cache_id = 'AdminSimpleReCaptchaController_selects_' . pSQL($defaultLang);
        
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

        $include_forms = $this->module->getAvailableModuleForms();

        $input_config_fields = array();

        if (_PS_MODE_DEV_) {
            $input_config_fields[$this->module::CONF_FORMS_CONFIG] = array(
                'type' => 'textarea',
                'cols' => 10,
                'rows' => 5,
                'title' => $this->module::CONF_FORMS_CONFIG,
                'desc' => '<i class="material-icons">bug_report</i>'.$this->trans('Your shop is in debug mode.', array(), 'Admin.Navigation.Notification')
            );
        }

        foreach ($include_forms as $name => $displayName) {
            $new_field = array(
                'RECAPTCHA_ENABLE_' . strtoupper($name) => array(
                    'type' => 'bool',
                    'title' => $this->trans('Enable reCaptcha for "%name%"', array('%name%'=>$name), 'Modules.Simplerecaptcha.Admin'),
                    'auto_value' => false,
                    'tab'   => $name
                ),
                $name.'[widget_type]' => array(
                    'type' => 'select',
                    'title' => $this->trans('reCAPTCHA v2 widget type', array(), 'Modules.Simplerecaptcha.Admin'),
                    'auto_value' => false,
                    'value' => 0,
                    'identifier' => 'id',
                    'list' => array(
                        array(
                            'id' => 0,
                            'name' => $this->trans('I am not a robot box', array(), 'Modules.Simplerecaptcha.Admin'),
                        ),
                        array(
                            'id' => 1,
                            'name' => $this->trans('Invisible', array(), 'Modules.Simplerecaptcha.Admin'),
                        ),
                    ),
                    'tab'   => $name
                ),
                $name.'[country]' => array(
                    'type' => 'select',
                    'title' => $this->trans('Language code', array(), 'Modules.Simplerecaptcha.Admin'),
                    'auto_value' => false,
                    'defaultValue' => Configuration::get('PS_COUNTRY_DEFAULT'),
                    'identifier' => 'id',
                    'list' => $select_array['country'],
                    'tab'   => $name
                ),
                $name.'[size]' => array(
                    'type' => 'select',
                    'title' => $this->trans('Size', array(), 'Modules.Simplerecaptcha.Admin'),
                    'auto_value' => false,
                    'defaultValue' => $select_array['size'][0]['id'],
                    'identifier' => 'id',
                    'list' => $select_array['size'],
                    'tab'   => $name
                ),
                $name.'[theme]' => array(
                    'type' => 'select',
                    'title' => $this->trans('Theme', array(), 'Modules.Simplerecaptcha.Admin'),
                    'auto_value' => false,
                    'defaultValue' => $select_array['theme'][0]['id'],
                    'identifier' => 'id',
                    'list' => $select_array['theme'],
                    'tab'   => $name
                )
            );

            $input_config_fields = array_merge($input_config_fields, $new_field);
        }

        $this->fields_options[1] = array(
            'title' => $this->trans('Settings', array(), 'Admin.Global'),
            'icon' => 'icon-cogs',
            'tabs' => $include_forms,
            'fields' => $input_config_fields,
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
                'name'  => 'submitSimpleRecaptchaConfig'
            )
        );
    }

    /**
     * From init()
     * Retrieve submit value and translate them to actions.
     */
    public function initProcess()
    {
        parent::initProcess();
        if (Tools::isSubmit('submitRecaptchaKeys')) {
            $this->action = 'submit_recaptcha_keys';
        }

        if (Tools::isSubmit('submitSimpleRecaptchaConfig')) {
            $this->action = 'submit_simple_recaptcha_config';
        }
    }
    
    /**
     * Custom action 'submit_recaptcha_keys' that update options and preferences
     */
    public function processSubmitRecaptchaKeys() 
    {
        $this->validateUpdateOptions();

        if (!count($this->errors)) {
            $submit_values = serialize( $this->getSubmitRecaptchaKeys());

            if (Validate::isCleanHtml($submit_values)) {
                Configuration::updateValue($this->module::CONF_API_KEYS, $submit_values);
            } else {
                $this->errors[] = $this->trans('Cannot add configuration %s', array($this->module::CONF_API_KEYS), 'Admin.Notifications.Error');
            }
        }

        $this->display = 'list';
        if (empty($this->errors)) {
            $this->confirmations[] = $this->_conf[6];
        }

    }

    /**
     * Retrieve GET and POST values.
     * @return array
     */
    public function getSubmitRecaptchaKeys()
    {
        $values = array();
    
        for($i = 0; $i <= 1; $i++) {
            $values['RECAPTCHA_API_KEY_'.$i] = strval(Tools::getValue('RECAPTCHA_API_KEY_'.$i));
            $values['RECAPTCHA_SECRET_API_KEY_'.$i] = strval(Tools::getValue('RECAPTCHA_SECRET_API_KEY_'.$i));
        }

        return $values;
    }

    /**
     * Custom action 'submit_simple_recaptcha_config' that update options and preferences
     */
    public function processSubmitSimpleRecaptchaConfig()
    {
        $this->validateUpdateOptions();

        if (!count($this->errors)) {
            $submit_values = serialize( $this->getSubmitSimpleRecaptchaConfig());

            if (Validate::isCleanHtml($submit_values)) {
                Configuration::updateValue($this->module::CONF_FORMS_CONFIG, $submit_values);
            } else {
                $this->errors[] = $this->trans('Cannot add configuration %s', array($this->module::CONF_FORMS_CONFIG), 'Admin.Notifications.Error');
            }
        }

        $this->display = 'list';
        if (empty($this->errors)) {
            $this->confirmations[] = $this->_conf[6];
        }
    }

    /**
     * Retrieve GET and POST values.
     * @return array
     */
    public function getSubmitSimpleRecaptchaConfig() 
    {
        $values = array();

        foreach ($this->module->getAvailableModuleForms() as $name => $displayName) {
            $values['RECAPTCHA_ENABLE_' . strtoupper($name)] = (int)Tools::getValue('RECAPTCHA_ENABLE_' . strtoupper($name));
            $submit_values = Tools::getValue($name);
            
            foreach ($submit_values as $key => $value) {
                $values[$name.'['.$key.']'] = $value;
            }
            
        }

        return $values;
    }

    /**
     *  Fields validation from default processUpdateOptions()
     */
    public function validateUpdateOptions() 
    {

        $languages = Language::getLanguages(false);

        $hide_multishop_checkbox = (Shop::getTotalShops(false, null) < 2) ? true : false;
        foreach ($this->fields_options as $category_data) {
            if (!isset($category_data['fields'])) {
                continue;
            }

            $fields = $category_data['fields'];

            foreach ($fields as $field => $values) {
                if (isset($values['type']) && $values['type'] == 'selectLang') {
                    foreach ($languages as $lang) {
                        if (Tools::getValue($field . '_' . strtoupper($lang['iso_code']))) {
                            $fields[$field . '_' . strtoupper($lang['iso_code'])] = array(
                                'type' => 'select',
                                'cast' => 'strval',
                                'identifier' => 'mode',
                                'list' => $values['list'],
                            );
                        }
                    }
                }
            }

            // Validate fields
            foreach ($fields as $field => $values) {
                // We don't validate fields with no visibility
                if (!$hide_multishop_checkbox && Shop::isFeatureActive() && isset($values['visibility']) && $values['visibility'] > Shop::getContext()) {
                    continue;
                }

                // Check if field is required
                if ((!Shop::isFeatureActive() && !empty($values['required']))
                    || (Shop::isFeatureActive() && isset($_POST['multishopOverrideOption'][$field]) && !empty($values['required']))) {
                    if (isset($values['type']) && $values['type'] == 'textLang') {
                        foreach ($languages as $language) {
                            if (($value = Tools::getValue($field . '_' . $language['id_lang'])) == false && (string) $value != '0') {
                                $this->errors[] = $this->trans('field %s is required.', array($values['title']), 'Admin.Notifications.Error');
                            }
                        }
                    } elseif (($value = Tools::getValue($field)) == false && (string) $value != '0') {
                        $this->errors[] = $this->trans('field %s is required.', array($values['title']), 'Admin.Notifications.Error');
                    }
                }

                // Check field validator
                if (isset($values['type']) && $values['type'] == 'textLang') {
                    foreach ($languages as $language) {
                        if (Tools::getValue($field . '_' . $language['id_lang']) && isset($values['validation'])) {
                            $values_validation = $values['validation'];
                            if (!Validate::$values_validation(Tools::getValue($field . '_' . $language['id_lang']))) {
                                $this->errors[] = $this->trans('The %s field is invalid.', array($values['title']), 'Admin.Notifications.Error');
                            }
                        }
                    }
                } elseif (Tools::getValue($field) && isset($values['validation'])) {
                    $values_validation = $values['validation'];
                    if (!Validate::$values_validation(Tools::getValue($field))) {
                        $this->errors[] = $this->trans('The %s field is invalid.', array($values['title']), 'Admin.Notifications.Error');
                    }
                }

                // Set default value
                if (Tools::getValue($field) === false && isset($values['default'])) {
                    $_POST[$field] = $values['default'];
                }
            }

            if (!count($this->errors)) {
                foreach ($fields as $key => $options) {
                    if (Shop::isFeatureActive() && isset($options['visibility']) && $options['visibility'] > Shop::getContext()) {
                        continue;
                    }

                    if (!$hide_multishop_checkbox && Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && empty($options['no_multishop_checkbox']) && empty($_POST['multishopOverrideOption'][$key])) {
                        Configuration::deleteFromContext($key);

                        continue;
                    }

                    if (isset($options['type']) && in_array($options['type'], array('textLang', 'textareaLang'))) {
                        $list = array();
                        foreach ($languages as $language) {
                            $key_lang = Tools::getValue($key . '_' . $language['id_lang']);
                            $val = (isset($options['cast']) ? $options['cast']($key_lang) : $key_lang);
                            if ($this->validateField($val, $options)) {
                                if (Validate::isCleanHtml($val)) {
                                    $list[$language['id_lang']] = $val;
                                } else {
                                    $this->errors[] = $this->trans('Cannot add configuration %1$s for %2$s language', array($key, Language::getIsoById((int) $language['id_lang'])), 'Admin.International.Notification');
                                }
                            }
                        }
                    } else {
                        $val = (isset($options['cast']) ? $options['cast'](Tools::getValue($key)) : Tools::getValue($key));
                        if ($this->validateField($val, $options)) {
                            if (! Validate::isCleanHtml($val)) {
                                $this->errors[] = $this->trans('Cannot add configuration %s', array($key), 'Admin.Notifications.Error');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Assign values for custom variables to the view.
     * all fields that have [auto_value] set to false
     * 
     * @return string
     */
    public function renderOptions()
    {
        if ($this->fields_options && is_array($this->fields_options)) {

            if ( ! $values = unserialize(Configuration::get($this->module::CONF_API_KEYS)) ) {
                $values = $this->getSubmitRecaptchaKeys();
            }
    
            if ( ! $config = unserialize(Configuration::get($this->module::CONF_FORMS_CONFIG)) ) {
                $config = $this->getSubmitSimpleRecaptchaConfig();
            }

            $value_options = array_merge($values, $config);
            foreach ($this->fields_options as $category => &$category_data) {
                if (!isset($category_data['fields'])) {
                    $category_data['fields'] = array();
                }

                foreach ($category_data['fields'] as $key => &$field) {
                    if (isset($field['auto_value']) && ! $field['auto_value']) {
                        $field['value'] = $value_options[$key];
                    }
                }
            }
        }

        return parent::renderOptions();
    }
}