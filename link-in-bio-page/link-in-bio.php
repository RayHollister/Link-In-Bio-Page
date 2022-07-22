<?php

/*
* Plugin Name:       Link In Bio
* Plugin URI:        https://rayhollister.com
* Description:       Easily create an Instagram "Link in Bio" page in Wordpress
* Version:           0.2
* Author:            Ray Hollister
* Author URI:        https://rayhollister.com
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       link-in-bio-page
*/

// First, let's hide the Links Manager from the admin menu (because that's just confusing!)
function hide_links_menu()
{
    remove_menu_page('link-manager.php');
}
add_action('admin_menu', 'hide_links_menu');

// create a custom post type called "link_in_bio"
function create_post_type_link_in_bio()
{
    register_post_type(
        'link_in_bio',
        array(
            'labels' => array(
                'name' => __('Link in Bio'),
                'singular_name' => __('Link in Bio'),
                'add_new' => __('Add New'),
                'add_new_item' => ('Add New'),
                'new_item'           => __('New Link in Bio'),
                'edit_item'          => __('Edit Link in Bio'),
                'view_item'          => __('View Link in Bio'),
                'all_items'          => __('All Links in Bio'),
                'search_items'       => __('Search Links in Bio'),
                'not_found'          => __('No Links in Bio found.'),
                'not_found_in_trash' => __('No Links in Bio found in Trash.')
            ),
            'public' => true,
            'rewrite' => array('slug' => 'link_in_bio'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'taxonomies' => array('channel'),
            'menu_icon' => 'dashicons-admin-links',
            'menu_position' => 5,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'has_archive' => false,
            'show_in_rest' => true,
            'rest_base' => 'link_in_bio',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        )
    );
}
add_action('init', 'create_post_type_link_in_bio');

// give the custom post type a custom taxonomy called "channel" and plural "channels" that is hierarchical
function create_taxonomy_channel()
{
    register_taxonomy('channel', 'link_in_bio', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => __('Channel'),
            'singular_name' => __('Channel'),
            'add_new_item' => __('Add New Channel'),
            'new_item_name' => __('New Channel Name'),
            'all_items' => __('All Channels'),
            'edit_item' => __('Edit Channel'),
            'update_item' => __('Update Channel'),
            'add_or_remove_items' => __('Add or Remove Channels'),
            'choose_from_most_used' => __('Choose from the most used channels'),
            'menu_name' => __('Channels'),
        ),
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'channel'),
        'show_in_rest' => true,
        'rest_base' => 'channel',
        'rest_controller_class' => 'WP_REST_Terms_Controller',
    ));
}
add_action('init', 'create_taxonomy_channel');

// add the link in bio url to the link_in_bio post type admin menu column

add_filter('manage_link_in_bio_posts_columns', 'set_custom_edit_link_in_bio_columns');
function set_custom_edit_link_in_bio_columns($columns)
{
    $columns['link_in_bio_url'] = 'Link in Bio URL';
    $columns['photo'] = __('Featured Image');
    return $columns;
}

add_action('manage_link_in_bio_posts_custom_column', 'custom_link_in_bio_column', 10, 2);

function custom_link_in_bio_column($column, $post_id)
{
    switch ($column) {
            // display a thumbnail photo
        case 'photo':
            echo get_the_post_thumbnail($post_id, 'thumbnail');
            break;
        case 'link_in_bio_url':
            echo "<a href='".get_post_meta($post_id, 'link_in_bio_url', true) ."' target='_blank'>".get_post_meta($post_id, 'link_in_bio_url', true) ."</a>";
            break;
        case 'post_type':
            echo "";
            break;
    }
}

// remove the post_type column from the link_in_bio post type admin menu
add_filter('manage_edit-link_in_bio_columns', 'remove_link_in_bio_columns');
function remove_link_in_bio_columns($columns)
{
    unset($columns['post_type']);
    return $columns;
}

// add a custom field to the link_in_bio post type called "link_in_bio_url"
function add_link_in_bio_url_field()
{
    add_meta_box(
        'link_in_bio_url_field',
        'Link in Bio URL',
        'link_in_bio_url_field_html',
        'link_in_bio',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_link_in_bio_url_field');

// add the link_in_bio_url field to the link_in_bio post type
function link_in_bio_url_field_html($post)
{
    wp_nonce_field(basename(__FILE__), 'link_in_bio_url_field_html_nonce');
    $link_in_bio_url_value = get_post_meta($post->ID, 'link_in_bio_url', true);
    echo '<input type="text" name="link_in_bio_url" id="link_in_bio_url" value="' . esc_attr($link_in_bio_url_value) . '" size="200" />';
    // make the input full width
    echo '<style>#link_in_bio_url { width: 100%; }</style>';
}

// save the link_in_bio_url field to the custom field
function save_link_in_bio_url_field($post_id)
{
    if (!isset($_POST['link_in_bio_url_field_html_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['link_in_bio_url_field_html_nonce'], basename(__FILE__))) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['link_in_bio_url'])) {
        return;
    }
    $link_in_bio_url_value = sanitize_text_field($_POST['link_in_bio_url']);
    update_post_meta($post_id, 'link_in_bio_url', $link_in_bio_url_value);
}
add_action('save_post', 'save_link_in_bio_url_field');

// Create a new "channel" called "Instagram"
function create_channel_instagram()
{
    wp_insert_term('Instagram', 'channel', array(
        'description' => 'Default channel for Instagram posts',
        'slug' => 'instagram',
    ));
}
add_action('init', 'create_channel_instagram');

// Set the Instagram channel as the default channel for all Link in Bio posts
function set_default_channel_instagram()
{
    $channel_instagram = get_term_by('slug', 'instagram', 'channel');
    $link_in_bio_posts = get_posts(array(
        'post_type' => 'link_in_bio',
        'posts_per_page' => -1,
    ));
    foreach ($link_in_bio_posts as $link_in_bio_post) {
        wp_set_post_terms($link_in_bio_post->ID, $channel_instagram->term_id, 'channel', true);
    }
}
add_action('init', 'set_default_channel_instagram');


