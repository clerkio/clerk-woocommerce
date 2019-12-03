<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clerk_Search
{
    /**
     * Clerk_Search constructor.
     */
    protected $logger;

    public function __construct()
    {
        $this->initHooks();
        require_once(__DIR__ . '/class-clerk-logger.php');
        $this->logger = new ClerkLogger();
    }

    /**
     * Init hooks
     */
    private function initHooks()
    {
        add_filter('query_vars', [$this, 'add_search_vars']);
        add_shortcode('clerk-search', [$this, 'handle_shortcode']);
    }

    /**
     * Add query var for searchterm
     *
     * @param $vars
     *
     * @return array
     */
    public function add_search_vars($vars)
    {

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
                  data-limit="40"
                  data-offset="0"
                  data-target="#clerk-search-results"
                  data-after-render="_clerk_after_load_event"
                  data-query="<?php echo esc_attr(get_query_var('searchterm')); ?>">
		    </span>

            <ul id="clerk-search-results"></ul>
            <div id="clerk-search-no-results" style="display: none; margin-left: 3em;"><h2><?php echo $options['search_no_results_text'] ?></h2></div>

            <script type="text/javascript">F
                var total_loaded = 0;

                function _clerk_after_load_event(data) {
                    total_loaded += data.response.result.length;
                    var e = jQuery('#clerk-search');
                    if (typeof e.data('limit') === "undefined") {
                        e.data('limit', data.response.result.length)
                    }
                    if (total_loaded == 0) {
                        jQuery('#clerk-search-no-results').show();
                    } else {
                        jQuery('#clerk-search-no-results').hide();
                    }

                }
            </script>
            <?php

        } catch (Exception $e) {

            $this->logger->error('ERROR handle_shortcode', ['error' => $e->getMessage()]);

        }

    }
}

new Clerk_Search();