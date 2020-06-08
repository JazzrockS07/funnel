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
class Submission {
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
        $endpoint = '/submission/';

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'   => \WP_REST_Server::EDITABLE,
                'callback'  => array( $this, 'update_post' ),
                'args'      => array(
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
                    'new_name' => array(
                        'required' => true,
                        'description' => 'New post title',
                    ),
                    'exist_forms' => array(
                        'required' => true,
                        'description' => 'Exist forms'
                    ),
                    'new_forms' => array(
                        'required' => true,
                        'description' => 'New forms'
                    )
                ),
                )
            )
        );
    }

    /**
     * Create OR Update Example
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_post( $request ) {
        $post = get_post($request->get_param('page'));
        $title = $request->get_param( 'new_name' );
        $exist_forms = $request->get_param('exist_forms');
        $new_forms = $request->get_param('new_forms');
        $exist_links = $request->get_param('exist_links');
        $new_links = $request->get_param('new_links');
        $content = $post->post_content;
        foreach($exist_forms as $key => $value) {
            if (strcasecmp($exist_forms[$key], $new_forms{$key}) != 0) {
                $content = str_replace($exist_forms{$key}, $new_forms[$key], $content);
            }
        }
        foreach($exist_forms as $key => $value) {
            if (strcasecmp($exist_links[$key], $new_links[$key]) !=0) {
                $content = str_replace(($exist_links[$key]), $new_links[$key], $content);
            }
        }

        // create array with data
        $my_post = array();
        $my_post['ID'] = $post->ID;
        if ($title) {
            $my_post['post_title'] = $title;
        }
        $my_post['post_content'] = $content;

        // update post in database
        wp_update_post( wp_slash($my_post) );

        $new_post = get_post($post->ID);
        $new_title = $new_post->post_title;
        $new_content = $new_post->post_content;

        $form_count = preg_match_all('#\[.+form[^]]+]#uiU',$new_content,$forms);
        $exist_forms = $forms[0];
        foreach ($forms as $key => $value) {
            foreach($value as $k => $v){
                $count = preg_match_all('#id=\"(\d+)\"#ui',$v,$matches);
                $form_id = get_post((int)$matches[1][0]);
                $form[] = $form_id->post_title;
            }

        }

        $link_count = preg_match_all('/<a\s+href="[^>]+">.+?<\/a>/',$new_content,$links);
        $exist_links = $links[0];
        foreach($links as $key => $value) {
            foreach ($value as $k => $v) {
                $link_count = preg_match_all('#href=\"(.+)\">(.+?)<#ui',$v,$matches);
                $link[$k]['href'] = $matches[1][0];
                $link[$k]['text'] = $matches[2][0];
            }
        }


        return new \WP_REST_Response( array(
            'success'       => true,
            'name'          => $new_title,
            'forms'         => $form,
            'exist_forms'   => $exist_forms,
            'pre_forms'     => $exist_forms,
            'links'         => $link,
            'exist_links'   => $exist_links,
            'pre_links'     => $exist_links,
        ), 200 );
       
    }

}
