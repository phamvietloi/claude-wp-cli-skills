<?php
/**
 * Plugin Name: Dev mail catcher
 * Description: Routes every outgoing email to the Mailpit container. Dev sandbox only.
 */

add_action(
	'phpmailer_init',
	function ( $phpmailer ) {
		$phpmailer->isSMTP();
		$phpmailer->Host     = 'mail';
		$phpmailer->Port     = 1025;
		$phpmailer->SMTPAuth = false;
		$phpmailer->SMTPAutoTLS = false;
	}
);

// The default sender is wordpress@localhost, which PHPMailer rejects.
add_filter(
	'wp_mail_from',
	function () {
		return 'store@example.com';
	}
);
