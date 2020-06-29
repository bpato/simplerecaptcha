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
class PasswordController extends PasswordControllerCore
{
    /**
     * Start forms process.
     *
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->setTemplate('customer/password-email');

        if (Tools::isSubmit('email')) {
            Hook::coreRenderWidget(Module::getInstanceByName('simplerecaptcha'), 'actionFormRecaptchaSubmitBefore', array());
            if ( !sizeof(Context::getContext()->controller->errors)) {
                $this->sendRenewPasswordLink();
            }
        } elseif (Tools::getValue('token') && ($id_customer = (int) Tools::getValue('id_customer'))) {
            $this->changePassword();
        } elseif (Tools::getValue('token') || Tools::getValue('id_customer')) {
            $this->errors[] = $this->trans('We cannot regenerate your password with the data you\'ve submitted', array(), 'Shop.Notifications.Error');
        }
    }
}