<div class="form-group row">
    <div class="col-md-6">
        <div id='recaptcha' class="g-recaptcha {$simplerecaptcha.name}" 
            data-sitekey="{$simplerecaptcha.RECAPTCHA_API_KEY|escape:'html'}"
        {if $simplerecaptcha.widget_type eq 0}
            data-theme="{$simplerecaptcha.theme}"
            data-size="{$simplerecaptcha.size}"
        {else}
            data-size="invisible"
            data-callback="_{$simplerecaptcha.name}OnSubmit"
        {/if}>
        </div>
    </div>
    <div class="col-md-3 form-control-comment"></div>
</div>

