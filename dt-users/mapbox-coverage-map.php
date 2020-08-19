<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class DT_Users_Mapbox_Coverage_Map extends DT_Metrics_Chart_Base
{

    //slug and title of the top menu folder
    public $base_slug = 'user-management'; // lowercase
    public $base_title;
    public $title;
    public $slug = 'mapbox-map'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = '/dt-users/mapbox-coverage-map.js'; // should be full file name plus extension
    public $permissions = [ 'list_users', 'manage_dt' ];
    public $namespace = 'user-management/v1';

    public function __construct() {
        if ( ! DT_Mapbox_API::get_key() ) {
            return;
        }
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }

        $url_path = dt_get_url_path();
        if ( strpos( $url_path, 'user-management' ) !== false ) {
            add_filter( 'dt_metrics_menu', [ $this, 'add_menu' ], 20 );
        }
        if ( "$this->base_slug/$this->slug" === $url_path ) {
            add_filter( 'dt_metrics_menu', [ $this, 'base_menu' ], 20 ); //load menu links
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_filter( 'dt_templates_for_urls', [ $this, 'dt_templates_for_urls' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        }
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function dt_templates_for_urls( $template_for_url ) {
        $template_for_url['user-management/mapbox-map'] = 'template-metrics.php';
        return $template_for_url;
    }

    public function base_menu( $content ) {
        return $content;
    }

    public function base_add_url( $template_for_url ) {
        return $template_for_url;
    }

    public function add_menu( $content ) {
        $content .= '<li><a href="'. esc_url( site_url( '/user-management/mapbox-map/' ) ) .'" >' .  esc_html__( 'Coverage Map', 'disciple_tools' ) . '</a></li>';
        return $content;
    }

    public function scripts() {
        $dependencies = [
            'jquery',
            'moment',
            'lodash'
        ];

        wp_enqueue_script( 'dt_user_map', get_template_directory_uri() . $this->js_file_name, $dependencies, filemtime( get_theme_file_path() . $this->js_file_name ), true );
        wp_localize_script(
            'dt_user_map', 'dt_user_management_localized', [
                'root'               => esc_url_raw( rest_url() ),
                'theme_uri'          => trailingslashit( get_stylesheet_directory_uri() ),
                'nonce'              => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id'    => get_current_user_id(),
                'map_key'            => DT_Mapbox_API::get_key(),
                'map_mirror'         => trailingslashit( dt_get_location_grid_mirror( true ) ),
                'url_path'           => dt_get_url_path(),
                'translations'       => [
                    'title' => __( 'Coverage Map', 'disciple_tools' ),
                    'responsibility' => __( 'Responsibility', 'disciple_tools' ),
                    'zoom_level' => __( 'Zoom Level', 'disciple_tools' ),
                    'auto_zoom' => __( 'Auto Zoom', 'disciple_tools' ),
                    'world' => __( 'World', 'disciple_tools' ),
                    'country' => __( 'Country', 'disciple_tools' ),
                    'state' => __( 'State', 'disciple_tools' ),
                    'status' => __( 'Status', 'disciple_tools' ),
                    'status_all' => __( 'Status - All', 'disciple_tools' ),
                    'active' => __( 'Active', 'disciple_tools' ),
                    'away' => __( 'Away', 'disciple_tools' ),
                    'inconsistent' => __( 'Inconsistent', 'disciple_tools' ),
                    'inactive' => __( 'Inactive', 'disciple_tools' ),
                    'response_coverage' => __( 'Response Coverage', 'disciple_tools' ),
                ]
            ]
        );

        DT_Mapbox_API::load_mapbox_header_scripts();
        DT_Mapbox_API::load_mapbox_search_widget_users();
    }

    public function add_api_routes() {
        register_rest_route(
            $this->namespace, '/grid_totals', [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'grid_totals' ],
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/get_user_list', [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'get_user_list' ],
                ],
            ]
        );
    }

    public function grid_totals( WP_REST_Request $request ) {
        if ( !$this->has_permission() ){
            return new WP_Error( __METHOD__, "Missing Permissions", [ 'status' => 400 ] );
        }
        $params = $request->get_json_params() ?? $request->get_body_params();
        $status = null;
        if ( isset( $params['status'] ) && $params['status'] !== 'all' ) {
            $status = sanitize_text_field( wp_unslash( $params['status'] ) );
        }

        $results = Disciple_Tools_Mapping_Queries::query_user_location_grid_totals( $status );

        return $results;

    }

    public function get_user_list( WP_REST_Request $request ){
        if ( !$this->has_permission() ){
            return new WP_Error( __METHOD__, "Missing Permissions", [ 'status' => 400 ] );
        }

        global $wpdb;
        $results = $wpdb->get_results( "
                SELECT u.display_name as name, lgm.grid_meta_id, lgm.grid_id, lgm.post_id as user_id
                FROM $wpdb->dt_location_grid_meta as lgm
                LEFT JOIN $wpdb->users as u ON u.ID=lgm.post_id
                WHERE lgm.post_type = 'users'
                ", ARRAY_A );

        $list = [];
        foreach ( $results as $result ) {
            if ( ! isset( $list[$result['grid_id']] ) ) {
                $list[$result['grid_id']] = [];
            }
            $list[$result['grid_id']][] = $result;
        }

        return $list;
    }

}
new DT_Users_Mapbox_Coverage_Map();
