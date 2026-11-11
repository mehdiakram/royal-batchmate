<div id="tab-breadcrumb" class="BatchMate-tab-pane">
            <div class="BatchMate-settings-card">
                <div class="BatchMate-card-head">
                    <span class="dashicons dashicons-layout"></span>
                    <h3>Page Title &amp; Breadcrumb Design</h3>
                </div>
                <div class="BatchMate-card-body">
                    <p style="margin-bottom: 20px; color: #50575e;">Customize the background and typography of the top banner shown on the Registration and Member List pages.</p>
                    
                    <div class="BatchMate-field-grid">
                        <div class="BatchMate-field-group">
                            <label for="bc_bg_type">Background Style</label>
                            <select id="bc_bg_type" name="bc_bg_type" onchange="document.getElementById('bc_color_wrap').style.display = (this.value === 'color' || this.value === 'both') ? 'flex' : 'none'; document.getElementById('bc_image_wrap').style.display = (this.value === 'image' || this.value === 'both') ? 'flex' : 'none';">
                                <option value="color" <?php selected('color', $bc_bg_type); ?>>Solid Color</option>
                                <option value="image" <?php selected('image', $bc_bg_type); ?>>Image</option>
                            </select>
                        </div>
                        
                        <div class="BatchMate-field-group" id="bc_color_wrap" style="display: <?php echo ($bc_bg_type === 'color' || $bc_bg_type === 'both') ? 'flex' : 'none'; ?>;">
                            <label for="bc_bg_color">Background Color</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" id="bc_bg_color_picker" value="<?php echo esc_attr($bc_bg_color); ?>" oninput="document.getElementById('bc_bg_color').value = this.value;" style="height:42px; width:42px; padding:0; border-radius:4px; border:1px solid #ccc; cursor:pointer;">
                                <input type="text" id="bc_bg_color" name="bc_bg_color" value="<?php echo esc_attr($bc_bg_color); ?>" oninput="document.getElementById('bc_bg_color_picker').value = this.value;" style="flex:1;">
                            </div>
                        </div>

                        <div class="BatchMate-field-group BatchMate-field-full" id="bc_image_wrap" style="display: <?php echo ($bc_bg_type === 'image' || $bc_bg_type === 'both') ? 'flex' : 'none'; ?>;">
                            <label for="bc_bg_image">Background Image</label>
                            <div style="display:flex; gap:10px;">
                                <input type="text" id="bc_bg_image" name="bc_bg_image" value="<?php echo esc_attr($bc_bg_image); ?>" placeholder="Paste image URL or use uploader..." style="flex:1;">
                                <button type="button" class="BatchMate-save-btn" id="bc_bg_image_upload_btn" style="padding: 0 15px; background: #64748b; border:none;"><span class="dashicons dashicons-upload" style="margin-top:2px;"></span> Upload</button>
                            </div>
                            <p class="BatchMate-field-hint">Recommended size: 1920x300 pixels.</p>
                        </div>
                        
                        <div class="BatchMate-field-group">
                            <label for="bc_text_color">Text Color</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" id="bc_text_color_picker" value="<?php echo esc_attr($bc_text_color); ?>" oninput="document.getElementById('bc_text_color').value = this.value;" style="height:42px; width:42px; padding:0; border-radius:4px; border:1px solid #ccc; cursor:pointer;">
                                <input type="text" id="bc_text_color" name="bc_text_color" value="<?php echo esc_attr($bc_text_color); ?>" oninput="document.getElementById('bc_text_color_picker').value = this.value;" style="flex:1;">
                            </div>
                        </div>
                        
                        <div class="BatchMate-field-group">
                            <label for="bc_font_size">Title Font Size (px)</label>
                            <input type="number" id="bc_font_size" name="bc_font_size" value="<?php echo esc_attr($bc_font_size); ?>" min="14" max="100">
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /#tab-breadcrumb -->

    </form>