// simplerecaptcha - AuthController
document.addEventListener("DOMContentLoaded", function(event) {
    (function($) {
        $('#authentication #login-form .form-footer').prepend('<div class="form-group row"><div id="recaptcha" class="offset-md-3"></div></div>');
    })(jQuery);
});