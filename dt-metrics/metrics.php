<?php

/**
 * Disciple_Tools_Metrics
 *
 * @class      Disciple_Tools_Metrics
 * @version    0.1.0
 * @since      0.1.0
 * @package    Disciple_Tools
 * @author     Chasm.Solutions & Kingdom.Training
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Disciple_Tools_Admin_Menus
 */
class Disciple_Tools_Metrics
{

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $url_path = dt_get_url_path();
        if ( strpos( $url_path, "metrics" ) !== false ) {

            // Personal
            //@todo fix query and re-enable
            //require_once( get_template_directory() . '/dt-metrics/personal/coaching-tree.php' );
            //require_once( get_template_directory() . '/dt-metrics/personal/baptism-tree.php' );
            //require_once( get_template_directory() . '/dt-metrics/personal/group-tree.php' );
            require_once( get_template_directory() . '/dt-metrics/personal/mapbox-groups-cluster-map.php' );
            require_once( get_template_directory() . '/dt-metrics/personal/mapbox-groups-point-map.php' );
            require_once( get_template_directory() . '/dt-metrics/personal/mapbox-groups-area-map.php' );
            require_once( get_template_directory() . '/dt-metrics/personal/mapbox-contacts-cluster-map.php' );
            require_once( get_template_directory() . '/dt-metrics/personal/mapbox-contacts-point-map.php' );
            require_once( get_template_directory() . '/dt-metrics/personal/mapbox-contacts-area-map.php' );
            require_once( get_template_directory() . '/dt-metrics/personal/overview.php' );

            /* Contacts */
            require_once( get_template_directory() . '/dt-metrics/contacts/baptism-tree.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/coaching-tree.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/sources.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/milestones.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/milestones-map.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/mapbox-cluster-map.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/mapbox-point-map.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/mapbox-area-map.php' );
            require_once( get_template_directory() . '/dt-metrics/contacts/overview.php' );

            /* Groups */
            require_once( get_template_directory() . '/dt-metrics/groups/tree.php' );
            require_once( get_template_directory() . '/dt-metrics/groups/mapbox-cluster-map.php' );
            require_once( get_template_directory() . '/dt-metrics/groups/mapbox-point-map.php' );
            require_once( get_template_directory() . '/dt-metrics/groups/mapbox-area-map.php' );
            require_once( get_template_directory() . '/dt-metrics/groups/overview.php' );

            // Combined
            require_once( get_template_directory() . '/dt-metrics/combined/locations-list.php' );
            require_once( get_template_directory() . '/dt-metrics/combined/hover-map.php' );
            require_once( get_template_directory() . '/dt-metrics/combined/critical-path.php' );

            // default menu order
            add_filter( 'dt_metrics_menu', function ( $content ){
                if ( $content === "" ){
                    $content .= '
                        <li><a>' . __( "Personal", "disciple_tools" ) . '</a>
                            <ul class="menu vertical nested" id="personal-menu"></ul>
                        </li>
                        <li><a>' . __( "Project", "disciple_tools" ) . '</a>
                            <ul class="menu vertical nested" id="combined-menu"></ul>
                        </li>
                        <li><a>' . __( "Contacts", "disciple_tools" ) . '</a>
                            <ul class="menu vertical nested" id="contacts-menu"></ul>
                        </li>
                        <li><a>' . __( "Groups", "disciple_tools" ) . '</a>
                            <ul class="menu vertical nested" id="groups-menu"></ul>
                        </li>
                    ';
                }
                return $content;
            }, 10 ); //load menu links
        }
    }

}
Disciple_Tools_Metrics::instance();


function dt_get_time_until_midnight() {
    $midnight = mktime( 0, 0, 0, gmdate( 'n' ), gmdate( 'j' ) +1, gmdate( 'Y' ) );
    return intval( $midnight - current_time( 'timestamp' ) );
}

// Tests if timestamp is valid.
if ( ! function_exists( 'is_valid_timestamp' ) ) {
    function is_valid_timestamp( $timestamp ) {
        return ( (string) (int) $timestamp === $timestamp )
            && ( $timestamp <= PHP_INT_MAX )
            && ( $timestamp >= ~PHP_INT_MAX );
    }
}

