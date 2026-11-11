<?php
/*
Template Name: BatchMate Members
*/

get_header(); ?>

<div id="welcome"> <section class="breadcrumb">
		<div class="container">
			<h1 class="pagetitle"><?php the_title(); ?></h1>
		</div>
	</section>
    <div class="container pagesection">       	
        <div class="rightcontainer">
            <div class="content">
                
                <!-- Filter Row -->
                <div id="BatchMate-public-member-filters" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-bottom:16px; padding:14px 16px; background:#f0f4f8; border:1px solid #d0d7de; border-radius:8px;">
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <label for="pub-filter-district" style="font-weight:600; font-size:13px;">Permanent District</label>
                        <select id="pub-filter-district" style="min-width:160px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                            <option value="">-- All Districts --</option>
                            <?php
                            $bd_districts = ['Bagerhat','Bandarban','Barguna','Barishal','Bhola','Bogura','Brahmanbaria','Chandpur', 'Chapai Nawabganj', 'Chattogram','Chuadanga',"Cox's Bazar",'Cumilla','Dhaka','Dinajpur','Faridpur','Feni','Gaibandha','Gazipur','Gopalganj','Habiganj','Jamalpur','Jashore','Jhalokati','Jhenaidah','Joypurhat','Khagrachari','Khulna','Kishoreganj','Kurigram','Kushtia','Lakshmipur','Lalmonirhat','Madaripur','Magura','Manikganj','Meherpur','Moulvibazar','Munshiganj','Mymensingh','Naogaon','Narail','Narayanganj','Narsingdi','Natore','Netrokona','Nilphamari','Noakhali','Pabna','Panchagarh','Patuakhali','Pirojpur','Rajbari','Rajshahi','Rangamati','Rangpur','Satkhira','Shariatpur','Sherpur','Sirajganj','Sunamganj','Sylhet','Tangail','Thakurgaon'];
                            $db_districts = $wpdb->get_col( "SELECT DISTINCT permanent_district FROM {$wpdb->prefix}batchmate_member WHERE permanent_district IS NOT NULL AND permanent_district != ''" );
                            $all_districts = array_unique( array_filter( array_merge( $bd_districts, (array) $db_districts ) ) );
                            sort( $all_districts );
                            foreach ( $all_districts as $dist ) {
                                echo '<option value="' . esc_attr( $dist ) . '">' . esc_html( $dist ) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <label for="pub-filter-profession" style="font-weight:600; font-size:13px;">Profession</label>
                        <select id="pub-filter-profession" style="min-width:160px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                            <option value="">-- All Professions --</option>
                            <?php
                            $db_professions = $wpdb->get_col( "SELECT DISTINCT employment_position FROM {$wpdb->prefix}batchmate_member WHERE employment_position IS NOT NULL AND employment_position != '' ORDER BY employment_position ASC" );
                            foreach ( $db_professions as $prof ) {
                                echo '<option value="' . esc_attr( $prof ) . '">' . esc_html( $prof ) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <label for="pub-filter-bloodgroup" style="font-weight:600; font-size:13px;">Blood Group</label>
                        <select id="pub-filter-bloodgroup" style="min-width:140px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:4px;">
                            <option value="">-- All --</option>
                            <?php
                            $blood_groups = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
                            foreach ( $blood_groups as $bg ) {
                                echo '<option value="' . esc_attr( $bg ) . '">' . esc_html( $bg ) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:4px; justify-content:flex-end;">
                        <button type="button" id="pub-filter-reset-btn" style="height:40px; padding:0 18px; border:1px solid #c3c4c7; border-radius:4px; background:#fff; cursor:pointer; font-size:14px; font-weight:500; color:#333; display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box; transition:all 0.2s ease;">Reset</button>
                    </div>
                </div>
                <style>
    table.dataTable > tbody > tr > th, 
    table.dataTable > tbody > tr > td {
        padding: 3px !important;
        vertical-align: middle !important;
    }

                    #pub-filter-reset-btn:hover {
                        background-color: #f0f4f8 !important;
                        border-color: #8c8f94 !important;
                        color: #1d2327 !important;
                    }
                    #pub-filter-reset-btn:active {
                        background-color: #e2e8f0 !important;
                    }
                </style>

                <table id="memberstable" class="display" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th><b>Member ID</b></th>
                            <th><b>Name</b></th>
                            <th><b>Photo</b></th>
                            <th><b>District</b></th>						
                            <th><b>Profession</b></th>
                            <th><b>Blood Group</b></th>		
                            <th><b>Contact</b></th>
                            <th><b>Email</b></th>			
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th><b>Member ID</b></th>
                            <th><b>Name</b></th>						
                            <th><b>Photo</b></th>
                            <th><b>District</b></th>						
                            <th><b>Profession</b></th>
                            <th><b>Blood Group</b></th>	
                            <th><b>Contact</b></th>
                            <th><b>Email</b></th>		
                        </tr>
                    </tfoot>		
                    <tbody>
                        </tbody>
                </table>
                
            </div>
        </div>
    </div>
</div> <script type="text/javascript">
jQuery(document).ready(function($) {
    
    // functions.php ???? BatchMate_public_ajax ????????? ??? ?????
    const ajax_url = BatchMate_public_ajax.ajax_url;

    $('#pub-filter-district').select2({
        placeholder: '-- All Districts --',
        allowClear: true,
        width: '180px'
    });
    $('#pub-filter-profession').select2({
        placeholder: '-- All Professions --',
        allowClear: true,
        width: '180px'
    });
    $('#pub-filter-bloodgroup').select2({
        placeholder: '-- All --',
        allowClear: true,
        width: '120px'
    });

    $('#pub-filter-district').on('change', function() { table.ajax.reload(); });
    $('#pub-filter-profession').on('change', function() { table.ajax.reload(); });
    $('#pub-filter-bloodgroup').on('change', function() { table.ajax.reload(); });

    $('#pub-filter-reset-btn').on('click', function() {
        $('#pub-filter-district').val(null).trigger('change.select2');
        $('#pub-filter-profession').val(null).trigger('change.select2');
        $('#pub-filter-bloodgroup').val(null).trigger('change.select2');
        table.ajax.reload();
    });

    var table = $('#memberstable').DataTable({
        "pageLength": 50,
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": ajax_url,
            "type": "POST",
            "data": function(d) {
                d.action = 'get_public_members';
                d.filter_district = $('#pub-filter-district').val();
                d.filter_profession = $('#pub-filter-profession').val();
                d.filter_bloodgroup = $('#pub-filter-bloodgroup').val();
            }
        },
        "columns": [
            { "data": "member_number" },
            { 
                "data": "name",
                "render": function(data, type, row) {
                    return `<a href="${row.profile_url}">${data}</a>`;
                }
            },
            { 
                "data": "photo_url", 
                "render": function(data, type, row) {
                    var photo = data ? data : '<?php echo get_bloginfo('template_directory'); ?>/images/noprofile.png';
                    return `<a href="${row.profile_url}"><img src="${photo}" class="membersphoto" /></a>`;
                },
                "orderable": false,
                "searchable": false
            },
            { "data": "permanent_district" },
            { "data": "employment_position" },
            { "data": "blood_group", "width": "80px" },
            { "data": "cell_number", "width": "110px" },
            { "data": "email" }
        ],
        
        "order": [[ 0, "asc" ]],
        
        "scrollX": true,
        "pagingType": "full_numbers",
        "lengthMenu": [[10, 20, 50, -1], [10, 20, 50, "All"]],
    });     
});
</script>	

<?php get_footer(); ?>