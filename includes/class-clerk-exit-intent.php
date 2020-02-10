<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clerk_Exit_Intent
{
    /**
     * Clerk_Exit_Intent constructor.
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
        add_action('wp_footer', [$this, 'add_exit_intent']);
    }

    /**
     * Include exit intent
     */
    public function add_exit_intent()
    {

        try {

            $options = get_option('clerk_options');

            if (isset($options['exit_intent_enabled']) && $options['exit_intent_enabled']){

                $templates = explode(',',$options['exit_intent_enabled']);

                foreach ($templates as $template) {

                    ?>
                    <span class="clerk"
                          data-template="@<?php echo esc_attr(str_replace(' ','',$template)); ?>"
                          data-exit-intent="true">
                    </span>
                    <?php

                }

            }

        } catch (Exception $e) {

            $this->logger->error('ERROR add_exit_intent', ['error' => $e->getMessage()]);

        }
    }
}

new Clerk_Exit_Intent();