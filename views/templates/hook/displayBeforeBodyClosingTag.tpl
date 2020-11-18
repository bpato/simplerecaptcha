<!-- begin: recaptcha invisible -->
<script type="text/javascript">
    function sortByExplicitRender(a, b){
        var aExpl = $(a).hasClass('g-recaptcha');
        var bExpl = $(b).hasClass('g-recaptcha');
        return (aExpl ? -1 : (bExpl ? 1 : 0));
    };

{foreach $simplerecaptcha_js as $instance}
// <!-- begin {$instance}: recaptcha invisible -->

    (function($) {
        window._attach{$instance}Event = function() {
            var recaptcha_colection = $('div#recaptcha').sort(sortByExplicitRender);
            $('div#recaptcha.{$instance}').closest('form').submit(function(event){
                if (! grecaptcha.getResponse(recaptcha_colection.index($('.{$instance}'))) ) {
                    window._{$instance}Submitter = (event.originalEvent !== undefined) ? event.originalEvent.submitter : event.target;
                    event.preventDefault();
                    grecaptcha.reset(recaptcha_colection.index($('.{$instance}')));
                    grecaptcha.execute(recaptcha_colection.index($('.{$instance}')));
                } else {
                    delete window._{$instance}Submitter;
                }
            });
        };

        window._{$instance}OnSubmit = function(token) {
            var recaptcha_colection = $('div#recaptcha').sort(sortByExplicitRender);
            event.preventDefault();
            if ( grecaptcha.getResponse(recaptcha_colection.index($('.{$instance}'))) ) {
                $(window._{$instance}Submitter).trigger('click');
            }
        };

        $(document).ready(_attach{$instance}Event);
        
    })(jQuery);

//<!-- end {$instance}: recaptcha invisible -->
{/foreach}
</script>
<!-- end: recaptcha invisible -->
{if isset($simplerecaptcha)}
<!-- begin explicit render: {$simplerecaptcha.name} -->
<script type="text/javascript">
    (function($) {
        window.onloadRender = function() {
            if ($('{$simplerecaptcha.widget_id} #recaptcha').length > 0) {
                grecaptcha.render($('{$simplerecaptcha.widget_id} #recaptcha').get(0), {
                    "sitekey" : "{$simplerecaptcha.RECAPTCHA_API_KEY|escape:'html'}",
                    {if $simplerecaptcha.widget_type eq 0}
                    "theme"   : "{$simplerecaptcha.theme}",
                    "size"    : "{$simplerecaptcha.size}",
                    {else}
                    "callback": "_{$simplerecaptcha.name}OnSubmit",
                    "size"    : "invisible",
                    {/if}
                });

                $('{$simplerecaptcha.widget_id} #recaptcha').addClass('{$simplerecaptcha.name}');
                {if $simplerecaptcha.widget_type eq 1}
                _attach{$simplerecaptcha.name}Event();
                {/if}
            }
        };
    })(jQuery);
</script>
<!--end explicit render: {$simplerecaptcha.name} -->
{/if}
