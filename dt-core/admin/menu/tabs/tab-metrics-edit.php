<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Disciple_Tools_Metric_Edit_Tab
 */
class Disciple_Tools_Metric_Edit_Tab extends Disciple_Tools_Abstract_Menu_Base
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_submenu' ], 90 );
//        add_action( 'dt_metrics_tab_menu', [ $this, 'add_tab' ], 99, 1 ); // use the priority setting to control load order
        add_action( 'init', [ $this, 'process_data' ] );
        add_action( 'dt_metrics_tab_content', [ $this, 'content' ], 99, 1 );


        parent::__construct();
    } // End __construct()


    public function add_submenu() {
        add_submenu_page( 'dt_metrics', __( 'Create New', 'disciple_tools' ), __( 'Create New', 'disciple_tools' ), 'manage_dt', 'dt_metrics&tab=new', [ 'Disciple_Tools_Metrics_Menu', 'content' ] );
    }

    public function add_tab( $tab ) {
        echo '<a href="'. esc_url( admin_url() ).'admin.php?page=dt_metrics&tab=new" class="nav-tab ';
        if ( $tab == 'sources' ) {
            echo 'nav-tab-active';
        }
        echo '">'. esc_attr__( 'Create Report', 'disciple_tools' ) .'</a>';
    }

    public function process_data(){
        if ( !empty( $_POST ) ){
            if ( isset( $_POST['report_edit_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['report_edit_nonce'] ), 'report_edit' ) ) {
                if ( isset( $_POST["create_report"], $_POST["report"]["year"], $_POST["report"]["month"] ) ){
                    $year = sanitize_key( wp_unslash( $_POST["report"]["year"] ) );
                    $month = sanitize_key( wp_unslash( $_POST["report"]["month"] ) );
                    $report = [
                        "report_date" => $year . '-' . $month . '-01',
                        "report_source" => "monthly_report",
                        'meta_input' => []
                    ];
                    $sources = get_option( 'dt_critical_path_sources', [] );
                    foreach ( $sources as $source ){
                        if ( isset( $_POST["report"][ $source["key"] ] ) ) {
                            $report["meta_input"][ $source["key"] ] = sanitize_text_field( wp_unslash( $_POST["report"][ $source["key"] ] ) );
                        }
                    }

                    //create report
                    dt_report_insert( $report );
                } elseif ( isset( $_POST["update_report"] ) ) {
                    $api = new Disciple_Tools_Reports_API();
                    $sources = get_option( 'dt_critical_path_sources', [] );
                    foreach ( $sources as $source ){
                        if ( isset( $_POST["report"][ $source["key"] ] ) ) {
                            $id = isset( $_GET["report_id"] ) ? sanitize_key( wp_unslash( $_GET["report_id"] ) ) : null;
                            $api->update_report_meta( $id, $source["key"], sanitize_text_field( wp_unslash( $_POST["report"][ $source["key"] ] ) ) );
                        }
                    }
                } elseif ( isset( $_POST["delete_report"] ) ) {
                    $api = new Disciple_Tools_Reports_API();
                    $id = isset( $_GET["report_id"] ) ? sanitize_key( wp_unslash( $_GET["report_id"] ) ) : null;
                    $api->delete_report( $id );
                    wp_redirect( '?page=dt_metrics&tab=list' );
                }
            }
        }
    }

    public function content( $tab ) {
        if ( 'edit' == $tab ) {
            self::template( 'begin' );

            $this->save_report();
            $this->table( "edit" );

            self::template( 'right_column' );
            self::template( 'end' );
        }
        if ( 'new' == $tab ) {
            self::template( 'begin' );

            $this->save_report();
            $this->table( $tab );

            self::template( 'right_column' );

            self::template( 'end' );
        }
    }

    public function table( $tab ) {
        $sources = get_option( 'dt_critical_path_sources', [] );
        $report = [
            "year" => '',
            "month" => ''
        ];

        if ( $tab == "new" ){
            $this->box( 'top', 'Create new Report' );
        } else {
            $this->box( 'top', 'Edit' );
            global $wpdb;
            $report_api = new Disciple_Tools_Reports_API();
            $id = isset( $_GET["report_id"] ) ? sanitize_key( wp_unslash( $_GET["report_id"] ) ) : null;
            $result = $report_api->get_report_by_id( $id );
            $report["year"] = dt_format_date( $result["report_date"], "Y" );
            $report["month"] = dt_format_date( $result["report_date"], "m" );

            foreach ( $result['meta_input'] as $meta ){
                $report[$meta["meta_key"]] = $meta["meta_value"];
            }
        }
        ?>

        <form method="POST" action="">
            <?php if ( $tab === 'edit' ) : ?>
            <p >
                <button type="submit" style="float:right; margin: 10px;" name="delete_report" class="button button-secondary">DELETE Report</button>
            </p>
            <?php else : ?>
                <p>Reports are tracked monthly. Select the year and month of your report and fill out as many fields as you can. You can come back later to update the fields if you need to.</p>
            <?php endif; ?>
            <?php wp_nonce_field( 'report_edit', 'report_edit_nonce' ); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Year</td>
                    <td>
                        <?php if ( empty( $report["year"] ) ) : ?>
                        <select name="report[year]" id="year">
                            <?php
                            $current_year = (int) gmdate( 'Y' );
                            $number_of_years = 20;
                            for ( $i = 0; $number_of_years >= $i; $i++ ): ?>
                                <option <?php echo esc_html( (int) $report["year"] == $current_year ? 'selected' : '' ) ?>>
                                    <?php echo esc_attr( $current_year )?>
                                </option>
                                <?php $current_year--;
                            endfor;
                            ?>
                        </select>
                        <?php else :
                            echo esc_html( $report["year"] );
                        endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Month</td>
                    <td>
                        <?php if ( empty( $report["month"] ) ) : ?>
                        <select name="report[month]" id="month">
                            <?php
                            $number_of_months = 12;
                            for ( $i = 1; $number_of_months >= $i; $i++ ) : ?>
                                <option value="<?php echo esc_html( $i ) ?>" <?php echo esc_html( (int) $report["month"] == $i ? 'selected' : '' ) ?>>
                                    <?php echo esc_attr( DateTime::createFromFormat( '!m', $i )->format( 'F' ) ) ?>
                                </option>
                            <?php endfor;?>
                        </select>
                        <?php else :
                            echo esc_attr( DateTime::createFromFormat( '!m', $report["month"] )->format( 'F' ) );
                        endif; ?>
                    </td>
                </tr>
                <?php foreach ( $sources as $source ) : ?>
                <tr>
                    <td><?php echo esc_html( $source["label"] ) ?></td>
                    <td>
                        <input name="report[<?php echo esc_html( $source["key"] ) ?>]"
                               value="<?php echo esc_html( isset( $report[$source["key"] ] ) ? $report[$source["key"] ] : '' ) ?>">
                    </td>
                    <td>
                        <?php echo esc_html( $source["description"] ?? '' ) ?>
                    </td>
                </tr>
                <?php endforeach;?>
                </tbody>

            </table>
            <p style="margin-top: 10px">

            <?php if ( $tab === 'new' ) : ?>
                <button type="submit" name="create_report" class="button button-primary">Create Report</button>
            <?php else : ?>
                <button type="submit" name="update_report" class="button button-primary">Update Report</button>
            <?php endif; ?>
            </p>
        </form>

        <?php
        $this->box( 'bottom' );
    }

    public function save_report(){

    }


}
Disciple_Tools_Metric_Edit_Tab::instance();
