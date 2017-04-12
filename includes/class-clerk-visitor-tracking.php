<?php

class Clerk_Visitor_Tracking {
	/**
	 * Clerk_Visitor_Tracking constructor.
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {
		add_action( 'wp_footer', [ $this, 'add_tracking' ] );
	}

	/**
	 * Include tracking
	 */
	public function add_tracking() {
		$options = get_option( 'clerk_options' );
		?>
        <!-- Start of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
        <script type="text/javascript">
            window.clerkAsyncInit = function () {
                Clerk.config({
                    key: '<?php echo $options['public_key']; ?>'
                });
            };

            (function () {
                var e = document.createElement('script');
                e.type = 'text/javascript';
                e.async = true;
                e.src = document.location.protocol + '//api.clerk.io/static/clerk.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(e, s);
            })();
        </script>
        <!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
		<?php
	}
}

new Clerk_Visitor_Tracking();