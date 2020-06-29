<?php
/**
 * Copyright (C) 2020 Brais Pato
 *
 * NOTICE OF LICENSE
 *
 * This file is part of Simplerecaptcha <https://github.com/bpato/simplerecaptcha.git>.
 * 
 * Simplerecaptcha is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Simplerecaptcha is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Foobar. If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    Brais Pato <patodevelop@gmail.com>
 * @copyright 2020 Brais Pato
 * @license   https://www.gnu.org/licenses/ GNU GPLv3
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

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

        $this->meta_title = $this->module->displayName;

        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $cache_id = 'AdminSimpleReCaptchaController_select_lang_' . pSQL($defaultLang);

        if (!Cache::isStored($cache_id)) {
            $select_lang = array();

            $countries = Country::getCountries($defaultLang);
            foreach ($countries as $country) {
                $select_lang[] = array(
                    'id' => $country['id_country'],
                    'name' => $country['name'] . ' (' . $country['iso_code'] . ')',
                );
            }

            Cache::store($cache_id, $select_lang);
        }

        $select_lang = Cache::retrieve($cache_id);

        $this->fields_options = array();
        $this->fields_options[0] = array(
            'title' => $this->trans('reCAPTCHA API key pair', array(), 'Modules.Simplerecaptcha.Admin'),
            'icon' => 'icon-cogs',
            'tabs' => array(
                'visible' => $this->trans('reCAPTCHA v2 - Checkbox', array(), 'Modules.Simplerecaptcha.Admin'),
                'invisible' => $this->trans('reCAPTCHA v2 - Invisible', array(), 'Modules.Simplerecaptcha.Admin'),
            ),
            'fields' => array(
                'RECAPTCHA_COUNTRY' => array(
                    'type' => 'select',
                    'title' => $this->trans('Language code', array(), 'Modules.Simplerecaptcha.Admin'),
                    'auto_value' => false,
                    'defaultValue' => Configuration::get('PS_COUNTRY_DEFAULT'),
                    'identifier' => 'id',
                    'list' => $select_lang,
                ),
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
        
        $select_array = array(
            'size' => array(
                array('id' => 'normal', 'name' => $this->trans('Normal', array(), 'Modules.Simplerecaptcha.Admin')),
                array('id' => 'compact', 'name' => $this->trans('Compact', array(), 'Modules.Simplerecaptcha.Admin')),
            ),
            'theme' => array(
                array('id' => 'light', 'name' => $this->trans('Light', array(), 'Modules.Simplerecaptcha.Admin')),
                array('id' => 'dark', 'name' => $this->trans('Dark', array(), 'Modules.Simplerecaptcha.Admin')),
            )
        );

        $include_forms = array();
        
        foreach( $this->module->getAvailableInstances() as $key => $name) {
            if (is_int($key)) {
                // Integer Module id 
                $include_forms[$name] = Module::getModuleName($name);
            } else {
                // String Controller filename
                $include_forms[$name] = $key;
            }
            
        }

        $input_config_fields = array();

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
                            'name' => $this->trans('Checkbox', array(), 'Modules.Simplerecaptcha.Admin'),
                        ),
                        array(
                            'id' => 1,
                            'name' => $this->trans('Invisible', array(), 'Modules.Simplerecaptcha.Admin'),
                        ),
                    ),
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

        if (_PS_MODE_DEV_) {
            // Debug stored variables on ps_configuration
            $this->fields_options[0]['fields'][$this->module::CONF_API_KEYS] = array(
                'type' => 'textarea',
                'cols' => 10,
                'rows' => 5,
                'title' => $this->module::CONF_API_KEYS,
                'desc' => '<i class="material-icons">bug_report</i>'.$this->trans('Your shop is in debug mode.', array(), 'Admin.Navigation.Notification')
            );
            
            $this->fields_options[1]['fields'][$this->module::CONF_FORMS_CONFIG] = array(
                'type' => 'textarea',
                'cols' => 10,
                'rows' => 5,
                'title' => $this->module::CONF_FORMS_CONFIG,
                'desc' => '<i class="material-icons">bug_report</i>'.$this->trans('Your shop is in debug mode.', array(), 'Admin.Navigation.Notification')
            );
        }
    }

    /**
     * Set default toolbar title.
     */
    public function initToolbarTitle()
    {
        $this->toolbar_title[] = $this->module->displayName;
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
        $values = array('RECAPTCHA_COUNTRY' => strval(Tools::getValue('RECAPTCHA_COUNTRY')));
    
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

        foreach ($this->module->getAvailableInstances() as $name) {
            $values['RECAPTCHA_ENABLE_' . strtoupper($name)] = Tools::getValue('RECAPTCHA_ENABLE_' . strtoupper($name));
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
                    if (isset($field['auto_value']) && false === $field['auto_value']) {
                        $field['value'] = isset($value_options[$key])?$value_options[$key]:'';
                    }
                }
            }
        }

        return parent::renderOptions();
    }
}