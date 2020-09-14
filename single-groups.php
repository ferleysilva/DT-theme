<?php
declare(strict_types=1);

if ( ! current_user_can( 'access_groups' ) ) {
    wp_safe_redirect( '/settings' );
}

( function() {

    if ( !Disciple_Tools_Groups::can_view( 'groups', get_the_ID() )){
        get_template_part( "403" );
        die();
    }

    Disciple_Tools_Notifications::process_new_notifications( get_the_ID() ); // removes new notifications for this post
    $following = DT_Posts::get_users_following_post( "groups", get_the_ID() );
    $group = Disciple_Tools_Groups::get_group( get_the_ID(), true, true );
    $group_fields = Disciple_Tools_Groups_Post_Type::instance()->get_custom_fields_settings();

    if (get_option('vc_progress_circle_template') != null) {
        $progressCircleTemplates = json_decode(get_option('vc_progress_circle_template'), TRUE);
    }

    $group_preferences = dt_get_option( 'group_preferences' );
    $current_user_id = get_current_user_id();
    $pluginIsActive = false;

    if(in_array('disciple-tools-visual-customization-plugin/disciple-tools-visual-customization-plugin.php', apply_filters('active_plugins', get_option('active_plugins')))){ 
        $pluginIsActive = true;
    } else {
        $pluginIsActive = false;
    }

    global $wpdb;
    $results = $wpdb->get_results( "SELECT meta_value FROM wp_postmeta WHERE post_id = {$group["ID"]} AND meta_key = 'health_metrics'", OBJECT );

    get_header();?>

    <?php
    dt_print_details_bar(
        true,
        true,
        true,
        isset( $group["requires_update"] ) && $group["requires_update"] === true,
        in_array( $current_user_id, $following ),
        isset( $group["assigned_to"]["id"] ) ? $group["assigned_to"]["id"] == $current_user_id : false,
        [],
        true
    ); ?>

<style>
    #slider-influence {
        background: #82CFD0;
        border: solid 1px #82CFD0;
        border-radius: 8px;
        height: 7px;
        width: 356px;
        outline: none;
        -webkit-appearance: none;
    }

    #message-modal-influence {
        margin-bottom: 0;
        text-align: center;
    }

    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        padding-top: 16%; /* Location of the box */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    /* Modal Content */
    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 20%;
    }

    /* The Close Button */
    .close {
        color: #aaaaaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover, .close:focus {
        color: #000;
        text-decoration: none;
        cursor: pointer;
    }

</style>

<!--<div id="errors"> </div>-->
<div id="content" class="single-groups">
    <span id="group-id" style="display: none"><?php echo get_the_ID()?></span>
    <span id="post-id" style="display: none"><?php echo get_the_ID()?></span>
    <span id="post-type" style="display: none">group</span>

    <div id="inner-content" class="grid-x grid-margin-x grid-margin-y">

        <main id="main" class="large-7 medium-12 small-12 cell" role="main" style="padding:0">
            <div class="cell grid-y grid-margin-y">

                <!-- Requires update block -->
                <section class="cell small-12 update-needed-notification"
                         style="display: <?php echo esc_html( ( isset( $group['requires_update'] ) && $group['requires_update'] === true ) ? "block" : "none" ) ?> ">
                    <div class="bordered-box detail-notification-box" style="background-color:#F43636">
                        <h4><img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/alert-circle-exc.svg' ) ?>"/><?php esc_html_e( 'This group needs an update', 'disciple_tools' ) ?>.</h4>
                        <p><?php esc_html_e( 'Please provide an update by posting a comment.', 'disciple_tools' )?></p>
                    </div>
                </section>

                <?php get_template_part( 'dt-assets/parts/group', 'details' ); ?>

                <div class="cell small-12">
                    <div class="grid-x grid-margin-x grid-margin-y grid">

                        <!-- MEMBERS TILE -->
                        <section id="relationships" class="xlarge-6 large-12 medium-6 cell grid-item" >
                            <div class="bordered-box" id="members-tile">
                                <h3 class="section-header"><?php echo esc_html( $group_fields["members"]["name"] )?>
                                    <button class="help-button" data-section="members-help-text">
                                        <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                                    </button>
                                    <button class="section-chevron chevron_down">
                                        <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                                    </button>
                                    <button class="section-chevron chevron_up">
                                        <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                                    </button>
                                </h3>
                                <div class="section-body"><!-- start collapse -->
                                <div class="section-subheader"><?php echo esc_html( $group_fields["member_count"]["name"] )?>
                                <input id="member_count"
                                       class="number-input" type="number" min="0"
                                       placeholder="<?php echo esc_html( sizeof( $group["members"] ) )?>"
                                       value="<?php echo esc_html( $group["member_count"] ?? "" ) ?>">


                                <div class="section-subheader members-header" style="padding-top: 10px">
                                    <div style="padding-bottom: 5px; margin-right:10px; display: inline-block">
                                        <?php esc_html_e( "Member List", 'disciple_tools' ) ?>
                                    </div>
                                    <button type="button" class="create-new-contact" style="height: 36px;">
                                        <?php echo esc_html__( 'Create', 'disciple_tools' )?>
                                        <img style="height: 14px; width: 14px" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/small-add.svg' ) ?>"/>
                                    </button>
                                    <button type="button"
                                            class="add-new-member">
                                        <?php echo esc_html__( 'Select', 'disciple_tools' )?>
                                        <img style="height: 16px; width: 16px" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/add-group.svg' ) ?>"/>
                                    </button>
                                </div>
                                <div class="members-section">
                                    <div id="empty-members-list-message"><?php esc_html_e( "To add new members, click on 'Create' or 'Select'.", 'disciple_tools' ) ?></div>
                                    <div class="member-list">

                                    </div>
                                </div>
                            </div><!-- end collapse --></div>
                        </section>

                        <!-- GROUPS TILE -->
                        <section id="groups" class="xlarge-6 large-12 medium-6 cell grid-item">
                            <div class="bordered-box" id="groups-tile">
                                <h3 class="section-header"><?php esc_html_e( "Groups", 'disciple_tools' ) ?>
                                    <button class="help-button" data-section="group-connections-help-text">
                                        <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                                    </button>
                                    <button class="section-chevron chevron_down">
                                        <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                                    </button>
                                    <button class="section-chevron chevron_up">
                                        <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                                    </button>
                                </h3>
                                <div class="section-body"><!-- start collapse -->
                                <div class="section-subheader">
                                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/group-type.svg' ) ?>"/>
                                    <?php echo esc_html( $group_fields["group_type"]["name"] )?>
                                </div>
                                <select class="select-field" id="group_type">
                                    <?php
                                    foreach ($group_fields["group_type"]["default"] as $key => $option){
                                        $value = $option["label"] ?? "";
                                        if ( $group["group_type"]["key"] === $key ) {
                                            ?>
                                            <option value="<?php echo esc_html( $key ) ?>" selected><?php echo esc_html( $value ); ?></option>
                                        <?php } else { ?>
                                            <option value="<?php echo esc_html( $key ) ?>"><?php echo esc_html( $value ); ?></option>
                                        <?php }
                                    }
                                    ?>
                                </select>
                                <div class="section-subheader">
                                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/group-parent.svg' ) ?>"/>
                                <?php echo esc_html( $group_fields["parent_groups"]["name"] )?>
                                <var id="parent_groups-result-container" class="result-container"></var>
                                <div id="parent_groups_t" name="form-groups" class="scrollable-typeahead typeahead-margin-when-active">
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                        <span class="typeahead__query">
                                            <input class="js-typeahead-parent_groups input-height"
                                                   name="groups[query]"
                                                   placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), Disciple_Tools_Groups_Post_Type::instance()->plural ) )?>"
                                                   autocomplete="off">
                                        </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="section-subheader">
                                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/group-peer.svg' ) ?>"/>
                                <?php echo esc_html( $group_fields["peer_groups"]["name"] )?>
                                <var id="peer_groups-result-container" class="result-container"></var>
                                <div id="peer_groups_t" name="form-groups" class="scrollable-typeahead typeahead-margin-when-active">
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                        <span class="typeahead__query">
                                            <input class="js-typeahead-peer_groups input-height"
                                                   name="groups[query]"
                                                   placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), Disciple_Tools_Groups_Post_Type::instance()->plural ) )?>"
                                                   autocomplete="off">
                                        </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="section-subheader">
                                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/group-child.svg' ) ?>"/>
                                <?php echo esc_html( $group_fields["child_groups"]["name"] )?>
                                <var id="child_groups-result-container" class="result-container"></var>
                                <div id="child_groups_t" name="form-child_groups" class="scrollable-typeahead typeahead-margin-when-active">
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                        <span class="typeahead__query">
                                            <input class="js-typeahead-child_groups input-height"
                                                   name="groups[query]"
                                                   placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), Disciple_Tools_Groups_Post_Type::instance()->plural ) )?>"
                                                   autocomplete="off">
                                        </span>
                                            <span class="typeahead__button">
                                            <button type="button" data-open="create-group-modal" class="create-new-group typeahead__image_button input-height">
                                                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/add-group.svg' ) ?>"/>
                                            </button>
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- end collapse --></div>
                        </section>
                        <!-- OTHER GROUP TILE -->
                            <section id="other" class="xlarge-6 large-12 medium-6 cell grid-item">
                                <div class="bordered-box">
                                    <label class="section-header"><?php esc_html_e( 'Other', 'disciple_tools' )?>
                                        <button class="help-button" data-section="other-tile-help-text">
                                            <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                                        </button>
                                    </label>

                                    <div class="section-subheader">
                                        <?php //echo esc_html( $contact_fields["tags"]["name"] ) ?>
                                        <?php esc_html_e( "Tags", 'disciple_tools' ) ?>
                                    </div>
                                    <div class="tags">
                                        <var id="tags-result-container" class="result-container"></var>
                                        <div id="tags_t" name="form-tags" class="scrollable-typeahead typeahead-margin-when-active">
                                            <div class="typeahead__container">
                                                <div class="typeahead__field">
                                                    <span class="typeahead__query">
                                                        <input class="js-typeahead-tags input-height"
                                                               name="tags[query]" placeholder="<?php esc_html_e( "Search Tags", 'disciple_tools' ) ?>"
                                                               autocomplete="off">
                                                    </span>
                                                    <span class="typeahead__button">
                                                        <button type="button" data-open="create-tag-modal" class="create-new-tag typeahead__image_button input-height">
                                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/tag-add.svg' ) ?>"/>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ( $pluginIsActive ) : ?>
                                    
                                    <div class="section-subheader">
                                        Influence: <span id="influence-number"></span>
                                    </div>
                                    <div class="influence">
                                        <input id="slider-influence" style="width: 100%;" type="range" min="0" max="100" onchange="onChangeSlider(this.value)">
                                    </div>

                                    <?php endif; ?>

                                </div>

                            </section>

                        <?php if ( !empty( $group_preferences['church_metrics']) && !$pluginIsActive ) : ?>
                            <section id="health-metrics" class="xlarge-6 large-12 medium-6 cell grid-item">
                                <div class="bordered-box js-progress-bordered-box half-opacity" id="health-tile">

                                    <h3 class="section-header"><?php echo esc_html( $group_fields["health_metrics"]["name"] )?>
                                        <button class="help-button" data-section="health-metrics-help-text">
                                            <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                                        </button>
                                        <button class="section-chevron chevron_down">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                                        </button>
                                        <button class="section-chevron chevron_up">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                                        </button>
                                    </h3>
                                    <div class="section-body"><!-- start collapse -->

                                    <div class="grid-x">
                                        <div style="margin-right:auto; margin-left:auto;min-height:302px">
                                            <object id="church-svg-wrapper" type="image/svg+xml" data="<?php echo esc_attr( get_template_directory_uri() . '/dt-assets/images/groups/church-wheel.svg' ); ?>"></object>
                                        </div>
                                    </div>
                                    <div style="display:flex;flex-wrap:wrap;margin-top:10px">
                                        <?php foreach ( $group_fields["health_metrics"]["default"] as $key => $option ) : ?>
                                            <div class="group-progress-button-wrapper">
                                                <button  class="group-progress-button" id="<?php echo esc_html( $key ) ?>">
                                                    <img src="<?php echo esc_html( $option["image"] ?? "" ) ?>">
                                                </button>
                                                <p><?php echo esc_html( $option["label"] ) ?> </p>
                                            </div>
                                        <?php endforeach; ?>

                                    </div>

                                </div><!-- end collapse --></div>
                            </section>
                        <?php endif; ?>

                        <!-- Health Metrics-->
                        <?php if ( !empty( $group_preferences['church_metrics']) && $pluginIsActive ) : ?>
                            <section id="health-metrics_2" class="xlarge-12 large-12 medium-12 cell grid-item">
                                <div class="bordered-box js-progress-bordered-box" id="health-tile">

                                    <h3 class="section-header"><?php echo esc_html( $group_fields["health_metrics"]["name"] )?>
                                        <button class="help-button" data-section="health-metrics-help-text">
                                            <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                                        </button>
                                        <button class="section-chevron chevron_down">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                                        </button>
                                        <button class="section-chevron chevron_up">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                                        </button>
                                    </h3>
                                    <div class="section-body"><!-- start collapse -->

                                    <div style="text-align: center;">
                                        <canvas id="canvas-church-metrics" width="450" height="450"></canvas>
                                    </div>

                                </div><!-- end collapse --></div>
                            </section>
                        <?php endif; ?>


                        <!-- Four Fields -->
                        <?php if ( ! empty( $group_preferences['four_fields'] ) ) : ?>
                            <section id="four-fields" class="xlarge-6 large-12 medium-6 cell grid-item">
                                <div class="bordered-box js-progress-bordered-box" id="four-fields-tile">

                                    <h3 class="section-header"><?php esc_html_e( 'Four Fields', 'disciple_tools' ) ?>
                                        <button class="help-button" data-section="four-fields-help-text">
                                            <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                                        </button>
                                        <button class="section-chevron chevron_down">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                                        </button>
                                        <button class="section-chevron chevron_up">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                                        </button>
                                    </h3>
                                    <div class="section-body"><!-- start collapse -->

                                    <div class="grid-x" id="four-fields-inputs">
                                        <div style="width:100%; height:375px;background-image:url('<?php echo esc_attr( get_template_directory_uri() . '/dt-assets/images/four-fields.svg' ); ?>');background-repeat:no-repeat;"></div>
                                    </div>
                                </div><!-- end collapse --></div>
                            </section>
                        <?php endif; ?>


                        <?php
                        //get sections added by plugins
                        $sections = apply_filters( 'dt_details_additional_section_ids', [], "groups" );
                        //get custom sections
                        $custom_tiles = dt_get_option( "dt_custom_tiles" );
                        foreach ( $custom_tiles["groups"] as $tile_key => $tile_options ){
                            if ( !in_array( $tile_key, $sections ) ){
                                $sections[] = $tile_key;
                            }
                            //remove section if hidden
                            if ( isset( $tile_options["hidden"] ) && $tile_options["hidden"] == true ){
                                $index = array_search( $tile_key, $sections );
                                if ( $index !== false) {
                                    unset( $sections[ $index ] );
                                }
                            }
                        }
                        foreach ( $sections as $section ){
                            ?>
                            <section id="<?php echo esc_html( $section ) ?>" class="xlarge-6 large-12 medium-6 cell grid-item">
                                <div class="bordered-box" id="<?php echo esc_html( $section ); ?>-tile">
                                    <?php
                                    // let the plugin add section content
                                    do_action( "dt_details_additional_section", $section, 'groups' );
                                    //setup tile label if see by customizations
                                    if ( isset( $custom_tiles["groups"][$section]["label"] ) ){ ?>
                                        <label class="section-header">
                                            <?php echo esc_html( $custom_tiles["groups"][$section]["label"] )?>
                                        </label>
                                    <?php }
                                    //setup the order of the tile fields
                                    $order = $custom_tiles["groups"][$section]["order"] ?? [];
                                    foreach ( $group_fields as $key => $option ){
                                        if ( isset( $option["tile"] ) && $option["tile"] === $section ){
                                            if ( !in_array( $key, $order )){
                                                $order[] = $key;
                                            }
                                        }
                                    }
                                    foreach ( $order as $field_key ) {
                                        if ( !isset( $group_fields[$field_key] ) ){
                                            continue;
                                        }
                                        $field = $group_fields[$field_key];
                                        if ( isset( $field["tile"] ) && $field["tile"] === $section ){ ?>
                                            <div class="section-subheader">
                                                <?php echo esc_html( $field["name"] )?>
                                            </div>
                                            <?php
                                            /**
                                             * Key Select
                                             */
                                            if ( $field["type"] === "key_select" ) : ?>
                                                <select class="select-field" id="<?php echo esc_html( $field_key ); ?>">
                                                    <?php foreach ($field["default"] as $option_key => $option_value):
                                                        $selected = isset( $group[$field_key]["key"] ) && $group[$field_key]["key"] === $option_key; ?>
                                                        <option value="<?php echo esc_html( $option_key )?>" <?php echo esc_html( $selected ? "selected" : "" )?>>
                                                            <?php echo esc_html( $option_value["label"] ) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ( $field["type"] === "multi_select" ) : ?>
                                                <div class="small button-group" style="display: inline-block">
                                                    <?php foreach ( $group_fields[$field_key]["default"] as $option_key => $option_value ): ?>
                                                        <?php
                                                        $class = ( in_array( $option_key, $group[$field_key] ?? [] ) ) ?
                                                            "selected-select-button" : "empty-select-button"; ?>
                                                        <button id="<?php echo esc_html( $option_key ) ?>" data-field-key="<?php echo esc_html( $field_key ) ?>"
                                                                class="dt_multi_select <?php echo esc_html( $class ) ?> select-button button ">
                                                            <?php echo esc_html( $group_fields[$field_key]["default"][$option_key]["label"] ) ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif ( $field["type"] === "text" ) :?>
                                                <input id="<?php echo esc_html( $field_key ) ?>" type="text"
                                                       class="text-input"
                                                       value="<?php echo esc_html( $group[$field_key] ?? "" ) ?>"/>
                                            <?php elseif ( $field["type"] === "date" ) :?>
                                                <div class="<?php echo esc_html( $field_key ) ?> input-group">
                                                    <input id="<?php echo esc_html( $field_key ) ?>" class="input-group-field dt_date_picker" type="text" autocomplete="off"
                                                            value="<?php echo esc_html( $group[$field_key]["timestamp"] ?? '' ) ?>" >
                                                    <div class="input-group-button">
                                                        <button id="<?php echo esc_html( $field_key ) ?>-clear-button" class="button alert clear-date-button" data-inputid="<?php echo esc_html( $field_key ) ?>" title="Delete Date">x</button>
                                                    </div>
                                                </div>
                                            <?php endif;
                                        }
                                    }
                                    ?>
                                </div>
                            </section>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </main> <!-- end #main -->

        <aside class="auto cell grid-x">
            <section class="comment-activity-section cell"
                     id="comment-activity-section">
                <?php get_template_part( 'dt-assets/parts/loop', 'activity-comment' ); ?>
            </section>
        </aside>

    </div> <!-- end #inner-content -->

    <div id="modal-influence" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="message-modal-influence"></p>
        </div>
    </div>

</div> <!-- end #content -->

<script type="application/javascript">

    var canvas = document.getElementById('canvas-church-metrics');
    let group = wpApiGroupsSettings.group
    let groupId = group.ID
    var progressCircleBackground = <?php echo json_encode($group_fields["health_metrics"]); ?>;
    var inputInfluenceValue = <?php echo json_encode(get_post_meta($group['ID'], 'influence')) ?>;
    var progressCircleTemplates = <?php echo json_encode($progressCircleTemplates); ?>;
    var progressCircleOptionsActive = <?php echo json_encode($group_fields["health_metrics"]["default"]); ?>;
    var iconsActive = <?php echo json_encode($results); ?>;
    var selected_group = <?php echo json_encode($group); ?>;
    var countCircleOptionsActive = Object.keys(progressCircleOptionsActive).length

    if(canvas){

        var context = canvas.getContext('2d');
        var centerX = canvas.width / 2;
        var centerY = canvas.height / 2;
        var radius = 200;
        var canvasIcons = []
    
        switch(countCircleOptionsActive){
    
            case 5:
                applyTemplate(progressCircleTemplates["iconTemplate5"])
            break;
            case 6:
                applyTemplate(progressCircleTemplates["iconTemplate6"])
            break;
            case 7:
                applyTemplate(progressCircleTemplates["iconTemplate7"])
            break;
            case 8:
                applyTemplate(progressCircleTemplates["iconTemplate8"])
            break;
            case 9:
                applyTemplate(progressCircleTemplates["iconTemplate9"])
            break;
            case 10:
                applyTemplate(progressCircleTemplates["iconTemplate10"])
            break;
            case 11:
                applyTemplate(progressCircleTemplates["iconTemplate11"])
            break;
            case 12:
                applyTemplate(progressCircleTemplates["iconTemplate12"])
            break;
        }

        canvas.addEventListener('mousedown', function(e) {
            getCursorPosition(canvas, e)
        })
    }

    function applyTemplate (template) {

        var index = 0

        for (var key in progressCircleOptionsActive) {
            
            var option = progressCircleOptionsActive[key]
            var icon = template[index]
            var imagePath = null

            if(option.image){
                if(option.image.includes("dt-assets")){
                    imagePath = '<?php echo get_template_directory_uri(); ?>';
                } else {

                    path = '<?php echo get_template_directory_uri(); ?>'
                    arrayUrlImages = path.split('/themes/disciple-tools-theme-edited')

                    imagePath = arrayUrlImages[0] + '/uploads'
                }
            }

            icon.id = key
            icon.label = option.label
            icon.imageUrl = imagePath ? imagePath + option.image : ""
            canvasIcons.push(icon)
            index ++
            imagePath = null
        }

        index = 0
        drawProgressCircle()
    }

    function drawProgressCircle () {

        var churchCommitmentActive = false

        iconsActive.forEach((elem, key) => {
            if(elem.meta_value == "church_commitment"){
                churchCommitmentActive = true
            }
        });

        context.beginPath();
        if (!churchCommitmentActive) {
            context.setLineDash([10, 10]);
        } else {
            context.setLineDash([]);
        }
        context.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        context.strokeStyle = progressCircleBackground["border"];
        context.lineWidth = 2;
        context.fillStyle = progressCircleBackground["background"];
        context.globalAlpha = 1;
        context.fill();
        context.stroke();

        canvasIcons.forEach((element, key) => {
            var icon = canvas.getContext('2d');
            var label = canvas.getContext('2d');
            var img = new Image;
            var iconIsActive = findIconInArray(element.id)

            icon.id = element.id
            img.onload = function () {

                var imgWidth = img.width
                var imgHeight = img.height

                labelArray = element.label.split(' ')
                label.globalAlpha = iconIsActive ? 1 : element.globalAlpha
                label.fillStyle = progressCircleBackground["label"]

                if (labelArray[0].length > 5 && labelArray.length > 1) {

                    var labelWidth = labelArray[0].length * 4
                    var labelTwo = labelArray[2] ? labelArray[1] + ' ' + labelArray[2] : labelArray[1]
                    var labelTwoWidth = labelTwo.length * 4

                    if (labelWidth > imgWidth) {

                        var diferent = labelWidth - imgWidth

                        label.fillText(labelArray[0], (element.x - diferent), element.y + (imgHeight + 10));
                    } else {

                        var diferent = (imgWidth - labelWidth)

                        label.fillText(labelArray[0], element.x + Math.round(diferent / 3), element.y + (imgHeight + 10));
                    }

                    if (labelTwoWidth > imgWidth) {

                        var diferent = labelTwoWidth - imgWidth

                        label.fillText(labelTwo, (element.x - diferent), (element.y + 10) + (imgHeight + 10));
                    } else {

                        var diferent = (imgWidth - labelTwoWidth)

                        label.fillText(labelTwo, element.x + Math.round(diferent / 3), (element.y + 10) + (imgHeight + 10));
                    }

                    
                } else {

                    var labelWidth = element.label.length * 4

                    if (labelWidth > imgWidth) {

                        var diferent = labelWidth - imgWidth

                        label.fillText(element.label, (element.x - diferent), element.y + (imgHeight + 10));
                    } else {

                        var diferent = (imgWidth - labelWidth)

                        label.fillText(element.label, element.x + Math.round(diferent / 3), element.y + (imgHeight + 10));
                    }

                }

                icon.globalAlpha = iconIsActive ? 1 : element.globalAlpha
                element.globalAlpha = iconIsActive ? 1 : element.globalAlpha
                icon.drawImage(img, element.x, element.y);
            }
            img.src = element.imageUrl;
        });
    }

    function findIconInArray (key){

        var iconFined = false

        iconsActive.forEach(element => {
            if(!iconFined){
                if(key == element.meta_value) {
                    iconFined = true
                }
            }
        });

        return iconFined;
    }

    function getCursorPosition(canvas, event) {

        const rect = canvas.getBoundingClientRect()
        const xAxis = event.clientX - rect.left
        const yAxis = event.clientY - rect.top
        var contextIcon = null

        canvasIcons.forEach(element => {

            if (!contextIcon) {
                if ((xAxis >= element.x && xAxis <= element.x + 40) && (yAxis >= element.y && yAxis <= element.y + 40)){

                    contextIcon = element
                    var contextIconCopy = contextIcon
                    
                    let fieldId = element.id
                    $("#"+fieldId).css('opacity', ".6");
                    let already_set = _.get(group, `health_metrics`, []).includes(fieldId)
                    let update = {values:[{value:fieldId}]}

                    if ( already_set ){
                        update.values[0].delete = true;
                    }

                    API.update_post( 'groups', groupId, {"health_metrics": update }).then(groupData => {

                        group = groupData

                        iconsActive = []

                        if(group.health_metrics){
                            group.health_metrics.forEach(metrics => {
                                iconsActive.push({meta_value: metrics})
                            });
                        }

                        context.clearRect(0, 0, canvas.width, canvas.height);

                        var churchCommitmentActive = false

                        iconsActive.forEach((elem, key) => {
                            if(elem.meta_value == "church_commitment"){
                                churchCommitmentActive = true
                            }
                        });

                        context.beginPath();
                        if (!churchCommitmentActive) {
                            context.setLineDash([10, 10]);
                        } else {
                            context.setLineDash([]);
                        }
                        context.arc(centerX, centerY, radius, 0, 2 * Math.PI);
                        context.strokeStyle = progressCircleBackground["border"];
                        context.lineWidth = 2;
                        context.fillStyle = progressCircleBackground["background"];
                        context.globalAlpha = 1;
                        context.fill();
                        context.stroke();

                        canvasIcons.forEach((element, key) => {
                            
                            var icon = canvas.getContext('2d');
                            var label = canvas.getContext('2d');
                            var img = new Image;
                            var iconIsActive = findIconInArray(element.id)

                            icon.id = element.id

                            if (icon.id == contextIconCopy.id) {
                                if(iconIsActive && contextIconCopy.globalAlpha == 1){
                                    iconIsActive = false

                                } else if(!iconIsActive && contextIconCopy.globalAlpha == 0.3) {
                                    iconIsActive = true
                                }
                            }

                            img.onload = function () {

                                var imgWidth = img.width
                                var imgHeight = img.height

                                labelArray = element.label.split(' ')
                                label.globalAlpha = iconIsActive ? 1 : 0.3
                                label.fillStyle = progressCircleBackground["label"];

                                // VALIDATION IF LABEL HAVE SPACES

                                if (labelArray[0].length > 5 && labelArray.length > 1) {

                                    var labelWidth = labelArray[0].length * 4
                                    var labelTwo = labelArray[2] ? labelArray[1] + ' ' + labelArray[2] : labelArray[1]
                                    var labelTwoWidth = labelTwo.length * 4

                                    // LOGIC FOR LABEL ONE

                                    if (labelWidth > imgWidth) {

                                        var diferent = labelWidth - imgWidth

                                        label.fillText(labelArray[0], (element.x - diferent), element.y + (imgHeight + 10));
                                    } else {

                                        var diferent = (imgWidth - labelWidth)

                                        label.fillText(labelArray[0], element.x + Math.round(diferent / 3), element.y + (imgHeight + 10));
                                    }

                                    // LOGIC FOR LABEL TWO

                                    if (labelTwoWidth > imgWidth) {

                                        var diferent = labelTwoWidth - imgWidth

                                        label.fillText(labelTwo, (element.x - diferent), (element.y + 10) + (imgHeight + 10));
                                    } else {

                                        var diferent = (imgWidth - labelTwoWidth)

                                        label.fillText(labelTwo, element.x + Math.round(diferent / 3), (element.y + 10) + (imgHeight + 10));
                                    }

                                    
                                } else {

                                    // LOGIC FOR LABEL

                                    var labelWidth = element.label.length * 4

                                    if (labelWidth > imgWidth) {

                                        var diferent = labelWidth - imgWidth

                                        label.fillText(element.label, (element.x - diferent), element.y + (imgHeight + 10));
                                    } else {

                                        var diferent = (imgWidth - labelWidth)

                                        label.fillText(element.label, element.x + Math.round(diferent / 3), element.y + (imgHeight + 10));
                                    }

                                }

                                icon.globalAlpha = iconIsActive ? 1 : 0.3
                                element.globalAlpha = iconIsActive ? 1 : 0.3
                                icon.drawImage(img, element.x, element.y);
                            }
                            img.src = element.imageUrl;
                        });

                    }).catch(err=>{
                        console.log(err)
                    })


                }
            } else {
                contextIcon = null
            }
        });

    }


    /* LOGIC FOR INFLUENCE */

    let sliderInfluence = document.getElementById('slider-influence'),
    influenceNumber = document.getElementById("influence-number"),
    modalInfluence = document.getElementById("modal-influence"),
    messageModalInfluence = document.getElementById("message-modal-influence")

    if(sliderInfluence){

        sliderInfluence.value = inputInfluenceValue =! null ? inputInfluenceValue[0] : 0
        influenceNumber.innerHTML = sliderInfluence.value;
        setColorToSlider(false)
    
        sliderInfluence.addEventListener('input', function () {
            setColorToSlider(true)
            influenceNumber.innerHTML = sliderInfluence.value
        }, false);
    }

    function setColorToSlider (showModal) {
        if(sliderInfluence.value > 99){
            sliderInfluence.style.background = '#027500'
            sliderInfluence.style.border = 'solid 1px #027500'
            if(showModal){
                messageModalInfluence.innerHTML = "COMPLETE INFLUENCE"
                modalInfluence.style.display = "block";
            }
        } else if (sliderInfluence.value < 1) {
            sliderInfluence.style.background = '#750000'
            sliderInfluence.style.border = 'solid 1px #750000'
            if(showModal){
                messageModalInfluence.innerHTML = "NO INFLUENCE"
                modalInfluence.style.display = "block";
            }
        } else {
            sliderInfluence.style.background = '#82CFD0'
            sliderInfluence.style.border = 'solid 1px #82CFD0'
        }
    }

    function onChangeSlider(value) {
        API.create_or_update_influence( 'groups', groupId, {"influence": value }).then(data => {
            console.log(data)
        }).catch(err=>{
            console.log(err)
        })
    }

    // When the user clicks on <span> (x), close the modal
    document.getElementsByClassName("close")[0].onclick = function() {
        modalInfluence.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modalInfluence) {
            modalInfluence.style.display = "none";
        }
    }


</script>

<!--    Modals-->
    <?php get_template_part( 'dt-assets/parts/modals/modal', 'share' ); ?>
    <?php get_template_part( 'dt-assets/parts/modals/modal', 'new-group' ); ?>
    <?php get_template_part( 'dt-assets/parts/modals/modal', 'new-contact' ); ?>
    <?php get_template_part( 'dt-assets/parts/modals/modal', 'tasks' ); ?>

    <div class="reveal" id="add-new-group-member-modal" data-reveal style="min-height:500px">
        <h3><?php echo esc_html_x( "Add members from existing contacts", 'Add members modal', 'disciple_tools' )?></h3>
        <p><?php echo esc_html_x( "In the 'Member List' field, type the name of an existing contact to add them to this group.", 'Add members modal', 'disciple_tools' )?></p>
        <div class="section-subheader"><?php esc_html_e( "Member List", 'disciple_tools' ) ?></div>
        <div class="members">
            <var id="members-result-container" class="result-container"></var>
            <div id="members_t" name="form-members" class="scrollable-typeahead typeahead-margin-when-active">
                <div class="typeahead__container">
                    <div class="typeahead__field">
                        <span class="typeahead__query">
                            <input class="js-typeahead-members"
                                   name="members[query]"
                                   placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), Disciple_Tools_Contact_Post_Type::instance()->plural ) )?>"
                                   autocomplete="off">
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid-x pin-to-bottom">
            <div class="cell">
                <hr size="1px">
                <span style="float:right; bottom: 0;">
                    <button class="button" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Close', 'disciple_tools' )?>
                    </button>
                </span>
            </div>
        </div>
        <button class="close-button" data-close aria-label="Close modal" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="reveal" id="create-tag-modal" data-reveal data-reset-on-close>

        <p class="lead"><?php esc_html_e( 'Create Tag', 'disciple_tools' )?></p>

        <form class="js-create-tag">
            <label for="title">
                <?php esc_html_e( "Tag", "disciple_tools" ); ?>
            </label>
            <input name="title" id="new-tag" type="text" placeholder="<?php esc_html_e( "tag", "disciple_tools" ); ?>" required aria-describedby="name-help-text">
            <p class="help-text" id="name-help-text"><?php esc_html_e( "This is required", "disciple_tools" ); ?></p>
        </form>

        <div class="grid-x">
            <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                <?php esc_html_e( 'Cancel', 'disciple_tools' )?>
            </button>
            <button class="button" data-close type="button" id="create-tag-return">
                <?php esc_html_e( 'Create and apply tag', 'disciple_tools' ); ?>
            </button>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>

    <?php
} )();

get_footer();
