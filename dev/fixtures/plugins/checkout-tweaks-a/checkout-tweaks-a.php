<?php
/**
 * Plugin Name: Checkout Tweaks A
 * Description: Eval fixture. Fatals on load, but only for processes started with WPCLI_SCENARIO=a, so a "broken plugin" can be staged without breaking the sandbox for anyone else.
 * Version: 2.4.0
 */

if ( 'a' === getenv( 'WPCLI_SCENARIO' ) ) {
	checkout_tweaks_register_gateway_fields();
}
