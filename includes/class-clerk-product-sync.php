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
		add_action( 'save_post_product', [ $this, 'save_product' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'remove_product' ] );
	}

	public function save_product( $post_id, $post ) {

        $options = get_option('clerk_options');

        try {

            if (!$options['realtime_updates'] == 1) {
                return;
            }

            if (!$post) {
                return;
            }

            if (!$product = wc_get_product($post)) {
                return;
            }

            if (clerk_check_version()) {
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

                } elseif (!$product->get_status() === 'draft') {
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
            $categories = wp_get_post_terms($product->get_id(), 'product_cat');

              /** @var WC_Product $product */
              $categories = wp_get_post_terms($product->get_id(), 'product_cat');

              $on_sale = $product->is_on_sale();

              if ($product->is_type('variable')) {
                  /**
                   * Variable product sync fields
                   * Will sync the lowest price, and set the sale flag if that variant is on sale.
                   */

                  $variation = $product->get_available_variations();
                  $stock_quantity = 0;
                  $displayPrice = array();
                  $regularPrice = array();
                  foreach ($variation as $v) {
                      $vId = $v['variation_id'];
                      $displayPrice[$vId] = $v['display_price'];
                      $regularPrice[$vId] = $v['display_regular_price'];
                      $variation_obj = new WC_Product_variation($v['variation_id']);
                      $stock_quantity += $variation_obj->get_stock_quantity();
                  }

                  if (empty($displayPrice)) {

                    return;

                  }

                  $lowestDisplayPrice = array_keys($displayPrice, min($displayPrice)); // Find the corresponding product ID
                  $price = $displayPrice[$lowestDisplayPrice[0]]; // Get the lowest price
                  $list_price = $regularPrice[$lowestDisplayPrice[0]]; // Get the corresponding list price (regular price)

                  if ($price === $list_price) $on_sale = false; // Remove the sale flag if the cheapest variant is not on sale

              } else {

                  /**
                   * Default single product sync fields
                   */
                  $price = $product->get_price();
                  $list_price = $product->get_regular_price();
              }

              if ($product->managing_stock() && !isset($options['outofstock_products']) && $product->get_stock_quantity() === 0) {

                  if (isset($stock_quantity) && $stock_quantity === 0) {

                    return;

                  }elseif(!isset($stock_quantity)) {

                    return;

                  }elseif(!$product->is_in_stock()) {

                    return;

                  }
              } elseif (! $product->managing_stock() && ! $product->is_in_stock() && !isset($options['outofstock_products'])) {

                return;

              }

              $productArray = [
                  'id' => $product->get_id(),
                  'name' => $product->get_name(),
                  'description' => get_post_field('post_content', $product->get_id()),
                  'price' => (float)$price,
                  'list_price' => (float)$list_price,
                  'image' => wp_get_attachment_image_src($product->get_image_id(),'medium')[0],
                  'url' => $product->get_permalink(),
                  'categories' => wp_list_pluck($categories, 'term_id'),
                  'sku' => $product->get_sku(),
                  'on_sale' => $on_sale,
                  'type' => $product->get_type(),
                  'created_at' => strtotime($product->get_date_created())
              ];

              $productArray['all_images'] = [];

              foreach (get_intermediate_image_sizes() as $key => $image_size) {

                  if (!in_array(wp_get_attachment_image_src($product->get_image_id(),$image_size)[0], $productArray['all_images'])) {

                      array_push($productArray['all_images'] , wp_get_attachment_image_src($product->get_image_id(), $image_size)[0]);

                  }

              }

              if (!empty($product->get_stock_quantity())) {

                  $productArray['stock'] = $product->get_stock_quantity();

              }elseif (isset($stock_quantity)) {

                  $productArray['stock'] = $stock_quantity;

              }

              //Append additional fields
              foreach ($this->getAdditionalFields() as $field) {

                  if ($field == '') {

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

                      $productArray[$this->clerk_friendly_attributes($field)] = get_post_meta( $product->get_id(), $field, true );

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