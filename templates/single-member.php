<?php
/**
 * The template for displaying a single member profile page.
 * LinkedIn-style layout with sidebar, sections, and Related Batchmates.
 */

global $wpdb;
$member_number = get_query_var('member_number');
$member = null;

if ( ! empty( $member_number ) ) {
    $member_number = strtoupper( urldecode( $member_number ) );

    $member_table = $wpdb->prefix . 'batchmate_member';
    $dept_table   = $wpdb->prefix . 'batchmate_department';
    $prog_table   = $wpdb->prefix . 'batchmate_program';

    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT m.* 
         FROM %i m
         WHERE m.member_number = %s",
         $member_table, $member_number
    ) );
}

if ( $member ) {
    $BatchMate_settings = get_option( 'batchmate_settings', [] );
    add_filter( 'document_title_parts', function( $title_parts ) use ( $member, $BatchMate_settings ) {
        $org_name = ! empty( $BatchMate_settings['org_name'] ) ? $BatchMate_settings['org_name'] : 'BatchMate';
        $title_parts['title'] = esc_html( $member->name );
        $title_parts['site'] = esc_html( $org_name );
        return $title_parts;
    }, 10, 1 );
}

get_header();

// EC tables
$ec_table   = $wpdb->prefix . 'batchmate_ec';
$comm_table = $wpdb->prefix . 'batchmate_committee';
$post_table = $wpdb->prefix . 'batchmate_ec_post_title';

// Settings: banner + org info
$BatchMate_settings = get_option( 'batchmate_settings', [] );
$default_banner = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/banner.jpg';
$banner_url     = ! empty( $BatchMate_settings['member_profile_banner_url'] )
                  ? $BatchMate_settings['member_profile_banner_url']
                  : $default_banner;
$no_photo       = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/noprofile.png';
?>

<div id="welcome">
    <div class="container">
        <div class="rightcontainer">

<?php if ( $member ) :

    /* Derived data */
    $photo_url   = ! empty( $member->photo_url ) ? $member->photo_url : $no_photo;
    $full_name   = esc_html( $member->name );
    $skills_arr  = [];
    if ( ! empty( $member->skills_json ) ) {
        $decoded = json_decode( $member->skills_json, true );
        if ( is_array( $decoded ) ) $skills_arr = $decoded;
    }

    /* Current EC position (latest) */
    $current_ec = $wpdb->get_row( $wpdb->prepare(
        "SELECT c.session_year, p.post_title, c.is_active
         FROM {$ec_table} AS ec
         LEFT JOIN {$comm_table} AS c ON ec.committee_id = c.id
         LEFT JOIN {$post_table} AS p ON ec.post_id = p.id
         WHERE ec.member_id = %d
         ORDER BY c.session_year DESC LIMIT 1",
        $member->id
    ) );

    /* All EC memberships */
    $ec_memberships = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.session_year, p.post_title, c.is_active
         FROM {$ec_table} AS ec
         LEFT JOIN {$comm_table} AS c ON ec.committee_id = c.id
         LEFT JOIN {$post_table} AS p ON ec.post_id = p.id
         WHERE ec.member_id = %d
         ORDER BY c.session_year DESC, p.position_order ASC",
        $member->id
    ) );

    /* Related Batchmates (max 4 each) */
    $base_select = "SELECT m.id, m.name, m.member_number, m.photo_url, m.employment_position, m.organization, m.batch, m.institution
                    FROM {$member_table} AS m";

    $related_batch = $related_dept = $related_prof = $related_nearby = [];

    $related_group = [];
    if ( ! empty( $member->edu_group ) ) {
        $related_group = $wpdb->get_results( $wpdb->prepare(
            "$base_select WHERE m.edu_group = %s AND m.id != %d ORDER BY RAND() LIMIT 8",
            $member->edu_group, $member->id
        ) );
    }
    if ( ! empty( $member->industry ) || ! empty( $member->employment_position ) ) {
        $prof_conditions = [];
        $prof_values = [];
        if ( ! empty( $member->industry ) ) {
            $prof_conditions[] = "m.industry = %s";
            $prof_values[] = $member->industry;
        }
        if ( ! empty( $member->employment_position ) ) {
            $prof_conditions[] = "m.employment_position = %s";
            $prof_values[] = $member->employment_position;
        }
        $prof_values[] = $member->id;
        
        $prof_where = implode( " OR ", $prof_conditions );
        $related_prof = $wpdb->get_results( $wpdb->prepare(
            "$base_select WHERE ($prof_where) AND m.id != %d ORDER BY RAND() LIMIT 8",
            ...$prof_values
        ) );
    }
    $member_country = ! empty( $member->present_country ) ? $member->present_country : 'Bangladesh';
    if ( $member_country === 'Bangladesh' && ! empty( $member->present_district ) ) {
        // Bangladeshi: match same district
        $related_nearby = $wpdb->get_results( $wpdb->prepare(
            "$base_select WHERE m.present_district = %s AND m.id != %d ORDER BY RAND() LIMIT 8",
            $member->present_district, $member->id
        ) );
    } elseif ( $member_country !== 'Bangladesh' ) {
        // Foreign/abroad: match same country
        $related_nearby = $wpdb->get_results( $wpdb->prepare(
            "$base_select WHERE m.present_country = %s AND m.id != %d ORDER BY RAND() LIMIT 8",
            $member_country, $member->id
        ) );
    }
?>

        </div><!-- /.rightcontainer -->
    </div><!-- /.container -->

    <!-- COVER BANNER (FULL WIDTH) -->
    <div class="mp-cover" style="background-image:url(<?php echo esc_url( $banner_url ); ?>);">
        <?php 
        $raw_tagline = ! empty( $BatchMate_settings['org_tagline'] ) ? $BatchMate_settings['org_tagline'] : '';
        if ( $raw_tagline ) : 
            // Try to add span to the second line if <br> exists, to keep the nice styling
            $formatted_tagline = preg_replace('/(<br\s*\/?>)\s*(.*)/i', '$1<span>$2</span>', $raw_tagline);
        ?>
        <div class="mp-cover-tagline">
            <?php echo wp_kses_post( $formatted_tagline ); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="rightcontainer">
            <!-- MAIN PROFILE LAYOUT -->
            <div class="mp-layout">

    <!-- LEFT SIDEBAR -->
    <aside class="mp-sidebar">
        <div class="mp-sidebar-card">

            <div class="mp-avatar-wrap">
                <img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo $full_name; ?>" class="mp-avatar">
            </div>

            <div class="mp-sidebar-info">
                <h1 class="mp-name"><?php echo $full_name; ?></h1>

                <?php if ( $current_ec ) : ?>
                <p class="mp-title"><?php echo esc_html( $current_ec->post_title ); ?></p>
                <p class="mp-org-sub"><?php echo batchmate_org_label( 'short' ); ?></p>
                <?php if ( $current_ec->session_year ) : ?>
                <p class="mp-session">Executive Committee <?php echo esc_html( $current_ec->session_year ); ?></p>
                <?php endif; ?>
                <?php elseif ( ! empty( $member->employment_position ) ) : ?>
                <p class="mp-title"><?php echo esc_html( $member->employment_position ); ?></p>
                <?php endif; ?>

                <div class="mp-quick-info">
                    <?php if ( ! empty( $member->program_name ) ) : ?>
                    <div class="mp-qi-item">
                        <span class="mp-qi-icon">🎓</span>
                        <span><?php echo esc_html( $member->program_name ); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $member->batch ) ) : ?>
                    <div class="mp-qi-item">
                        <span class="mp-qi-icon">🗓️</span>
                        <span>Batch <?php echo esc_html( $member->batch ); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $member->dept_name ) ) : ?>
                    <div class="mp-qi-item">
                        <span class="mp-qi-icon">🏛️</span>
                        <span><?php echo esc_html( $member->dept_name ); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php
                    $disp_country = ! empty( $member->present_country ) ? $member->present_country : 'Bangladesh';
                    if ( ! empty( $member->present_district ) && $disp_country === 'Bangladesh' ) : ?>
                    <div class="mp-qi-item">
                        <span class="mp-qi-icon">📍</span>
                        <span><?php echo esc_html( $member->present_district ); ?>, Bangladesh</span>
                    </div>
                    <?php elseif ( $disp_country !== 'Bangladesh' ) : ?>
                    <div class="mp-qi-item">
                        <span class="mp-qi-icon">📍</span>
                        <span><?php echo esc_html( $disp_country ); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php
            $has_social = ! empty( $member->facebook_url ) || ! empty( $member->linkedin_url )
                       || ! empty( $member->x_url );
            if ( $has_social ) :
            ?>
            <div class="mp-social-icons">
                <?php if ( ! empty( $member->facebook_url ) ) : ?>
                    <a href="<?php echo esc_url( $member->facebook_url ); ?>" target="_blank" rel="noopener" title="Facebook">
                        <i class="royalicon-facebook-circled"></i>
                    </a>
                <?php endif; ?>
                <?php if ( ! empty( $member->linkedin_url ) ) : ?>
                    <a href="<?php echo esc_url( $member->linkedin_url ); ?>" target="_blank" rel="noopener" title="LinkedIn">
                        <i class="royalicon-linkedin-squared"></i>
                    </a>
                <?php endif; ?>
                <?php if ( ! empty( $member->x_url ) ) : ?>
                    <a href="<?php echo esc_url( $member->x_url ); ?>" target="_blank" rel="noopener" title="X / Twitter">
                        <i class="royalicon-twitter"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div><!-- /.mp-sidebar-card -->

        <!-- SIDEBAR NAVIGATION -->
        <div class="mp-nav-card">
            <ul class="mp-sidebar-nav">
                <li><a href="#section-overview" class="active"><i class="dashicons dashicons-admin-home"></i> Overview</a></li>
                <li><a href="#section-academic"><i class="dashicons dashicons-welcome-learn-more"></i> Academic Information</a></li>
                <li><a href="#section-professional-full"><i class="dashicons dashicons-portfolio"></i> Professional Information</a></li>
                <?php if ( $ec_memberships ) : ?>
                <li><a href="#section-leadership"><i class="dashicons dashicons-groups"></i> Leadership & Activities</a></li>
                <?php endif; ?>
                <li><a href="#section-personal"><i class="dashicons dashicons-admin-users"></i> Personal Information</a></li>
                <?php if ( ! empty( $skills_arr ) ) : ?>
                <li><a href="#section-skills"><i class="dashicons dashicons-admin-tools"></i> Skills & Expertise</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </aside><!-- /.mp-sidebar -->

    <!-- MAIN CONTENT -->
    <main class="mp-main">

        <!-- About Me -->
        <?php if ( ! empty( $member->about_me ) ) : ?>
        <div class="mp-card">
            <div class="mp-card-head">
                <span class="mp-card-icon">👤</span>
                <h2>About Me</h2>
            </div>
            <div class="mp-card-body">
                <p class="mp-about-text"><?php echo nl2br( esc_html( $member->about_me ) ); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Current Professional Role + Batchmate Status (two columns) -->
        <div class="mp-two-col">

            <!-- Current Role (snapshot) -->
            <div class="mp-card" id="section-professional">
                <div class="mp-card-head">
                    <span class="mp-card-icon">💼</span>
                    <h2>Current Role</h2>
                </div>
                <div class="mp-card-body">
                    <table class="mp-info-table">
                        <tr>
                            <td>Position</td><td>:</td>
                            <td><?php echo ! empty( $member->employment_position ) ? esc_html( $member->employment_position ) : '—'; ?></td>
                        </tr>
                        <tr>
                            <td>Organization</td><td>:</td>
                            <td><?php echo ! empty( $member->organization ) ? esc_html( $member->organization ) : '—'; ?></td>
                        </tr>
                        <?php if ( ! empty( $member->website ) ) : ?>
                        <tr>
                            <td>Website</td><td>:</td>
                            <td><a href="<?php echo esc_url( $member->website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $member->website ); ?></a></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Batchmate Status -->
            <div class="mp-card" id="section-overview">
                <div class="mp-card-head">
                    <span class="mp-card-icon">👥</span>
                    <h2><?php echo batchmate_get_label('batchmate'); ?> Status</h2>
                </div>
                <div class="mp-card-body">
                    <table class="mp-info-table">
                        <tr>
                            <td><?php echo batchmate_get_label('member'); ?> ID</td><td>:</td>
                            <td><strong class="mp-member-id"><?php echo esc_html( $member->member_number ); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Association Joined</td><td>:</td>
                            <td><?php echo ( $member->joining_date && $member->joining_date !== '0000-00-00 00:00:00' ) ? esc_html( date( 'Y', strtotime( $member->joining_date ) ) ) : '—'; ?></td>
                        </tr>
                        <?php if ( $current_ec ) : ?>
                        <tr>
                            <td>Current Position</td><td>:</td>
                            <td><strong><?php echo esc_html( $current_ec->post_title ); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Session</td><td>:</td>
                            <td><?php echo esc_html( $current_ec->session_year ); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><?php echo batchmate_get_label('member'); ?> Since</td><td>:</td>
                            <td><?php echo ( $member->joining_date && $member->joining_date !== '0000-00-00 00:00:00' ) ? esc_html( date( 'F Y', strtotime( $member->joining_date ) ) ) : '—'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div><!-- /.mp-two-col -->

        <!-- Academic Information -->
        <div class="mp-card" id="section-academic">
            <div class="mp-card-head">
                <span class="mp-card-icon">🎓</span>
                <h2>Academic Information</h2>
            </div>
            <div class="mp-card-body">
                <table class="mp-info-table">
                    <?php 
                    $ssc_roll = ! empty( $member->roll_no ) ? $member->roll_no : ( ! empty( $member->student_id ) ? $member->student_id : '' );
                    if ( ! empty( $ssc_roll ) ) : 
                    ?>
                    <tr><td>SSC Roll</td><td>:</td><td><?php echo esc_html( $ssc_roll ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->board ) ) : ?>
                    <tr><td>Name of Board</td><td>:</td><td><?php echo esc_html( ucfirst( $member->board ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->edu_group ) ) : ?>
                    <tr><td>Group</td><td>:</td><td><?php echo esc_html( ucfirst( $member->edu_group ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->institution ) ) : ?>
                    <tr><td><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_institution', 'Institute') : 'Institute' ); ?></td><td>:</td><td><?php echo esc_html( $member->institution ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( empty( $ssc_roll ) && empty( $member->board ) && empty( $member->edu_group ) && empty( $member->institution ) ) : ?>
                    <tr><td colspan="3" class="mp-empty">—</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>


        <!-- Professional Information (full details) -->
        <div class="mp-card" id="section-professional-full">
            <div class="mp-card-head">
                <span class="mp-card-icon">💼</span>
                <h2>Professional Information</h2>
            </div>
            <div class="mp-card-body">
                <table class="mp-info-table">
                    <tr>
                        <td>Current Position</td><td>:</td>
                        <td><?php echo ! empty( $member->employment_position ) ? esc_html( $member->employment_position ) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td>Organization</td><td>:</td>
                        <td><?php echo ! empty( $member->organization ) ? esc_html( $member->organization ) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td>Industry</td><td>:</td>
                        <td><?php echo ! empty( $member->industry ) ? esc_html( $member->industry ) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td>Work Experience</td><td>:</td>
                        <td><?php echo ! empty( $member->work_experience ) ? esc_html( $member->work_experience ) . ' Years' : '—'; ?></td>
                    </tr>
                    <tr>
                        <td>Previous Organizations</td><td>:</td>
                        <td><?php echo ! empty( $member->previous_organizations ) ? nl2br( esc_html( $member->previous_organizations ) ) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td>Website</td><td>:</td>
                        <td><?php echo ! empty( $member->website ) ? '<a href="' . esc_url( $member->website ) . '" target="_blank" rel="noopener">' . esc_html( $member->website ) . '</a>' : '—'; ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Leadership & Batchmate Activities -->
        <?php if ( ! empty( $ec_memberships ) ) : ?>
        <div class="mp-card" id="section-leadership">
            <div class="mp-card-head">
                <span class="mp-card-icon">👥</span>
                <h2>Leadership &amp; <?php echo batchmate_get_label('batchmate'); ?> Activities</h2>
            </div>
            <div class="mp-card-body">
                <div class="mp-timeline">
                    <?php foreach ( $ec_memberships as $i => $ec ) :
                        $is_active = ! empty( $ec->is_active );
                    ?>
                    <div class="mp-timeline-item <?php echo $is_active ? 'mp-tl-active' : ''; ?>">
                        <div class="mp-tl-dot <?php echo $is_active ? 'mp-tl-dot-active' : ''; ?>"></div>
                        <div class="mp-tl-content">
                            <span class="mp-tl-year"><?php echo esc_html( $ec->session_year ); ?></span>
                            <strong class="mp-tl-post"><?php echo esc_html( $ec->post_title ); ?></strong>
                            <span class="mp-tl-org">(Executive Committee)</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Skills & Expertise -->
        <!-- Personal Information -->
        <div class="mp-card" id="section-personal">
            <div class="mp-card-head">
                <span class="mp-card-icon">👤</span>
                <h2>Personal Information</h2>
            </div>
            <div class="mp-card-body">
                <table class="mp-info-table">
                    <?php if ( ! empty( $member->gender ) ) : ?>
                    <tr><td>Gender</td><td>:</td><td><?php echo esc_html( $member->gender ); ?></td></tr>
                    <?php endif; ?>

                    <?php if ( ! empty( $member->blood_group ) ) : ?>
                    <tr><td>Blood Group</td><td>:</td><td><?php echo esc_html( $member->blood_group ); ?></td></tr>
                    <?php endif; ?>

                    <?php if ( ! empty( $member->cell_number ) && ! empty( $member->cell_show ) ) : ?>
                    <tr><td>Cell Number</td><td>:</td><td><?php echo esc_html( $member->cell_number ); ?></td></tr>
                    <?php endif; ?>

                    <?php if ( ! empty( $member->land_phone_number ) ) : ?>
                    <tr><td>Alternative Number</td><td>:</td><td><?php echo esc_html( $member->land_phone_number ); ?></td></tr>
                    <?php endif; ?>

                    <?php if ( ! empty( $member->email ) ) : ?>
                    <tr><td>Email</td><td>:</td><td><a href="mailto:<?php echo esc_attr( $member->email ); ?>"><?php echo esc_html( $member->email ); ?></a></td></tr>
                    <?php endif; ?>

                    <?php if ( ! empty( $member->present_upazila ) ) : ?>
                    <tr><td><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_upazila', 'Present Thana / Upazila') : 'Present Thana / Upazila' ); ?></td><td>:</td><td><?php echo esc_html( $member->present_upazila ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->present_district ) ) : ?>
                    <tr><td><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_present_district', 'Present District') : 'Present District' ); ?></td><td>:</td><td><?php echo esc_html( $member->present_district ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->present_country ) && $member->present_country !== 'Bangladesh' ) : ?>
                    <tr><td>Present Country</td><td>:</td><td><?php echo esc_html( $member->present_country ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->present_address ) ) : ?>
                    <tr><td>Present Address</td><td>:</td><td><?php echo nl2br( esc_html( $member->present_address ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->upazila ) ) : ?>
                    <tr><td><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_upazila', 'Permanent Thana / Upazila') : 'Permanent Thana / Upazila' ); ?></td><td>:</td><td><?php echo esc_html( $member->upazila ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->permanent_district ) ) : ?>
                    <tr><td><?php echo esc_html( function_exists('batchmate_get_field_label') ? batchmate_get_field_label('reg_permanent_district', 'Permanent District') : 'Permanent District' ); ?></td><td>:</td><td><?php echo esc_html( $member->permanent_district ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->permanent_country ) && $member->permanent_country !== 'Bangladesh' ) : ?>
                    <tr><td>Permanent Country</td><td>:</td><td><?php echo esc_html( $member->permanent_country ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $member->permanent_address ) ) : ?>
                    <tr><td>Permanent Address</td><td>:</td><td><?php echo nl2br( esc_html( $member->permanent_address ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( empty( $member->gender ) && empty( $member->blood_group ) && empty( $member->present_address ) && empty( $member->permanent_address ) ) : ?>
                    <tr><td colspan="3" class="mp-empty">—</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Skills & Expertise -->
        <?php if ( ! empty( $skills_arr ) ) : ?>
        <div class="mp-card" id="section-skills">
            <div class="mp-card-head">
                <span class="mp-card-icon">⚙️</span>
                <h2>Skills &amp; Expertise</h2>
            </div>
            <div class="mp-card-body">
                <div class="mp-skills-wrap">
                    <?php foreach ( $skills_arr as $skill ) : ?>
                        <span class="mp-skill-tag"><?php echo esc_html( trim( $skill ) ); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main><!-- /.mp-main -->
</div><!-- /.mp-layout -->

<!-- Related Batchmates -->
<div class="mp-related-section">
    <div class="mp-related-header">
        <span class="mp-card-icon">👥</span>
        <h2>Related <?php echo batchmate_get_label('batchmates'); ?></h2>
        <div class="mp-related-tabs">
            <button class="mp-rtab mp-rtab-active" data-tab="prof">Same Profession</button>
            <button class="mp-rtab" data-tab="nearby">Nearby <?php echo batchmate_get_label('batchmates'); ?></button>
            <button class="mp-rtab" data-tab="group">Same Group</button>
        </div>
    </div>

    <?php
    $related_groups = [
        'prof'   => $related_prof,
        'nearby' => $related_nearby,
        'group'  => $related_group,
    ];
    foreach ( $related_groups as $group_key => $group_members ) :
    ?>
    <div class="mp-related-pane <?php echo $group_key === 'prof' ? 'mp-rpane-active' : ''; ?>" data-pane="<?php echo $group_key; ?>">
        <?php if ( ! empty( $group_members ) ) : ?>
        <div class="mp-carousel-wrap">
            <button class="mp-car-arrow mp-car-prev" aria-label="Previous">&#8249;</button>
            <div class="mp-related-grid" data-carousel>
            <?php foreach ( $group_members as $rel ) :
                $rel_photo = ! empty( $rel->photo_url ) ? $rel->photo_url : $no_photo;
                $rel_url   = home_url( '/member/' . strtolower( $rel->member_number ) . '/' );
            ?>
            <div class="mp-rel-card">
                <img src="<?php echo esc_url( $rel_photo ); ?>" alt="<?php echo esc_attr( $rel->name ); ?>" class="mp-rel-photo">
                <div class="mp-rel-info">
                    <strong class="mp-rel-name"><?php echo esc_html( $rel->name ); ?></strong>
                    <span class="mp-rel-meta"><?php if(!empty($rel->batch)) echo "Batch " . esc_html( $rel->batch ) . " | "; ?><?php echo esc_html( $rel->institution ); ?></span>
                    <?php if ( ! empty( $rel->employment_position ) ) : ?>
                    <span class="mp-rel-pos"><?php echo esc_html( $rel->employment_position ); ?></span>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $rel_url ); ?>" class="mp-rel-btn">View Profile</a>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <button class="mp-car-arrow mp-car-next" aria-label="Next">&#8250;</button>
        </div>
        <?php else : ?>
        <p class="mp-related-empty">No Related <?php echo batchmate_get_label('batchmates'); ?> found.</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

</div><!-- /.mp-related-section -->

<?php else : ?>
    <div class="singlepagepast memberpage">
        <h1 class="pagetitle"><?php echo batchmate_get_label('member'); ?> Not Found</h1>
        <p style="text-align:center; padding:50px;">The <?php echo strtolower(batchmate_get_label('member')); ?> profile '<strong><?php echo esc_html( $member_number ); ?></strong>' could not be found.</p>
        <p style="text-align:center;"><a href="<?php echo home_url('/members-list/'); ?>" class="button">Back to <?php echo batchmate_get_label('members'); ?> List</a></p>
    </div>
<?php endif; ?>

        </div><!-- /.rightcontainer -->
    </div><!-- /.container -->
</div><!-- /#welcome -->

<style>
/* MEMBER PROFILE PAGE  CSS (Mobile-first) */

@import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');

/* Outer wrapper spacing */
.memberpage-wrap { padding: 0 0 40px; }

/* Cover Banner */
.mp-cover {
    width: 100%;
    height: 300px;
    background: linear-gradient(135deg, #003d82 0%, #007cba 60%, #003d82 100%);
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    position: relative;
    border-radius: 0;
}

.mp-cover-tagline {
    position: absolute;
    top: 50%;
    right: 15px;
    font-family: 'Great Vibes', cursive;
    color: #fff;
    font-size: 18px; /* Smaller on mobile */
    line-height: 1.1;
    text-align: right;
    text-shadow: 1px 1px 4px rgba(0,0,0,0.6);
    transform: translateY(-50%) rotate(-3deg);
}
.mp-cover-tagline span {
    font-size: 1.3em;
    display: inline-block;
    padding-left: 15px;
}

/* MOBILE LAYOUT (default / < 768px)
   Avatar overlaps cover, full-width card */
.mp-layout { display: block; }

/* Mobile profile card  sits right after cover */
.mp-sidebar {
    width: 100%;
    margin-top: -50px;          /* pulls avatar up over cover */
    position: relative;
    z-index: 10;
}
.mp-sidebar-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.10);
    overflow: hidden;
    margin-bottom: 14px;
}

/* Avatar */
.mp-avatar-wrap {
    display: flex;
    justify-content: center;
    padding: 14px 0 6px;
}
.mp-avatar {
    width: 200px;
    height: 200px;
    border-radius: 16px;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 2px 14px rgba(0,0,0,.18);
    transition: border-radius 0.3s ease;
}
.mp-avatar:hover {
    border-radius: 50%;
}

/* Sidebar text info */
.mp-sidebar-info {
    padding: 4px 18px 16px;
    text-align: center;
}
.mp-name {
    font-size: 19px;
    font-weight: 700;
    color: #002d6b;
    margin: 4px 0 3px;
    line-height: 1.3;
}
.mp-title {
    font-size: 13px;
    font-weight: 600;
    color: #007cba;
    margin: 0 0 2px;
}
.mp-org-sub {
    font-size: 12px;
    color: #64748b;
    margin: 0 0 2px;
}
.mp-session {
    font-size: 12px;
    color: #94a3b8;
    margin: 0 0 10px;
}

/* Sidebar Nav */
.mp-nav-card {
    padding: 8px 0;
}
.mp-sidebar-nav {
    list-style: none;
    margin: 0;
    padding: 0;
}
.mp-sidebar-nav li {
    margin: 0;
    border-bottom: 1px solid #f1f5f9;
}
.mp-sidebar-nav li:last-child {
    border-bottom: none;
}
.mp-sidebar-nav a {
    display: flex;
    align-items: center;
    padding: 12px 18px;
    color: #475569;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}
.mp-sidebar-nav a i {
    margin-right: 12px;
    font-size: 18px;
    width: 20px;
    height: 20px;
    color: #64748b;
}
.mp-sidebar-nav a:hover,
.mp-sidebar-nav a.active {
    background: #f8fafc;
    color: #007cba;
    border-left: 3px solid #007cba;
    padding-left: 15px;
}
.mp-sidebar-nav a:hover i,
.mp-sidebar-nav a.active i {
    color: #007cba;
}

html {
    scroll-behavior: smooth;
}

/* Quick info chips on mobile */
.mp-quick-info {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: center;
    padding: 10px 14px 4px;
    border-top: 1px solid #f1f5f9;
}
.mp-qi-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #374151;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 4px 10px;
    line-height: 1.3;
    text-align: left;
}
.mp-qi-icon { font-size: 13px; flex-shrink: 0; }

/* Send Message button */
.mp-msg-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: calc(100% - 32px);
    margin: 12px 16px 14px;
    padding: 10px 14px;
    background: #007cba;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: not-allowed;
    opacity: 0.7;
    font-family: inherit;
}

/* Social icons */
.mp-social-icons {
    display: flex;
    justify-content: center;
    gap: 18px;
    padding: 12px 16px 14px;
    border-top: 1px solid #f1f5f9;
}
.mp-social-icons a {
    color: #007cba;
    font-size: 22px;
    transition: color .2s, transform .2s;
    display: inline-flex;
}
.mp-social-icons a:hover { color: #003d82; transform: translateY(-2px); }

/* Main content: full width on mobile, padding-top = 0 */
.mp-main { padding-top: 0; min-width: 0; }

/* Cards */
.mp-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
    margin-bottom: 12px;
    overflow: hidden;
}
.mp-card-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.mp-card-icon { font-size: 16px; line-height: 1; }
.mp-card-head h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #002d6b;
}
.mp-card-body { padding: 14px 16px; }

/* Two-col cards  single column on mobile */
.mp-two-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    margin-bottom: 12px;
}
.mp-two-col .mp-card { margin-bottom: 0; }

/* Info Table */
.mp-info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.mp-info-table tr {
    display: flex;
    flex-wrap: wrap;
    padding: 5px 0;
    border-bottom: 1px solid #f3f4f6;
}
.mp-info-table tr:last-child { border-bottom: none; }
.mp-info-table td {
    padding: 1px 3px;
    color: #374151;
    vertical-align: top;
}
/* Label: full width on mobile */
.mp-info-table td:first-child {
    flex: 0 0 100%;
    color: #6b7280;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
}
/* Colon: hidden on mobile */
.mp-info-table td:nth-child(2) {
    display: none;
}
/* Value: full width on mobile */
.mp-info-table td:nth-child(3) {
    flex: 0 0 100%;
}
.mp-empty { color: #9ca3af; font-style: italic; }
.mp-member-id { color: #007cba; font-size: 13px; letter-spacing: .5px; }

/* About Me */
.mp-about-text {
    font-size: 14px;
    color: #374151;
    line-height: 1.7;
    margin: 0;
}

/* Leadership Timeline */
.mp-timeline { position: relative; padding-left: 22px; }
.mp-timeline::before {
    content: '';
    position: absolute;
    left: 6px; top: 8px; bottom: 8px;
    width: 2px;
    background: #e2e8f0;
}
.mp-timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
    position: relative;
}
.mp-timeline-item:last-child { margin-bottom: 0; }
.mp-tl-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    background: #cbd5e1;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #cbd5e1;
    flex-shrink: 0;
    margin-left: -22px;
    margin-top: 3px;
    position: relative;
    z-index: 1;
}
.mp-tl-dot-active { background: #007cba; box-shadow: 0 0 0 2px #007cba; }
.mp-tl-content { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
.mp-tl-year {
    font-size: 11px; font-weight: 700; color: #007cba;
    background: #eff6ff; padding: 2px 8px; border-radius: 20px; white-space: nowrap;
}
.mp-tl-post { font-size: 13px; font-weight: 700; color: #002d6b; }
.mp-tl-org  { font-size: 12px; color: #6b7280; }
.mp-tl-active .mp-tl-post { color: #007cba; }

/* Skills */
.mp-skills-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
.mp-skill-tag {
    display: inline-block;
    padding: 5px 14px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 500;
}

/* Related Batchmates */
.mp-related-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
    margin-top: 16px;
    margin-bottom: 24px;
    overflow: visible;
}
.mp-related-header {
    padding: 12px 16px 0;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px 8px;
    border-radius: 12px 12px 0 0;
    overflow: hidden;
}
.mp-related-header h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #002d6b;
    flex: 1;
}
.mp-related-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    scrollbar-width: none;
    flex: 0 0 100%;
}
.mp-related-tabs::-webkit-scrollbar { display: none; }
.mp-rtab {
    padding: 8px 6px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    margin-bottom: -1px;
    transition: color .2s, border-color .2s;
    font-family: inherit;
    flex: 1 1 auto;
    min-width: 22%;
    text-align: center;
}
.mp-rtab:hover { color: #007cba; }
.mp-rtab-active { color: #007cba; border-bottom-color: #007cba; font-weight: 700; }
.mp-related-pane { display: none; padding: 14px; }
.mp-rpane-active { display: block; }
.mp-related-grid {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #e2e8f0 transparent;
    padding-bottom: 8px;
}
.mp-related-grid::-webkit-scrollbar { height: 4px; }
.mp-related-grid::-webkit-scrollbar-track { background: transparent; }
.mp-related-grid::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.mp-rel-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 10px;
    gap: 6px;
    transition: box-shadow .2s, transform .2s;
    min-width: 150px;
    flex-shrink: 0;
    scroll-snap-align: start;
}
.mp-rel-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); transform: translateY(-2px); }
.mp-rel-photo {
    width: 60px; height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
.mp-rel-info { display: flex; flex-direction: column; gap: 3px; width: 100%; }
.mp-rel-name { font-size: 12px; font-weight: 700; color: #002d6b; }
.mp-rel-meta { font-size: 10px; color: #6b7280; }
.mp-rel-pos  { font-size: 10px; color: #4b5563; }
.mp-rel-btn {
    display: inline-block;
    margin-top: 4px;
    padding: 4px 12px;
    background: #fff;
    border: 1.5px solid #007cba;
    color: #007cba;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s, color .2s;
}
.mp-rel-btn:hover { background: #007cba; color: #fff; }
.mp-related-empty { color: #9ca3af; font-style: italic; text-align: center; padding: 20px 0; margin: 0; }

/* Carousel */
.mp-carousel-wrap {
    position: relative;
    display: flex;
    align-items: center;
    gap: 4px;
}
.mp-car-arrow {
    flex-shrink: 0;
    width: 32px; height: 32px;
    border-radius: 50%;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    color: #007cba;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s, color .2s, border-color .2s;
    padding: 0;
}
.mp-car-arrow:hover { background: #007cba; color: #fff; border-color: #007cba; }
.mp-car-arrow:disabled { opacity: 0.3; cursor: default; }
.mp-car-arrow:disabled:hover { background: #fff; color: #007cba; border-color: #e2e8f0; }

/* TABLET ( 600px)  horizontal sidebar card */
@media (min-width: 600px) {
    .mp-cover { border-radius: 0 0 10px 10px; }
    .mp-cover-tagline { font-size: 24px; top: 50%; right: 20px; transform: translateY(-50%) rotate(-3deg); }
    .mp-cover-tagline span { padding-left: 20px; }
    .mp-sidebar { margin-top: -56px; }
    .mp-sidebar-card {
        display: flex;
        flex-direction: row;
        align-items: stretch;
    }
    .mp-avatar-wrap {
        padding: 20px 12px 16px 20px;
        flex-shrink: 0;
        align-items: flex-start;
        justify-content: flex-start;
    }
    .mp-avatar { width: 200px; height: 200px; }
    .mp-sidebar-info {
        flex: 1;
        text-align: left;
        padding: 16px 14px 14px 4px;
    }
    .mp-name { font-size: 18px; }
    .mp-quick-info {
        justify-content: flex-start;
        padding: 8px 0 0;
        border-top: none;
    }
    .mp-msg-btn {
        width: auto;
        margin: 10px 0 0;
        padding: 8px 18px;
        align-self: flex-start;
    }
    .mp-social-icons {
        flex-direction: column;
        justify-content: center;
        gap: 10px;
        padding: 16px 18px;
        border-top: none;
        border-left: 1px solid #f1f5f9;
    }
    .mp-related-header { flex-direction: row; flex-wrap: nowrap; gap: 12px; padding: 14px 18px 0; }
    .mp-related-header h2 { margin: 0; flex-shrink: 0; flex: unset; }
    .mp-related-tabs { margin-left: auto; flex: unset; width: auto; }
    .mp-rel-card { min-width: 180px; }
    .mp-card-body { padding: 16px 18px; }
    .mp-card-head { padding: 12px 18px; }
    .mp-related-tabs { flex-wrap: nowrap; flex: unset; width: auto; overflow-x: auto; }
    .mp-rtab { font-size: 13px; padding: 8px 16px; flex: unset; min-width: unset; white-space: nowrap; }
}

/* DESKTOP ( 900px)  two-column layout */
@media (min-width: 900px) {
    .mp-cover {border-radius: 0 0 12px 12px;}
    .mp-cover-tagline { font-size: 32px; top: 50%; right: 30px; transform: translateY(-50%) rotate(-3deg); }
    .mp-layout {
        display: flex;
        gap: 22px;
        margin-top: -65px;
        align-items: flex-start;
    }
    .mp-sidebar {
        width: 260px;
        flex-shrink: 0;
        margin-top: 0;
        align-self: flex-start;
        display: flex;
        flex-direction: column;
        gap: 14px;
        position: sticky;
        top: 20px;
    }
    .mp-sidebar-card {
        display: block;
    }
    .mp-nav-card {
        /* part of sticky sidebar */
    }
    .mp-avatar-wrap {
        display: flex;
        justify-content: center;
        padding: 24px 0 8px;
    }
    .mp-avatar { width: 200px; height: 200px; }
    .mp-sidebar-info {
        text-align: center;
        padding: 0 18px 18px;
    }
    .mp-name { font-size: 20px; }
    .mp-quick-info {
        display: block;
        border-top: 1px solid #f1f5f9;
        padding: 12px 0 0;
        justify-content: unset;
    }
    .mp-qi-item {
        display: flex;
        background: none;
        border: none;
        border-radius: 0;
        padding: 0;
        margin-bottom: 7px;
        font-size: 13px;
    }
    .mp-msg-btn {
        width: 100%;
        margin: 4px 0 0;
    }
    .mp-social-icons {
        flex-direction: row;
        justify-content: center;
        border-left: none;
        border-top: 1px solid #f1f5f9;
        padding: 14px 18px 18px;
        gap: 14px;
    }
    .mp-main { padding-top: 70px; }
    .mp-card-head { padding: 14px 20px; }
    .mp-card-head h2 { font-size: 15px; }
    .mp-card-body { padding: 18px 20px; }
    .mp-two-col { grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .mp-info-table { font-size: 13.5px; }
    .mp-info-table tr { display: table-row; border-bottom: none; padding: 0; }
    .mp-info-table td { padding: 6px 3px; }
    .mp-info-table td:first-child { flex: unset; width: 150px; font-size: 13.5px; text-transform: none; letter-spacing: 0; font-weight: 500; }
    .mp-info-table td:nth-child(2) { display: table-cell; width: 14px; color: #9ca3af; text-align: center; }
    .mp-info-table td:nth-child(3) { flex: unset; }
    .mp-rel-card { min-width: 200px; }
}
</style>

<script>
/* Related Batchmates Tab Switching */
document.addEventListener('DOMContentLoaded', function () {
    var tabs  = document.querySelectorAll('.mp-rtab');
    var panes = document.querySelectorAll('.mp-related-pane');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = this.dataset.tab;
            tabs.forEach(function (t) { t.classList.remove('mp-rtab-active'); });
            panes.forEach(function (p) { p.classList.remove('mp-rpane-active'); });
            this.classList.add('mp-rtab-active');
            var pane = document.querySelector('.mp-related-pane[data-pane="' + target + '"]');
            if (pane) pane.classList.add('mp-rpane-active');
        });
    });

    /* Carousel Arrow Buttons */
    function initCarouselArrows(wrap) {
        var grid = wrap.querySelector('[data-carousel]');
        var prev = wrap.querySelector('.mp-car-prev');
        var next = wrap.querySelector('.mp-car-next');
        if (!grid || !prev || !next) return;

        function updateArrows() {
            prev.disabled = grid.scrollLeft <= 2;
            next.disabled = grid.scrollLeft + grid.clientWidth >= grid.scrollWidth - 2;
        }

        prev.addEventListener('click', function () {
            grid.scrollBy({ left: -220, behavior: 'smooth' });
        });
        next.addEventListener('click', function () {
            grid.scrollBy({ left: 220, behavior: 'smooth' });
        });
        grid.addEventListener('scroll', updateArrows);
        updateArrows();
    }

    document.querySelectorAll('.mp-carousel-wrap').forEach(initCarouselArrows);

    /* Sidebar Scroll Spy */
    var sectionIds = ['section-overview', 'section-academic', 'section-professional-full', 'section-leadership', 'section-personal', 'section-skills'];
    var navLinks = document.querySelectorAll('.mp-sidebar-nav a[href^="#"]');

    function setActiveNav(id) {
        navLinks.forEach(function(link) {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + id) {
                link.classList.add('active');
            }
        });
    }

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                setActiveNav(entry.target.id);
            }
        });
    }, {
        rootMargin: '-20% 0px -70% 0px',
        threshold: 0
    });

    sectionIds.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) observer.observe(el);
    });
});
</script>

<?php get_footer(); ?>
