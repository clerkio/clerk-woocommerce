<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Clerk_Exit_Intent {
	/**
	 * Clerk_Exit_Intent constructor.
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {
		add_action( 'wp_footer', [ $this, 'add_exit_intent' ] );
	}

	/**
	 * Include exit intent
	 */
	public function add_exit_intent() {
		$options = get_option( 'clerk_options' );

		if ($options['exit_intent_enabled']) :
        ?>
            <span class="clerk"
                  data-template="@<?php echo esc_attr($options['exit_intent_template']); ?>"
                  data-exit-intent="true"></span>
        <?php
        endif;
	}
}

new Clerk_Exit_Intent();