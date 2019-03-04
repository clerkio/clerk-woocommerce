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
				'label_for'   => 'import_url',
				'description' => 'Use this url to configure an importer from my.clerk.io',
				'readonly'    => true,
				'value'       => get_site_url(),
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
				'default'   => 1
			]
		);

		add_settings_field( 'additional_fields',
			__( 'Additional Fields', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_datasync',
			[
				'label_for'   => 'additional_fields',
				'description' => 'A comma separated list of additional fields to sync'
			]
		);

		add_settings_field( 'disable_order_synchronization',
			__( 'Disable Order Synchronization', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_datasync',
			[
				'label_for' => 'disable_order_synchronization',
				'default'   => 0
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
			__( 'Content', 'clerk' ),
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
			__( 'Content', 'clerk' ),
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

		add_settings_field( 'powerstep_type',
			__( 'Powerstep Type', 'clerk' ),
			[ $this, 'addPowerstepTypeDropdown' ],
			'clerk',
			'clerk_section_powerstep',
			[
				'label_for' => 'powerstep_type',
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
			__( 'Contents', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_powerstep',
			[
				'label_for'   => 'powerstep_templates',
				'description' => 'A comma separated list of clerk templates to render'
			]
		);

		//Add exit intent section
		add_settings_section(
			'clerk_section_exit_intent',
			__( 'Exit Intent Settings', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'exit_intent_enabled',
			__( 'Enabled', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_exit_intent',
			[
				'label_for' => 'exit_intent_enabled',
			]
		);

		add_settings_field( 'exit_intent_template',
			__( 'Content', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_exit_intent',
			[
				'label_for' => 'exit_intent_template'
			]
		);

		//Add category section
		add_settings_section(
			'clerk_section_category',
			__( 'Category Settings', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'category_enabled',
			__( 'Enabled', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_category',
			[
				'label_for' => 'category_enabled',
			]
		);

		add_settings_field( 'category_content',
			__( 'Content', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_category',
			[
				'label_for' => 'category_content',
			]
		);

		//Add product section
		add_settings_section(
			'clerk_section_product',
			__( 'Product Settings', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'product_enabled',
			__( 'Enabled', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_product',
			[
				'label_for' => 'product_enabled',
			]
		);

		add_settings_field( 'product_content',
			__( 'Content', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_product',
			[
				'label_for' => 'product_content',
			]
		);

		//Add cart section
		add_settings_section(
			'clerk_section_cart',
			__( 'Cart Settings', 'clerk' ),
			null,
			'clerk' );

		add_settings_field( 'cart_enabled',
			__( 'Enabled', 'clerk' ),
			[ $this, 'addCheckboxField' ],
			'clerk',
			'clerk_section_cart',
			[
				'label_for' => 'cart_enabled',
			]
		);

		add_settings_field( 'cart_content',
			__( 'Content', 'clerk' ),
			[ $this, 'addTextField' ],
			'clerk',
			'clerk_section_cart',
			[
				'label_for' => 'cart_content',
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

		if ( isset( $args['value'] ) ) {
			$value = $args['value'];
		}
		?>
        <input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="clerk_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
               value="<?php echo $value; ?>"<?php if ( isset( $args['readonly'] ) ): ?> readonly<?php endif; ?>>
		<?php
		if ( isset( $args['description'] ) ) :
			?>
            <p class="description"
               id="<?php echo $args['label_for']; ?>-description"><?php echo $args['description']; ?></p>
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

	/**
	 * Add page dropdown
	 *
	 * @param $args
	 */
	public function addPageDropdown( $args ) {
		//Get settings value
		$options = get_option( 'clerk_options' );
		wp_dropdown_pages( [
			'selected' => $options[ $args['label_for'] ],
			'name'     => sprintf( 'clerk_options[%s]', $args['label_for'] )
		] );
	}

	/**
	 * Add dropdown for powerstep type
	 *
	 * @param $args
	 */
	public function addPowerstepTypeDropdown( $args ) {
		//Get settings value
		$options = get_option( 'clerk_options' );
		?>
        <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
                name="clerk_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
			<?php foreach ( array( Clerk_Powerstep::TYPE_PAGE, Clerk_Powerstep::TYPE_POPUP ) as $type ) : ?>
                <option value="<?php echo $type; ?>"
				        <?php if ( $options['powerstep_type'] === $type ) : ?>selected<?php endif; ?>><?php echo __( $type, 'clerk' ); ?></option>
			<?php endforeach; ?>
        </select>
		<?php
	}

	/**
	 * Create options page
	 */
	public function clerk_options_page() {
		//Add top level menu page
		add_menu_page(
			__( 'Clerk', 'clerk' ),
			__( 'Clerk', 'clerk' ),
			'manage_options',
			'clerk',
			[ $this, 'clerk_options_page_html' ],
			plugin_dir_url( CLERK_PLUGIN_FILE ) . 'assets/img/clerk.png'
		);

		add_submenu_page( 'clerk', '', __( 'Clerk Settings', 'clerk' ), 'manage_options', 'clerk', [
			$this,
			'clerk_options_page_html'
		] );

		$options = get_option( 'clerk_options' );

		if ( $options && ! empty( $options['public_key'] ) && ! empty( $options['private_key'] ) ) {
			//Add Dashboard menu
			add_submenu_page(
				'clerk',
				__( 'Dashboard', 'clerk' ),
				__( 'Dashboard', 'clerk' ),
				'manage_options',
				'dashboard',
				[ $this, 'clerk_dashboard_page_html' ]
			);

			//Add Search Insights menu
			add_submenu_page(
				'clerk',
				__( 'Search Insights', 'clerk' ),
				__( 'Search Insights', 'clerk' ),
				'manage_options',
				'search-insights',
				[ $this, 'clerk_search_insights_page_html' ]
			);

			//Add Recommendations Insights menu
			add_submenu_page(
				'clerk',
				__( 'Recommendations Insights', 'clerk' ),
				__( 'Recommendations Insights', 'clerk' ),
				'manage_options',
				'recommendations-insights',
				[ $this, 'clerk_recommendations_insights_page_html' ]
			);

			//Add Email Insights menu
			add_submenu_page(
				'clerk',
				__( 'Email Insights', 'clerk' ),
				__( 'Email Insights', 'clerk' ),
				'manage_options',
				'email-insights',
				[ $this, 'clerk_email_insights_page_html' ]
			);

			//Add Audience Insights menu
			add_submenu_page(
				'clerk',
				__( 'Audience Insights', 'clerk' ),
				__( 'Audience Insights', 'clerk' ),
				'manage_options',
				'audience-insights',
				[ $this, 'clerk_audience_insights_page_html' ]
			);
		}
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
			delete_transient( 'clerk_api_contents' );
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

	/**
	 * Render Dashboard page
	 */
	public function clerk_dashboard_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = $this->getEmbedUrl( 'dashboard' );
		?>
        <div class="wrap">
            <iframe id="clerk-embed" src="<?php echo $url; ?>" frameborder="0" width="100%" height="2400"></iframe>
        </div>
		<?php
	}

	/**
	 * Render Search Insights page
	 */
	public function clerk_search_insights_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = $this->getEmbedUrl( 'search' );
		?>
        <div class="wrap">
            <iframe id="clerk-embed" src="<?php echo $url; ?>" frameborder="0" width="100%" height="2400"></iframe>
        </div>
		<?php
	}

	/**
	 * Render Recommendations Insights page
	 */
	public function clerk_recommendations_insights_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = $this->getEmbedUrl( 'recommendations' );
		?>
        <div class="wrap">
            <iframe id="clerk-embed" src="<?php echo $url; ?>" frameborder="0" width="100%" height="2400"></iframe>
        </div>
		<?php
	}

	/**
	 * Render Email Insights page
	 */
	public function clerk_email_insights_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = $this->getEmbedUrl( 'email' );
		?>
        <div class="wrap">
            <iframe id="clerk-embed" src="<?php echo $url; ?>" frameborder="0" width="100%" height="2400"></iframe>
        </div>
		<?php
	}

	/**
	 * Render Audience Insights page
	 */
	public function clerk_audience_insights_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = $this->getEmbedUrl( 'audience' );
		?>
        <div class="wrap">
            <iframe id="clerk-embed" src="<?php echo $url; ?>" frameborder="0" width="100%" height="2400"></iframe>
        </div>
		<?php
	}

	/**
	 * Get first 8 characters of public key
	 *
	 * @param $publicKey
	 *
	 * @return string
	 */
	private function getStorePart( $publicKey ) {
		return substr( $publicKey, 0, 8 );
	}

	/**
	 * Get embed URL for dashboard type
	 *
	 * @param $string
	 */
	private function getEmbedUrl( $type ) {
		$options = get_option( 'clerk_options' );

		$publicKey  = $options['public_key'];
		$privateKey = $options['private_key'];
		$storePart  = $this->getStorePart( $publicKey );

		return sprintf( 'https://my.clerk.io/#/store/%s/analytics/%s?key=%s&private_key=%s&embed=yes', $storePart, $type, $publicKey, $privateKey );
	}
}

new Clerk_Admin_Settings();