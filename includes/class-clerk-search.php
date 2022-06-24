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

        if ($options['faceted_navigation_enabled'] !== null && $options['faceted_navigation_enabled']) {

            $facets_design =  isset($options['faceted_navigation_design']) ? $options['faceted_navigation_design'] : false;
            $search_include_categories =  isset($options['search_include_categories']) ? $options['search_include_categories'] : false;
            $search_categories =  isset($options['search_categories']) ? $options['search_categories'] : false;
            $search_include_pages = isset($options['search_include_pages']) ? $options['search_include_pages'] : false;
            $search_pages =  isset($options['search_pages']) ? $options['search_pages'] : false;
            $search_pages_type =  isset($options['search_pages_type']) ? $options['search_pages_type'] : false;

            $_Attributes = json_decode($options['faceted_navigation']);
            $count = 0;

            foreach ($_Attributes as $key => $_Attribute) {

                if ($_Attribute->checked) {

                    array_push($Attributes, $_Attribute);

                }

            }

           
            /**
             * Changed to use usort instead to fix sorting bug 22-07-2021 KKY
             * 
             * foreach ($Attributes as $key => $Sorted_Attribute) {
             * 
             *      $Sorted_Attributes[$Sorted_Attribute->position] = $Sorted_Attribute;
             * 
             * }
             * 
             */
                
            $Sorted_Attributes = $Attributes;

            usort($Sorted_Attributes, function($a, $b) {
                return $a->position <=> $b->position;
            });

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
                      echo "data-facets-design='".$facets_design ."'";

                  }

                if (isset($search_include_categories) && $search_include_categories) {
                    echo "data-search-categories='".$search_categories."'";
                }
                if (isset($search_include_pages) && $search_include_pages) {
                    echo "data-search-pages='".$search_pages."'";
                    if($search_pages_type != 'All'){
                        echo "data-search-pages-type='".$search_pages_type."'";
                    }
                }
                  ?>
                  data-query="<?php echo esc_attr(get_query_var('searchterm')); ?>">
		    </span>
            <?php
            if (count($Attributes) > 0) {

                echo '<div id="clerk-search-page-wrap" style="display: flex;">';
                echo '<div id="clerk-search-filters"></div>';

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

            <script>
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