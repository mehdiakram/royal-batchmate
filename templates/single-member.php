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

$sm_css_ver = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/single-member.css' ) : (string) time();
$sm_js_ver  = defined( 'ROYAL_BATCHMATE_VERSION' ) ? ROYAL_BATCHMATE_VERSION . '.' . (string) @filemtime( ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/single-member.js' )  : (string) time();
wp_enqueue_style( 'batchmate-single-member', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/single-member.css', array(), $sm_css_ver );
wp_enqueue_script( 'batchmate-single-member', ROYAL_BATCHMATE_PLUGIN_URL . 'assets/single-member.js', array(), $sm_js_ver, true );
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

<?php get_footer(); ?>
