<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Search {
	/**
	 * Clerk_Search constructor.
	 */
    protected $logger;
	public function __construct() {
		$this->initHooks();
        require_once(__DIR__ . '/class-clerk-logger.php');
        $this->logger = new ClerkLogger();
	}

	/**
	 * Init hooks
	 */
	private function initHooks() {
		add_filter( 'query_vars', [ $this, 'add_search_vars' ] );
		add_shortcode( 'clerk-search', [ $this, 'handle_shortcode' ] );
	}

	/**
	 * Add query var for searchterm
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_search_vars( $vars ) {

        try {

            $vars[] = 'searchterm';

            return $vars;

        } catch (Exception $e) {

            $this->logger->error('ERROR add_search_vars', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Output clerk-search shortcode
	 *
	 * @param $atts
	 */
	public function handle_shortcode($atts)
    {

        try {

            $options = get_option('clerk_options');
            ?>
            <span id="clerk-search"
                  class="clerk"
                  data-template="@<?php echo esc_attr(strtolower(str_replace(' ', '-', $options['search_template']))); ?>"
                  data-query="<?php echo esc_attr(get_query_var('searchterm')); ?>">
		</span>
            <?php

        } catch (Exception $e) {

            $this->logger->error('ERROR handle_shortcode', ['error' => $e->getMessage()]);

        }

	}
}

new Clerk_Search();