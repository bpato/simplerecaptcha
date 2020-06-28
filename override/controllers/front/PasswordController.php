<?php

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