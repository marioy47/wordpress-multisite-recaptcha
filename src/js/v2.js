
/**
 * This function will be called by the callback on successful captcha.
 *
 * @param {string} val The return valur for the capcha validation.
 */
window.recaptchaOnSubmit = (val) => {
	document.getElementById('wp-submit').disabled = false;
}


/**
 * Callback fuction name should be the same as class-auth-recpatcha.php.
 */
window.recaptchaCallback = () => {
	MULTISITE_RECAPTCHA.options.callback = recaptchaOnSubmit;
	grecaptcha.render(
		MULTISITE_RECAPTCHA.element,
		MULTISITE_RECAPTCHA.options
	);
	document.getElementById('wp-submit').disabled = true;
}
