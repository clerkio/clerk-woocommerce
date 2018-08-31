<?php

class Clerk_Widget_Content extends WP_Widget {
	/**
	 * @var Clerk_Api
	 */
	protected $api;

	/**
	 * Clerk_Widget_Content constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'clerk widget_content',
			'description'                 => __( 'Clerk content widget', 'clerk' ),
			'customize_selective_refresh' => true,
		);
		$this->api  = new Clerk_Api();

		parent::__construct( 'clerk_content', __( 'Clerk Content', 'clerk' ), $widget_ops );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_clerk_get_parameters_for_content', [ $this, 'get_parameters_for_content' ] );
	}

	/**
	 * Render clerk content
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$spanAttributes = array();

		if ( is_product_category() && $instance['category'] == 0 ) {
			$spanAttributes['data-category'] = get_queried_object()->term_id;
		}

		if ( $instance['category'] != 0 ) {
			$spanAttributes['data-category'] = $instance['category'];
		}

		if ( is_product() && $instance['product'] == 0 ) {
			$spanAttributes['data-products'] = '[' . get_queried_object()->ID . ']';
		}

		if ( $instance['product'] != 0 ) {
			$spanAttributes['data-products'] = '[' . $instance['product'] . ']';
		}

		echo $args['before_widget'];

		printf( '<span class="clerk" data-template="@%s" %s></span>', $instance['content'], $this->parseSpanAttributes( $spanAttributes ) );

		echo $args['after_widget'];
	}

	/**
	 * Outputs the widget settings form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'content'  => '',
				'category' => 0,
				'product'  => 0,
			)
		);

		$contents = $this->api->getContent();

		if ( $contents->status === 'ok' ) {
			?>
            <p>
                <label for="<?php echo $this->get_field_id( 'content' ); ?>"><?php echo __( 'Content', 'clerk' ); ?></label>
                <select name="<?php echo $this->get_field_name( 'content' ); ?>"
                        id="<?php echo $this->get_field_id( 'content' ); ?>" onchange="clerkGetContent(this)">
                    <option value=""><?php _e( 'Select Content', 'clerk' ); ?></option>
					<?php foreach ( $contents->contents as $content ) : ?>
						<?php if ( $content->type !== 'html' ) {
							continue;
						} ?>
                        <option value="<?php echo esc_attr( $content->id ); ?>"
						        <?php if ( $instance['content'] === $content->id ) : ?>selected<?php endif; ?>><?php echo $content->name; ?></option>
					<?php endforeach; ?>
                </select>
            </p>
            <p <?php if ( $instance['category'] == 0 ) : ?>style="display:none;"<?php endif; ?> data-clerk-category>
                <label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php echo __( 'Category', 'clerk' ); ?></label>
				<?php
				echo wc_product_dropdown_categories( array(
					'name'        => $this->get_field_name( 'category' ),
					'id'          => $this->get_field_id( 'category' ),
					'show_count'  => false,
					'value_field' => 'id',
					'selected'    => isset( $instance['category'] ) ? $instance['category'] : false
				) );
				?>
            </p>
            <p <?php if ( $instance['product'] == 0 ) : ?>style="display:none;"<?php endif; ?> data-clerk-product>
                <label for="<?php echo $this->get_field_id( 'product' ); ?>"><?php echo __( 'Product', 'clerk' ); ?></label>
				<?php echo $this->getProductDropdown( $instance ); ?>
            </p>
			<?php
		} else {
			?>
            <p>
				<?php echo __( 'Failed to load content, please ensure that your api keys are correct.', 'clerk' ); ?>
            </p>
			<?php
		}
	}

	/**
	 * Enqueue admin javascript
	 *
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {
		if ( $hook !== 'widgets.php' ) {
			return;
		}

		wp_register_script( 'clerk_admin_widget', plugins_url( '../../assets/js/admin/widget.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'clerk_admin_widget' );
	}

	/**
	 * Parse span attributes to attribute string
	 *
	 * @param $spanAttributes
	 *
	 * @return string
	 */
	private function parseSpanAttributes( $spanAttributes ) {
		$output = '';

		foreach ( $spanAttributes as $attribute => $value ) {
			$output .= ' ' . $attribute . '=\'' . esc_attr( $value ) . '\'';
		}

		return $output;
	}

	/**
	 * Get parameters for content endpoint
	 */
	public function get_parameters_for_content() {
		$contentParam = $_POST['content'];

		$contents = $this->api->getContent();

		$response = array();

		if ( $contents->status === 'ok' ) {
			foreach ( $contents->contents as $content ) {
				if ( $content->id === $contentParam ) {
					$parameters = $this->getParametersForEndpoint( $content->api );

					if ( in_array( 'category', $parameters ) ) {
						$response['category'] = true;
					}

					if ( in_array( 'products', $parameters ) ) {
						$response['product'] = true;
					}

					echo json_encode( $response );
					wp_die();
				}
			}
		}

		wp_die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Get parameters for API endpoint
	 *
	 * @param $endpoint
	 *
	 * @return bool|array
	 */
	public function getParametersForEndpoint( $endpoint ) {
		$endpointMap = [
			'search/search'                          => [
				'query',
				'limit'
			],
			'search/predictive'                      => [
				'query',
				'limit'
			],
			'search/categories'                      => [
				'query',
				'limit'
			],
			'search/suggestions'                     => [
				'query',
				'limit'
			],
			'search/popular'                         => [
				'query',
				'limit'
			],
			'recommendations/popular'                => [
				'limit'
			],
			'recommendations/trending'               => [
				'limit'
			],
			'recommendations/currently_watched'      => [
				'limit'
			],
			'recommendations/popular'                => [
				'limit'
			],
			'recommendations/keywords'               => [
				'limit',
				'keywords'
			],
			'recommendations/complementary'          => [
				'limit',
				'products'
			],
			'recommendations/substituting'           => [
				'limit',
				'products'
			],
			'recommendations/category/popular'       => [
				'limit',
				'category'
			],
			'recommendations/category/trending'      => [
				'limit',
				'category'
			],
			'recommendations/visitor/history'        => [
				'limit',
			],
			'recommendations/visitor/complementary'  => [
				'limit',
			],
			'recommendations/visitor/substituting'   => [
				'limit',
			],
			'recommendations/customer/history'       => [
				'limit',
				'email'
			],
			'recommendations/customer/complementary' => [
				'limit',
				'email'
			],
			'recommendations/customer/substituting'  => [
				'limit',
				'email'
			],
		];

		if ( array_key_exists( $endpoint, $endpointMap ) ) {
			return $endpointMap[ $endpoint ];
		}

		return false;
	}

	/**
	 * Get dropdown of products
	 *
	 * @param $instance
	 *
	 * @return string
	 */
	public function getProductDropdown( $instance ) {
		$html = '<select name="' . $this->get_field_name( 'product' ) . '" id="' . $this->get_field_id( 'content' ) . '">';

		$html .= '<option value="0">' . __( 'Select Product', 'clerk' ) . '</option>';

		$products = clerk_get_products( array(
			'status' => array( 'publish' ),
		) );

		foreach ( $products as $product ) {
			$selected = ( $instance['product'] == $product->get_id() ) ? 'selected' : '';
			$html     .= '<option value="' . esc_attr( $product->get_id() ) . '"' . $selected . '>' . $product->get_title() . '</option>';
		}

		$html .= '</select>';

		return $html;
	}
}