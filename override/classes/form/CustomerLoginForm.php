<?php

class CustomerLoginForm extends CustomerLoginFormCore
{
    public function submit()
    {
        Hook::coreRenderWidget(Module::getInstanceByName('simplerecaptcha'), 'actionFormRecaptchaSubmitBefore', array());

        if ( !sizeof(Context::getContext()->controller->errors)) {
            return parent::submit();
        } else {
            $this->errors[''] = Context::getContext()->controller->errors;
            Context::getContext()->controller->errors = array();
            return !$this->hasErrors();
        }
    }
}