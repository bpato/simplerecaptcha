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