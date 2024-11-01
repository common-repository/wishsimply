<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Wishsimply
{
    const post_type = 'wishsimply';

    public function __construct()
    {

    }

    public function init()
    {
        add_shortcode(self::post_type, array( $this, self::post_type . '_shortcode_callback'));

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action('init', array( $this, 'register_wishsimply_post_type'));
        add_action('add_meta_boxes_' . self::post_type, array( $this, self::post_type . '_add_meta_box'));
        add_action('save_post', array($this, self::post_type . '_save_meta_box'), 10, 3);

        add_filter( 'manage_' . self::post_type . '_posts_columns', array($this, 'manage_' . self::post_type . '_posts_columns') );
        add_action( 'manage_' . self::post_type . '_posts_custom_column' , array($this, 'manage_' . self::post_type . '_posts_custom_column'), 10, 2 );

        add_action( 'admin_notices', array($this, self::post_type . '_notice'), 999999 );

    }

    public function wishsimply_shortcode_callback($attr) {
        $atts  = shortcode_atts(
            array(
                'id'      => '',
            ),
            $attr,
            self::post_type
        );

        wp_enqueue_script('wishsimply-embedded-list', 'https://wishsimply.com/public/5985/embeddedList.js');

        $output = '';
        $post_id = (int)$atts['id'];

        $wishlist_url = get_post_meta($post_id, 'wishlist_url', true);
        $wishlist_id = $this->get_wishsimply_id($wishlist_url);

        if(!empty($wishlist_id)) {
            ob_start();
            echo '<iframe class="wishSimplyIframe" id="' . $wishlist_id . '" src="' . $wishlist_url . '" title="' . get_the_title($post_id) . '" alt="My Wishlist at WishSimply.com" style="border: 0px none; min-height: 253px; width:100%;"></iframe>';
            $output .= ob_get_clean();
        }
        return $output;
    }

    public function admin_scripts() {
        if ( get_current_screen()->id !== self::post_type ) {
            return;
        }
        wp_enqueue_style(self::post_type . '_admin_css', WP_WISHSIMPLY_BASE_URL . 'assets/css/admin.css');
        wp_enqueue_script(self::post_type . '_admin_js', WP_WISHSIMPLY_BASE_URL . 'assets/js/admin.js', array( 'jquery'), WP_WISHSIMPLY_VERSION, true);

    }

    public function register_wishsimply_post_type() {

        $labels = array(
            'name'               => _x( 'Wishlists', 'post type general name', 'wishsimply' ),
            'singular_name'      => _x( 'Wishlist', 'post type singular name', 'wishsimply' ),
            'menu_name'          => _x( 'WishSimply', 'admin menu', 'wishsimply' ),
            'name_admin_bar'     => _x( 'WishSimply', 'add new on admin bar', 'wishsimply' ),
            'add_new'            => _x( 'Add New', 'wishlist', 'wishsimply' ),
            'add_new_item'       => __( 'Add New Wishlist', 'wishsimply' ),
            'new_item'           => __( 'New Wishlist', 'wishsimply' ),
            'edit_item'          => __( 'Edit Wishlist', 'wishsimply' ),
            'view_item'          => __( 'View Wishlist', 'wishsimply' ),
            'all_items'          => __( 'All Wishlists', 'wishsimply' ),
            'search_items'       => __( 'Search Wishlists', 'wishsimply' ),
            'not_found'          => __( 'No Wishlists found.', 'wishsimply' ),
            'not_found_in_trash' => __( 'No Wishlists found in Trash.', 'wishsimply' )
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'menu_icon'          => 'dashicons-star-empty',
            'capability_type'    => 'post',
            'supports'           => array( 'title' )
        );

        register_post_type( self::post_type, $args );
    }

    public function wishsimply_add_meta_box() {
        global $wp_meta_boxes;
        unset($wp_meta_boxes[self::post_type]);
        add_meta_box( 'submitdiv', __( 'Publish', 'wishsimply' ), 'post_submit_meta_box', self::post_type, 'side', 'core' , array( '__back_compat_meta_box' => true ));
        add_meta_box( self::post_type . '_meta_box', __( 'WishSimply', 'wishsimply' ), array( $this, self::post_type . '_meta_box_callback'), self::post_type, 'normal', 'high' );
        if (isset($_GET['post'])) {
            add_meta_box(self::post_type . '_shortcode_meta_box', __('WishSimply shortcode', 'wishsimply'), array($this, self::post_type . '_shortcode_meta_box_callback'), self::post_type, 'normal', 'default');
        }
    }

    public function wishsimply_meta_box_callback($post) {
        wp_nonce_field('wishsimply-meta-box-nonce-action', 'wishsimply-meta-box-nonce');
        ?>
        <label for="wishlist_url"><?php echo __('WishSimply public list url', 'wishsimply'); ?></label>
        <input type="url" name="wishlist_url" id="wishlist_url" class="large-text" value="<?php echo get_post_meta($post->ID, 'wishlist_url', true); ?>" required />

        <?php
    }

    public function wishsimply_save_meta_box($post_id, $post, $update) {

        if (!isset($_POST['wishsimply-meta-box-nonce']) || !wp_verify_nonce($_POST['wishsimply-meta-box-nonce'], 'wishsimply-meta-box-nonce-action'))
            return;
        if(!current_user_can('edit_post', $post_id))
            return;
        if($post->post_type != self::post_type)
            return;

        if (array_key_exists('wishlist_url', $_POST)) {
            update_post_meta($post_id, 'wishlist_url', $_POST['wishlist_url']);
        }
    }

    public function manage_wishsimply_posts_columns($columns) {
        $columns = array_slice($columns, 0, 2, true) +
            array(self::post_type . "_id" => __("WishSimply ID", 'wishsimply')) +
            array(self::post_type . "_shortcode" => __("Shortcode", 'wishsimply')) +
            array_slice($columns, 2, null, true);

        return $columns;
    }

    public function manage_wishsimply_posts_custom_column($column, $post_id ) {
        if ( self::post_type . '_id' === $column ) {
            $wishlist_url = get_post_meta($post_id, 'wishlist_url', true);
            $wishlist_id = $this->get_wishsimply_id($wishlist_url);
            echo $wishlist_id;
        }
        if ( self::post_type . '_shortcode' === $column ) {
            echo $this->generate_shortcode_string($post_id);
        }
    }

    public function wishsimply_shortcode_meta_box_callback($post) {
        ?>
        <label for="wishsimply-shortcode"><?php echo esc_html(__("Copy this shortcode and paste it into your post, page, or text widget content:", 'wishsimply')); ?></label><br>
        <input type="text" id="wishsimply-shortcode" class="regular-text" readonly="readonly"
               value="<?php echo esc_attr($this->generate_shortcode_string($post)); ?>">
        <?php
    }

    public function generate_shortcode_string($post) {
        if ( $post instanceof WP_Post ) {
            $_post = $post;
        }else {
            $_post = WP_Post::get_instance( $post );
        }
        if ( ! $_post ) {
            return null;
        }

        return sprintf('[wishsimply id="%1$d"]', $_post->ID);
    }

    public function wishsimply_notice() {
        if(get_post_type() === self::post_type && $this->is_edit_page()) {
            ?>
            <div class="notice wishsimply-notice">
                <div class="logo-wrap">
                    <img class="logo" src="<?php echo WP_WISHSIMPLY_BASE_URL . 'assets/images/wishsimply_logo_60x60_bold.png'; ?>">
                </div>
                <div class="desc">
                    <h2><?php echo __('How to make your wishlist', 'wishsimply');?></h2>
                    <ol>
                        <li><?php echo sprintf(__('Make your free list at <a href="%s" target="_blank">wishsimply</a> and make it public.', 'wishsimply'), 'https://wishsimply.com/');?></li>
                        <li><?php echo __('Copy the list url here, give it a name, and click "Publish".', 'wishsimply');?></li>
                        <li><?php echo __('Copy & paste the short code any where you want to have the wish list embedded on your site.', 'wishsimply');?></li>
                    </ol>
                </div>
            </div>
            <?php
        }
    }

    public function get_wishsimply_id($wishlist_url) {
        $parts = parse_url($wishlist_url);
        parse_str($parts['query'], $query);
        return isset($query['wishlist']) ? $query['wishlist'] : '';
    }

    public function is_edit_page($new_edit = null){
        global $pagenow;
        //make sure we are on the backend
        if (!is_admin()) return false;


        if($new_edit == "edit")
            return in_array( $pagenow, array( 'post.php') );
        elseif($new_edit == "new") //check for new post page
            return in_array( $pagenow, array( 'post-new.php' ) );
        else //check for either new or edit
            return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
    }

}