<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Clerk_Visitor_Tracking {
    /**
     * Clerk_Visitor_Tracking constructor.
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
        add_action( 'wp_footer', [ $this, 'add_tracking' ] );
    }

    /**
     * Include tracking
     */
    public function add_tracking() {

        try {

            $options = get_option('clerk_options');

            //Default to true
            if (!isset($options['collect_emails'])) {
                $options['collect_emails'] = true;
            }

            if (isset($options['lang'])) {

                if ($options['lang'] == 'auto') {
                    $LangsAuto = [
                        'da_DK' => 'Danish',
                        'nl_NL' => 'Dutch',
                        'en_US' => 'English',
                        'en_GB' => 'English',
                        'fi' => 'Finnish',
                        'fr_FR' => 'French',
                        'fr_BE' => 'French',
                        'de_DE' => 'German',
                        'hu_HU' => 'Hungarian',
                        'it_IT' => 'Italian',
                        'nn_NO' => 'Norwegian',
                        'nb_NO' => 'Norwegian',
                        'pt_PT' => 'Portuguese',
                        'pt_BR' => 'Portuguese',
                        'ro_RO' => 'Romanian',
                        'ru_RU' => 'Russian',
                        'ru_UA' => 'Russian',
                        'es_ES' => 'Spanish',
                        'sv_SE' => 'Swedish',
                        'tr_TR' => 'Turkish'
                    ];

                    $Lang = strtolower($LangsAuto[get_locale()]);

                } else {

                    $Lang = $options['lang'];

                }

            }

            ?>
            <!-- Start of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
            <script>
                (function(w,d){
                    var e=d.createElement('script');e.type='text/javascript';e.async=true;
                    e.src=(d.location.protocol=='https:'?'https':'http')+'://cdn.clerk.io/clerk.js';
                    var s=d.getElementsByTagName('script')[0];s.parentNode.insertBefore(e,s);
                    w.__clerk_q=w.__clerk_q||[];w.Clerk=w.Clerk||function(){w.__clerk_q.push(arguments)};
                })(window,document);

                Clerk('config', {
                    key: '<?php echo $options['public_key']; ?>',
                    collect_email: <?php echo $options['collect_emails'] ? 'true' : 'false'; ?>,
                    language: '<?php echo $Lang; ?>'
                });
            </script>
            <!-- End of Clerk.io E-commerce Personalisation tool - www.clerk.io -->
            <?php

            if ( isset( $options['livesearch_enabled'] ) && $options['livesearch_enabled'] ) :

                ?>
                <span
                        class="clerk"
                        data-template="@<?php echo esc_attr( strtolower( str_replace( ' ', '-', $options['livesearch_template'] ) ) ); ?>"
                        data-instant-search-suggestions="<?php echo $options['livesearch_suggestions']; ?>"
                        data-instant-search-categories="<?php echo $options['livesearch_categories']; ?>"
                        data-instant-search-pages="<?php echo $options['livesearch_pages']; ?>"
                        data-instant-search-positioning="<?php echo strtolower($options['livesearch_dropdown_position']); ?>"
                        <?php

                        if ( isset( $options['livesearch_pages_type'] ) && $options['livesearch_pages_type'] != 'All') :

                            ?>
                            data-instant-search-pages-type="<?php echo $options['livesearch_pages_type']; ?>"
                        <?php
                        endif;
                        if (isset( $options['livesearch_field_selector'] )) :
                        ?>
                        data-instant-search="<?php echo $options['livesearch_field_selector']; ?>">
                        <?php
                        else:
                            ?>
                            data-instant-search=".search-field">
                        <?php
                        endif;
                        ?>
                </span>
            <?php
            endif;

            if ( isset( $options['search_enabled'] ) && $options['search_enabled'] ) :

                ?>
                <script>
                    jQuery(document).ready(function ($) {
                        ClerkSearchPage = function(){

                            $("<?php echo $options['livesearch_field_selector']; ?>").each(function() {
                                $(this).attr('name', 'searchterm');
                                $(this).attr('value', '<?php echo get_search_query() ?>');
                            });
                            $("<?php echo $options['livesearch_form_selector']; ?>").each(function (){
                                $(this).attr('action', '<?php echo esc_url( get_page_link( $options['search_page'] ) ); ?>');
                            });

                            $('input[name="post_type"][value="product"]').each(function (){
                                $(this).remove();
                            });

                        };

                        ClerkSearchPage();
                    });
                </script>
            <?php
            endif;


        } catch (Exception $e) {

            $this->logger->error('ERROR add_tracking', ['error' => $e->getMessage()]);

        }

    }
}

new Clerk_Visitor_Tracking();
