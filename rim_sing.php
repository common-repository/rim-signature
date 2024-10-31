<?php

/*
  Plugin Name: Rim Signature
  Plugin URI: http://www.rimazrauf.com/
  Description: This plugin allows you to append a signature after every post. You just have to import the edited image to your media library and configure it in the Signature menu, Each author can have their own signature, and it is easy to use. You just need to include a short code in to your post.

Expert developers can place the short code in the template itself.

How to Use:

1. Create a New Post under Rim Slider

2. Add the image links in the fields then publish it

3. Use the short code [rim_sig id='1' /]

* Replace the ID to your post id.

  Version: 1.0
  Author: Rimaz Rauf
  Author URI: http://www.rimazrauf.com/
  License: GPLv2 or later
 */


// Activation and Deactivation Hooks
function rim_signature_activation() {

}

register_activation_hook(__FILE__, 'rim_sing_activation');

function rim_signature_deactivation() {

}

register_deactivation_hook(__FILE__, 'rim_sing_deactivation');


add_action('wp_enqueue_scripts', 'rim_styles');

function rim_styles() {

    wp_register_style('slidesjs_example', plugins_url('css/example.css', __FILE__));
    wp_enqueue_style('slidesjs_example');
}


// Shortcode Scripts

add_shortcode("rim_sig", "rim_display_signature");

function rim_display_signature($attr, $content) {

    extract(shortcode_atts(array(
                'id' => ''
                    ), $attr));

    $gallery_images = get_post_meta($id, "_rim_gallery_images", true);
    $gallery_images = ($gallery_images != '') ? json_decode($gallery_images) : array();



    $plugins_url = plugins_url();


    $html = '<div class="container">
    <div id="slides">';

    foreach ($gallery_images as $gal_img) {
        if ($gal_img != "") {
            $html .= "<img src='" . $gal_img . "' />";
        }
    }
    return $html;
}


// Add the Menue in the Dashboard

add_action('init', 'rimsig_register_signature');

function rimsig_register_signature() {
    $labels = array(
        'menu_name' => _x('Rim Signature', 'rimaz_signature'),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Rim Slideshows',
        'supports' => array('title', 'editor'),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    register_post_type('rimaz_signature', $args);
}

/* Define shortcode column in Rhino signature List View */

add_filter('manage_edit-rimaz_signature_columns', 'rim_set_custom_edit_rimaz_signature_columns');
add_action('manage_rimaz_signature_posts_custom_column', 'rim_custom_rimaz_signature_column', 10, 2);

function rim_set_custom_edit_rimaz_signature_columns($columns) {
    return $columns
    + array('sing_shortcode' => __('Shortcode'));
}

function rim_custom_rimaz_signature_column($column, $post_id) {

    $signature_meta = get_post_meta($post_id, "_rim_signature_meta", true);
    $signature_meta = ($signature_meta != '') ? json_decode($signature_meta) : array();

    switch ($column) {
        case 'sing_shortcode':
            echo "[rim_sig id='$post_id' /]";
            break;
    }
}


// Meta Box to the post

add_action('add_meta_boxes', 'rim_signature_meta_box');

function rim_signature_meta_box() {

    add_meta_box("rim-signature-images", "Signature Images", 'rim_view_signature_images_box', "rimaz_signature", "normal");
}

function rim_view_signature_images_box() {
    global $post;

    $gallery_images = get_post_meta($post->ID, "_rim_gallery_images", true);
    // print_r($gallery_images);exit;
    $gallery_images = ($gallery_images != '') ? json_decode($gallery_images) : array();

    // Use nonce for verification
    $html = '<input type="hidden" name="rim_signature_box_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '" />';

    $html .= '<table class="form-table">';

    $html .= "
          <tr>
            <th style=''><label for='Upload Images'>Image 1</label></th>
            <td><input name='gallery_img[]' id='rim_signature_upload' type='text' value='" . $gallery_images[0] . "'  /></td>
          </tr>  

        </table>";

    echo $html;
}

/* Save signature Options to database */
add_action('save_post', 'rim_save_signature_info');

function rim_save_signature_info($post_id) {


    // verify nonce
    if (!wp_verify_nonce($_POST['rim_signature_box_nonce'], basename(__FILE__))) {
        return $post_id;
    }

    // check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // check permissions
    if ('rimaz_signature' == $_POST['post_type'] && current_user_can('edit_post', $post_id)) {

        /* Save signature Images */
        //echo "<pre>";print_r($_POST['gallery_img']);exit;
        $gallery_images = (isset($_POST['gallery_img']) ? $_POST['gallery_img'] : '');
        $gallery_images = strip_tags(json_encode($gallery_images));
        update_post_meta($post_id, "_rim_gallery_images", $gallery_images);

       
    } else {
        return $post_id;
    }
}
?>