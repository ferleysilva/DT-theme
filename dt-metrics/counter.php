<?php

/**
 * Counter factory for reporting
 *
 * @package Disciple_Tools
 * @version 0.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

function dt_counter() {
    return Disciple_Tools_Counter::instance();
}
function dt_queries() {
    return Disciple_Tools_Queries::instance();
}

/**
 * Class Disciple_Tools_Counter_Factory
 */
class Disciple_Tools_Counter
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        // Load required files
        require_once( 'counters/abstract-class-counter.php' );
        require_once( 'counters/counter-connected.php' );
        require_once( 'counters/counter-generations-status.php' );
        require_once( 'counters/counter-baptism.php' );
        require_once( 'counters/counter-groups.php' );
        require_once( 'counters/counter-contacts.php' );
        require_once( 'counters/counter-outreach.php' );
        require_once( 'counters/counter-locations.php' );
    } // End __construct

    /**
     * Gets the critical path.
     * The steps of the critical path can be called direction, or the entire array for the critical path can be called with 'full'.
     *
     * @param string $step_name
     * @param null $start , unix time stamp
     * @param null $end
     * @param array $args
     *
     * @return int|array
     */
    public static function critical_path( string $step_name = 'all', $start = null, $end = null, $args = [] ) {

        if ( $start === null){
            $start = 0;
        }
        if ( $end === null ){
            $end = PHP_INT_MAX;
        }

        $step_name = strtolower( $step_name );

        switch ( $step_name ) {
            case 'new_contacts':
                return Disciple_Tools_Counter_Contacts::get_contacts_count( 'new_contacts', $start, $end );
                break;
            case 'contacts_attempted':
//                return Disciple_Tools_Counter_Contacts::get_contacts_count( 'contacts_attempted' );
                break;
            case 'contacts_established':
//                return Disciple_Tools_Counter_Contacts::get_contacts_count( 'contacts_established' );
                break;
            case 'first_meetings':
                return Disciple_Tools_Counter_Contacts::get_contacts_count( 'first_meetings', $start, $end );
                break;
            case 'ongoing_meetings':
                return Disciple_Tools_Counter_Contacts::get_contacts_count( 'ongoing_meetings', $start, $end );
                break;
            case 'baptisms':
                return Disciple_Tools_Counter_Baptism::get_number_of_baptisms( $start, $end );
                break;
            case 'baptism_generations':
                return Disciple_Tools_Counter_Baptism::get_baptism_generations( $start, $end );
                break;
            case 'baptizers':
                return Disciple_Tools_Counter_Baptism::get_number_of_baptizers( $start, $end );
                break;
            case 'churches_and_groups':
                return Disciple_Tools_Counter_Groups::get_groups_count( 'churches_and_groups', $start, $end );
                break;
            case 'active_groups':
                return Disciple_Tools_Counter_Groups::get_groups_count( 'active_groups', $start, $end );
                break;
            case 'active_churches':
                return Disciple_Tools_Counter_Groups::get_groups_count( 'active_churches', $start, $end );
                break;
            case 'church_generations':
                return Disciple_Tools_Counter_Groups::get_groups_count( 'church_generations', $start, $end );
                break;
            case 'all_group_generations':
                return Disciple_Tools_Counter_Groups::get_groups_count( 'generations', $start, $end, $args );
                break;
            case 'church_planters':
                return Disciple_Tools_Counter_Groups::get_groups_count( 'church_planters', $start, $end );
                break;
            case 'people_groups':

            case 'manual_additions':
                return Disciple_Tools_Counter_Outreach::get_outreach_count( 'manual_additions', $start, $end );
            case 'all':
                $manual_additions = self::critical_path( 'manual_additions', $start, $end );
                $data = [];
                foreach ( $manual_additions as $addition ){
                    if ( $addition["section"] == "before") {
                        $data[] = [
                            "key" => $addition["source"],
                            "label" => $addition["label"],
                            "value" => $addition["total"]
                        ];
                    }
                }
                $data[] = [
                    "key" => "new_contacts",
                    "label" => __( "New Contacts", "disciple_tools" ),
                    "value" => self::critical_path( 'new_contacts', $start, $end )
                ];
                $data[] = [
                    "key" => "first_meetings",
                    "label" => __( "First Meetings", "disciple_tools" ),
                    "value" => self::critical_path( 'first_meetings', $start, $end )
                    ];
                $data[] = [
                    "key" => "ongoing_meetings",
                    "label" => __( "Ongoing Meetings", "disciple_tools" ),
                    "value" => self::critical_path( 'ongoing_meetings', $start, $end )
                    ];
                $data[] = [
                    "key" => "baptisms",
                    "label" => __( "Total Baptisms", "disciple_tools" ),
                    "value" => self::critical_path( 'baptisms', $start, $end )
                ];
                $data[] = [
                    "key" => "baptizers",
                    "label" => __( "Total Baptizers", "disciple_tools" ),
                    "value" => self::critical_path( 'baptizers', $start, $end )
                ];
                $baptism_generations = self::critical_path( 'baptism_generations', $start, $end );
                foreach ( $baptism_generations as $generation => $num ){
                    $data[] = [
                        "key" => "baptism_generation_$generation",
                        "label" => __( "Baptism Generation", "disciple_tools" ) . ' ' . $generation,
                        "value" => $num
                    ];
                }
                $data[] = [
                    "key" => "churches_and_groups",
                    "label" => __( "Total Churches and Groups", "disciple_tools" ),
                    "value" => self::critical_path( 'churches_and_groups', $start, $end )
                ];
                $data[] = [
                    "key" => "active_groups",
                    "label" => __( "Active Groups", "disciple_tools" ),
                    "value" => self::critical_path( 'active_groups', $start, $end )
                ];
                $data[] = [
                    "key" => "active_churches",
                    "label" => __( "Active Churches", "disciple_tools" ),
                    "value" => self::critical_path( 'active_churches', $start, $end )
                ];
                $church_generations = self::critical_path( 'church_generations', $start, $end );
                foreach ( $church_generations as $generation => $num ){
                    $data[] = [
                        "key" => "church_generation_$generation",
                        "label" => __( "Church Generation", "disciple_tools" ) . ' ' . $generation,
                        "value" => $num
                    ];
                }
                $data[] = [
                    "key" => "church_planters",
                    "label" => __( "Church Planters", "disciple_tools" ),
                    "value" => self::critical_path( 'church_planters', $start, $end )
                ];
//                @todo ppl groups
                foreach ( $manual_additions as $addition ){
                    if ( $addition["section"] == "after") {
                        $data[] = [
                            "key" => $addition["source"],
                            "label" => $addition["label"],
                            "value" => $addition["total"]
                        ];
                    }
                }
                return $data;
                break;
            default:
                return 0;
        }
    }

    /**
     * Counts the meta_data attached to a P2P connection
     *
     * @param $type
     * @param $meta_value
     *
     * @return null|string
     */
    public function connection_type_counter( $type, $meta_value ) {
        $type = $this->set_connection_type( $type );
        $count = new Disciple_Tools_Counter_Connected();
        $result = $count->has_meta_value( $type, $meta_value );

        return $result;
    }

    /**
     * Counts Contacts with matching $meta_key and $meta_value provided.
     * Used to retrieve the number of contacts that match the meta_key and meta_value supplied.
     * Example usage: How many contacts have the "unassigned" status? or How many contacts have a "Contact Attempted" status?
     *
     * @param $meta_key
     * @param $meta_value
     *
     * @return int
     */
    public function contacts_meta_counter( $meta_key, $meta_value ) {
        $query = new WP_Query( [
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
            'post_type' => 'contacts',
        ] );

        return $query->found_posts;
    }

    /**
     * Counts Contacts with matching $meta_key and $meta_value provided.
     * Used to retrieve the number of contacts that match the meta_key and meta_value supplied.
     * Example usage: How many contacts have the "unassigned" status? or How many contacts have a "Contact Attempted" status?
     *
     * @param $meta_key
     * @param $meta_value
     *
     * @return int
     */
    public function groups_meta_counter( $meta_key, $meta_value ) {
        $query = new WP_Query( [
            'meta_key' => $meta_key,
            'meta_value' => $meta_value,
            'post_type' => 'groups',
        ] );

        return $query->found_posts;
    }

    /**
     * Contact generations counting factory
     *
     * @param         $generation_number 1,2,3 etc for generation number
     * @param  string $type              contacts or groups or baptisms
     *
     * @return number
     */
    public function get_generation( $generation_number, $type = 'contacts' ) {

        // Set the P2P type for selecting group or contacts
        $type = $this->set_connection_type( $type );

        switch ( $generation_number ) {

            case 'has_one_or_more':
                $gen_object = new Disciple_Tools_Counter_Connected();
                $count = $gen_object->has_at_least( 1, $type );
                break;

            case 'has_two_or_more':
                $gen_object = new Disciple_Tools_Counter_Connected();
                $count = $gen_object->has_at_least( 2, $type );
                break;

            case 'has_three_or_more':
                $gen_object = new Disciple_Tools_Counter_Connected();
                $count = $gen_object->has_at_least( 3, $type );
                break;

            case 'has_0':
                $gen_object = new Disciple_Tools_Counter_Connected();
                $count = $gen_object->has_zero( $type );
                break;

            case 'has_1':
                $gen_object = new Disciple_Tools_Counter_Connected();
                $count = $gen_object->has_exactly( 1, $type );
                break;

            case 'has_2':
                $gen_object = new Disciple_Tools_Counter_Connected();
                $count = $gen_object->has_exactly( 2, $type );
                break;

            case 'has_3':
                $gen_object = new Disciple_Tools_Counter_Connected();
                $count = $gen_object->has_exactly( 3, $type );
                break;

            case 'generation_list':
                $gen_object = new Disciple_Tools_Counter_Generations();
                $count = $gen_object->generation_status_list( $type );
                break;

            case 'at_zero':
                $gen_object = new Disciple_Tools_Counter_Generations();
                $count = $gen_object->gen_level( 0, $type );
                break;

            case 'at_first':
                $gen_object = new Disciple_Tools_Counter_Generations();
                $count = $gen_object->gen_level( 1, $type );
                break;

            case 'at_second':
                $gen_object = new Disciple_Tools_Counter_Generations();
                $count = $gen_object->gen_level( 2, $type );
                break;

            case 'at_third':
                $gen_object = new Disciple_Tools_Counter_Generations();
                $count = $gen_object->gen_level( 3, $type );
                break;

            case 'at_fourth':
                $gen_object = new Disciple_Tools_Counter_Generations();
                $count = $gen_object->gen_level( 4, $type );
                break;

            case 'at_fifth':
                $gen_object = new Disciple_Tools_Counter_Generations();
                $count = $gen_object->gen_level( 5, $type );
                break;

            default:
                $count = null;
                break;
        }

        return $count;
    }

    /**
     * Sets the p2p_type for the where statement
     *
     * @param  string = 'contacts' or 'groups' or 'baptisms'
     *
     * @return string
     */
    protected function set_connection_type( $type ) {
        if ( $type == 'contacts' ) {
            $type = 'contacts_to_contacts';
        } elseif ( $type == 'groups' ) {
            $type = 'groups_to_groups';
        } elseif ( $type == 'baptisms' ) {
            $type = 'baptizer_to_baptized';
        } elseif ( $type == 'participation' ) {
            $type = 'contacts_to_groups';
        } else {
            $type = '';
        }

        return $type;
    }

}
Disciple_Tools_Counter::instance();



/**
 * Class Disciple_Tools_Counter_Factory
 */
class Disciple_Tools_Queries
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        // Load required files
    } // End __construct

    public function tree( $query_name, $args = [] ) {
        global $wpdb;

        switch ( $query_name ) {

            case 'baptisms_all':
                /**
                 * Query returns a generation tree with all baptisms in the system, whether multiplying or not.
                 * @columns id
                 *          parent_id
                 *          name
                 */
                $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name
                    FROM $wpdb->posts as a
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'contacts'
                    AND a.ID NOT IN (
                    SELECT DISTINCT (p2p_from)
                    FROM $wpdb->p2p
                    WHERE p2p_type = 'baptizer_to_baptized'
                    GROUP BY p2p_from)
                    UNION
                    SELECT
                      p.p2p_from  as id,
                      p.p2p_to    as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'baptizer_to_baptized'
                ", ARRAY_A );
                return $query;
                break;

            case 'multiplying_baptisms_only':
                $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name
                    FROM $wpdb->posts as a
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'contacts'
                    AND a.ID NOT IN (
                      SELECT DISTINCT (p2p_from)
                      FROM $wpdb->p2p
                      WHERE p2p_type = 'baptizer_to_baptized'
                      GROUP BY p2p_from
                    )
                      AND a.ID IN (
                      SELECT DISTINCT (p2p_to)
                      FROM $wpdb->p2p
                      WHERE p2p_type = 'baptizer_to_baptized'
                      GROUP BY p2p_to
                    )
                    UNION
                    SELECT
                      p.p2p_from  as id,
                      p.p2p_to    as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'baptizer_to_baptized'
                ", ARRAY_A );
                return $query;
                break;

            case 'group_all':
                $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name,
                      gs1.meta_value as group_status,
                      type1.meta_value as group_type
                    FROM $wpdb->posts as a
                      LEFT JOIN $wpdb->postmeta as gs1
                      ON gs1.post_id=a.ID
                      AND gs1.meta_key = 'group_status'
                      LEFT JOIN $wpdb->postmeta as type1
                      ON type1.post_id=a.ID
                      AND type1.meta_key = 'group_type'
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'groups'
                    AND a.ID NOT IN (
                    SELECT DISTINCT (p2p_from)
                    FROM $wpdb->p2p
                    WHERE p2p_type = 'groups_to_groups'
                    GROUP BY p2p_from)
                    UNION
                    SELECT
                      p.p2p_from                          as id,
                      p.p2p_to                            as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name,
                      (SELECT gsmeta.meta_value FROM $wpdb->postmeta as gsmeta WHERE gsmeta.post_id = p.p2p_from AND gsmeta.meta_key = 'group_status' LIMIT 1 ) as group_status,
                      (SELECT gsmeta.meta_value FROM $wpdb->postmeta as gsmeta WHERE gsmeta.post_id = p.p2p_from AND gsmeta.meta_key = 'group_type' LIMIT 1 ) as group_type
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'groups_to_groups'
                ", ARRAY_A );
                return $query;
                break;

            case 'multiplying_groups_only':
                $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name,
                      gs1.meta_value as group_status,
                      type1.meta_value as group_type
                    FROM $wpdb->posts as a
                     LEFT JOIN $wpdb->postmeta as gs1
                      ON gs1.post_id=a.ID
                      AND gs1.meta_key = 'group_status'
                      LEFT JOIN $wpdb->postmeta as type1
                      ON type1.post_id=a.ID
                      AND type1.meta_key = 'group_type'
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'groups'
                    AND a.ID NOT IN (
                      SELECT DISTINCT (p2p_from)
                      FROM $wpdb->p2p
                      WHERE p2p_type = 'groups_to_groups'
                      GROUP BY p2p_from
                    )
                      AND a.ID IN (
                      SELECT DISTINCT (p2p_to)
                      FROM $wpdb->p2p
                      WHERE p2p_type = 'groups_to_groups'
                      GROUP BY p2p_to
                    )
                    UNION
                    SELECT
                      p.p2p_from  as id,
                      p.p2p_to    as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name,
                      (SELECT gsmeta.meta_value FROM $wpdb->postmeta as gsmeta WHERE gsmeta.post_id = p.p2p_from AND gsmeta.meta_key = 'group_status' LIMIT 1 ) as group_status,
                      (SELECT gsmeta.meta_value FROM $wpdb->postmeta as gsmeta WHERE gsmeta.post_id = p.p2p_from AND gsmeta.meta_key = 'group_type' LIMIT 1 ) as group_type
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'groups_to_groups'
                ", ARRAY_A );
                return $query;
                break;

            case 'coaching_all':
                $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name
                    FROM $wpdb->posts as a
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'contacts'
                    AND a.ID NOT IN (
                    SELECT DISTINCT (p2p_from)
                    FROM $wpdb->p2p
                    WHERE p2p_type = 'contacts_to_contacts'
                    GROUP BY p2p_from)
                    UNION
                    SELECT
                      p.p2p_from                          as id,
                      p.p2p_to                            as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'contacts_to_contacts'
                ", ARRAY_A );
                return $query;
                break;

            case 'multiplying_coaching_only':
                $query = $wpdb->get_results("
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name
                    FROM $wpdb->posts as a
                    WHERE a.post_status = 'publish'
                    AND a.post_type = 'contacts'
                    AND a.ID NOT IN (
                      SELECT DISTINCT (p2p_from)
                      FROM $wpdb->p2p
                      WHERE p2p_type = 'contacts_to_contacts'
                      GROUP BY p2p_from
                    )
                      AND a.ID IN (
                      SELECT DISTINCT (p2p_to)
                      FROM $wpdb->p2p
                      WHERE p2p_type = 'contacts_to_contacts'
                      GROUP BY p2p_to
                    )
                    UNION
                    SELECT
                      p.p2p_from  as id,
                      p.p2p_to    as parent_id,
                      (SELECT sub.post_title FROM $wpdb->posts as sub WHERE sub.ID = p.p2p_from ) as name
                    FROM $wpdb->p2p as p
                    WHERE p.p2p_type = 'contacts_to_contacts'
                ", ARRAY_A );
                return $query;
                break;

            case 'user_hero_stats':
                $query = $wpdb->get_row("
                    SELECT
                      (
                        SELECT
                          COUNT( a.ID )
                        FROM $wpdb->posts as a
                                    WHERE a.post_status = 'publish'
                                    AND a.post_type = 'locations'
                      ) as total_locations,
                      (
                        SELECT
                          COUNT( DISTINCT a.ID )
                        FROM $wpdb->posts as a
                        WHERE a.post_status = 'publish'
                              AND a.ID IN ( SELECT p2p_to FROM $wpdb->p2p WHERE p2p_type = 'contacts_to_locations' OR p2p_type = 'groups_to_locations' )
                              AND a.post_type = 'locations'
                      ) as total_active_locations,
                      (
                        SELECT
                          COUNT( DISTINCT a.ID )
                        FROM $wpdb->posts as a
                        WHERE a.post_status = 'publish'
                              AND a.ID NOT IN ( SELECT p2p_to FROM $wpdb->p2p WHERE p2p_type = 'contacts_to_locations' OR p2p_type = 'groups_to_locations' )
                              AND a.post_type = 'locations'
                      ) as total_inactive_locations,
                      (
                        SELECT
                          COUNT( DISTINCT a.ID )
                        FROM $wpdb->posts as a
                        WHERE a.post_status = 'publish'
                              AND a.ID IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'types' AND meta_value = 'country' )
                              AND a.post_type = 'locations'
                      ) as total_countries,
                      (
                        SELECT
                          COUNT( DISTINCT a.ID )
                        FROM $wpdb->posts as a
                        WHERE a.post_status = 'publish'
                              AND a.ID IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'types' AND meta_value = 'administrative_area_level_1' )
                              AND a.post_type = 'locations'
                      ) as total_states,
                      (
                        SELECT
                          COUNT( DISTINCT a.ID )
                        FROM $wpdb->posts as a
                        WHERE a.post_status = 'publish'
                              AND a.ID IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'types' AND meta_value = 'administrative_area_level_2' )
                              AND a.post_type = 'locations'
                      ) as total_counties;
                ");
                return $query;
                break;

            default:
                return false;
                break;
        }
    }

    public function query( $query_name, $args = [] ) {
        global $wpdb;

        switch ( $query_name ) {
            case 'contact_progress_per_worker':
                $query = $wpdb->get_results("
                    SELECT
                      ( TRIM( LEADING 'user-' from a.meta_value ) ) as user_id,
                      count(a.meta_value) as assigned,
                      count(b.meta_value) as accepted,
                      count(e.meta_value) as active,
                      count(c.meta_value) as attempt_needed,
                      count(d.meta_value) as attempted,
                      count(f.meta_value) as established,
                      count(g.meta_value) as meeting_scheduled,
                      count(h.meta_value) as meeting_complete,
                      count(i.meta_value) as ongoing,
                      count(j.meta_value) as being_coached
                    FROM $wpdb->posts as p
                      LEFT JOIN $wpdb->postmeta as a
                        ON a.post_id=p.ID
                        AND a.meta_key = 'assigned_to'
                      LEFT JOIN $wpdb->postmeta as e
                        ON e.post_id=p.ID
                           AND e.meta_key = 'overall_status' AND e.meta_value = 'active'
                      LEFT JOIN $wpdb->postmeta as b
                        ON b.post_id=e.post_id
                        AND b.meta_key = 'accepted' AND b.meta_value = '1'
                      LEFT JOIN $wpdb->postmeta as c
                        ON c.post_id=e.post_id
                        AND c.meta_key = 'seeker_path' AND c.meta_value = 'none'
                      LEFT JOIN $wpdb->postmeta as d
                        ON d.post_id=e.post_id
                           AND d.meta_key = 'seeker_path' AND d.meta_value = 'attempted'
                      LEFT JOIN $wpdb->postmeta as f
                        ON f.post_id=e.post_id
                           AND f.meta_key = 'seeker_path' AND f.meta_value = 'established'
                      LEFT JOIN $wpdb->postmeta as g
                        ON g.post_id=e.post_id
                           AND g.meta_key = 'seeker_path' AND g.meta_value = 'scheduled'
                      LEFT JOIN $wpdb->postmeta as h
                        ON h.post_id=e.post_id
                           AND h.meta_key = 'seeker_path' AND h.meta_value = 'met'
                      LEFT JOIN $wpdb->postmeta as i
                        ON i.post_id=e.post_id
                           AND i.meta_key = 'seeker_path' AND i.meta_value = 'ongoing'
                      LEFT JOIN $wpdb->postmeta as j
                        ON j.post_id=e.post_id
                           AND j.meta_key = 'seeker_path' AND j.meta_value = 'coaching'
                    WHERE post_status = 'publish'
                      AND post_type = 'contacts'
                    GROUP BY a.meta_value
                ", ARRAY_A);

                return $query;
                break;


            case 'baptized_per_worker':
                $query = $wpdb->get_results("
                    SELECT
                    p2p_to as contact_id,
                    (
                        SELECT meta_value 
                        FROM $wpdb->postmeta 
                        WHERE meta_key = 'corresponds_to_user' 
                        AND post_id = p2p_to
                    ) as user_id,
                    count(*) as count
                    FROM $wpdb->p2p
                    WHERE p2p_type = 'baptizer_to_baptized'
                    GROUP BY p2p_to;", ARRAY_A);

                return $query;
                break;

            case 'recent_unique_logins':
                /**
                 * Returns unique logins for the last 30 days.
                 */
                $query = $wpdb->get_results("
                    SELECT
                      DATE(FROM_UNIXTIME(hist_time)) AS report_date,
                      COUNT(DISTINCT object_id) as total
                    FROM $wpdb->dt_activity_log
                    WHERE object_type = 'User'
                          AND action = 'logged_in'
                    GROUP BY DATE(FROM_UNIXTIME(hist_time))
                    ORDER BY report_date DESC
                    LIMIT 30;
                ", ARRAY_A);
                return $query;
                break;

            case 'last_update_per_worker':
                /**
                 * Returns the date of last update for each user
                 */
                $query = $wpdb->get_results("
                    SELECT
                      MAX(hist_time) AS last_update,
                      user_id
                    FROM $wpdb->dt_activity_log
                    WHERE 1=1
                    GROUP BY user_id DESC;
                ", ARRAY_A);
                return $query;
                break;

            case 'recent_seeker_path':
                $days = 30;
                if ( ! empty( $args['days'] ) ) {
                    $days = (int) $args['days'];
                }
                $query = $wpdb->get_results( $wpdb->prepare( "
                    SELECT
                      object_id as id,
                      posts.post_title as name,
                      meta_value as type,
                      p.p2p_to as location_id,
                      ( SELECT post_title FROM $wpdb->posts WHERE ID = p.p2p_to) as location_name,
                      hist_time
                    FROM $wpdb->dt_activity_log as l
                    LEFT JOIN $wpdb->p2p as p
                          ON l.object_id=p.p2p_from
                          AND p.p2p_type = 'contacts_to_locations'
                    LEFT JOIN $wpdb->posts as posts
                          ON posts.ID = l.object_id
                    WHERE action = 'field_update'
                          AND object_type = 'contacts'
                          AND object_subtype = 'seeker_path'
                          AND (meta_value = 'attempted' OR meta_value = 'scheduled' OR meta_value = 'met')
                          AND hist_time > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY ))
                    ORDER BY hist_time DESC
                ", $days ), ARRAY_A);
                return $query;
                break;

            case 'recent_baptisms':
                $days = 30;
                if ( ! empty( $args['days'] ) ) {
                    $days = (int) $args['days'];
                }
                $query = $wpdb->get_results( $wpdb->prepare( "
                    SELECT
                      object_id as id,
                      posts.post_title as name,
                      p.p2p_to as location_id,
                      ( SELECT post_title FROM $wpdb->posts WHERE ID = p.p2p_to) as location_name,
                      hist_time
                    FROM $wpdb->dt_activity_log as l
                      LEFT JOIN $wpdb->p2p as p
                        ON l.object_id=p.p2p_from
                           AND p.p2p_type = 'contacts_to_locations'
                      LEFT JOIN $wpdb->posts as posts
                        ON posts.ID = l.object_id
                    WHERE meta_key = 'baptism_date'
                          AND meta_value > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY ))
                    ORDER BY meta_value DESC
                ", $days ), ARRAY_A);
                return $query;
                break;

            case 'new_contacts_groups':
                $days = 30;
                if ( ! empty( $args['days'] ) ) {
                    $days = (int) $args['days'];
                }
                $query = $wpdb->get_results( $wpdb->prepare( "
                    SELECT
                      object_id as id,
                      posts.post_title as name,
                      object_type as type,
                      p.p2p_to as location_id,
                      ( SELECT post_title FROM $wpdb->posts WHERE ID = p.p2p_to) as location_name,
                      hist_time
                    FROM $wpdb->dt_activity_log as l
                      LEFT JOIN $wpdb->p2p as p
                        ON l.object_id=p.p2p_from
                        AND ( p.p2p_type = 'groups_to_locations' OR p.p2p_type = 'contacts_to_locations' )
                      LEFT JOIN $wpdb->posts as posts
                        ON posts.ID = l.object_id
                    WHERE action = 'created'
                      AND hist_time > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY ))
                    ORDER BY hist_time DESC
                ", $days ), ARRAY_A);
                return $query;
                break;

            case 'pace_users':
                if ( empty( $args['begin_date'] ) || empty( $args['end_date'] ) ) {
                    return false;
                }

                /**
                 * # - includes assigned and accepted time stamps and calculates the time between events to form 'pace' column
                # - excludes assigned but not accepted
                # - excludes assigned and accepted instantly, or less than 5 seconds
                # - excludes negative pace
                # - order by newest assignment first
                 */
                $query = $wpdb->get_results( $wpdb->prepare( "
                    SELECT
                      a.object_id as contact_id,
                      a.meta_value as assigned_to,
                      a.hist_time as assigned_time,
                      b.hist_time as accepted_time,
                      ( b.hist_time - a.hist_time ) as pace
                    FROM $wpdb->dt_activity_log as a
                    LEFT JOIN $wpdb->dt_activity_log as b
                      ON a.object_id=b.object_id
                        AND b.meta_key = 'accepted'
                        AND b.meta_value = 'yes'
                    WHERE
                      a.object_subtype = 'assigned_to'
                      AND a.object_type = 'contacts'
                      AND ( b.hist_time - a.hist_time ) > 5
                      AND a.hist_time > %d
                      AND b.hist_time < %d
                    ORDER BY a.hist_time DESC
                ", $args['begin_date'], $args['end_date'] ), ARRAY_A);

                return $query;
                break;

            case 'pace_coalition':
                if ( empty( $args['begin_date'] ) || empty( $args['end_date'] ) ) {
                    return false;
                }
                /**
                 * # - average pace assigned to accepted
                # - slowest pace between assigned to accepted
                # - fastest pace between assigned to accepted
                 */

                $query = $wpdb->get_row( $wpdb->prepare( "
                        SELECT
                          COUNT( a.object_id ) as count,
                          AVG( b.hist_time - a.hist_time ) as avg,
                          MAX( b.hist_time - a.hist_time ) as max,
                          MIN( b.hist_time - a.hist_time ) as min
                        FROM $wpdb->dt_activity_log as a
                          LEFT JOIN $wpdb->dt_activity_log as b
                            ON a.object_id=b.object_id
                               AND b.meta_key = 'accepted'
                               AND b.meta_value = 'yes'
                        WHERE
                          a.object_subtype = 'assigned_to'
                          AND a.object_type = 'contacts'
                          AND ( b.hist_time - a.hist_time ) > 5
                          AND a.hist_time > %d
                          AND b.hist_time < %d
                        ;
                    ", $args['begin_date'], $args['end_date'] ), ARRAY_A );

                    return $query;
                break;

            default:
                return false;
                break;
        }
    }

}
