<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Product_Sync {
	/** @var Clerk_Api */
	protected $api;
    protected $logger;

	public function __construct() {
		$this->includes();
		$this->initHooks();
        $this->logger = new ClerkLogger();
		$this->api = new Clerk_Api();
	}

	private function includes() {
		require_once( __DIR__ . '/class-clerk-api.php' );
		require_once( __DIR__ . '/class-clerk-logger.php' );
	}

	private function initHooks() {
		add_action( 'save_post', [ $this, 'save_product' ], 10, 3 );
        add_action( 'woocommerce_new_product', [ $this, 'save_product' ], 10, 3 );
        add_action( 'woocommerce_product_import_inserted_product_object', [ $this, 'save_product' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'remove_product' ] );
	}

	public function save_product( $post_id, $post, $update ) {

        $options = get_option('clerk_options');

        try {

            // Handling for products created through wordpress import feature
            $object_type = is_int($post_id);
            if (!$object_type){
                $product = $post_id;
            }

            if (!array_key_exists('realtime_updates', $options)) {
                return;
            }

            if (!$post) {
                return;
            }

            if (!$product = wc_get_product($post)) {
                return;
            }

            if (clerk_check_version()) {
		    
		//Don't send variations when parent is not published
                if ($product->is_type('variation')) {
                    $parent = wc_get_product($product->get_parent_id());

                    if (!$parent) {
                        return;
                    }

                    if ($parent->get_status() !== 'publish') {
                        $this->remove_product($product->get_id());
                        return;
                    }
                }
		    
                if ($product->get_status() === 'publish') {
                    //Send product to Clerk
                    $this->add_product($product);

                    //check all groups for this product *sigh*
                    $grouped_products = wc_get_products( array( 'limit' => -1, 'type' => 'grouped' ) );
                    foreach($grouped_products as $grouped_product){
                        $childrenids = $grouped_product->get_children();
                        foreach($childrenids as $childid){
                            if($product->get_id() === $childid){
                                $this->add_product($grouped_product);
                            }
                        }

                    }

                } else {
                    //Remove product
                    $this->remove_product($product->get_id());
                }
            } else {
                //Fix for WooCommerce 2.6
                if ($product->post->status === 'publish') {
                    //Send product to Clerk
                    $this->add_product($product);

                    //check all groups for this product *sigh*
                    $grouped_products = wc_get_products( array( 'limit' => -1, 'type' => 'grouped' ) );
                    foreach($grouped_products as $grouped_product){
                        $childrenids = $grouped_product->get_children();
                        foreach($childrenids as $childid){
                            if($product->get_id() === $childid){
                                $this->add_product($grouped_product);
                            }
                        }

                    }

                } elseif (!$product->post->status === 'draft') {
                    //Remove product
                    $this->remove_product($product->get_id());
                }
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR save_product', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Remove product from Clerk
	 *
	 * @param $post_id
	 */
	public function remove_product( $post_id ) {

        try {
            $options = get_option('clerk_options');
            if (!$options['realtime_updates'] == 1) {
                return;
            }
            //Remove product from Clerk
            $this->api->removeProduct($post_id);

        } catch (Exception $e) {

            $this->logger->error('ERROR remove_product', ['error' => $e->getMessage()]);

        }
	}

	/**
	 * Add product in Clerk
	 *
	 * @param WC_Product $product
	 */
	private function add_product( WC_Product $product ) {

        $productArray = [];

        try {
            $options = get_option('clerk_options');
            if (!$options['realtime_updates'] == 1) {
                return;
            }

              /** @var WC_Product $product */
              $categories = wp_get_post_terms($product->get_id(), 'product_cat');

              $on_sale = $product->is_on_sale();

              if ($product->is_type('variable')) {
                  /**
                   * Variable product sync fields
                   * Will sync the lowest price, and set the sale flag if that variant is on sale.
                   */
                  $productArray['variant_images'] = array();
                  $productArray['variant_prices'] = array();
                  $productArray['variant_list_prices'] = array();
                  $productArray['variant_skus'] = array();
                  $productArray['variant_ids'] = array();
                  $productArray['variant_options'] = array();
                  $productArray['variant_stocks'] = array();
                  $variation = $product->get_available_variations();
                  $stock_quantity = 0;
                  $displayPrice = array();
                  $regularPrice = array();
                  foreach ($variation as $v) {
                        $variant_id = $variation['variation_id'];
                        $is_available = false;
                        if(array_key_exists('is_in_stock', $variation) && array_key_exists('is_purchasable', $variation) && array_key_exists('backorders_allowed', $variation)){
                            $is_available = ($variation['is_in_stock'] && $variation['is_purchasable']) || ($variation['backorders_allowed'] && $variation['is_purchasable']) ? true : false;
                        }

                        if(!isset($options['outofstock_products'])){
                            if(!$is_available){
                                continue;
                            }
                        }

                        $variation_obj = new WC_Product_variation($variation['variation_id']);
                        $stock_quantity += $variation_obj->get_stock_quantity();

                        $options_array = array_values($variation['attributes']);
                        $options_array = array_filter($options_array, function($var){
                            return (gettype($var) != 'boolean' && $var != NULL && $var != '' && $var != 'Yes' && $var != 'No');
                        });
                        $options_string = implode(' ', $options_array);

                        $productArray['variant_options'][] = $options_string;
                        $productArray['variant_images'][] = $variation['image']['url'];
                        $productArray['variant_skus'][] = $variation['sku'];
                        $productArray['variant_ids'][] = $variation['variation_id'];
                        $productArray['variant_stocks'][] = ($variation_obj->get_stock_quantity() != null) ? $variation_obj->get_stock_quantity() : 0;
                        $productArray['variant_prices'][] = $variation['display_price'];
                        $productArray['variant_list_prices'][] = $variation['display_regular_price'];

                        $displayPrice[$variant_id] = $variation['display_price'];
                        $regularPrice[$variant_id] = $variation['display_regular_price'];
                  }

                if(!empty($displayPrice)){
                    $lowestDisplayPrice = array_keys($displayPrice, min($displayPrice)); // Find the corresponding product ID
                    $price = $displayPrice[$lowestDisplayPrice[0]]; // Get the lowest price
                    $list_price = $regularPrice[$lowestDisplayPrice[0]]; // Get the corresponding list price (regular price)
                }
                  $price = ($price > 0) ? $price : $product->get_price();
                  $list_price = ($list_price > 0) ? $list_price : $product->get_regular_price();

                  if ($price === $list_price) {
                    $on_sale = false; // Remove the sale flag if the cheapest variant is not on sale
                  }
              }
              if ($product->is_type('simple') || $product->is_type('grouped')) {
                  /**
                   * Default single product sync fields
                   */
                  $price = $product->get_price();
                  $list_price = $product->get_regular_price();
                  $stock_quantity = $product->get_stock_quantity();
              }

              if ($product->is_type('bundle')) {
                $bundled_product = new WC_Product_Bundle($product->get_id());
                $bundled_items = $bundled_product->get_bundled_items();
                $stock_quantity = $product->get_stock_quantity();
                if($price == 0 || $list_price == 0){
                    $price = 0;
                    $list_price = 0;
                    foreach ($bundled_items as $item) {
                        $price += $item->get_price();
                        $price += $item->get_regular_price();
                    }
                }
              }

              if (!isset($options['outofstock_products'])) {
                if($product->get_stock_status() !== 'instock'){
                    return;
                }
              }
              $image_size_setting = isset($options['data_sync_image_size']) ? $options['data_sync_image_size'] : 'medium';
              $productArray['id'] = $product->get_id();
              $productArray['name'] = $product->get_name();
              $productArray['description'] = get_post_field('post_content', $product->get_id());
              $productArray['price'] = (float)$price;
              $productArray['list_price'] = (float)$list_price;
              $productArray['image'] = wp_get_attachment_image_src($product->get_image_id(), $image_size_setting)[0];
              $productArray['url'] = $product->get_permalink();
              $productArray['categories'] = wp_list_pluck($categories, 'term_id');
              $productArray['sku'] = $product->get_sku();
              $productArray['on_sale'] = $on_sale;
              $productArray['type'] = $product->get_type();
              $productArray['created_at'] = strtotime($product->get_date_created());
              $productArray['all_images'] = [];
              $productArray['stock'] = ($stock_quantity != null) ? $stock_quantity: 1;
              $productArray['managing_stock'] = $product->managing_stock();
              $productArray['backorders'] = $product->get_backorders();
              $productArray['stock_status'] = $product->get_stock_status();

              if (!empty($product->get_stock_quantity())) {

                  $productArray['stock'] = ($product->get_stock_quantity() != null) ? $product->get_stock_quantity() : 1;

              }elseif (isset($stock_quantity)) {

                  $productArray['stock'] = $stock_quantity;

              }

              //Append additional fields
              foreach ($this->getAdditionalFields() as $field) {

                if ($field == '') {
                    continue;
                }

                if($field == 'all_images'){
                    foreach (get_intermediate_image_sizes() as $key => $image_size) {
                        if (!in_array(wp_get_attachment_image_src($product->get_image_id(),$image_size)[0], $productArray['all_images'])) {
                            array_push($productArray['all_images'] , wp_get_attachment_image_src($product->get_image_id(), $image_size)[0]);
                        }
                    }
                    continue;
                }

                  if ($product->get_attribute($field) || isset($product->$field)) {

                    if(!isset( $productArray[$this->clerk_friendly_attributes($field)])){
                        $productArray[$this->clerk_friendly_attributes($field)] = str_replace(' ','',explode(',',$product->get_attribute($field)));
                    }

                       // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields

                       if ($product->is_type('variable')) {
                          $variations = $product->get_available_variations();
                          $child_atributes = array();

                          foreach ($variations as $v) {
                                $collectinfo = "";
                                $variation_obj = new WC_Product_variation($v['variation_id']);
                                $atribute = str_replace(' ','',explode(',',$variation_obj->get_attribute($field)));

                                if(is_array($atribute)){
                                    $collectinfo = $atribute[0];
                                }else{
                                    $collectinfo = $atribute;
                                }

                                if($collectinfo == '' && isset($variation_obj->get_data()[$field])){
                                    $collectinfo = $variation_obj->get_data()[$field];
                                }

                                $child_atributes[] = $collectinfo;


                          }

                          $productArray['child_'. $this->clerk_friendly_attributes($field) .'s'] = $child_atributes;
                      }

                      if ($product->is_type('grouped')) {
                          $childproductids = $product->get_children();
                          $child_atributes = array();

                          foreach ($childproductids as $childID) {
                            $collectinfo = "";
                              $childproduct = wc_get_product($childID);

                              $atribute = str_replace(' ','',explode(',',$childproduct->get_attribute($field)));

                              if(is_array($atribute)){
                                $collectinfo = $atribute[0];
                              }else{
                                $collectinfo = $atribute;
                              }

                              if($collectinfo == '' && isset($childproduct->$field)){
                                $collectinfo = $childproduct->$field;
                            }

                            $child_atributes[] = $collectinfo;
                          }

                          $productArray['child_'. $this->clerk_friendly_attributes($field) .'s'] = $child_atributes;
                      }

                      // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields

                  }elseif (get_post_meta( $product->get_id(), $field, true )) {

                      $productArray[str_replace('-','_',$this->clerk_friendly_attributes($field))] = get_post_meta( $product->get_id(), $field, true );

                       // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields

                        if ($product->is_type('variable')) {
                            $variation = $product->get_available_variations();
                            $child_atributes = array();
                            foreach ($variation as $v) {
                                $collectinfo = "";
                                $variation_obj = new WC_Product_variation($v['variation_id']);
                                $atribute = get_post_meta( $variation_obj->get_id(), $field, true );

                                if(is_array($atribute)){
                                    $collectinfo = $atribute[0];
                                }else{
                                    $collectinfo = $atribute;
                                }

                                if($collectinfo == '' && isset($variation_obj->get_data()[$field])){
                                    $collectinfo = $variation_obj->get_data()[$field];
                                }

                                $child_atributes[] = $collectinfo;

                            }

                            $productArray['child_'. $this->clerk_friendly_attributes($field) .'s'] = $child_atributes;
                        }

                      if ($product->is_type('grouped')) {
                          $childproductids = $product->get_children();
                          $child_atributes = array();

                          foreach ($childproductids as $childID) {
                            $collectinfo = "";
                              $childproduct = wc_get_product($childID);

                              $atribute = get_post_meta( $childproduct->get_id(), $field, true );

                              if(is_array($atribute)){
                                $collectinfo = $atribute[0];
                              }else{
                                $collectinfo = $atribute;
                              }

                              if($collectinfo == '' && isset($childproduct->$field)){
                                $collectinfo = $childproduct->$field;
                            }

                            $child_atributes[] = $collectinfo;
                          }

                          $productArray['child_'. $this->clerk_friendly_attributes($field) .'s'] = $child_atributes;
                      }

                      // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields

                  }elseif (wp_get_post_terms( $product->get_id(), strtolower($field), array('fields'=> 'names'))) {

                      $attrubutefield = wp_get_post_terms( $product->get_id(), strtolower($field), array('fields'=> 'names'));

                      if (!property_exists($attrubutefield, 'errors')) {

                          $productArray[strtolower($this->clerk_friendly_attributes($field))] = $attrubutefield;

                          // 21-10-2021 KKY - Additional Fields for Configurable and Grouped Products - additional fields

                          if ($product->is_type('variable')) {
                              $variation = $product->get_available_variations();
                              $child_atributes = array();

                              foreach ($variation as $v) {
                                $collectinfo = "";
                                  $variation_obj = new WC_Product_variation($v['variation_id']);

                                  $attrubutefield = wp_get_post_terms( $variation_obj->get_id(), strtolower($field), array('fields'=> 'names'));

                                  if (!property_exists($attrubutefield, 'errors')) {

                                      $atribute = $attrubutefield;

                                      if(is_array($atribute)){
                                        $collectinfo = $atribute[0];
                                      }else{
                                        $collectinfo = $atribute;
                                      }

                                      if($collectinfo == '' && isset($variation_obj->get_data()[$field])){
                                          $collectinfo = $variation_obj->get_data()[$field];
                                      }

                                    $child_atributes[] = $collectinfo;

                                  }
                              }

                              $productArray['child_'. strtolower($this->clerk_friendly_attributes($field)) .'s'] = $child_atributes;
                          }

                          if ($product->is_type('grouped')) {
                              $childproductids = $product->get_children();
                              $child_atributes = array();

                              foreach ($childproductids as $childID) {
                                $collectinfo = "";
                                  $childproduct = wc_get_product($childID);

                                  $attrubutefield = wp_get_post_terms( $childproduct->get_id(), strtolower($field), array('fields'=> 'names'));

                                  if(is_array($atribute)){
                                    $collectinfo = $atribute[0];
                                  }else{
                                    $collectinfo = $atribute;
                                  }

                                  if($collectinfo == '' && isset($childproduct->$field)){
                                    $collectinfo = $childproduct->$field;
                                }

                                $child_atributes[] = $collectinfo;
                              }

                              $productArray['child_'. strtolower($this->clerk_friendly_attributes($field)) .'s'] = $child_atributes;
                          }

                      }

                  }

              }

           // 22-10-2021 KKY

           $params = "";

            $params = apply_filters('clerk_product_sync_array', $productArray, $product);
            $this->api->addProduct($params);

        } catch (Exception $e) {

            $this->logger->error('ERROR add_product', ['error' => $e->getMessage()]);

        }

	}

    function clerk_friendly_attributes($attribute) {
        $attribute = strtolower($attribute);
        $attribute=str_replace('æ','ae',$attribute);
        $attribute=str_replace('ø','oe',$attribute);
        $attribute=str_replace('å','aa',$attribute);
        return urlencode($attribute);
    }

	/**
	 * Get additional fields for product export
	 *
	 * @return array
	 */
	private function getAdditionalFields() {

        try {

            $options = get_option('clerk_options');

            $additional_fields = $options['additional_fields'];

            $fields = explode(',', $additional_fields);

            return $fields;

        } catch (Exception $e) {

            $this->logger->error('ERROR getAdditionalFields', ['error' => $e->getMessage()]);

        }

	}
}

new Clerk_Product_Sync();
