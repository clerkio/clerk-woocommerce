<?php
/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.3
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 */

/**
 * Clerk_Widget_Content Class
 *
 * Clerk Module Core Class
 */
class Clerk_Widget_Content extends WP_Widget {

	/**
	 * Clerk Api Interface
	 *
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_clerk_get_parameters_for_content', array( $this, 'get_parameters_for_content' ) );
	}

	/**
	 * Render clerk content
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {

		$span_attributes = array();

		if ( is_product_category() && 0 === (int) $instance['category'] ) {
			$span_attributes['data-category'] = get_queried_object()->term_id;
		}

		if ( 0 !== (int) $instance['category'] ) {
			$span_attributes['data-category'] = $instance['category'];
		}

		if ( is_product() && 0 === $instance['product'] ) {
			$span_attributes['data-products'] = '[' . get_queried_object()->ID . ']';
		}

		if ( 0 !== $instance['product'] ) {
			$span_attributes['data-products'] = '[' . $instance['product'] . ']';
		}

		echo wp_kses_post( $args['before_widget'] );

		echo '<span class="clerk" data-template="@' . wp_kses_post( $instance['content'] ) . '" ' . wp_kses_post( $this->parseSpanAttributes( $span_attributes ) ) . '></span>';

		echo wp_kses_post( $args['after_widget'] );
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

		$contents = $this->api->get_content();

		if ( 'ok' === $contents->status ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>"><?php echo esc_html__( 'Content', 'clerk' ); ?></label>
				<select name="<?php echo esc_attr( $this->get_field_name( 'content' ) ); ?>"
						id="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" onchange="clerkGetContent(this)">
					<option value=""><?php esc_attr_e( 'Select Content', 'clerk' ); ?></option>
			<?php foreach ( $contents->contents as $content ) : ?>
				<?php
				if ( 'html' !== $content->type ) {
					continue;
				}
				?>
						<option value="<?php echo esc_attr( $content->id ); ?>"
				<?php
				if ( $instance['content'] === $content->id ) :
					?>
					selected
					<?php
				endif;
				?>
				><?php echo esc_attr( $content->name ); ?></option>
			<?php endforeach; ?>
				</select>
			</p>
			<p
			<?php
			if ( 0 === (int) $instance['category'] ) :
				?>
				style="display:none;"
				<?php
			endif;
			?>
			data-clerk-category>
				<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php echo esc_html__( 'Category', 'clerk' ); ?></label>
			<?php
			echo wp_kses_post(
				wc_product_dropdown_categories(
					array(
						'name'        => $this->get_field_name( 'category' ),
						'id'          => $this->get_field_id( 'category' ),
						'show_count'  => false,
						'value_field' => 'id',
						'selected'    => isset( $instance['category'] ) ? $instance['category'] : false,
					)
				)
			);
			?>
			</p>
			<p
			<?php
			if ( 0 === (int) $instance['product'] ) :
				?>
				style="display:none;"
				<?php
			endif;
			?>
			data-clerk-product>
				<label for="<?php echo esc_attr( $this->get_field_id( 'product' ) ); ?>"><?php echo esc_html__( 'Product', 'clerk' ); ?></label>
			<?php echo wp_kses_post( $this->get_product_dropdown( $instance ) ); ?>
			</p>
			<?php
		} else {
			?>
			<p>
			<?php echo esc_html__( 'Failed to load content, please ensure that your api keys are correct.', 'clerk' ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Enqueue admin javascript
	 *
	 * @param string $hook Hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'widgets.php' !== $hook ) {
			return;
		}

		wp_register_script( 'clerk_admin_widget', plugins_url( '../../assets/js/admin/widget.js', __FILE__ ), array( 'jquery' ), get_bloginfo( 'version' ), true );
		wp_enqueue_script( 'clerk_admin_widget' );
	}

	/**
	 * Parse span attributes to attribute string
	 *
	 * @param array $span_attributes Span attributes.
	 *
	 * @return string
	 */
	private function parseSpanAttributes( $span_attributes ) {
		$output = '';

		foreach ( $span_attributes as $attribute => $value ) {
			$output .= ' ' . $attribute . '=\'' . esc_attr( $value ) . '\'';
		}

		return $output;
	}

	/**
	 * Get parameters for content endpoint
	 */
	public function get_parameters_for_content() {
		$content_param = ( null !== filter_input( INPUT_POST, 'content' ) ) ? filter_input( INPUT_POST, 'content' ) : '';

		$contents = $this->api->get_content();

		$response = array();

		if ( 'ok' === $contents->status ) {
			foreach ( $contents->contents as $content ) {
				if ( $content->id === $content_param ) {
					$parameters = $this->get_parameters_for_endpoint( $content->api );

					if ( in_array( 'category', $parameters, true ) ) {
						$response['category'] = true;
					}

					if ( in_array( 'products', $parameters, true ) ) {
						$response['product'] = true;
					}

					echo wp_json_encode( $response );
					wp_die();
				}
			}
		}

		wp_die(); // this is required to terminate immediately and return a proper response.
	}

	/**
	 * Get parameters for API endpoint
	 *
	 * @param string $endpoint Endpoint.
	 *
	 * @return bool|array
	 */
	public function get_parameters_for_endpoint( $endpoint ) {
		$endpoint_map = array(
			'search/search'                          => array(
				'query',
				'limit',
			),
			'search/predictive'                      => array(
				'query',
				'limit',
			),
			'search/categories'                      => array(
				'query',
				'limit',
			),
			'search/suggestions'                     => array(
				'query',
				'limit',
			),
			'search/popular'                         => array(
				'query',
				'limit',
			),
			'recommendations/popular'                => array(
				'limit',
			),
			'recommendations/trending'               => array(
				'limit',
			),
			'recommendations/currently_watched'      => array(
				'limit',
			),
			'recommendations/popular'                => array(
				'limit',
			),
			'recommendations/keywords'               => array(
				'limit',
				'keywords',
			),
			'recommendations/complementary'          => array(
				'limit',
				'products',
			),
			'recommendations/substituting'           => array(
				'limit',
				'products',
			),
			'recommendations/category/popular'       => array(
				'limit',
				'category',
			),
			'recommendations/category/trending'      => array(
				'limit',
				'category',
			),
			'recommendations/visitor/history'        => array(
				'limit',
			),
			'recommendations/visitor/complementary'  => array(
				'limit',
			),
			'recommendations/visitor/substituting'   => array(
				'limit',
			),
			'recommendations/customer/history'       => array(
				'limit',
				'email',
			),
			'recommendations/customer/complementary' => array(
				'limit',
				'email',
			),
			'recommendations/customer/substituting'  => array(
				'limit',
				'email',
			),
		);

		if ( array_key_exists( $endpoint, $endpoint_map ) ) {
			return $endpoint_map[ $endpoint ];
		}

		return false;
	}

	/**
	 * Get dropdown of products
	 *
	 * @param array $instance Request Instance.
	 *
	 * @return string
	 */
	public function get_product_dropdown( $instance ) {
		$html = '<select name="' . $this->get_field_name( 'product' ) . '" id="' . $this->get_field_id( 'content' ) . '">';

		$html .= '<option value="0">' . esc_html__( 'Select Product', 'clerk' ) . '</option>';

		$products = clerk_get_products(
			array(
				'status' => array( 'publish' ),
			)
		);

		foreach ( $products as $product ) {
			$selected = ( (string) $instance['product'] === (string) $product->get_id() ) ? 'selected' : '';
			$html    .= '<option value="' . esc_attr( $product->get_id() ) . '"' . $selected . '>' . $product->get_title() . '</option>';
		}

		$html .= '</select>';

		return $html;
	}
}
