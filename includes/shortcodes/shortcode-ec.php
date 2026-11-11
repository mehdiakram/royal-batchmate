<?php

/**
 * ===================================================================
 * Executive Committee (EC) Shortcode Handler
 * ===================================================================
 */
function batchmate_ec_shortcode_handler( $atts ) {
    global $wpdb;

    // Parse shortcode attributes
    $atts = shortcode_atts( array(
        'session'    => 0,       // 0 means current active committee
        'style'      => 'photo', // Default style
        'show_title' => 'no'     // Default is 'no'. Set 'yes' to show the title.
    ), $atts, 'ec' );

    $committee_id = intval( $atts['session'] );
    $style = $atts['style'];
    $show_title = $atts['show_title']; 

    // Table names
    $committee_table = $wpdb->prefix . 'batchmate_committee';
    $ec_table = $wpdb->prefix . 'batchmate_ec';
    $member_table = $wpdb->prefix . 'batchmate_member';
    $post_table = $wpdb->prefix . 'batchmate_ec_post_title';
    $dept_table = $wpdb->prefix . 'batchmate_department';
    $prog_table = $wpdb->prefix . 'batchmate_program';

    // 1. Find Committee (by ID or active)
    $committee = null;
    if ( $committee_id === 0 ) {
        $committee = $wpdb->get_row( "SELECT id, session_year FROM $committee_table WHERE is_active = 1 LIMIT 1" );
    } else {
        $committee = $wpdb->get_row( $wpdb->prepare( "SELECT id, session_year FROM $committee_table WHERE id = %d", $committee_id ) );
    }

    if ( ! $committee ) {
        return '';
    }

    $session_title = $committee->session_year;
    $committee_id = $committee->id; 

    // 2. Fetch Committee Members Data
    $members = $wpdb->get_results( $wpdb->prepare(
        "SELECT
            m.member_number, 
            m.name, m.email, m.cell_number, m.photo_url, m.present_district,
            p.post_title, p.position_order, p.position_row
        FROM %i AS ec
        LEFT JOIN %i AS m ON ec.member_id = m.id
        LEFT JOIN %i AS p ON ec.post_id = p.id
        WHERE ec.committee_id = %d
        ORDER BY p.position_order ASC, m.name ASC", 
        $ec_table, $member_table, $post_table, $committee_id
    ) );

    if ( empty( $members ) ) {
        if ( $show_title === 'yes' ) {
            return '<h2>Executive Committee ' . esc_html( $session_title ) . '</h2><p>No members found for this committee.</p>';
        }
        return '';
    }

    // 3. Generate HTML Output
    ob_start();
    ?>
    
    <div class="BatchMate-ec-wrapper">
        
        <?php if ( $show_title === 'yes' ) : ?>
            <h2>Executive Committee <?php echo esc_html( $session_title ); ?></h2>
        <?php endif; ?>

        <?php if ( $style === 'list' ) : ?>
            
            <table class="BatchMate-ec-list-table">
                <thead>
                    <tr>
                        <th>SI No.</th>
                        <th>Name</th>
                        <th>District</th>
                        <th>Position</th>
                        <th>Email</th>
                        <th>Cell No.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $si = 1; ?>
                    <?php foreach ( $members as $member ) : 
                        $profile_url = home_url( '/member/' . strtolower( $member->member_number ) . '/' );
                    ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $si++; ?></td>
                            <td>
                                <a href="<?php echo esc_url( $profile_url ); ?>" style="text-decoration: none; color: inherit; font-weight: bold;">
                                    <?php echo esc_html( $member->name ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( $member->present_district ); ?></td>
                            <td><?php echo esc_html( $member->post_title ); ?></td>
                            <td><?php echo esc_html( $member->email ); ?></td>
                            <td><?php echo esc_html( $member->cell_number ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else : ?>

            <?php 
            $default_photo = get_bloginfo('template_directory') . '/images/noprofile.png';
            
            // Group members by row
            $grouped_members = [];
            foreach ( $members as $member ) {
                $row_key = intval($member->position_row);
                if ( ! isset( $grouped_members[ $row_key ] ) ) {
                    $grouped_members[ $row_key ] = [];
                }
                $grouped_members[ $row_key ][] = $member;
            }
            ksort( $grouped_members, SORT_NUMERIC ); 
            ?>

            <div class="BatchMate-ec-photo-grid">
                <?php foreach ( $grouped_members as $row_members ) : ?>
                    <div class="BatchMate-ec-photo-row">
                        <?php foreach ( $row_members as $member ) : ?>
                            <?php
                            $photo_url = $member->photo_url ? esc_url( $member->photo_url ) : $default_photo;
                            $profile_url = home_url( '/member/' . strtolower( $member->member_number ) . '/' );
                            ?>
                            <div class="BatchMate-ec-member-card">
                                <a href="<?php echo esc_url( $profile_url ); ?>">
                                    <img src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr( $member->name ); ?>">
                                </a>
                                <h3>
                                    <a href="<?php echo esc_url( $profile_url ); ?>" style="text-decoration: none; color: inherit;">
                                        <?php echo esc_html( $member->name ); ?>
                                    </a>
                                </h3>
                                <p class="BatchMate-ec-position"><?php echo esc_html( $member->post_title ); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
}

add_action('init', function() {
    add_shortcode('ec', 'batchmate_ec_shortcode_handler');
});