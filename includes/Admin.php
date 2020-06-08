<?php
/**
 * WP-Funnel
 *
 *
 * @package   WP-Funnel
 * @author    Sergiy Levchuk
 * @license   GPL-3.0
 */

namespace Pangolin\WPR;

/**
 * @subpackage Admin
 */
class Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin basename.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_basename = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$instance->do_hooks();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		$plugin = Plugin::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->version = $plugin->get_plugin_version();

		$this->plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
	}


	/**
	 * Handle WP actions and filters.
	 *
	 * @since 	1.0.0
	 */
	private function do_hooks() {
		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add funnel admin menu
        add_action( 'init', array($this, 'true_register_funnels' ) );

        //add meta box
        add_action( 'admin_menu', array($this, 'true_meta_boxes_funnels' ) );

        //save meta box
        add_action('save_post', array($this, 'true_save_box_data_funnels' ) );

        // Add plugin action link point to settings page
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug . '-style', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), array(), $this->version );
		}
	}

	/**
	 * Register and enqueue admin-specific javascript
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {

			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version );

			wp_localize_script( $this->plugin_slug . '-admin-script', 'wpr_object', array(
				'api_nonce'   => wp_create_nonce( 'wp_rest' ),
				'api_url'	  => rest_url( $this->plugin_slug . '/v1/' ),
				)
			);
		}
	}


    /**
     * Add settings for this plugin.
     *
     * @since    1.0.0
     */

    public function true_register_funnels() {
        $labels = array(
            'name' => 'Funnels',
            'singular_name' => 'Funnel',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Funnel', // <title>
            'edit_item' => 'Edit Funnel',
            'new_item' => 'New Funnel',
            'all_items' => 'All Funnels',
            'view_item' => 'View Funnels on the site',
            'search_items' => 'Find Funnel',
            'not_found' =>  'Funnels not found',
            'not_found_in_trash' => 'There are no funnels in the basket',
            'menu_name' => 'Funnels' // ссылка в меню в админке
        );
        $args = array(
            'labels' => $labels,
            'public' => true, // благодаря этому некоторые параметры можно пропустить
            'menu_icon' => 'dashicons-filter', // иконка корзины
            'menu_position' => 5,
            'has_archive' => true,
            'publicly_queryable' => false,
            'supports' => array( 'title'),
            'taxonomies' => array('category')
        );
        register_post_type('funnels',$args);
    }

    /**
     * 1. add metabox
     */
    public function true_meta_boxes_funnels() {
        	$this->plugin_screen_hook_suffix = 'funnels';//delete this if need work in submenu
			add_meta_box('reactivate', 'step1', array($this, 'true_print_box_funnels'), 'funnels', 'normal', 'high');
    }

    /**
     * 2. create html for metabox
     */
    public function true_print_box_funnels($post) {
        wp_nonce_field( basename( __FILE__ ), 'funnel_metabox_nonce' );
        /*
         * add html for react js
         */
        ?><div id="wp-reactivate-admin"></div><?php
    }

    /**
     * 3. save metabox
     */
    public function true_save_box_data_funnels ( $post_id ) {
        // проверяем, пришёл ли запрос со страницы с метабоксом
        if ( !isset( $_POST['funnel_metabox_nonce'] )
            || !wp_verify_nonce( $_POST['funnel_metabox_nonce'], basename( __FILE__ ) ) )
            return $post_id;
        // проверяем, является ли запрос автосохранением
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
            return $post_id;
        // проверяем, права пользователя, может ли он редактировать записи
        if ( !current_user_can( 'edit_post', $post_id ) )
            return $post_id;
        // теперь также проверим тип записи
        $post = get_post($post_id);
        if ($post->post_type == 'funnels') { // укажите собственный
            update_post_meta($post_id, 'funnel_title', esc_attr($_POST['seotitle']));
            update_post_meta($post_id, 'funnel_noindex', $_POST['noindex']);
        }
        return $post_id;
    }

    /**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'edit.php?post_type=funnels') . '">' . __( 'Settings', $this->plugin_slug ) . '</a>',
			),
			$links
		);
	}
}
