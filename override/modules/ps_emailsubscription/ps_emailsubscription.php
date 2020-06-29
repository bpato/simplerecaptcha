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

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

/** ps_emailsubscription v2.6.0 */

class Ps_EmailsubscriptionOverride extends Ps_Emailsubscription implements WidgetInterface
{
    public function newsletterRegistration($hookName = NULL)
    {
        Hook::coreRenderWidget(Module::getInstanceByName('simplerecaptcha'), 'actionFormRecaptchaSubmitBefore', array('id_module' => $this->id));
        if ( !sizeof(Context::getContext()->controller->errors)) {
            parent::newsletterRegistration($hookName);
        } else {
            $this->error = Context::getContext()->controller->errors[0];
        }
    }

}