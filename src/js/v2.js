
/**
 * This function will be called by the callback on successful captcha.
 *
 * @param {string} val The return valur for the capcha validation.
 */
window.recaptchaOnSubmit = (val) => {
	const wp_submit = document.getElementById('wp-submit');
	if (wp_submit) {
		wp_submit.disabled = false;
	}


	const woo_submit = document.querySelectorAll('.woocommerce-form-login__submit');
	if (woo_submit) {
		for (let i = 0; i < woo_submit.length; i++) {
			woo_submit[i].disabled = false;
		}
	}

	var woo_forgot = document.querySelectorAll('.woocommerce-ResetPassword .woocommerce-Button');
	if (woo_forgot) {
		for (var i = 0; i < woo_forgot.length; i++) {
			woo_forgot[i].disabled = false;
		}
	}
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

	let wp_submit = document.getElementById('wp-submit');
	if (wp_submit) {
		wp_submit.disabled = true;
	}

	const woo_submit = document.querySelectorAll('.woocommerce-form-login__submit');
	if (woo_submit) {
		for (let i = 0; i < woo_submit.length; i++) {
			woo_submit[i].disabled = true;
		}
	}

	var woo_forgot = document.querySelectorAll('.woocommerce-ResetPassword .woocommerce-Button');
	if (woo_forgot) {
		for (var i = 0; i < woo_forgot.length; i++) {
			woo_forgot[i].disabled = true;
		}
	}
}
