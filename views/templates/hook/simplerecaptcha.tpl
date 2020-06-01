<div class="form-group row">
    <div class="col-md-6">
        <div class="g-recaptcha" 
            data-sitekey="{$simplerecaptcha.$id_module.RECAPTCHA_API_KEY|escape:'html'}"
        {if $simplerecaptcha.$id_module.widget_type eq 0}
            data-theme="{$simplerecaptcha.$id_module.theme}"
            data-size="{$simplerecaptcha.$id_module.size}"
        {else}
            data-size="invisible"
        {/if}>
        </div>
    </div>
    <div class="col-md-3 form-control-comment">
        {debug}
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js{if isset($simplerecaptcha.$id_module.country)}?hl={$simplerecaptcha.$id_module.country}{/if}" async defer></script>
