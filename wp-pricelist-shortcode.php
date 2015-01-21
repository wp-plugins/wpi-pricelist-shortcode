<?php
/**
 * Plugin Name: WPi Pricelist Shortcode
 * Plugin URI: http://wooprali.prali.in/plugins/wpi-pricelist-shortcode
 * Description: Create pricelist using shortcode in easy way
 * Version: 1.0.0
 * Author: wooprali
 * Author URI: http://wooprali.in
 * Text Domain: wooprali
 * Domain Path: /locale/
 * Network: true
 * License: GPL2
 */
defined('ABSPATH') or die("No script kiddies please!");
if ( !defined('WPIPL_URL' ) ) {
	define( 'WPIPL_URL', plugin_dir_url( __FILE__ ) ); 
}
class WPiPricelistShortcode{
	public function __construct(){	
		add_shortcode("wpi_pricelist", array($this, "wpi_pricelist"));
		add_action("init", array($this,"custom_post_type"));
		add_action("admin_head",array($this,"custom_help_tab"));
		add_action("wp_enqueue_scripts",array($this,'enqueue_styles'));
		add_action("admin_enqueue_scripts",array($this,'enqueue_styles'));
		add_action("add_meta_boxes",array($this, "meta_boxes"));
		add_action("save_post",array($this, "save_meta"));		
		//add_filter( 'is_protected_meta', array($this, 'is_protected_meta'), 10, 2 );
		
	}
	/*public function is_protected_meta( $protected, $meta_key ) {
		if ( 'link' == $meta_key )
			return true;
		return $protected;
	}*/
	public function is_protected_meta( $meta_key, $meta_type = null ) {
		$protected = ( '_' == $meta_key[0] );
		return apply_filters( 'is_protected_meta', $protected, $meta_key, $meta_type );
	}	
	/*public function hide_extra_custom_fields( $protected, $meta_key ) {
		if ( 'wpi_pl_price' == $meta_key ) return;
		return $protected;
	}*/
	public function meta_boxes(){
		add_meta_box("wpi_pl_order", "Package Order", array($this, "package_order_html"), "wpi_pricelist", "normal", "high");
		add_meta_box("wpi_pl_details", "Package Details", array($this, "package_details_html"), "wpi_pricelist", "normal", "high");
	}
	public function package_order_html($post){
		wp_nonce_field("wpi_pl_meta_box","wpi_pl_meta_box_nonce");		
		$order_value=get_post_meta($post->ID,'order',true);		
		echo "Order: <input type='text' id='order'  name='order' value='".esc_attr($order_value)."'/>";
	}
	public function package_details_html($post){
		wp_nonce_field("wpi_pl_meta_box","wpi_pl_meta_box_nonce");
		$price_value=get_post_meta($post->ID,'price',true);	
		$recommend_value=get_post_meta($post->ID,'recommend',true);	
		$link_value=get_post_meta($post->ID,'link',true);		
		echo "<p>Price: <input type='text' id='price'  name='price' value='".esc_attr($price_value)."'/></p>";
		if($recommend_value=="1"){ $checked1='checked="checked"'; $checked2="";}else{  $checked1=""; $checked2='checked="checked"';}		
		echo  '<p>Recommend:  <input type="radio" name="recommend" '.$checked1.' value="1"> Enable <input type="radio" name="recommend"  '.$checked2.' value="0">Disable<p>';
		echo "<p>Link: <input type='text' id='link'  name='link' value='".esc_attr($link_value)."'/><p>";
		
	}
	public function save_meta( $post_id){
		if(!$_POST['wpi_pl_meta_box_nonce']) return;
		if(!wp_verify_nonce($_POST['wpi_pl_meta_box_nonce'],'wpi_pl_meta_box')) return;
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if(!current_user_can("edit_post",$post_id)) return;		
		if(isset($_POST['post_type']) && 'wpi_pricelist'==$_POST['post_type']){
			if(isset($_POST['order'])){ 
				$order_value = sanitize_text_field( $_POST['order'] );
				update_post_meta( $post_id, 'order', $order_value);
			}
			if(isset($_POST['price'])){ 
				$price_value = sanitize_text_field( $_POST['price'] );
				update_post_meta( $post_id, 'price', $price_value);
			}
			if(isset($_POST['link'])){ 
				$link_value = sanitize_text_field( $_POST['link'] );
				update_post_meta( $post_id, 'link', $link_value);
			}
			$recommend_value = sanitize_text_field( $_POST['recommend'] );
			update_post_meta( $post_id, 'recommend', $recommend_value);
			
			
		}
		
	}
	public function wpi_pricelist($attr, $content=""){
		$fields=array();
		$output="<div class='wpi-pricelist'>";
				
		//$v=array(12,34);
		//$output.=print_r($v, true);
		$hide_keys=array("_edit_lock","_edit_last","price","link","order","recommend");
		$args=array("post_type"=>array("wpi_pricelist"),"post_status"=>array("publish"),"orderby"=>"wpi_pl_order",'order' => 'ASC','posts_per_page'=>4);
		$posts=new WP_Query($args);
		if($posts->post_count>0) $columns='wpi-pl-1'.($posts->post_count+1); else $columns='wpi-pl-11';
		if($posts->have_posts()){
			while($posts->have_posts()){
				$posts->the_post();	
				$custom_fields = get_post_custom(get_the_ID());				
				foreach($custom_fields as $key => $val){
					if(!in_array($key,$hide_keys)){						
						$fields[$key]=$key;
					}
				}			
			}
			
			$output.="<div class='wpi-pl-package {$columns}'>";
			$output.="<div class='wpi-pl-row'>&nbsp;</div>";
			$output.="<div class='wpi-pl-row'>Price</div>";			
			foreach($fields as $key){
				$output.="<div class='wpi-pl-row'>".$key."</div>";
			}	
			$output.="</div>";
			wp_reset_postdata();
			
			while($posts->have_posts()){				
				$posts->the_post();	
				$custom_fields = get_post_custom(get_the_ID());	
				if($custom_fields['recommend'][0]=="1"){$recommend="wpi-pl-recommend";}else{$recommend="";}		
				$output.="<div class='wpi-pl-package {$columns} wpi-pl-values {$recommend} {$posts->post_count}'>";
				$output.="<div class='wpi-pl-row wpi-pl-title '>".get_the_title()."</div>";
				$output.="<div class='wpi-pl-row'>".$custom_fields['price'][0]."</div>";							
				foreach($fields as $key){
					$output.="<div class='wpi-pl-row'>".$custom_fields[$key][0]."</div>";
				}
				$output.="<div class='wpi-pl-btn-row'><a class='wpi-pl-btn' href='".home_url($custom_fields['link'][0])."'>Sign Up</a></div>";
				$output.="</div>";
			}
			wp_reset_postdata();
		}
		$output.="</div>";
		return $output;	
	}
	public function enqueue_styles(){			
    	wp_enqueue_style("wpi_pricelist_shortcode", WPIPL_URL ."style.css", array(), NULL, NULL);
		//wp_enqueue_style("wpi_pricelist_shortcode_css", plugins_url("/style.css", __FILE__ ));	
	}
	public function custom_help_tab(){
		$screen=get_current_screen();
		if("wpi_pricelist"!=$screen->post_type){
			return;
		}
		$args=array(
			'id'=>'wpi_pricelist_help_tab',
			'title'=>'Pricelist Help',
			'content'=>'To Create new fileds/properties, find Custom Fields Meta box at the bottom of this page. Create new custom field name and value. Next Click on Update/Publish.<br>You can create maximum of 4 packages. ',
		);
		$screen->add_help_tab($args);
	
	}
	public function custom_post_type(){
		$args=array(
		"label"=>"Price Packages", 
		"labels"=>array(
			'name'=>'Price Package',
			'singular_name'=>'Price Package',
			'menu_name'=>'WPi PriceList', 
			'add_new_item'=>'Add New Price Package',			
			'new_item'=>'New Price Package',
			'edit_item'=>'Edit Price Package',
		),
		'public'=>true,
		'menu_position'=>26,
		'menu_icon'=>"dashicons-chart-bar",	
		);
		register_post_type("wpi_pricelist",$args);
		add_post_type_support("wpi_pricelist", array('custom-fields'));	
		remove_post_type_support("wpi_pricelist", 'editor');
		
	}
}
$wpi_pricelist_shortcode=new WPiPricelistShortcode;
?>