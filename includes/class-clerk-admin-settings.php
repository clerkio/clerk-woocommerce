<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Clerk_Admin_Settings {
	/**
	 * Clerk_Admin_Settings constructor.
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Add actions
	 */
	private function initHooks() {
		add_action( 'admin_init', [ $this, 'settings_init' ] );
		add_action( 'admin_menu', [ $this, 'clerk_options_page' ] );
	}

	/**
	 * Init settings
	 */
	public function settings_init() {
		// register a new setting
		register_setting( 'clerk', 'clerk_options' );

		//Add general section
		add_settings_section(
			'clerk_section_general',
			__( 'General', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'public_key',
			__( 'Public Key', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_general',
			[
				'label_for' => 'public_key',
			]
		);

		add_settings_field( 'private_key',
			__( 'Private Key', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_general',
			[
				'label_for' => 'private_key',
			]
		);

		add_settings_field( 'import_url',
			__( 'Import URL', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_general',
			[
				'label_for' => 'import_url',
                'description' => 'Use this url to configure an importer from my.clerk.io',
                'readonly' => true,
                'value' => get_site_url(),
			]
		);

        //Add data sync section
        add_settings_section(
            'clerk_section_datasync',
            __( 'Data Sync', 'clerk' ),
            null,
            'clerk' );

        add_settings_field( 'collect_emails',
            __( 'Collect Emails', 'clerk' ),
            [ $this, 'addCheckboxField' ],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'collect_emails',
                'default' => 1
            ]
        );

        add_settings_field( 'additional_fields',
            __( 'Additional Fields', 'clerk' ),
            [ $this, 'addTextField' ],
            'clerk',
            'clerk_section_datasync',
            [
                'label_for' => 'additional_fields',
                'description' => 'A comma separated list of additional fields to sync'
            ]
        );

		//Add search section
		add_settings_section(
			'clerk_section_search',
			__( 'Search Settings', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'search_enabled',
			__( 'Enabled', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_search',
			[
				'label_for' => 'search_enabled',
			]
		);

		add_settings_field( 'search_page',
			__( 'Search Page', 'clerk' ),
			[ $this, 'addPageDropdown' ],
			'clerk',
			'clerk_section_search',
			[
				'label_for' => 'search_page',
			]
		);

		add_settings_field( 'search_template',
			__( 'Template', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_search',
			[
				'label_for' => 'search_template',
			]
		);

		//Add livesearch section
		add_settings_section(
			'clerk_section_livesearch',
			__( 'Live search Settings', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'livesearch_enabled',
			__( 'Enabled', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_livesearch',
			[
				'label_for' => 'livesearch_enabled',
			]
		);

		add_settings_field( 'livesearch_include_categories',
			__( 'Include Categories', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_livesearch',
			[
				'label_for' => 'livesearch_include_categories',
			]
		);

		add_settings_field( 'livesearch_template',
			__( 'Template', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_livesearch',
			[
				'label_for' => 'livesearch_template',
			]
		);

		//Add powerstep section
		add_settings_section(
			'clerk_section_powerstep',
			__( 'Powerstep Settings', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'powerstep_enabled',
			__( 'Enabled', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_powerstep',
			[
				'label_for' => 'powerstep_enabled',
			]
		);

		add_settings_field( 'powerstep_page',
			__( 'Powerstep Page', 'clerk' ),
			[ $this, 'addPageDropdown' ],
			'clerk',
			'clerk_section_powerstep',
			[
				'label_for' => 'powerstep_page',
			]
		);

		add_settings_field( 'powerstep_templates',
			__( 'Templates', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_powerstep',
			[
				'label_for' => 'powerstep_templates',
                'description' => 'A comma separated list of clerk templates to render'
			]
		);
	}

	/**
	 * Add text field
	 *
	 * @param $args
	 */
	public function addTextField( $args ) {
		//Get settings value
		$options = get_option( 'clerk_options' );

		$value = $options[ $args['label_for'] ];

		if ( isset($args['value']) ) {
		    $value = $args['value'];
        }
		?>
        <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="clerk_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
               value="<?php echo $value; ?>"<?php if (isset($args['readonly'])): ?> readonly<?php endif; ?>>
		<?php
        if ( isset($args['description']) ) :
        ?>
            <p class="description" id="<?php echo $args['label_for']; ?>-description"><?php echo $args['description']; ?></p>
        <?php
        endif;
	}

	/**
	 * Add text field
	 *
	 * @param $args
	 */
	public function addCheckboxField( $args ) {
		//Get settings value
		$options = get_option( 'clerk_options' );
		?>
        <input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="clerk_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
               value="1" <?php checked( '1', $options[ $args['label_for'] ] ); ?>>
		<?php
	}

	public function addPageDropdown( $args )
    {
	    //Get settings value
	    $options = get_option( 'clerk_options' );
	    wp_dropdown_pages([
            'selected' => $options[$args['label_for']],
            'name' => sprintf('clerk_options[%s]', $args['label_for'])
        ]);
    }

	public function clerk_options_page() {
		// add top level menu page
		add_menu_page(
			'Clerk',
			'Clerk Options',
			'manage_options',
			'clerk',
			[ $this, 'clerk_options_page_html' ],
            plugin_dir_url(CLERK_PLUGIN_FILE) . 'assets/img/clerk.png'
		);
	}

	public function clerk_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages

		// check if the user have submitted the settings
		// wordpress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'wporg_messages', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'wporg_messages' );
		?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "wporg"
				settings_fields( 'clerk' );
				// output setting sections and their fields
				// (sections are registered for "wporg", each field is registered to a specific section)
				do_settings_sections( 'clerk' );
				// output save settings button
				submit_button( 'Save Settings' );
				?>
            </form>
        </div>
		<?php
	}
}

new Clerk_Admin_Settings();