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

        $facets_attributes = '[';
        $facets_titles = '{';
        $Attributes = [];

        $options = get_option('clerk_options');

        if (in_array('faceted_navigation_enabled', $options) && $options['faceted_navigation_enabled']) {

            $_Attributes = json_decode($options['faceted_navigation']);
            $count = 0;

            foreach ($_Attributes as $key => $_Attribute) {

                if ($_Attribute->checked) {

                    array_push($Attributes, $_Attribute);

                }

            }

            $Sorted_Attributes = [];

            foreach ($Attributes as $key => $Sorted_Attribute) {

                $Sorted_Attributes[$Sorted_Attribute->position] = $Sorted_Attribute;

            }

            foreach ($Sorted_Attributes as $key => $Attribute) {

                $count++;

                if ($count == count($Attributes)) {

                    $facets_attributes .= '"' . $Attribute->attribute . '"';
                    $facets_titles .= '"' . $Attribute->attribute . '": "' . $Attribute->title . '"';

                } else {

                    $facets_attributes .= '"' . $Attribute->attribute . '", ';
                    $facets_titles .= '"' . $Attribute->attribute . '": "' . $Attribute->title . '",';

                }
            }

        }

        $facets_attributes .= ']\'';
        $facets_titles .= '}\'';

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
                  <?php
                  if (count($Attributes) > 0) {

                      echo 'data-facets-target="#clerk-search-filters"';
                      echo "data-facets-attributes='".$facets_attributes;
                      echo "data-facets-titles='".$facets_titles;

                  }

                  ?>
                  data-query="<?php echo esc_attr(get_query_var('searchterm')); ?>">
		    </span>
            <?php
            if (count($Attributes) > 0) {

                echo '<div style="display: flex;">';
                echo  '<div style="width: 30%; margin-top: 75px;" id="clerk-search-filters"></div>';


            }

            ?>

            <ul style="width: 100%;" id="clerk-search-results"></ul>

            <?php

            if (count($Attributes) > 0) {

                echo ' </div>';

            }

            ?>
            </div>
            <div id="clerk-search-no-results" style="display: none; margin-left: 3em;"><h2><?php echo $options['search_no_results_text'] ?></h2></div>

            <script type="text/javascript">
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