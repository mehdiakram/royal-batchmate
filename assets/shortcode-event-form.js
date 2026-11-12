/**
 * Royal BatchMate - Event Registration Form Shortcode Scripts
 */
(function($) {
    function initBatchmateEventForm(cfg) {
        var evtId   = cfg.evtId;
        var nonce   = cfg.nonce;
        var ajaxUrl = cfg.ajaxUrl;
        var noPhoto = cfg.noPhoto;
        var regLink = cfg.regLink;
        var allowNonMembers = cfg.allowNonMembers;
        var hasTshirt = cfg.hasTshirt;
        var hasSaree  = cfg.hasSaree;
        var guestFieldsCfg = cfg.guestFieldsCfg || [];
        var paymentMethods = cfg.paymentMethods || [];
        var distinctInstitutions = cfg.distinctInstitutions || [];
        var membershipFeeInfo = cfg.membershipFeeInfo || {};
        var _memFeeActive = !!membershipFeeInfo.active;
        var _memFeeAmount = parseFloat(membershipFeeInfo.amount || 0);
        var _memFeeLabel  = membershipFeeInfo.label || 'Membership Fee';
        var _bmDistricts  = cfg.bdDistricts || [];
        var _bmUpazilas   = cfg.bdUpazilas || {};
        var _bmCountries  = cfg.bmCountries || [];
        var _isCurrentGuest = false;
        var customDirectRegTitle = cfg.customDirectRegTitle || '';
    var $actionBtns   = $('#BatchMate-ef-action-btns-' + evtId);
    var $searchWrap   = $('#BatchMate-ef-search-wrap-' + evtId);
    var $input        = $('#BatchMate-ef-search-' + evtId);
    var $spinner      = $('#BatchMate-ef-spinner-' + evtId);
    var $result       = $('#BatchMate-ef-result-' + evtId);
    var $hint         = $('#BatchMate-ef-char-hint-' + evtId);

    // Helper: reset search view
    function resetSearch() {
        $result.html('');
        $input.val('').focus();
        lastQ = '';
        $hint.removeClass('typing').html('&#9660; Results appear automatically after 3 characters.');
    }

    function switchToDirect() {
        $('#BatchMate-ef-tab-search-' + evtId).removeClass('active').css({ 'background':'#f8fafc', 'color':'#64748b', 'border-bottom':'3px solid transparent' });
        $('#BatchMate-ef-tab-direct-' + evtId).addClass('active').css({ 'background':'#fff', 'color':'#1e3a8a', 'border-bottom':'3px solid #2563eb' });
        $searchWrap.slideUp(180);
        renderMemberForm({
            member: { is_guest: true },
            already: false
        });
    }

    function switchToSearch() {
        $('#BatchMate-ef-tab-direct-' + evtId).removeClass('active').css({ 'background':'#f8fafc', 'color':'#64748b', 'border-bottom':'3px solid transparent' });
        $('#BatchMate-ef-tab-search-' + evtId).addClass('active').css({ 'background':'#fff', 'color':'#1e3a8a', 'border-bottom':'3px solid #2563eb' });
        $result.html('');
        $searchWrap.slideDown(180);
        $input.val('').focus();
        lastQ = '';
        $hint.removeClass('typing').html('&#9660; Results appear automatically after 3 characters.');
    }

    $('#BatchMate-ef-tab-direct-' + evtId).on('click', switchToDirect);
    $('#BatchMate-ef-tab-search-' + evtId).on('click', switchToSearch);
    $('#BatchMate-ef-direct-reg-btn-' + evtId).on('click', switchToDirect);

    // Back button -> return to search
    $(document).on('click', '.BatchMate-ef-guest-back-btn, #BatchMate-ef-back-' + evtId, function() {
        if (allowNonMembers === 2) {
            switchToSearch();
        } else if (allowNonMembers !== 3) {
            resetSearch();
        }
    });
    var timer     = null;
    var lastQ     = '';
    var activeXhr = null;
    var _evtOptions = []; // event guest options cache
    var _evtMemberFee = 0;

    // Load event options on init; for Option 3 & 4 also trigger direct form render AFTER options load
    $.ajax({ url: ajaxUrl, type: 'POST',
        data: { action: 'BatchMate_get_event_options', event_id: evtId }
    }).done(function(res){
        if (res.success && res.data && res.data.options) {
            _evtOptions      = res.data.options;
            _evtMemberFee    = parseFloat(res.data.member_fee) || 0;
        }
        // Option 4: Direct Form Only — render AFTER options are loaded so guest options appear
        if (allowNonMembers === 3) {
            $searchWrap.hide();
            renderMemberForm({ member: { is_guest: true }, already: false });
        }
    });

    /* -- Live input handler -- */
    $input.on('input', function() {
        var q = $.trim($(this).val());

        clearTimeout(timer);
        if (activeXhr) { activeXhr.abort(); activeXhr = null; }

        /* Less than 3 chars ? clear */
        if (q.length < 3) {
            $spinner.removeClass('active');
            $input.removeClass('ef-searching');
            $result.html('');
            lastQ = '';
            if (q.length === 0) {
                $hint.removeClass('typing').html('&#9660; Results appear automatically after 3 characters.');
            } else {
                $hint.addClass('typing').text((3 - q.length) + ' more character' + ((3 - q.length) > 1 ? 's' : '') + ' to search...');
            }
            return;
        }

        /* Same query ? skip */
        if (q === lastQ) return;

        /* Show loading state */
        $hint.addClass('typing').text('Searching...');
        $spinner.addClass('active');
        $input.addClass('ef-searching');

        /* Debounce 350ms */
        timer = setTimeout(function() {
            lastQ = q;
            activeXhr = $.ajax({
                url: ajaxUrl, type: 'POST',
                data: { action: 'BatchMate_search_member_for_event', query: q, event_id: evtId, _wpnonce: nonce }
            }).done(function(res) {
                $spinner.removeClass('active');
                $input.removeClass('ef-searching');
                $hint.removeClass('typing').text('');
                renderResult(res, q);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                if (jqXHR.statusText === 'abort') return;
                $spinner.removeClass('active');
                $input.removeClass('ef-searching');
                $hint.removeClass('typing').text('');
                var errMsg = 'Connection error. Please try again.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.data) {
                    errMsg = jqXHR.responseJSON.data;
                } else if (jqXHR.status === 403 || jqXHR.status === 400) {
                    errMsg = 'Security check or session expired. Please refresh the page.';
                }
                $result.html(mkNotice('error', errMsg));
            });
        }, 350);
    });

    /* -- Helpers -- */
    function mkNotice(type, msg) {
        return '<div class="ef-anim BatchMate-ef-notice BatchMate-ef-notice--' + type + '">' + msg + '</div>';
    }

    
    /* ---- Build guest form HTML from guestFieldsCfg ---- */
    function buildGuestFormHtml(currentGender) {
        var html = '<div class="BatchMate-ef-pay-title">&#128100; Personal Information</div>';
        var fields = guestFieldsCfg;
        var req = function(r){ return r ? ' <span style="color:#c53030">*</span>' : ''; };

        // Row 1: Name, Name Bangla (if shown), Mobile (always)
        var nameBnShow = fields.name_bn && fields.name_bn.show;
        html += '<div class="' + (nameBnShow ? 'BatchMate-ef-grid-3' : 'BatchMate-ef-grid-2') + '">';
        html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Name <span style="color:#c53030">*</span></label><input type="text" id="ef-guest-name" placeholder="Full Name"></div>';
        if (nameBnShow) {
            html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Name (Bangla)' + req(fields.name_bn.req) + '</label><input type="text" id="ef-guest-name-bn" placeholder="বাংলা নাম"></div>';
        }
        html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Mobile <span style="color:#c53030">*</span></label><input type="text" id="ef-guest-cell" inputmode="numeric" placeholder="01XXXXXXXXX"></div>';
        html += '</div>';

        // Row 2: Email, Gender, Blood Group
        var emailShow  = fields.email       && fields.email.show;
        var genderShow = fields.gender      && fields.gender.show;
        var bloodShow  = fields.blood_group && fields.blood_group.show;
        var r2Count = (emailShow ? 1 : 0) + (genderShow ? 1 : 0) + (bloodShow ? 1 : 0);
        if (r2Count > 0) {
            var r2Class = r2Count === 3 ? 'BatchMate-ef-grid-3' : (r2Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
            html += '<div class="' + r2Class + '">';
            if (emailShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>' + (fields.email.req ? ('Email' + req(true)) : 'Email (Optional)') + '</label><input type="email" id="ef-guest-email" placeholder="example@email.com"></div>';
            }
            if (genderShow) {
                var isFem = (currentGender === 'Female');
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Gender' + req(fields.gender.req) + '</label>' +
                    '<select id="ef-guest-gender">' +
                    '<option value="Male"' + (!isFem ? ' selected' : '') + '>Male</option>' +
                    '<option value="Female"' + (isFem ? ' selected' : '') + '>Female</option>' +
                    '</select></div>';
            }
            if (bloodShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Blood Group' + req(fields.blood_group.req) + '</label>' +
                    '<select id="ef-guest-blood"><option value="">Select...</option>' +
                    '<option>A+</option><option>A-</option><option>B+</option><option>B-</option>' +
                    '<option>O+</option><option>O-</option><option>AB+</option><option>AB-</option>' +
                    '</select></div>';
            }
            html += '</div>';
        }

        // Row 3: Date of Birth, Religion, Land Phone
        var dobShow      = fields.dob        && fields.dob.show;
        var religionShow = fields.religion   && fields.religion.show;
        var landShow     = fields.land_phone && fields.land_phone.show;
        var r3Count = (dobShow ? 1 : 0) + (religionShow ? 1 : 0) + (landShow ? 1 : 0);
        if (r3Count > 0) {
            var r3Class = r3Count === 3 ? 'BatchMate-ef-grid-3' : (r3Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
            html += '<div class="' + r3Class + '">';
            if (dobShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Date of Birth' + req(fields.dob.req) + '</label><input type="date" id="ef-guest-dob"></div>';
            }
            if (religionShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Religion' + req(fields.religion.req) + '</label>' +
                    '<select id="ef-guest-religion"><option value="">Select Religion...</option>' +
                    '<option>Islam</option><option>Hinduism</option><option>Buddhism</option><option>Christianity</option><option>Others</option>' +
                    '</select></div>';
            }
            if (landShow) {
                html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Alternative Number' + req(fields.land_phone.req) + '</label><input type="text" id="ef-guest-land-phone" placeholder="Alternative Number"></div>';
            }
            html += '</div>';
        }

        // Academic Information (only if any academic field is shown)
        var boardShow = fields.board       && fields.board.show;
        var rollShow  = fields.roll_no     && fields.roll_no.show;
        var regNoShow = fields.reg_no      && fields.reg_no.show;
        var groupShow = fields.group       && fields.group.show;
        var instShow  = fields.institution && fields.institution.show;

        if (boardShow || rollShow || regNoShow || groupShow || instShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#127891; Academic Information</div>';

            var acadRow1Count = (boardShow ? 1 : 0) + (rollShow ? 1 : 0) + (regNoShow ? 1 : 0);
            if (acadRow1Count > 0) {
                var a1Class = acadRow1Count === 3 ? 'BatchMate-ef-grid-3' : (acadRow1Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
                html += '<div class="' + a1Class + '">';
                if (boardShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Board' + req(fields.board.req) + '</label>' +
                        '<select id="ef-guest-board"><option value="">Select Board...</option>' +
                        '<option value="dhaka">Dhaka</option><option value="barisal">Barisal</option><option value="chittagong">Chittagong</option>' +
                        '<option value="comilla">Comilla</option><option value="dinajpur">Dinajpur</option><option value="jessore">Jessore</option>' +
                        '<option value="mymensingh">Mymensingh</option><option value="rajshahi">Rajshahi</option><option value="sylhet">Sylhet</option>' +
                        '<option value="madrasah">Madrasah</option><option value="tec">Technical</option>' +
                        '</select></div>';
                }
                if (rollShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Roll No' + req(fields.roll_no.req) + '</label><input type="text" id="ef-guest-roll" placeholder="SSC Roll"></div>';
                }
                if (regNoShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Reg No' + req(fields.reg_no.req) + '</label><input type="text" id="ef-guest-regno" placeholder="Registration No"></div>';
                }
                html += '</div>';
            }

            var acadRow2Count = (groupShow ? 1 : 0) + (instShow ? 1 : 0);
            if (acadRow2Count > 0) {
                var a2Class = acadRow2Count === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + a2Class + '">';
                if (groupShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Group' + req(fields.group.req) + '</label>' +
                        '<select id="ef-guest-group"><option value="">Select Group...</option>' +
                        '<option>Science</option><option>Humanities</option><option>Commerce</option><option>Vocational</option><option>Others</option>' +
                        '</select></div>';
                }
                if (instShow) {
                    var instDatalistOptions = '';
                    if (distinctInstitutions && distinctInstitutions.length) {
                        distinctInstitutions.forEach(function(inst){
                            instDatalistOptions += '<option value="' + inst.replace(/"/g, '&quot;') + '"></option>';
                        });
                    }
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>' + (fields.institution.label || 'Institution') + req(fields.institution.req) + '</label>' +
                        '<input type="text" id="ef-guest-institution" list="ef-institutions-list" placeholder="School / College Name" autocomplete="off">' +
                        '<datalist id="ef-institutions-list">' + instDatalistOptions + '</datalist>' +
                        '</div>';
                }
                html += '</div>';
            }
        }

        // Address Information (if shown)
        var presAddrShow    = fields.present_address    && fields.present_address.show;
        var presCountryShow = fields.present_country    && fields.present_country.show;
        var presDistShow    = fields.present_district   && fields.present_district.show;
        var presUpzShow     = fields.present_upazila    && fields.present_upazila.show;
        var permAddrShow    = fields.permanent_address  && fields.permanent_address.show;
        var permCountryShow = fields.permanent_country  && fields.permanent_country.show;
        var permDistShow    = fields.permanent_district && fields.permanent_district.show;
        var permUpzShow     = fields.permanent_upazila  && fields.permanent_upazila.show;

        if (presAddrShow || presCountryShow || presDistShow || presUpzShow || permAddrShow || permCountryShow || permDistShow || permUpzShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#128205; Address Information</div>';
            if (presAddrShow || permAddrShow) {
                var addrClass = (presAddrShow && permAddrShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + addrClass + '">';
                if (presAddrShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Present Address' + req(fields.present_address.req) + '</label><input type="text" id="ef-guest-pres-addr" placeholder="House, Road, Area"></div>';
                }
                if (permAddrShow) {
                    html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Permanent Address' + req(fields.permanent_address.req) + '</label><input type="text" id="ef-guest-perm-addr" placeholder="Village, Area"></div>';
                }
                html += '</div>';
            }

            // Present Location (Country, District, Upazila)
            var presLocCount = (presCountryShow ? 1 : 0) + (presDistShow ? 1 : 0) + (presUpzShow ? 1 : 0);
            if (presLocCount > 0) {
                var presLocClass = presLocCount === 3 ? 'BatchMate-ef-grid-3' : (presLocCount === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
                html += '<div class="' + presLocClass + '" id="wrap-ef-pres-loc" style="margin-top:10px;">';
                if (presCountryShow) {
                    var cOpts = '<option value="">Select Country...</option>';
                    if (_bmCountries && _bmCountries.length) {
                        _bmCountries.forEach(function(c){
                            cOpts += '<option value="' + c + '"' + (c === 'Bangladesh' ? ' selected' : '') + '>' + c + '</option>';
                        });
                    } else {
                        cOpts += '<option value="Bangladesh" selected>Bangladesh</option>';
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-pres-country" style="margin-bottom:0;"><label>Present Country' + req(fields.present_country.req) + '</label><select id="ef-guest-pres-country" class="ef-select2">' + cOpts + '</select></div>';
                }
                if (presDistShow) {
                    var dOpts = '<option value="">Select District...</option>';
                    if (_bmDistricts && _bmDistricts.length) {
                        _bmDistricts.forEach(function(d){
                            dOpts += '<option value="' + d + '">' + d + '</option>';
                        });
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-pres-dist" style="margin-bottom:0;"><label>' + (fields.present_district.label || 'Present District') + req(fields.present_district.req) + '</label><select id="ef-guest-pres-dist" class="ef-select2">' + dOpts + '</select></div>';
                }
                if (presUpzShow) {
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-pres-upazila" style="margin-bottom:0;"><label>' + (fields.present_upazila.label || 'Present Thana / Upazila') + req(fields.present_upazila.req) + '</label><select id="ef-guest-pres-upazila" class="ef-select2"><option value="">Select Thana / Upazila...</option></select></div>';
                }
                html += '</div>';
            }

            // Permanent Location (Country, District, Upazila)
            var permLocCount = (permCountryShow ? 1 : 0) + (permDistShow ? 1 : 0) + (permUpzShow ? 1 : 0);
            if (permLocCount > 0) {
                var permLocClass = permLocCount === 3 ? 'BatchMate-ef-grid-3' : (permLocCount === 2 ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1');
                html += '<div class="' + permLocClass + '" id="wrap-ef-perm-loc" style="margin-top:10px;">';
                if (permCountryShow) {
                    var cOpts2 = '<option value="">Select Country...</option>';
                    if (_bmCountries && _bmCountries.length) {
                        _bmCountries.forEach(function(c){
                            cOpts2 += '<option value="' + c + '"' + (c === 'Bangladesh' ? ' selected' : '') + '>' + c + '</option>';
                        });
                    } else {
                        cOpts2 += '<option value="Bangladesh" selected>Bangladesh</option>';
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-perm-country" style="margin-bottom:0;"><label>Permanent Country' + req(fields.permanent_country.req) + '</label><select id="ef-guest-perm-country" class="ef-select2">' + cOpts2 + '</select></div>';
                }
                if (permDistShow) {
                    var dOpts2 = '<option value="">Select District...</option>';
                    if (_bmDistricts && _bmDistricts.length) {
                        _bmDistricts.forEach(function(d){
                            dOpts2 += '<option value="' + d + '">' + d + '</option>';
                        });
                    }
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-perm-dist" style="margin-bottom:0;"><label>' + (fields.permanent_district.label || 'Permanent District') + req(fields.permanent_district.req) + '</label><select id="ef-guest-perm-dist" class="ef-select2">' + dOpts2 + '</select></div>';
                }
                if (permUpzShow) {
                    html += '<div class="BatchMate-ef-pay-group" id="wrap-ef-guest-perm-upazila" style="margin-bottom:0;"><label>' + (fields.permanent_upazila.label || 'Permanent Thana / Upazila') + req(fields.permanent_upazila.req) + '</label><select id="ef-guest-perm-upazila" class="ef-select2"><option value="">Select Thana / Upazila...</option></select></div>';
                }
                html += '</div>';
            }
        }

        // Professional Information (if any field shown)
        var orgShow     = fields.organization           && fields.organization.show;
        var posShow     = fields.position               && fields.position.show;
        var indShow     = fields.industry               && fields.industry.show;
        var expShow     = fields.work_experience        && fields.work_experience.show;
        var empAddrShow = fields.employment_address     && fields.employment_address.show;
        var prevOrgShow = fields.previous_organizations && fields.previous_organizations.show;
        var aboutShow   = fields.about_me               && fields.about_me.show;
        var skillsShow  = fields.skills                 && fields.skills.show;

        if (orgShow || posShow || indShow || expShow || empAddrShow || prevOrgShow || aboutShow || skillsShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#128188; Professional Information</div>';
            if (orgShow || posShow) {
                var p1Class = (orgShow && posShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p1Class + '">';
                if (orgShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Organization' + req(fields.organization.req) + '</label><input type="text" id="ef-guest-org" placeholder="Current Workplace"></div>';
                if (posShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Position / Designation' + req(fields.position.req) + '</label><input type="text" id="ef-guest-pos" placeholder="e.g. Manager, Officer"></div>';
                html += '</div>';
            }
            if (indShow || expShow) {
                var p2Class = (indShow && expShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p2Class + '">';
                if (indShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Industry' + req(fields.industry.req) + '</label><input type="text" id="ef-guest-ind" placeholder="e.g. IT, Textile, Banking"></div>';
                if (expShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Work Experience' + req(fields.work_experience.req) + '</label><input type="text" id="ef-guest-exp" placeholder="e.g. 5 Years"></div>';
                html += '</div>';
            }
            if (empAddrShow || prevOrgShow) {
                var p3Class = (empAddrShow && prevOrgShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p3Class + '">';
                if (empAddrShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Organization Address' + req(fields.employment_address.req) + '</label><input type="text" id="ef-guest-emp-addr" placeholder="Workplace Address"></div>';
                if (prevOrgShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Previous Organizations' + req(fields.previous_organizations.req) + '</label><input type="text" id="ef-guest-prev-org" placeholder="Past companies"></div>';
                html += '</div>';
            }
            if (aboutShow || skillsShow) {
                var p4Class = (aboutShow && skillsShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + p4Class + '">';
                if (aboutShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>About Me' + req(fields.about_me.req) + '</label><input type="text" id="ef-guest-about" placeholder="Brief bio"></div>';
                if (skillsShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Skills' + req(fields.skills.req) + '</label><input type="text" id="ef-guest-skills" placeholder="e.g. Management, Public Relations"></div>';
                html += '</div>';
            }
        }

        // Social & Web Links (if any field shown)
        var fbShow  = fields.facebook && fields.facebook.show;
        var liShow  = fields.linkedin && fields.linkedin.show;
        var xShow   = fields.x        && fields.x.show;
        var webShow = fields.website  && fields.website.show;

        if (fbShow || liShow || xShow || webShow) {
            html += '<div class="BatchMate-ef-pay-title" style="margin-top:16px;">&#127760; Social &amp; Web Links</div>';
            if (fbShow || liShow) {
                var s1Class = (fbShow && liShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + s1Class + '">';
                if (fbShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Facebook URL' + req(fields.facebook.req) + '</label><input type="url" id="ef-guest-fb" placeholder="https://facebook.com/username"></div>';
                if (liShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>LinkedIn URL' + req(fields.linkedin.req) + '</label><input type="url" id="ef-guest-li" placeholder="https://linkedin.com/in/username"></div>';
                html += '</div>';
            }
            if (xShow || webShow) {
                var s2Class = (xShow && webShow) ? 'BatchMate-ef-grid-2' : 'BatchMate-ef-grid-1';
                html += '<div class="' + s2Class + '">';
                if (xShow)  html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>X / Twitter URL' + req(fields.x.req) + '</label><input type="url" id="ef-guest-x" placeholder="https://x.com/username"></div>';
                if (webShow) html += '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Personal Website' + req(fields.website.req) + '</label><input type="url" id="ef-guest-web" placeholder="https://yourwebsite.com"></div>';
                html += '</div>';
            }
        }

        // Photo Upload Box with Instant Preview
        var photoShow = fields.photo && fields.photo.show;
        if (photoShow) {
            html += '<div class="BatchMate-ef-pay-group" style="margin-top:16px;margin-bottom:14px;">' +
                '<label style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;color:#334155;">Photo (Square 400&times;400)' + req(fields.photo.req) + '</label>' +
                '<div class="ef-photo-card">' +
                '<div class="ef-photo-thumb" id="ef-photo-thumb-container">' +
                '<img id="ef-photo-preview-img" src="' + noPhoto + '" alt="Photo Preview">' +
                '</div>' +
                '<div class="ef-photo-actions">' +
                '<label for="ef-guest-photo" class="ef-photo-btn"><span class="dashicons dashicons-upload" style="margin-right:4px;"></span> Choose Photo</label>' +
                '<input type="file" id="ef-guest-photo" accept="image/*" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;">' +
                '<div id="ef-photo-filename" class="ef-photo-filename">No photo chosen yet</div>' +
                '<div class="ef-photo-help">Recommended: Square format 400&times;400 px. Instant preview shown above.</div>' +
                '</div></div></div>';
        }

        return html;
    }

    /* ---- Gift / T-Shirt / Saree HTML ---- */
    function renderGiftOptionHtml(isFemale) {
        if (!hasTshirt) return '';
        var sizeOpts = '<option value="">-- Select Size --</option>' +
            '<option value="S">S</option><option value="M">M</option><option value="X">X</option>' +
            '<option value="L">L</option><option value="XL" selected>XL</option>' +
            '<option value="XXL">XXL</option><option value="XXXL">XXXL</option>';

        if (hasSaree && isFemale) {
            return '<div class="BatchMate-ef-pay-group" id="ef-gift-container" style="margin-bottom:16px;background:#fdf2f8;border:1.5px solid #fbcfe8;border-radius:12px;padding:16px;">' +
                '<label style="font-weight:700;color:#9d174d;display:flex;align-items:center;gap:6px;margin-bottom:12px;font-size:14px;">&#128088; Gift Choice <span style="color:#c53030">*</span></label>' +
                '<div class="ef-gift-selector-grid">' +
                '<div class="ef-gift-card selected" data-value="tshirt">' +
                '<input type="radio" name="ef_gift_choice" value="tshirt" checked style="display:none;">' +
                '<div class="ef-gift-icon-wrap">&#128085;</div>' +
                '<div class="ef-gift-text"><strong>T-Shirt</strong><span>Select your size below</span></div>' +
                '<div class="ef-gift-check">&#10003;</div>' +
                '</div>' +
                '<div class="ef-gift-card" data-value="saree">' +
                '<input type="radio" name="ef_gift_choice" value="saree" style="display:none;">' +
                '<div class="ef-gift-icon-wrap">&#129528;</div>' +
                '<div class="ef-gift-text"><strong>Saree (শাড়ি)</strong><span>Traditional festive gift</span></div>' +
                '<div class="ef-gift-check">&#10003;</div>' +
                '</div>' +
                '</div>' +
                '<div id="ef-tshirt-size-wrapper" style="margin-top:12px;">' +
                '<label style="font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;display:block;">Select T-Shirt Size: <span style="color:#c53030">*</span></label>' +
                '<select id="ef-tshirt-size">' + sizeOpts + '</select>' +
                '</div>' +
                '</div>';
        }
        return '<div class="BatchMate-ef-pay-group" id="ef-gift-container" style="margin-bottom:14px;">' +
            '<label>&#128085; T-Shirt Size <span style="color:#c53030">*</span></label>' +
            '<select id="ef-tshirt-size">' + sizeOpts + '</select>' +
            '</div>';
    }

    function cardHeader(m, photo) {
        return '<div class="BatchMate-ef-card-header">' +
            '<img src="' + photo + '" class="BatchMate-ef-card-photo">' +
            '<div class="BatchMate-ef-card-info">' +
            '<span class="BatchMate-ef-mid">' + (m.member_number||'') + '</span>' +
            '<h4>' + (m.name||'') + '</h4>' +
            '<p>' + (m.institution||m.program_name||'') + (m.batch ? ' &bull; Batch ' + m.batch : '') + '</p>' +
            '</div></div>';
    }

    /* -- Render single member form (after selection) -- */
    function renderMemberForm(memberData) {
        var m       = memberData.member;
        var isGuest = m.is_guest === true;
        var photo   = m.photo_url || noPhoto;
        _isCurrentGuest = isGuest;

        /* Already applied */
        if (memberData.already) {
            var st      = memberData.app_status;
            var stText  = st === 'approved' ? '&#10003; Your registration has been approved.'
                        : st === 'rejected' ? '&#10008; Your application was not accepted.'
                        : '&#8987; Your application is under review. Please wait for approval.';
            var stClass = st === 'approved' ? 'success' : st === 'rejected' ? 'error' : 'warning';
            $result.html('<div class="ef-anim BatchMate-ef-card">' + cardHeader(m, photo) +
                '<div class="BatchMate-ef-card-body">' + mkNotice(stClass, stText) + '</div></div>');
            return;
        }

        var headerHtml      = '';
        var guestFieldsHtml = '';
        var isFemaleGuest   = false;

        if (isGuest) {
            var isDirectMemberMode = (allowNonMembers === 2 || allowNonMembers === 3);
            var customHeader = customDirectRegTitle || '';
            var defaultTitle = isDirectMemberMode ? 'Membership &amp; Event Registration' : 'Guest Registration';
            var headerTitle = customHeader ? customHeader : defaultTitle;
            var subMsg = isDirectMemberMode
                ? 'Fill in your details to register for the event and apply for lifetime membership!'
                : 'Please provide your information below to register as a guest for this event.';
            var badgeHtml = isDirectMemberMode
                ? ' <span style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;margin-left:8px;display:inline-flex;align-items:center;gap:4px;">&#9733; Direct Member on Approval</span>'
                : ' <span style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;margin-left:8px;">Guest</span>';

            var backBtnHtml = (allowNonMembers === 3)
                ? ''
                : '<button type="button" class="BatchMate-ef-back-btn BatchMate-ef-guest-back-btn">&#8592; Search Another</button>';

            headerHtml = '<div class="BatchMate-ef-card-header">' +
                '<div class="BatchMate-ef-card-info" style="width:100%;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">' +
                '<div><h4 style="margin:0 0 4px;display:flex;align-items:center;flex-wrap:wrap;">' + headerTitle + badgeHtml + '</h4>' +
                '<p style="margin:0;font-size:13px;color:#64748b;">' + subMsg + '</p></div>' +
                backBtnHtml +
                '</div></div>';

            guestFieldsHtml = buildGuestFormHtml('Male');
        } else {
            headerHtml    = cardHeader(m, photo);
            isFemaleGuest = (m.gender && m.gender.toLowerCase() === 'female');
        }

        var giftHtml = isGuest ? '' : renderGiftOptionHtml(isFemaleGuest);
        var memberGiftHtml = (!isGuest && hasTshirt) ? giftHtml : '';
        var guestGiftPlaceholder = (isGuest && hasTshirt) ? '<div id="ef-guest-gift-wrap"></div>' : '';
        var giftSectionHtml = isGuest ? guestGiftPlaceholder : memberGiftHtml;

        /* Guest options */
        var optHtml = '';
        if (_evtOptions.length) {
            optHtml += '<p class="BatchMate-ef-pay-title" style="margin-top:16px;">&#128101; Guest Options</p>';
            _evtOptions.forEach(function(opt){
                var max = parseInt(opt.max_qty) || 0;
                optHtml += '<div class="BatchMate-ef-pay-group BatchMate-ef-opt-row" data-optid="'+opt.id+'" data-fee="'+opt.fee+'" data-max="'+max+'" data-name="'+opt.option_name+'">' +
                    '<label>'+opt.option_name+' <span class="BatchMate-ef-opt-fee">(&#2547; '+parseFloat(opt.fee).toFixed(2)+'/person'+(max>0?', max '+max:'')+' )</span></label>' +
                    '<div class="BatchMate-ef-opt-ctrl">' +
                    '<button type="button" class="BatchMate-ef-qty-btn" data-act="dec">&#8722;</button>' +
                    '<input type="number" class="BatchMate-ef-qty-inp" value="0" min="0"'+(max>0?' max="'+max+'"':'')+' readonly>' +
                    '<button type="button" class="BatchMate-ef-qty-btn" data-act="inc">&#43;</button>' +
                    '<span class="BatchMate-ef-opt-sub">&#2547; 0.00</span>' +
                    '</div></div>';
            });
        }

        /* Base Fee & Fee Breakdown (Show before Payment Information) */
        var baseFeeHtml = '';
        if (isGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0) {
            baseFeeHtml = '<div class="ef-fee-summary-card" style="margin:16px 0;padding:14px 18px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:13.5px;color:#334155;">' +
                '<span><span class="dashicons dashicons-calendar-alt" style="color:#2563eb;margin-right:6px;vertical-align:text-bottom;"></span>Event Registration Fee:</span>' +
                '<strong style="color:#0f172a;">&#2547; ' + _evtMemberFee.toFixed(2) + '</strong>' +
                '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13.5px;color:#15803d;">' +
                '<span><span class="dashicons dashicons-id" style="color:#16a34a;margin-right:6px;vertical-align:text-bottom;"></span>Membership Fee (' + (_memFeeLabel || 'Direct Member') + '):</span>' +
                '<strong style="color:#15803d;">+ &#2547; ' + _memFeeAmount.toFixed(2) + '</strong>' +
                '</div>' +
                '<div style="border-top:1px dashed #86efac;padding-top:8px;display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:700;color:#0f172a;">' +
                '<span>Total Base Registration:</span>' +
                '<span style="color:#15803d;font-size:16px;">&#2547; ' + (_evtMemberFee + _memFeeAmount).toFixed(2) + '</span>' +
                '</div>' +
                '</div>';
        } else if (_evtMemberFee > 0) {
            baseFeeHtml = '<div style="margin:16px 0;padding:12px 16px;background:#f0f6fc;border:1px solid #c8e1fa;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">' +
                '<span style="font-weight:600;color:#1d2327;font-size:14px;"><span class="dashicons dashicons-admin-users" style="color:#2271b1;margin-right:6px;vertical-align:text-bottom;"></span>Base Registration Fee:</span>' +
                '<span style="font-weight:700;color:#2271b1;font-size:15px;">&#2547; ' + _evtMemberFee.toFixed(2) + '</span>' +
                '</div>';
        }

        /* Configured Payment Methods */
        var methodOpts = '<option value="">&mdash; Select Payment Method &mdash;</option>';
        if (paymentMethods && paymentMethods.length) {
            paymentMethods.forEach(function(pm){
                var esc = pm.replace(/"/g, '&quot;');
                methodOpts += '<option value="' + esc + '">' + pm + '</option>';
            });
        } else {
            ['bKash', 'Nagad', 'Rocket', 'Bank Transfer', 'Cash'].forEach(function(pm){
                methodOpts += '<option value="' + pm + '">' + pm + '</option>';
            });
        }

        var amountLabel = (_evtOptions.length || (isGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0))
            ? 'Total Amount (BDT) <span style="font-size:11px;color:#6c757d;">&mdash; auto-calculated</span>'
            : 'Amount (BDT) <span style="color:#c53030">*</span>';

        var submitBtnText = (isGuest && allowNonMembers === 2)
            ? '&#10003; Confirm Membership &amp; Event Registration'
            : '&#10003; Confirm Registration';

        /* Order: Personal/Academic info -> Gift Choice -> Guest Options -> Base Fee Breakdown -> Payment Info -> Submit */
        $result.html('<div class="ef-anim BatchMate-ef-card">' + headerHtml +
            '<div class="BatchMate-ef-card-body">' +
            guestFieldsHtml +
            giftSectionHtml +
            optHtml +
            baseFeeHtml +
            '<p class="BatchMate-ef-pay-title" style="margin-top:18px;">&#128179; Payment Information</p>' +
            '<div class="BatchMate-ef-grid-3">' +
            '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Payment Method <span style="color:#c53030">*</span></label>' +
            '<select id="BatchMate-ef-pay-method">' + methodOpts + '</select></div>' +
            '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>Transaction / Ref ID <span style="color:#c53030">*</span></label>' +
            '<input type="text" id="BatchMate-ef-pay-details" placeholder="e.g. 8N7YXXABCD..."></div>' +
            '<div class="BatchMate-ef-pay-group" style="margin-bottom:0;"><label>' + amountLabel + '</label>' +
            '<input type="number" id="BatchMate-ef-pay-amount" placeholder="0.00" min="0" step="0.01" value="0.00" readonly style="background:#f8fafc; cursor:not-allowed;"></div>' +
            '</div>' +
            '<div class="BatchMate-ef-submit-row">' +
            '<button class="BatchMate-ef-submit-btn" id="BatchMate-ef-submit-btn-' + evtId + '" data-member-id="' + (isGuest ? 0 : m.id) + '">' +
            submitBtnText + '</button></div>' +
            '</div></div>');

        // Robust Init select2 on event form (multi-pass to handle Elementor & asynchronous script loads)
        function initSelect2Dropdowns() {
            if (typeof $.fn.select2 !== 'undefined') {
                $result.find('.ef-select2').each(function() {
                    var $s = $(this);
                    if (!$s.hasClass('select2-hidden-accessible')) {
                        $s.select2({
                            width: '100%',
                            dropdownAutoWidth: true
                        });
                    }
                });
            }
        }
        initSelect2Dropdowns();
        setTimeout(initSelect2Dropdowns, 100);
        setTimeout(initSelect2Dropdowns, 400);

        // Cascading District -> Upazila
        $result.off('change.presDist').on('change.presDist', '#ef-guest-pres-dist', function(){
            var dist = $(this).val();
            var upzList = (_bmUpazilas && _bmUpazilas[dist]) || [];
            var opts = '<option value="">Select Thana / Upazila...</option>';
            upzList.forEach(function(u){
                opts += '<option value="' + u + '">' + u + '</option>';
            });
            var $upz = $('#ef-guest-pres-upazila');
            $upz.html(opts);
            if (typeof $.fn.select2 !== 'undefined') {
                $upz.trigger('change');
            }
        });

        $result.off('change.permDist').on('change.permDist', '#ef-guest-perm-dist', function(){
            var dist = $(this).val();
            var upzList = (_bmUpazilas && _bmUpazilas[dist]) || [];
            var opts = '<option value="">Select Thana / Upazila...</option>';
            upzList.forEach(function(u){
                opts += '<option value="' + u + '">' + u + '</option>';
            });
            var $upz2 = $('#ef-guest-perm-upazila');
            $upz2.html(opts);
            if (typeof $.fn.select2 !== 'undefined') {
                $upz2.trigger('change');
            }
        });

        // Country toggle District & Upazila
        $result.off('change.presCountry').on('change.presCountry', '#ef-guest-pres-country', function(){
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap-ef-guest-pres-dist, #wrap-ef-guest-pres-upazila').toggle(isBD);
            if (!isBD) {
                $('#ef-guest-pres-dist, #ef-guest-pres-upazila').val('');
                if (typeof $.fn.select2 !== 'undefined') {
                    $('#ef-guest-pres-dist, #ef-guest-pres-upazila').trigger('change');
                }
            }
        });

        $result.off('change.permCountry').on('change.permCountry', '#ef-guest-perm-country', function(){
            var isBD = $(this).val() === 'Bangladesh';
            $('#wrap-ef-guest-perm-dist, #wrap-ef-guest-perm-upazila').toggle(isBD);
            if (!isBD) {
                $('#ef-guest-perm-dist, #ef-guest-perm-upazila').val('');
                if (typeof $.fn.select2 !== 'undefined') {
                    $('#ef-guest-perm-dist, #ef-guest-perm-upazila').trigger('change');
                }
            }
        });

        // For guests: listen to gender change → update gift section
        if (isGuest && hasTshirt) {
            $result.on('change', '#ef-guest-gender', function(){
                var isFem = $(this).val() === 'Female';
                $('#ef-guest-gift-wrap').html(renderGiftOptionHtml(isFem));
                bindGiftRadio();
            });
            // Init gift for default gender (Male)
            $('#ef-guest-gift-wrap').html(renderGiftOptionHtml(false));
        }
        bindGiftRadio();

        // Qty buttons
        $result.on('click', '.BatchMate-ef-qty-btn', function(){
            var $row = $(this).closest('.BatchMate-ef-opt-row');
            var $inp = $row.find('.BatchMate-ef-qty-inp');
            var max  = parseInt($row.data('max')) || 0;
            var fee  = parseFloat($row.data('fee')) || 0;
            var val  = parseInt($inp.val()) || 0;
            if ($(this).data('act') === 'inc') {
                if (max > 0 && val >= max) return;
                val++;
            } else {
                if (val > 0) val--;
            }
            $inp.val(val);
            $row.find('.BatchMate-ef-opt-sub').html('&#2547; '+(val*fee).toFixed(2));
            recalcTotal();
        });

        function recalcTotal() {
            var baseFee = _evtMemberFee;
            if (_isCurrentGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0) {
                baseFee += _memFeeAmount;
            }
            var total = baseFee;
            $result.find('.BatchMate-ef-opt-row').each(function(){
                total += parseFloat($(this).data('fee')) * (parseInt($(this).find('.BatchMate-ef-qty-inp').val())||0);
            });
            if (_evtOptions.length > 0 || _evtMemberFee > 0 || (_isCurrentGuest && allowNonMembers === 2 && _memFeeActive && _memFeeAmount > 0)) {
                $('#BatchMate-ef-pay-amount').val(total.toFixed(2));
            }
        }
        recalcTotal();
    }

    /* ---- Saree/TShirt card selector toggle ---- */
    function bindGiftRadio() {
        $(document).off('click.giftCard', '.ef-gift-card').on('click.giftCard', '.ef-gift-card', function(){
            var $this = $(this);
            $this.siblings('.ef-gift-card').removeClass('selected');
            $this.addClass('selected');
            var val = $this.data('value');
            $this.find('input[name="ef_gift_choice"]').prop('checked', true).trigger('change');
            if (val === 'saree') {
                $('#ef-tshirt-size-wrapper').slideUp(180);
            } else {
                $('#ef-tshirt-size-wrapper').slideDown(180);
            }
        });
    }

    /* -- Render search result -- */
    function renderResult(res, q) {
        if (!res.success) {
            var errData = res.data;
            // Duplicate non-member record
            if (errData && typeof errData === 'object' && errData.code === 'duplicate') {
                $result.html(mkNotice('warning', '&#9888; <strong>' + errData.name + '</strong> has already registered for this event using this email/mobile.'));
                return;
            }
            if (errData === 'not_found') {
                // allow_non_members === 2: Direct Member mode — show form instead of "not found"
                if (allowNonMembers === 2) {
                    renderMemberForm({ member: { is_guest: true }, already: false });
                    return;
                }
                var msg = (allowNonMembers === 1)
                    ? 'No matching member was found. You can register as a non-member or become a member.'
                    : 'No matching member was found. You must be a Batchmate member to register.';
                var btnHtml = '<a href="' + regLink + '" class="BatchMate-ef-submit-btn" style="background:#2271b1;text-decoration:none;" target="_blank">Become a Member</a>';
                if (allowNonMembers === 1) {
                    btnHtml += ' <button type="button" class="BatchMate-ef-submit-btn" id="BatchMate-ef-guest-btn" style="background:#6c757d;">Register as Guest</button>';
                }
                $result.html('<div class="ef-anim BatchMate-ef-notice BatchMate-ef-notice--warning" style="background:#fff;border-color:#dee2e6;">' +
                    '<h4 style="margin:0 0 10px;color:#1d2327;">No Member Found</h4>' +
                    '<p style="margin:0 0 15px;color:#6c757d;">' + msg + '</p>' +
                    '<div style="display:flex;gap:10px;flex-wrap:wrap;">' + btnHtml + '</div></div>');
            } else {
                $result.html(mkNotice('error', res.data || 'An error occurred.'));
            }
            return;
        }

        var members = res.data.members;
        if (!members || members.length === 0) {
            $result.html(mkNotice('error', 'No members found.'));
            return;
        }

        // If only 1 result -> show form directly
        if (members.length === 1) {
            renderMemberForm(members[0]);
            return;
        }

        // Multiple results -> show selection list
        var listHtml = '<div class="ef-anim BatchMate-ef-member-list">' +
            '<p class="BatchMate-ef-member-list-title">&#128269; ' + members.length + ' matching members found — select yours:</p>' +
            '<div class="BatchMate-ef-member-list-items">';

        members.forEach(function(md, idx) {
            var m = md.member;
            var photo = m.photo_url || noPhoto;
            var statusBadge = '';
            if (md.already) {
                var st = md.app_status;
                var stLabel = st === 'approved' ? '&#10003; Approved' : (st === 'rejected' ? '&#10008; Rejected' : '&#8987; Pending');
                var stBg    = st === 'approved' ? '#d1e7dd' : (st === 'rejected' ? '#f8d7da' : '#fff3cd');
                var stClr   = st === 'approved' ? '#0a7242' : (st === 'rejected' ? '#d63638' : '#856404');
                statusBadge = '<span class="BatchMate-ef-ml-status" style="background:' + stBg + ';color:' + stClr + ';">' + stLabel + '</span>';
            }
            listHtml += '<div class="BatchMate-ef-ml-item" data-idx="' + idx + '">' +
                '<img src="' + photo + '" class="BatchMate-ef-ml-photo">' +
                '<div class="BatchMate-ef-ml-info">' +
                '<span class="BatchMate-ef-mid">' + (m.member_number||'') + '</span>' +
                '<strong class="BatchMate-ef-ml-name">' + (m.name||'') + '</strong>' +
                '<span class="BatchMate-ef-ml-meta">' + (m.institution||'') + (m.batch ? ' &bull; Batch ' + m.batch : '') + '</span>' +
                '</div>' + statusBadge +
                '<span class="BatchMate-ef-ml-arrow">&#8250;</span>' +
                '</div>';
        });

        listHtml += '</div></div>';
        $result.html(listHtml);

        $result.on('click', '.BatchMate-ef-ml-item', function() {
            renderMemberForm(members[parseInt($(this).data('idx'))]);
        });
    }
    $(document).on('click', '#BatchMate-ef-guest-btn', function() {
        renderMemberForm({
            member: { is_guest: true },
            already: false
        });
    });

    $(document).on('change', '#ef-guest-photo', function() {
        var input = this;
        var file = input.files && input.files[0];
        if (!file) return;

        $('#ef-photo-filename').text(file.name + ' (Selected)');

        if (!file.type.match(/^image\//)) return;

        // INSTANT preview update without any delay
        if (window.URL && window.URL.createObjectURL) {
            try {
                $('#ef-photo-preview-img').attr('src', window.URL.createObjectURL(file));
                $('#ef-photo-thumb-container').show();
            } catch(e){}
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            $('#ef-photo-preview-img').attr('src', e.target.result);

            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                canvas.width = 400;
                canvas.height = 400;
                var ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                var w = img.width, h = img.height;
                var cropSize = Math.min(w, h);
                var sx = (w - cropSize) / 2, sy = (h - cropSize) / 2;
                ctx.drawImage(img, sx, sy, cropSize, cropSize, 0, 0, 400, 400);
                if (canvas.toBlob && window.DataTransfer) {
                    canvas.toBlob(function(blob) {
                        if (!blob) return;
                        var baseName = file.name ? file.name.replace(/\.[^/.]+$/, "") : 'photo';
                        var resizedFile = new File([blob], baseName + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                        var dt = new DataTransfer();
                        dt.items.add(resizedFile);
                        input.files = dt.files;
                    }, 'image/jpeg', 0.90);
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    /* -- Submit application -- */
    $(document).on('click', '#BatchMate-ef-submit-btn-' + evtId, function(e) {
        if (e && e.preventDefault) e.preventDefault();
        var $btn = $(this);
        if ($btn.prop('disabled') || $btn.hasClass('is-submitting') || $btn.attr('readonly')) {
            return false;
        }

        var method  = $('#BatchMate-ef-pay-method').val();
        var details = $.trim($('#BatchMate-ef-pay-details').val());
        var amount  = parseFloat($('#BatchMate-ef-pay-amount').val()) || 0;
        var mId     = $btn.data('member-id');

        var isGuest = (mId == '0' || !mId);
        var formData = new FormData();

        if (isGuest) {
            var gName     = $.trim($('#ef-guest-name').val());
            var gNameBn   = $.trim($('#ef-guest-name-bn').val());
            var gCell     = $.trim($('#ef-guest-cell').val());
            var gEmail    = $.trim($('#ef-guest-email').val());
            var gGender   = $('#ef-guest-gender').val() || 'Male';
            var gBlood    = $('#ef-guest-blood').val();
            var gInst     = $.trim($('#ef-guest-institution').val());
            var gRoll     = $.trim($('#ef-guest-roll').val());
            var gRegno    = $.trim($('#ef-guest-regno').val());
            var gBoard    = $.trim($('#ef-guest-board').val());
            var gGroup    = $('#ef-guest-group').val();
            var gDob      = $('#ef-guest-dob').val();
            var gReligion = $('#ef-guest-religion').val();
            var gLand     = $.trim($('#ef-guest-land-phone').val());
            var gPresAddr = $.trim($('#ef-guest-pres-addr').val());
            var gPermAddr = $.trim($('#ef-guest-perm-addr').val());
            var gPresCountry = $('#ef-guest-pres-country').val() || 'Bangladesh';
            var gPresDist = $.trim($('#ef-guest-pres-dist').val());
            var gPresUpz  = $.trim($('#ef-guest-pres-upazila').val());
            var gPermCountry = $('#ef-guest-perm-country').val() || 'Bangladesh';
            var gPermDist = $.trim($('#ef-guest-perm-dist').val());
            var gPermUpz  = $.trim($('#ef-guest-perm-upazila').val());
            var gOrg      = $.trim($('#ef-guest-org').val());
            var gPos      = $.trim($('#ef-guest-pos').val());
            var gInd      = $.trim($('#ef-guest-ind').val());
            var gExp      = $.trim($('#ef-guest-exp').val());
            var gEmpAddr  = $.trim($('#ef-guest-emp-addr').val());
            var gPrevOrg  = $.trim($('#ef-guest-prev-org').val());
            var gAbout    = $.trim($('#ef-guest-about').val());
            var gSkills   = $.trim($('#ef-guest-skills').val());
            var gFb       = $.trim($('#ef-guest-fb').val());
            var gLi       = $.trim($('#ef-guest-li').val());
            var gX        = $.trim($('#ef-guest-x').val());
            var gWeb      = $.trim($('#ef-guest-web').val());
            var gPhotoInp = document.getElementById('ef-guest-photo');
            var gPhoto    = gPhotoInp && gPhotoInp.files && gPhotoInp.files.length > 0 ? gPhotoInp.files[0] : null;

            var fields = guestFieldsCfg;
            var missingFields = [];
            var $firstMissing = null;

            // Remove existing error styling
            $result.find('.ef-field-error').removeClass('ef-field-error');

            function markMissing(label, $elem) {
                missingFields.push(label);
                if ($elem && $elem.length) {
                    $elem.addClass('ef-field-error');
                    $elem.next('.select2-container').find('.select2-selection').addClass('ef-field-error');
                    if (!$firstMissing) {
                        $firstMissing = $elem;
                    }
                }
            }

            if (!gName) markMissing('Name / পুরো নাম', $('#ef-guest-name'));
            if (!gCell) markMissing('Mobile / মোবাইল নম্বর', $('#ef-guest-cell'));
            if (fields.name_bn && fields.name_bn.show && fields.name_bn.req && !gNameBn) markMissing(fields.name_bn.label || 'Name (Bangla) / বাংলা নাম', $('#ef-guest-name-bn'));
            if (fields.email && fields.email.show && fields.email.req && !gEmail) markMissing('Email / ইমেইল', $('#ef-guest-email'));
            if (fields.gender && fields.gender.show && fields.gender.req && !gGender) markMissing('Gender / লিঙ্গ', $('#ef-guest-gender'));
            if (fields.blood_group && fields.blood_group.show && fields.blood_group.req && !gBlood) markMissing('Blood Group / রক্তের গ্রুপ', $('#ef-guest-blood'));
            if (fields.dob && fields.dob.show && fields.dob.req && !gDob) markMissing('Date of Birth / জন্মতারিখ', $('#ef-guest-dob'));
            if (fields.religion && fields.religion.show && fields.religion.req && !gReligion) markMissing('Religion / ধর্ম', $('#ef-guest-religion'));
            if (fields.land_phone && fields.land_phone.show && fields.land_phone.req && !gLand) markMissing('Alternative Number / বিকল্প নম্বর', $('#ef-guest-land-phone'));
            if (fields.board && fields.board.show && fields.board.req && !gBoard) markMissing('Board / বোর্ড', $('#ef-guest-board'));
            if (fields.institution && fields.institution.show && fields.institution.req && !gInst) markMissing(fields.institution.label || 'Institution / প্রতিষ্ঠান', $('#ef-guest-institution'));
            if (fields.roll_no && fields.roll_no.show && fields.roll_no.req && !gRoll) markMissing('Roll No / রোল নম্বর', $('#ef-guest-roll'));
            if (fields.reg_no && fields.reg_no.show && fields.reg_no.req && !gRegno) markMissing('Reg No / রেজিস্ট্রেশন নম্বর', $('#ef-guest-regno'));
            if (fields.group && fields.group.show && fields.group.req && !gGroup) markMissing('Group / বিভাগ', $('#ef-guest-group'));
            if (fields.present_address && fields.present_address.show && fields.present_address.req && !gPresAddr) markMissing('Present Address / বর্তমান ঠিকানা', $('#ef-guest-pres-addr'));
            if (fields.present_country && fields.present_country.show && fields.present_country.req && !gPresCountry) markMissing('Present Country / বর্তমান দেশ', $('#ef-guest-pres-country'));
            if (fields.present_district && fields.present_district.show && fields.present_district.req && gPresCountry === 'Bangladesh' && !gPresDist) markMissing(fields.present_district.label || 'Present District / বর্তমান জেলা', $('#ef-guest-pres-dist'));
            if (fields.present_upazila && fields.present_upazila.show && fields.present_upazila.req && gPresCountry === 'Bangladesh' && !gPresUpz) markMissing(fields.present_upazila.label || 'Present Thana / Upazila / বর্তমান থানা/উপজেলা', $('#ef-guest-pres-upazila'));
            if (fields.permanent_address && fields.permanent_address.show && fields.permanent_address.req && !gPermAddr) markMissing('Permanent Address / স্থায়ী ঠিকানা', $('#ef-guest-perm-addr'));
            if (fields.permanent_country && fields.permanent_country.show && fields.permanent_country.req && !gPermCountry) markMissing('Permanent Country / স্থায়ী দেশ', $('#ef-guest-perm-country'));
            if (fields.permanent_district && fields.permanent_district.show && fields.permanent_district.req && gPermCountry === 'Bangladesh' && !gPermDist) markMissing(fields.permanent_district.label || 'Permanent District / স্থায়ী জেলা', $('#ef-guest-perm-dist'));
            if (fields.permanent_upazila && fields.permanent_upazila.show && fields.permanent_upazila.req && gPermCountry === 'Bangladesh' && !gPermUpz) markMissing(fields.permanent_upazila.label || 'Permanent Thana / Upazila / স্থায়ী থানা/উপজেলা', $('#ef-guest-perm-upazila'));
            if (fields.organization && fields.organization.show && fields.organization.req && !gOrg) markMissing('Organization / প্রতিষ্ঠান', $('#ef-guest-org'));
            if (fields.position && fields.position.show && fields.position.req && !gPos) markMissing('Position / পদবি', $('#ef-guest-pos'));
            if (fields.industry && fields.industry.show && fields.industry.req && !gInd) markMissing('Industry / সেক্টর', $('#ef-guest-ind'));
            if (fields.work_experience && fields.work_experience.show && fields.work_experience.req && !gExp) markMissing('Work Experience / অভিজ্ঞতা', $('#ef-guest-exp'));
            if (fields.employment_address && fields.employment_address.show && fields.employment_address.req && !gEmpAddr) markMissing('Organization Address / অফিসের ঠিকানা', $('#ef-guest-emp-addr'));
            if (fields.previous_organizations && fields.previous_organizations.show && fields.previous_organizations.req && !gPrevOrg) markMissing('Previous Organizations / পূর্ববর্তী প্রতিষ্ঠান', $('#ef-guest-prev-org'));
            if (fields.about_me && fields.about_me.show && fields.about_me.req && !gAbout) markMissing('About Me / নিজের সম্পর্কে', $('#ef-guest-about'));
            if (fields.skills && fields.skills.show && fields.skills.req && !gSkills) markMissing('Skills / দক্ষতা', $('#ef-guest-skills'));
            if (fields.facebook && fields.facebook.show && fields.facebook.req && !gFb) markMissing('Facebook URL / ফেসবুক লিঙ্ক', $('#ef-guest-fb'));
            if (fields.linkedin && fields.linkedin.show && fields.linkedin.req && !gLi) markMissing('LinkedIn URL / লিংকডইন লিঙ্ক', $('#ef-guest-li'));
            if (fields.x && fields.x.show && fields.x.req && !gX) markMissing('X / Twitter URL', $('#ef-guest-x'));
            if (fields.website && fields.website.show && fields.website.req && !gWeb) markMissing('Personal Website / ওয়েবসাইট', $('#ef-guest-web'));
            if (fields.photo && fields.photo.show && fields.photo.req && !gPhoto) markMissing('Photo (400x400) / ছবি', $('#ef-guest-photo'));

            if (missingFields.length > 0) {
                var missingListHtml = '<ul style="text-align:left; margin:10px 0 0; padding-left:22px; color:#dc2626; font-size:13.5px; line-height:1.8;">' +
                    missingFields.map(function(f){ return '<li><strong>' + f + '</strong> পূরণ করা আবশ্যক</li>'; }).join('') +
                    '</ul>';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'প্রয়োজনীয় ফিল্ড পূরণ করুন (*)',
                        html: '<p style="margin:0; color:#475569; font-size:14px;">অনুগ্রহ করে নিচের লাল চিহ্নিত <strong>(*)</strong> ফিল্ডগুলো পূরণ করুন:</p>' + missingListHtml,
                        confirmButtonText: 'ঠিক আছে, পূরণ করছি',
                        confirmButtonColor: '#2563eb',
                    }).then(function(){
                        if ($firstMissing && $firstMissing.length) {
                            $('html, body').animate({ scrollTop: $firstMissing.offset().top - 140 }, 300);
                            $firstMissing.focus();
                        }
                    });
                } else {
                    alert('প্রয়োজনীয় ফিল্ড পূরণ করুন (*):\n\n' + missingFields.join('\n'));
                    if ($firstMissing && $firstMissing.length) {
                        $('html, body').animate({ scrollTop: $firstMissing.offset().top - 140 }, 300);
                        $firstMissing.focus();
                    }
                }
                return;
            }

            formData.append('is_guest', '1');
            formData.append('guest_name',   gName);
            formData.append('guest_name_bn', gNameBn);
            formData.append('guest_email',  gEmail);
            formData.append('guest_cell',   gCell);
            formData.append('guest_gender', gGender);
            formData.append('guest_blood',  gBlood);
            formData.append('guest_institution', gInst);
            formData.append('guest_roll',   gRoll);
            formData.append('guest_regno',  gRegno);
            formData.append('guest_board',  gBoard);
            formData.append('guest_group',  gGroup);
            formData.append('guest_dob',    gDob);
            formData.append('guest_religion', gReligion);
            formData.append('guest_land_phone', gLand);
            formData.append('guest_present_address', gPresAddr);
            formData.append('guest_present_country', gPresCountry);
            formData.append('guest_present_district', gPresDist);
            formData.append('guest_present_upazila', gPresUpz);
            formData.append('guest_permanent_address', gPermAddr);
            formData.append('guest_permanent_country', gPermCountry);
            formData.append('guest_permanent_district', gPermDist);
            formData.append('guest_upazila', gPermUpz);
            formData.append('guest_organization', gOrg);
            formData.append('guest_position', gPos);
            formData.append('guest_industry', gInd);
            formData.append('guest_work_experience', gExp);
            formData.append('guest_employment_address', gEmpAddr);
            formData.append('guest_previous_organizations', gPrevOrg);
            formData.append('guest_about_me', gAbout);
            formData.append('guest_skills', gSkills);
            formData.append('guest_facebook', gFb);
            formData.append('guest_linkedin', gLi);
            formData.append('guest_x', gX);
            formData.append('guest_website', gWeb);
            if (gPhoto) formData.append('guest_photo', gPhoto);
        } else {
            formData.append('member_id', mId);
        }

        if (!method) {
            Swal.fire({
                icon: 'warning',
                title: 'Payment Method Required',
                text: 'Please select a payment method.',
                confirmButtonColor: '#2271b1',
            });
            $('#BatchMate-ef-pay-method').focus();
            return;
        }
        if (!details) {
            Swal.fire({
                icon: 'warning',
                title: 'Transaction ID Required',
                text: 'Please enter your Transaction / Reference ID.',
                confirmButtonColor: '#2271b1',
            });
            $('#BatchMate-ef-pay-details').focus();
            return;
        }
        var totalExpected = parseFloat($('#BatchMate-ef-pay-amount').val()) || 0;
        if (totalExpected > 0 && amount <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Amount',
                text: 'Please enter a valid payment amount.',
                confirmButtonColor: '#2271b1',
            });
            $('#BatchMate-ef-pay-amount').focus();
            return;
        }

        var optsArr = [];
        $result.find('.BatchMate-ef-opt-row').each(function(){
            var qty = parseInt($(this).find('.BatchMate-ef-qty-inp').val()) || 0;
            optsArr.push({
                option_id: $(this).data('optid'),
                name:      $(this).data('name'),
                qty:       qty,
                fee:       parseFloat($(this).data('fee')),
                subtotal:  qty * parseFloat($(this).data('fee'))
            });
        });
        var options_json = optsArr.length ? JSON.stringify(optsArr) : '';

        formData.append('action', 'BatchMate_submit_event_application');
        formData.append('event_id', evtId);
        formData.append('payment_method', method);
        formData.append('payment_details', details);
        formData.append('amount', amount);
        formData.append('options_json', options_json);
        formData.append('_wpnonce', nonce);

        // T-Shirt / Saree (if event has T-shirt enabled)
        if (hasTshirt) {
            var giftChoice = $('input[name="ef_gift_choice"]:checked').val() || 'tshirt';
            var tshirtVal  = ''
            if (giftChoice === 'saree') {
                tshirtVal = 'Saree';
            } else {
                tshirtVal = $('#ef-tshirt-size').val();
                if (!tshirtVal) {
                    var $ts = $('#ef-tshirt-size');
                    $ts.addClass('ef-field-error');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'টি-শার্ট সাইজ আবশ্যক',
                            text: 'অনুগ্রহ করে আপনার টি-শার্ট সাইজ নির্বাচন করুন।',
                            confirmButtonText: 'ঠিক আছে',
                            confirmButtonColor: '#2563eb',
                        }).then(function(){ $ts.focus(); });
                    } else {
                        alert('অনুগ্রহ করে টি-শার্ট সাইজ নির্বাচন করুন।');
                        $ts.focus();
                    }
                    return;
                }
            }
            formData.append('tshirt_size', tshirtVal);
        }

        var originalBtnHtml = $btn.html();
        $btn.prop('disabled', true)
            .attr('readonly', 'readonly')
            .addClass('is-submitting')
            .css({'pointer-events': 'none', 'opacity': '0.7', 'cursor': 'not-allowed'})
            .html('<span class="ef-btn-spinner"></span> সাবমিট হচ্ছে...');
        $('#ef-submit-error-banner').remove();

        $.ajax({
            url: ajaxUrl, type: 'POST',
            data: formData,
            processData: false, contentType: false
        }).done(function(res) {
            if (res.success) {
                var successMsg = res.data || 'Your application has been submitted successfully. Please wait for approval.';
                $result.html('<div class="ef-anim" id="ef-success-box-' + evtId + '">' + mkNotice('success', '&#10003; ' + successMsg) + '</div>');
                $input.val(''); lastQ = '';
                $hint.html('&#9660; Results appear automatically after 3 characters.').removeClass('typing');

                // Smooth scroll up to where the success message is shown
                setTimeout(function() {
                    var $target = $('#ef-success-box-' + evtId);
                    if (!$target.length || !$target.is(':visible')) {
                        $target = $result;
                    }
                    if (!$target.length || !$target.is(':visible')) {
                        $target = $('#BatchMate-ef-wrap-' + evtId);
                    }
                    if ($target.length) {
                        var targetOffset = $target.offset().top - 140;
                        var $adminBar = $('#wpadminbar');
                        if ($adminBar.length && $adminBar.is(':visible')) {
                            targetOffset -= $adminBar.outerHeight();
                        }
                        if (targetOffset < 0) {
                            targetOffset = 0;
                        }
                        $('html, body').stop().animate({
                            scrollTop: targetOffset
                        }, 500);
                    }
                }, 80);
            } else {
                // Keep the form intact so user does not lose their entered data!
                $btn.prop('disabled', false)
                    .removeAttr('readonly')
                    .removeClass('is-submitting')
                    .css({'pointer-events': '', 'opacity': '', 'cursor': ''})
                    .html(originalBtnHtml);
                var errMsg = res.data || 'Failed to submit registration.';
                
                // Show notice banner inside form
                $('#ef-submit-error-banner').remove();
                $result.find('.BatchMate-ef-card-body').prepend('<div class="ef-anim" id="ef-submit-error-banner" style="margin-bottom:16px;">' + mkNotice('error', '&#10008; ' + errMsg) + '</div>');
                $('html, body').animate({ scrollTop: $('#ef-submit-error-banner').offset().top - 140 }, 300);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'আবেদন গ্রহণ করা যায়নি',
                        html: '<p style="color:#b91c1c; font-size:14px; margin:0;">' + errMsg + '</p>',
                        confirmButtonText: 'ঠিক আছে',
                        confirmButtonColor: '#2563eb',
                    });
                }
            }
        }).fail(function() {
            $btn.prop('disabled', false)
                .removeAttr('readonly')
                .removeClass('is-submitting')
                .css({'pointer-events': '', 'opacity': '', 'cursor': ''})
                .html(originalBtnHtml);
            var connErr = 'Connection error. Please try again.';
            $('#ef-submit-error-banner').remove();
            $result.find('.BatchMate-ef-card-body').prepend('<div class="ef-anim" id="ef-submit-error-banner" style="margin-bottom:16px;">' + mkNotice('error', '&#10008; ' + connErr) + '</div>');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'সংযোগ সমস্যা',
                    text: connErr,
                    confirmButtonColor: '#2563eb',
                });
            } else {
                alert(connErr);
            }
        });
    });

    // Clear red error highlights as soon as user types or changes the field
    $(document).on('input change', '.BatchMate-ef-wrap input, .BatchMate-ef-wrap select, .BatchMate-ef-wrap textarea', function(){
        $(this).removeClass('ef-field-error');
        $(this).next('.select2-container').find('.select2-selection').removeClass('ef-field-error');
        $('#ef-submit-error-banner').slideUp(200);
    });

    // Option 4 render is now handled inside the $.ajax().done() callback above
    // so that _evtOptions and _evtMemberFee are populated before the form renders.


    }

    $(document).ready(function() {
        var forms = window.batchmate_event_forms || [];
        forms.forEach(function(cfg) {
            initBatchmateEventForm(cfg);
        });
    });
})(jQuery);
