/**
 * Royal BatchMate - Admin Members List Scripts
 */
    jQuery(document).ready(function($) {

        const ajax_url = batchmate_admin_ajax.ajax_url;
        const member_nonce = batchmate_admin_ajax.member_nonce;
        const dept_nonce = batchmate_admin_ajax.dept_nonce;
        const prog_nonce = batchmate_admin_ajax.prog_nonce;
        const member_base_url = (window.batchmate_members_cfg && window.batchmate_members_cfg.member_base_url) ? window.batchmate_members_cfg.member_base_url : '';
        const bdUpazilas = (window.batchmate_members_cfg && window.batchmate_members_cfg.bdUpazilas) ? window.batchmate_members_cfg.bdUpazilas : {};

        function populateModalUpazilas($distSelect, $upzSelect, selectedVal) {
            var dist = $distSelect.val();
            var list = (bdUpazilas && bdUpazilas[dist]) || [];
            var opts = '<option value="">Select Thana / Upazila...</option>';
            list.forEach(function(u) {
                var sel = (selectedVal && selectedVal === u) ? ' selected' : '';
                opts += '<option value="' + u + '"' + sel + '>' + u + '</option>';
            });
            $upzSelect.html(opts);
            if ($.fn.select2) {
                $upzSelect.trigger('change.select2');
            }
        }

        $('#modal_present_district').on('change', function() {
            populateModalUpazilas($(this), $('#modal_present_upazila'), '');
        });
        $('#modal_permanent_district').on('change', function() {
            populateModalUpazilas($(this), $('#modal_upazila'), '');
        });

        $('#modal_present_country').on('change', function() {
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap_present_district, #wrap_present_upazila').toggle(isBD);
            if (!isBD) {
                $('#modal_present_district, #modal_present_upazila').val('');
                if ($.fn.select2) $('#modal_present_district, #modal_present_upazila').trigger('change.select2');
            }
        });
        $('#modal_permanent_country').on('change', function() {
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap_permanent_district, #wrap_permanent_upazila').toggle(isBD);
            if (!isBD) {
                $('#modal_permanent_district, #modal_upazila').val('');
                if ($.fn.select2) $('#modal_permanent_district, #modal_upazila').trigger('change.select2');
            }
        });

        var allPrograms = [];

        // Load all programs for filter dropdown
        $.ajax({
            url: ajax_url, type: 'POST',
            data: { action: 'BatchMate_get_programs_for_dropdown', _wpnonce: prog_nonce }
        }).done(function(res) {
            if (res.success) {
                allPrograms = res.data;
            }
        });

        $('#filter-institution').on('change', function() { table.ajax.reload(); });
        $('#filter-batch').on('change', function() { table.ajax.reload(); });
        $('#filter-gender').on('change', function() { table.ajax.reload(); });
        $('#filter-blood-group').on('change', function() { table.ajax.reload(); });
        $('#filter-payment-status').on('change', function() { table.ajax.reload(); });

        $('#filter-reset-btn').on('click', function() {
            $('#filter-institution').val('');
            $('#filter-batch').val('');
            $('#filter-gender').val('');
            $('#filter-blood-group').val('');
            $('#filter-payment-status').val('');
            table.ajax.reload();
        });

        var table = $('#members-table').DataTable({
            "processing": true,
            "serverSide": true,
            "autoWidth": false,
            "order": [[ 0, "asc" ]],
            "ajax": {
                "url": ajax_url,
                "type": "POST",
                "data": function(d) {
                    d.action = 'BatchMate_get_members';
                    d._wpnonce = member_nonce;
                    d.filter_institution = $('#filter-institution').val();
                    d.filter_batch = $('#filter-batch').val();
                    d.filter_gender = $('#filter-gender').val();
                    d.filter_blood_group = $('#filter-blood-group').val();
                    d.filter_payment_status = $('#filter-payment-status').val();
                }
            },
            "columns": [
                { "data": "member_number", "width": "120px" }, 
                { 
                    "data": "name",
                    "width": "200px",
                    "render": function(data, type, row) {
                        var member_number_lower = row.member_number.toLowerCase();
                        var profile_url = member_base_url + member_number_lower + '/';
                        return '<a href="' + profile_url + '" target="_blank" >' + data + '</a>';
                    }
                },
                { 
                "data": "photo_url", 
                "width": "50px",
                "orderable": false,
                "render": function(data, type, row) {
                    return data ? `<img src="${data}" class="photo">` : '';
                } 
                },{ "data": "institution" },
                { "data": "cell_number" }, { "data": "email" },
                { 
                    "data": "wp_user_id", 
                    "render": function(data, type, row) { 
                        if (data > 0) {
                            var username = row.wp_username ? row.wp_username : 'Unknown';
                            return `<span title="ID: ${data} | User: ${username}" style="color:green; cursor:help; display:inline-flex; align-items:center;"><span class="dashicons dashicons-yes-alt" style="font-size:22px; width:22px; height:22px;"></span></span>`;
                        } else {
                            return `<span style="color:#d63638; display:inline-flex; align-items:center;"><span class="dashicons dashicons-dismiss" style="font-size:22px; width:22px; height:22px;"></span></span>`;
                        }
                    } 
                },
                { "data": null, "orderable": false, "searchable": false, "render": function(data, type, row) {
                        return `
                        <div class="BatchMate-actions-btn-container">
                            <button class="BatchMate-actions-btn">Actions</button>
                            <div class="BatchMate-actions-dropdown">
                                <button class="button edit-btn" data-id="${row.id}"><span class="dashicons dashicons-edit"></span>Edit</button>
                                <button class="button create-user-btn" data-id="${row.id}"><span class="dashicons dashicons-admin-users"></span>Create User</button>
                                <button class="button reset-credentials-btn" data-id="${row.id}"><span class="dashicons dashicons-email"></span>Send Password Reset Link</button>
                                <button class="button print-btn" data-id="${row.id}"><span class="dashicons dashicons-printer"></span>Print</button>
                            </div>
                        </div>
                        `;
                    }
                }
            ]
        });

        // 2. "Edit" ? ?
        $('#members-table tbody').on('click', '.edit-btn', function() {
            var member_id = $(this).data('id');
            
            Swal.fire({
                title: 'Loading Member Data...',
                showConfirmButton: false,
                allowOutsideClick: false,
                willOpen: () => { Swal.showLoading(); }
            });

            var getMember = $.ajax({
                url: ajax_url, type: 'POST',
                data: { action: 'BatchMate_get_member_details', member_id: member_id, _wpnonce: member_nonce }
            });
            $.when(getMember).done(function(memberRes) {
                if (memberRes.length === 3) memberRes = memberRes[0]; // Just in case
                if (!memberRes.success) {
                    Swal.fire('Error!', memberRes.data, 'error');
                    return;
                }
                var member = memberRes.data; 

                $('#BatchMate-edit-form')[0].reset();
                adminMemberResizedPhoto = null;
                $('#modal_photo').val('');

                // --- ফর্ম ফিল্ড পূরণ ---
                $('#modal_member_id').val(member.id);
                $('#modal_member_number').val(member.member_number);
                $('#modal_name').val(member.name);
                $('#modal_name_bn').val(member.name_bn || '');
                $('#modal_email').val(member.email);
                $('#modal_cell_number').val(member.cell_number || ''); 
                $('#modal_cell_show').prop('checked', member.cell_show == 1);
                $('#modal_land_phone_number').val(member.land_phone_number || '');
                $('#modal_date_of_birth').val(member.date_of_birth || '');
                $('#modal_gender').val(member.gender || ''); // [ADDED] Populate Gender
                $('#modal_religion').val(member.religion || '');
                $('#modal_student_id').val(member.student_id || member.roll_no || '');
                $('#modal_institution').val(member.institution || '');
                $('#modal_board').val(member.board || '').trigger('change');
                $('#modal_edu_group').val(member.edu_group || '').trigger('change');
                $('#modal_employment_position').val(member.employment_position || '');
                $('#modal_employment_address').val(member.employment_address || '');
                $('#modal_organization').val(member.organization || '');
                $('#modal_industry').val(member.industry || '');
                $('#modal_work_experience').val(member.work_experience || '');
                $('#modal_previous_organizations').val(member.previous_organizations || '');
                $('#modal_present_address').val(member.present_address || '');
                $('#modal_present_district').val(member.present_district || '');
                populateModalUpazilas($('#modal_present_district'), $('#modal_present_upazila'), member.present_upazila || '');
                if ($.fn.select2) $('#modal_present_district').trigger('change.select2');
                var pc = member.present_country || 'Bangladesh';
                $('#modal_present_country').val(pc);
                if ($.fn.select2) $('#modal_present_country').trigger('change.select2');
                $('#wrap_present_district, #wrap_present_upazila').toggle(pc === 'Bangladesh');

                $('#modal_permanent_address').val(member.permanent_address || '');
                $('#modal_permanent_district').val(member.permanent_district || '');
                populateModalUpazilas($('#modal_permanent_district'), $('#modal_upazila'), member.upazila || '');
                if ($.fn.select2) $('#modal_permanent_district').trigger('change.select2');
                var pmc = member.permanent_country || 'Bangladesh';
                $('#modal_permanent_country').val(pmc);
                if ($.fn.select2) $('#modal_permanent_country').trigger('change.select2');
                $('#wrap_permanent_district, #wrap_permanent_upazila').toggle(pmc === 'Bangladesh');
                $('#modal_about_me').val(member.about_me || '');
                
                // Skills (JSON array to comma-separated string)
                let skills_str = '';
                if (member.skills_json) {
                    try {
                        let skills_arr = JSON.parse(member.skills_json);
                        if (Array.isArray(skills_arr)) {
                            skills_str = skills_arr.join(', ');
                        }
                    } catch (e) {}
                }
                $('#modal_skills').val(skills_str);

                $('#modal_facebook_url').val(member.facebook_url || '');
                $('#modal_linkedin_url').val(member.linkedin_url || '');
                $('#modal_x_url').val(member.x_url || '');
                $('#modal_website').val(member.website || '');

                // ? ?
                let bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                let bloodOptions = bloodGroups.map(bg => 
                    `<option value="${bg}" ${bg == member.blood_group ? 'selected' : ''}>${bg}</option>`
                ).join('');
                $('#modal_blood_group').html('<option value="">Select...</option>' + bloodOptions);

                

                // ??? ???
                if (member.photo_url) {
                    $('#modal_photo_preview').attr('src', member.photo_url).show();
                } else {
                    $('#modal_photo_preview').hide();
                }
                
                Swal.close(); 
                $('#BatchMate-edit-modal-backdrop, #BatchMate-edit-modal-wrapper').show(); 
                $('body').addClass('modal-open');

                // Initialize Select2 inside modal
                $('#BatchMate-edit-modal-wrapper .select2-field').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ dropdownParent: $('#BatchMate-edit-modal-wrapper'), width: '100%' });
                    }
                });
                // Re-set values after select2 init
                $('#modal_blood_group').val(member.blood_group || '').trigger('change');
                $('#modal_gender').val(member.gender || '').trigger('change');
                $('#modal_religion').val(member.religion || '').trigger('change');
                $('#modal_dept_id').val(member.dept_id || '').trigger('change');
                
                $('#modal_present_district').val(member.present_district || '').trigger('change');
                var pc2 = member.present_country || 'Bangladesh';
                $('#modal_present_country').val(pc2).trigger('change');
                $('#wrap_present_district').toggle(pc2 === 'Bangladesh');
                $('#modal_permanent_district').val(member.permanent_district || '').trigger('change');
                var pmc2 = member.permanent_country || 'Bangladesh';
                $('#modal_permanent_country').val(pmc2).trigger('change');
                $('#wrap_permanent_district').toggle(pmc2 === 'Bangladesh');
            }).fail(function() {
                Swal.fire('Error', 'Could not load member data.', 'error');
            });
          });

          // Country change ? show/hide district
        $(document).on('change', '#modal_present_country', function() {
            $('#wrap_present_district').toggle($(this).val() === 'Bangladesh');
            if ($(this).val() !== 'Bangladesh') $('#modal_present_district').val('').trigger('change');
        });
        $(document).on('change', '#modal_permanent_country', function() {
            $('#wrap_permanent_district').toggle($(this).val() === 'Bangladesh');
            if ($(this).val() !== 'Bangladesh') $('#modal_permanent_district').val('').trigger('change');
        });

        // ??? ???
        function batchmateResizeTo400(file, callback) {
            if (!file || !file.type.match(/^image\//)) {
                if (callback) callback(file, null);
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    canvas.width = 400;
                    canvas.height = 400;
                    const ctx = canvas.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    const w = img.width;
                    const h = img.height;
                    const cropSize = Math.min(w, h);
                    const sx = (w - cropSize) / 2;
                    const sy = (h - cropSize) / 2;

                    ctx.drawImage(img, sx, sy, cropSize, cropSize, 0, 0, 400, 400);

                    if (canvas.toBlob) {
                        canvas.toBlob(function(blob) {
                            if (!blob) {
                                if (callback) callback(file, e.target.result);
                                return;
                            }
                            const baseName = file.name ? file.name.replace(/\.[^/.]+$/, "") : 'photo';
                            const newFileName = baseName + '.jpg';
                            const resizedFile = new File([blob], newFileName, { type: 'image/jpeg', lastModified: Date.now() });
                            if (callback) callback(resizedFile, canvas.toDataURL('image/jpeg', 0.90));
                        }, 'image/jpeg', 0.90);
                    } else {
                        if (callback) callback(file, e.target.result);
                    }
                };
                img.onerror = function() {
                    if (callback) callback(file, null);
                };
                img.src = e.target.result;
            };
            reader.onerror = function() {
                if (callback) callback(file, null);
            };
            reader.readAsDataURL(file);
        }

        var adminMemberResizedPhoto = null;
        var isResizingAdminPhoto = false;
        $('#modal_photo').on('change', function() {
            if (isResizingAdminPhoto) return;
            const input = this;
            const file = input.files && input.files[0];
            if (!file) {
                adminMemberResizedPhoto = null;
                $('#modal_photo_preview').attr('src', '').hide();
                return;
            }

            isResizingAdminPhoto = true;
            $('#BatchMate-modal-save').prop('disabled', true);

            batchmateResizeTo400(file, function(resizedFile, previewUrl) {
                adminMemberResizedPhoto = resizedFile;
                if (previewUrl) {
                    $('#modal_photo_preview').attr('src', previewUrl).show();
                }
                if (resizedFile && window.DataTransfer) {
                    const dt = new DataTransfer();
                    dt.items.add(resizedFile);
                    input.files = dt.files;
                }
                isResizingAdminPhoto = false;
                $('#BatchMate-modal-save').prop('disabled', false);
            });
        });

        //  ???  ?
        $('#BatchMate-modal-save').on('click', function() {
            
            // ??
            const requiredFields = [
                { id: '#modal_member_number', name: 'Member ID' },
                { id: '#modal_name', name: 'Name' },
                // Email is optional (auto-generated if blank)
                { id: '#modal_cell_number', name: 'Cell Number' },
                { id: '#modal_institution', name: 'Institute' },
                { id: '#modal_gender', name: 'Gender' } // [ADDED] Gender Required
            ];

            for (const field of requiredFields) {
                const value = $(field.id).val();
                if (!value) {
                    Swal.fire('Validation Error', `${field.name} is required.`, 'error');
                    return; 
                }
            }
            
            Swal.fire({
                title: 'Saving Changes...',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // FormData   ??  ??
            var formData = new FormData();
            formData.append('action', 'BatchMate_save_member_details');
            formData.append('_wpnonce', member_nonce);
            formData.append('member_id', $('#modal_member_id').val());
            formData.append('member_number', $('#modal_member_number').val());
            
            // ফিল্ড এর মান যোগ করুন
            formData.append('name', $('#modal_name').val());
            formData.append('name_bn', $('#modal_name_bn').val() || '');
            formData.append('email', $('#modal_email').val());
            formData.append('cell_number', $('#modal_cell_number').val());
            formData.append('cell_show', $('#modal_cell_show').is(':checked') ? 1 : 0);
            formData.append('land_phone_number', $('#modal_land_phone_number').val());
            formData.append('date_of_birth', $('#modal_date_of_birth').val());
            formData.append('blood_group', $('#modal_blood_group').val());
            formData.append('gender', $('#modal_gender').val()); // [ADDED] Gender Field
            formData.append('religion', $('#modal_religion').val());
            formData.append('student_id', $('#modal_student_id').val());
            formData.append('institution', $('#modal_institution').val());
            formData.append('board', $('#modal_board').val());
            formData.append('edu_group', $('#modal_edu_group').val());
            formData.append('employment_position', $('#modal_employment_position').val());
            formData.append('employment_address', $('#modal_employment_address').val());
            formData.append('organization', $('#modal_organization').val());
            formData.append('industry', $('#modal_industry').val());
            formData.append('work_experience', $('#modal_work_experience').val());
            formData.append('previous_organizations', $('#modal_previous_organizations').val());
            formData.append('present_address', $('#modal_present_address').val());
            formData.append('present_district', $('#modal_present_district').val() || '');
            formData.append('present_upazila', $('#modal_present_upazila').val() || '');
            formData.append('present_country', $('#modal_present_country').val() || 'Bangladesh');
            formData.append('permanent_address', $('#modal_permanent_address').val());
            formData.append('permanent_district', $('#modal_permanent_district').val() || '');
            formData.append('upazila', $('#modal_upazila').val() || '');
            formData.append('permanent_country', $('#modal_permanent_country').val() || 'Bangladesh');
            formData.append('about_me', $('#modal_about_me').val());
            formData.append('skills', $('#modal_skills').val());
            formData.append('facebook_url', $('#modal_facebook_url').val());
            formData.append('linkedin_url', $('#modal_linkedin_url').val());
            formData.append('x_url', $('#modal_x_url').val());
            formData.append('website', $('#modal_website').val());

            var modalPhoto = adminMemberResizedPhoto || ($('#modal_photo')[0].files.length > 0 ? $('#modal_photo')[0].files[0] : null);
            if (modalPhoto) { formData.append('swal_photo', modalPhoto); }

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(result) {
                    if (result.success) {
                        Swal.fire({ title: 'Updated!', text: result.data, icon: 'success', timer: 1500, showConfirmButton: false });
                        table.ajax.reload(null, false);
                        adminMemberResizedPhoto = null;
                        $('#modal_photo').val('');
                        $('#BatchMate-edit-modal-backdrop, #BatchMate-edit-modal-wrapper').hide(); 
                        $('body').removeClass('modal-open');
                    } else {
                        Swal.fire('Error!', result.data, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Request failed. Please try again.', 'error');
                }
            });
        });

        // Modal Close
        $('.BatchMate-modal-close, #BatchMate-edit-modal-backdrop').on('click', function() {
            adminMemberResizedPhoto = null;
            $('#modal_photo').val('');
            $('#BatchMate-edit-modal-backdrop, #BatchMate-edit-modal-wrapper').hide();
            $('body').removeClass('modal-open');
        });


        // 3. "Print" ? ?
        $('#members-table tbody').on('click', '.print-btn', function() {
            var member_id = $(this).data('id');
            var print_url = ((window.batchmate_members_cfg && window.batchmate_members_cfg.print_base_url) ? window.batchmate_members_cfg.print_base_url : "") + "?member_id=" + member_id;
            window.open(print_url, '_blank');
        });

        // 4. "Create User" ? ?
        $('#members-table tbody').on('click', '.create-user-btn', function() {
            var member_id = $(this).data('id');
            
            Swal.fire({
                title: 'Processing...', text: 'Checking member status...', icon: 'info',
                showConfirmButton: false, allowOutsideClick: false,
                willOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'BatchMate_check_member_user',
                    member_id: member_id,
                    _wpnonce: member_nonce
                }
            }).done(function(response) {
                if (!response.success) {
                    Swal.fire('Error!', response.data, 'error');
                    return;
                }

                if (response.data.status === 'user_exists') {
                    Swal.fire({
                        title: 'User Already Exists',
                        html: `This member already has an active user account (Email: <b>${response.data.email}</b>).<br/><br/>Do you want to reset their credentials and send a temporary password with login details to their email?`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Reset Credentials',
                        cancelButtonText: 'No, Cancel',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return $.ajax({
                                url: ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'BatchMate_reset_member_credentials',
                                    member_id: member_id,
                                    _wpnonce: member_nonce
                                }
                            }).fail(function(xhr) {
                                var err = 'Request failed.';
                                if (xhr.responseJSON && xhr.responseJSON.data) {
                                    err = xhr.responseJSON.data;
                                }
                                Swal.showValidationMessage(err);
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value && result.value.success) {
                            Swal.fire('Credentials Reset!', result.value.data, 'success');
                            table.ajax.reload(null, false);
                        } else if (result.value && !result.value.success) {
                            Swal.fire('Error!', result.value.data, 'error');
                        }
                    });

                } else if (response.data.status === 'email_conflict') {
                    Swal.fire({
                        title: 'Email Conflict',
                        html: `Cannot create user. This member's email (<b>${response.data.email}</b>) is already registered to another WordPress user (ID: ${response.data.existing_user_id}).<br/><br/>Please edit the member's email address first.`,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });

                } else if (response.data.status === 'no_user') {
                    Swal.fire({
                        title: 'Create New User?',
                        text: `Do you want to create a new WordPress user for ${response.data.name} (Email: ${response.data.email})?`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Create User',
                        cancelButtonText: 'No, Cancel',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return $.ajax({
                                url: ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'BatchMate_create_member_wp_user',
                                    member_id: member_id,
                                    _wpnonce: member_nonce
                                }
                            }).fail(function(xhr) { 
                                var errMsg = xhr.responseText ? 'Request failed: ' + xhr.responseText : 'Request failed.';
                                Swal.showValidationMessage(errMsg); 
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value.success) {
                            Swal.fire({
                                title: 'User Created!',
                                text: result.value.data,
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                table.ajax.reload(null, false);
                            });
                        } else if (result.value) {
                            Swal.fire('Error!', result.value.data, 'error');
                        }
                    });
                }
            }).fail(function() {
                Swal.fire('Error', 'Could not check member status.', 'error');
            });
        });

        // 5. "Reset User Credentials" ? ?
        $('#members-table tbody').on('click', '.reset-credentials-btn', function() {
            var member_id = $(this).data('id');
            
            Swal.fire({
                title: 'Checking Status...',
                text: 'Checking member user account...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                willOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'BatchMate_check_member_user',
                    member_id: member_id,
                    _wpnonce: member_nonce
                }
            }).done(function(response) {
                if (!response.success) {
                    Swal.fire('Error!', response.data, 'error');
                    return;
                }

                if (response.data.status === 'no_user') {
                    Swal.fire({
                        title: 'No User Account',
                        html: `This member (<b>${response.data.name || ''}</b>) does not have a WordPress user account yet.<br/><br/>Please use <b>"Create User"</b> first to create their account.`,
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                } else if (response.data.status === 'email_conflict') {
                    Swal.fire({
                        title: 'Email Conflict',
                        html: `Cannot reset credentials. This member's email (<b>${response.data.email}</b>) is registered to another WordPress user (ID: ${response.data.existing_user_id}), but not linked to this member.<br/><br/>Please update the member's email address first.`,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else if (response.data.status === 'user_exists') {
                    Swal.fire({
                        title: 'Send Password Reset Link?',
                        html: `Are you sure you want to send a password reset link to <b>${response.data.name || 'this member'}</b>?<br/><br/>An email with the reset link will be sent to <b>${response.data.email}</b>.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Send Link',
                        cancelButtonText: 'Cancel',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return $.ajax({
                                url: ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'BatchMate_reset_member_credentials',
                                    member_id: member_id,
                                    _wpnonce: member_nonce
                                }
                            }).fail(function(xhr) {
                                var err = xhr.responseText ? 'Request failed: ' + xhr.responseText : 'Request failed.';
                                if (xhr.responseJSON && xhr.responseJSON.data) {
                                    err = xhr.responseJSON.data;
                                }
                                Swal.showValidationMessage(err);
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value && result.value.success) {
                            Swal.fire({
                                title: 'Credentials Reset!',
                                text: result.value.data,
                                icon: 'success'
                            }).then(() => {
                                table.ajax.reload(null, false);
                            });
                        } else if (result.value && !result.value.success) {
                            Swal.fire('Error!', result.value.data, 'error');
                        }
                    });
                }
            }).fail(function() {
                Swal.fire('Error', 'Could not check member status.', 'error');
            });
        });

    });
