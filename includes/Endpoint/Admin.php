<?php
/**
 * WP-Reactivate
 *
 *
 * @package   WP-Reactivate
 * @author    Pangolin
 * @license   GPL-3.0
 * @link      https://gopangolin.com
 * @copyright 2017 Pangolin (Pty) Ltd
 */

namespace Pangolin\WPR\Endpoint;
use Pangolin\WPR;

/**
 * @subpackage REST_Controller
 */
class Admin {
    /**
     * Instance of this class.
     *
     * @since    0.8.1
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     0.8.1
     */
    private function __construct() {
        $plugin = WPR\Plugin::get_instance();
        $this->plugin_slug = $plugin->get_plugin_slug();
    }

    /**
     * Set up WordPress hooks and filters
     *
     * @return void
     */
    public function do_hooks() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Return an instance of this class.
     *
     * @since     0.8.1
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
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $version = '1';
        $namespace = $this->plugin_slug . '/v' . $version;
        $endpoint = '/admin/';

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_post_types' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::CREATABLE,
                'callback'              => array( $this, 'get_forms_and_links' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
                'args'                  => array(
                    'post_type' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'WP post type',
                    ),
                    'page' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'WP post type page',
                    ),
                ),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'get_forms_and_links' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
                'args'                  => array(
                    'post_type' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'WP post type',
                    ),
                    'page' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'WP post type page',
                    ),
                ),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::DELETABLE,
                'callback'              => array( $this, 'delete_contact_email' ),
                'permission_callback'   => array( $this, 'admin_permissions_check' ),
                'args'                  => array(),
            ),
        ) );

    }

    /**
     * Get Example
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_post_types( $request ) {
        $post_types = get_post_types();



        // Don't return false if there is no option
        if ( ! post_types ) {
            return new \WP_REST_Response( array(
                'success' => true,
                'value' => ''
            ), 200 );
        }

        foreach( $post_types as $post_type ) {
            $posts = get_posts( array(
                'numberposts' => '',
                'category'    => 0,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'include'     => array(),
                'exclude'     => array(),
                'meta_key'    => '',
                'meta_value'  =>'',
                'post_type'   => $post_type,
                'suppress_filters' => true,
            ) );

            foreach( $posts as $post ){
                $post_type_example[$post_type][] = $post;
            }
        }

        //$all_posts = implode(",",$post_type_example);

        return new \WP_REST_Response( array(
            'success' => true,
            'value' => $post_type_example
        ), 200 );
    }

    /**
     * Request and filter forms and links on the page
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_forms_and_links( $request ) {
        $post_id = get_post($request->get_param('page'));
        $post_name = $post_id->post_title;
        $content = $post_id->post_content;
        $link_count = preg_match_all('/<a\s+href="[^>]+">.+?<\/a>/',$content,$links);
        $exist_links = $links[0];
        foreach($links as $key => $value) {
            foreach ($value as $k => $v) {
                $link_count = preg_match_all('#href=\"(.+)\">(.+?)<#ui',$v,$matches);
                $link[$k]['href'] = $matches[1][0];
                $link[$k]['text'] = $matches[2][0];
            }
        }
        $form_count = preg_match_all('#\[.+form[^]]+]#uiU',$content,$forms);
        $exist_forms = $forms[0];
        foreach ($forms as $key => $value) {
            foreach($value as $k => $v){
                $count = preg_match_all('#id=\"(\d+)\"#ui',$v,$matches);
                $form_id = get_post((int)$matches[1][0]);
                //$form[] = $form_id; for all fields
                //$form[(int)$matches[1][0]]['title'] = $form_id->post_title; if only ID and title need
                $form[] = $form_id->post_title;
            }

        }

        global $wpdb;

        $visitors = 0;
        $page_views = 0;

        $select_db = $wpdb->get_results("
                SELECT * 
                FROM `wp_koko_analytics_post_stats` 
                WHERE `id` = ".$request->get_param('page')."
                " );
        if( $select_db ) {
            foreach ( $select_db as $select ) {
                $visitors += (int)$select->visitors;
                $page_views += (int)$select->pageviews;
            }
        }
        $select_url = $wpdb->get_results("
                SELECT * 
                FROM `wp_koko_analytics_referrer_urls` 
                " );

        if( $select_url ) {
            foreach ( $select_url as $select ) {
                $url[] = $select->url;
            }
        }

        if (!isset($form)) {
            $form = '';
        }

        if (!isset($link)) {
            $link = '';
        }

        if (!isset($url)) {
            $url = '';
        }

        return new \WP_REST_Response( array(
            'success'       => true,
            'name'          => $post_name,
            'forms'         => $form,
            'exist_forms'   => $exist_forms,
            'pre_forms'     => $exist_forms,
            'links'         => $link,
            'exist_links'   => $exist_links,
            'pre_links'     => $exist_links,
            'visitors'      => $visitors,
            'views'         => $page_views,
            'url'           => $url,
        ), 200 );
    }

    /**
     * Create OR Update Example
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_contact_email( $request ) {
        $updated = update_option( 'wpr_contact_email', $request->get_param( 'email' ) );

        return new \WP_REST_Response( array(
            'success'   => $updated,
            'value'     => $request->get_param( 'email' )
        ), 200 );
    }

    /**
     * Delete Example
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function delete_contact_email( $request ) {
        $deleted = delete_option( 'wpr_contact_email' );

        return new \WP_REST_Response( array(
            'success'   => $deleted,
            'value'     => ''
        ), 200 );
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function admin_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }
}
