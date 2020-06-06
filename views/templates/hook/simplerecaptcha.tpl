<div class="form-group row">
    <div class="col-md-6">
        <div id='recaptcha' class="g-recaptcha {$simplerecaptcha.$id_module.name}" 
            data-sitekey="{$simplerecaptcha.$id_module.RECAPTCHA_API_KEY|escape:'html'}"
        {if $simplerecaptcha.$id_module.widget_type eq 0}
            data-theme="{$simplerecaptcha.$id_module.theme}"
            data-size="{$simplerecaptcha.$id_module.size}"
        {else}
            data-size="invisible"
            data-callback="_{$simplerecaptcha.$id_module.name}OnSubmit"
        {/if}>
        </div>
    </div>
    <div class="col-md-3 form-control-comment"></div>
</div>

