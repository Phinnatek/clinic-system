<?php require_once 'includes/header.php'; 
// Fetch pristine live record indicators for the active user account including relative profile image URL
$profileQuery = $con->prepare("SELECT staff_id, full_name, role, profile_image, DATE_FORMAT(created_at, '%d-%M-%Y') as date_joined FROM users WHERE id = ? LIMIT 1");
$profileQuery->execute([$admin_id]);
$profileRow = $profileQuery->fetch(PDO::FETCH_ASSOC);

$db_staff_id   = $profileRow['staff_id'] ?? $_SESSION['staff_id'];
$db_full_name  = $profileRow['full_name'] ?? $_SESSION['full_name'];
$db_role       = $profileRow['role'] ?? $_SESSION['role'];
$db_date_joined = $profileRow['date_joined'] ?? date('d-M-Y');
$db_avatar     = $profileRow['profile_image'] ?? '';
?>

<div class="container-fluid p-2.5">
    <div class="mb-2.5">
        <h4 class="fw-bold text-dark mb-0"><i class="bx bx-user text-primary me-1"></i> My Account Profile</h4>
        <small class="text-muted">Manage your personal credential tokens, update profiles pictures, and verify terminal passwords.</small>
    </div>

    <div class="row g-2.5">
         <!-- COLUMN 1: INTERACTIVE AVATAR GRAPHICS CHIP CARD -->
<div class="col-md-4">
    <div class="card shadow-none border text-center p-2.5 h-100 bg-white">
        
        <!-- HOVER PROFILE PHOTO INTERCEPT TRIGGER BOX WRAPPER -->
        <div class="position-relative mx-auto mb-2" style="width:90px; height:90px;">
            <!-- Main Avatar Container Ring -->
            <div class="w-100 h-100 rounded-circle overflow-hidden border" style="cursor:pointer;" onclick="$('#avatar_file_input').trigger('click');" title="Click to upload profile picture">
                <img src="<?php echo (!empty($db_avatar) && file_exists($db_avatar)) ? $db_avatar : 'assets/img/avatars/1.png'; ?>"  id="profile_avatar_display" class="w-100 h-100 object-fit-cover" alt="Avatar" />
            </div>
            
            <!-- FLOATING ACTIONS BADGE: Blue Round Camera Circle Button on Bottom Right Edge -->
            <div class="position-absolute d-flex align-items-center justify-content-center bg-primary text-white shadow" 
                 style="width: 26px; height: 26px; border-radius: 50%; bottom: 0; right: 0; cursor: pointer; border: 2px solid #ffffff; transition: transform 0.2s;" 
                 onclick="$('#avatar_file_input').trigger('click');"
                 onmouseover="this.style.transform='scale(1.1)'" 
                 onmouseout="this.style.transform='scale(1)'"
                 title="Change profile picture">
                <i class="bx bx-camera" style="font-size: 0.85rem;"></i>
            </div>
        </div>
        
        <!-- HIDDEN FILE STREAM INPUT -->
        <input type="file" id="avatar_file_input" class="d-none" accept="image/png, image/jpeg, image/jpg" />

        <h5 class="fw-bold text-dark mb-0.5" id="lbl_display_full_name"><?php echo htmlspecialchars($db_full_name); ?></h5>
        <span class="badge bg-label-primary font-monospace text-uppercase text-xs mx-auto mb-2.5" style="width:fit-content;"><?php echo htmlspecialchars($db_role); ?></span>
        
        <!-- UPDATED: Added px-2 padding selectors onto the item metrics blocks text columns -->
        <div class="border-top pt-2 text-start font-monospace small px-2" style="font-size:0.75rem;">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">System Log ID:</span>
                <b class="text-dark">#<?php echo $admin_id; ?></b>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">staff_id:</span>
                <b class="text-secondary"><?php echo htmlspecialchars($db_staff_id); ?></b>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Date Registered:</span>
                <b class="text-muted"><?php echo $db_date_joined; ?></b>
            </div>
        </div>
        
    </div>
</div>


        <!-- COLUMN 2: INTERACTIVE CREDENTIAL MODIFICATION SHEET -->
        <div class="col-md-8">
            <div class="card shadow-none border h-100 bg-white">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.82rem;"><i class="bx bx-cog text-secondary me-1"></i> Security Credentials Configuration</h6>
                </div>
                <div class="card-body p-2.5">
                    <form id="frmUpdateProfile" method="POST">
                        <input type="hidden" name="action_update_profile" value="true" />
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-semibold mb-0.5" for="full_name">Legal Full Name</label>
                            <input type="text" name="full_name" id="full_name" class="form-control form-control-sm text-dark fw-semibold" value="<?php echo htmlspecialchars($db_full_name); ?>" required />
                        </div>
                        <div class="border rounded p-2 bg-light-subtle mb-2.5">
                            <h6 class="fw-bold text-dark small mb-1.5" style="font-size:0.78rem;"><i class="bx bx-lock-open-alt text-danger me-0.5"></i> Change Security Access Passphrase <small class="text-muted fw-normal">(Optional)</small></h6>
                            <div class="mb-2">
                                <label class="form-label text-muted small scale-xs mb-0" style="font-size:0.7rem;" for="current_password">Verify Current Password</label>
                                <input type="password" name="current_password" id="current_password" class="form-control form-control-sm font-monospace" placeholder="••••••••" />
                            </div>
                            <div class="row g-2">
    <div class="col-6">
        <label class="form-label text-muted small scale-xs mb-0" for="new_password">New Password</label>
        <!-- Added real-time keyup input listener class -->
        <input type="password" name="new_password" id="new_password" class="form-control form-control-sm font-monospace" placeholder="••••••••" autocomplete="new-password" />
        
        <!-- DYNAMIC STRENGTH METER ENGINE INTERFACE DISPLAY -->
        <div id="password-strength-container" class="mt-1" style="display: none;">
            <div class="progress" style="height: 4px; border-radius: 4px; background-color: #f1f3f7;">
                <div id="strength-meter-bar" class="progress-bar transition-all" role="progressbar" style="width: 0%;"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-0.5" style="font-size: 0.65rem; font-family: monospace;">
                <span id="strength-meter-text" class="fw-bold text-muted">Weak</span>
                <span class="text-muted" id="strength-requirement-hint">Must contain atleast 6 characters of letters, numbers & symbols</span>
            </div>
        </div>
    </div>
    <div class="col-6">
        <label class="form-label text-muted small scale-xs mb-0" for="confirm_password">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-sm font-monospace" placeholder="••••••••" autocomplete="new-password" />
    </div>
</div>
 
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold shadow-sm"><i class="bx bx-check-shield fs-5 me-1"></i> Authorize Digital Signature & Save Updates</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- POPUP CROPPING WORK BENCH MODAL LAYOUT FRAME -->
<div class="modal fade" id="mdlCropperWorkbench" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom py-1.5 bg-light">
                <h6 class="modal-title fw-bold text-dark mb-0" style="font-size:0.82rem;"><i class="bx bx-crop me-1 text-warning"></i> Adjust Profile Picture</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="$('#avatar_file_input').val('');"></button>
            </div>
            <div class="modal-body p-1 d-flex justify-content-center bg-dark" style="max-height:300px;"><img id="cropper_image_canvas_hook" src="" style="max-width:100%; display:block;" /></div>
            <div class="modal-footer border-top p-1.5 bg-light d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2" data-bs-dismiss="modal" onclick="$('#avatar_file_input').val('');">Cancel</button>
                <button type="button" class="btn btn-warning btn-xs py-1 px-3 fw-bold shadow-sm" id="btn_save_cropped_avatar"><i class="bx bx-upload me-0.5"></i> Slice & Upload</button>
            </div>
        </div>
    </div>
</div>
 
<?php require_once 'includes/footer.php';?>

 
<script>

var centralCropperInstance = null;

// FILE CHANGE INTERCEPTOR: DOCK IMAGE AND FIRE UP POPUP MODAL
$('#avatar_file_input').on('change', function(e) {
    var files = e.target.files;
    if (files && files.length > 0) {
        var fileReader = new FileReader();
        fileReader.onload = function(event) {
            if (centralCropperInstance) { centralCropperInstance.destroy(); }
            
            $('#cropper_image_canvas_hook').attr('src', event.target.result);
            $('#mdlCropperWorkbench').modal('show');
        };
        fileReader.readAsDataURL(files[0]);
    }
});

// INITIALISE THE CROP MATRIX RULES ONCE THE WORKBENCH MOUNTS
$('#mdlCropperWorkbench').on('shown.bs.modal', function () {
    var imageElement = document.getElementById('cropper_image_canvas_hook');
    centralCropperInstance = new Cropper(imageElement, {
        aspectRatio: 1, // Locks the selection zone to a perfect 1:1 square box layout
        viewMode: 1,
        autoCropArea: 0.85,
        background: false,
        movable: true,
        zoomable: true
    });
}).on('hidden.bs.modal', function () {
    if (centralCropperInstance) {
        centralCropperInstance.destroy();
        centralCropperInstance = null;
    }
});

// CLIENT-SIDE SLICER & BASE64 AJAX DISPATCH TRANSMISSION POST ENGINE
$('#btn_save_cropped_avatar').on('click', function(e) {
    e.preventDefault();
    if (!centralCropperInstance) return;

    // PERFORMANCE: Generate the cropped image block on the client side canvas first
    var croppedCanvasElement = centralCropperInstance.getCroppedCanvas({
        width: 200,   // Downscales image payload parameters to clear network lags
        height: 200,  
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    // Extract the raw canvas rendering snapshot as a Base64 dataURL string pack
    var base64EncodedImageString = croppedCanvasElement.toDataURL('image/png');

    Swal.fire({ title: 'Uploading cropped avatar...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

    $.ajax({
        url: server_url,
        type: 'POST',
        data: {
            action_upload_base64_avatar: 'true',
            avatar_base64: base64EncodedImageString // Ships data as a clean standard text key parameters value
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            if (response.status === 'success') {
                $('#mdlCropperWorkbench').modal('hide');
                $('#avatar_file_input').val('');
                console.log('response: ', response);
                Swal.fire({ title: 'Avatar Saved', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                
                // Uses the return image_url parameter dynamically
                $('#profile_avatar_display').attr('src', response.image_url);
                $('#staff_image').attr('src', response.image_url);
                $('#staff_image1').attr('src', response.image_url);
            } else {
                Swal.fire('Upload Blocked', response.message, 'error');
            }
        },
        error: function(xhr) {
            Swal.close();
            console.error("Base64 dispatch crash trace response text: ", xhr.responseText);
            Swal.fire('Transmission Error', 'The base64 upload pipeline encountered a server transit fault.', 'error');
        }
    });
});


// ====================================================================
// REAL-TIME PASSWORD STRENGTH MONITORING CONTROLLER ENGINE
// ====================================================================
$('#new_password').on('keyup input', function() {
    var passwordValue = $(this).val();
    var $container = $('#password-strength-container');
    var $bar = $('#strength-meter-bar');
    var $text = $('#strength-meter-text');

    // If the input is completely empty, mask the meter container from the screen view layout
    if (passwordValue.length === 0) {
        $container.hide();
        return;
    }

    $container.show();
    var complexityScore = 0;

    // Rule Check 1: Minimum standard length parameter boundary evaluation
    if (passwordValue.length >= 6) complexityScore++;
    if (passwordValue.length >= 10) complexityScore++; // Bonus weight point for long keys

    // Rule Check 2: Contains uppercase letters regex match pattern
    if (/[A-Z]/.test(passwordValue)) complexityScore++;

    // Rule Check 3: Contains numeric integers symbols pattern
    if (/[0-9]/.test(passwordValue)) complexityScore++;

    // Rule Check 4: Contains special character glyphs criteria
    if (/[\W_]/.test(passwordValue)) complexityScore++;

    // MAP COMPLEXITY SCORES TO SYSTEM ACCENT LOOKUPS
    var progressPercentage = "0%";
    var barColorClass = "bg-danger";
    var trackingTextLabel = "Very Weak";

    switch(complexityScore) {
        case 1:
        case 2:
            progressPercentage = "25%";
            barColorClass = "bg-danger";
            trackingTextLabel = "Weak";
            break;
        case 3:
            progressPercentage = "50%";
            barColorClass = "bg-warning";
            trackingTextLabel = "Medium";
            break;
        case 4:
            progressPercentage = "75%";
            barColorClass = "bg-info";
            trackingTextLabel = "Strong";
            break;
        case 5:
            progressPercentage = "100%";
            barColorClass = "bg-success";
            trackingTextLabel = "Excellent/Secure";
            break;
    }

    // Apply live synchronized UI property adjustments
    $bar.removeClass('bg-danger bg-warning bg-info bg-success')
        .addClass(barColorClass)
        .css('width', progressPercentage);
    
    $text.removeClass('text-danger text-warning text-info text-success')
        .addClass(trackingTextLabel === 'Weak' || trackingTextLabel === 'Very Weak' ? 'text-danger' : (trackingTextLabel === 'Medium' ? 'text-warning' : (trackingTextLabel === 'Strong' ? 'text-info' : 'text-success')))
        .text(trackingTextLabel);
});

// UPGRADE FORM SUBMIT INTERCEPTOR VALIDATION
// Ensure this check intercepts right at the top of your existing $('#frmUpdateProfile').on('submit', ...) handler
$('#frmUpdateProfile').on('submit', function(e) {
    
    e.preventDefault();
    var form = this;
    // Fast client-side pre-submit verification validation step
    var np = $('#new_password').val();
    var cp = $('#confirm_password').val();

    // Block submittal if the password field is used but fails the minimum safety baseline metrics
    if (np.length > 0 && np.length < 6) {
        e.preventDefault();
        Swal.fire({
            title: 'Insecure Password',
            text: 'Your new security password must contain at least 6 characters to comply with data protection regulations.',
            icon: 'error',
            confirmButtonColor: '#ff3e1d'
        });
        return false;
    } 

    if(np !== cp) {
        Swal.fire('Validation Error', 'Your new password entries do not match. Please cross-check fields.', 'error');
        return false;
    }

    Swal.fire({
        title: 'Apply Profile Modifications?',
        text: 'This re-signs system identification keys, updates security tokens, and synchronizes active workstation settings.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#696cff',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Save Profile Updates',
        cancelButtonText: 'Review Form'
    }).then((res) => {
        if (!res.isConfirmed) return;
        
        Swal.fire({ title: 'Encrypting credential updates...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if(response.status === 'success') {
                    Swal.fire({ title: 'Profile Updated', text: response.message, icon: 'success', timer: 1500, showConfirmButton: false });
                    
                    // Live adjust top dashboard visual name metrics labels immediately without reloads
                    $('#lbl_display_full_name').text(response.new_name);
                    
                    // Clear secure entry fields boxes values cleanly
                    $('#current_password, #new_password, #confirm_password').val('');
                } else {
                    Swal.fire('Operation Declined', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                console.error("Profile dispatch transit failure output text: ", xhr.responseText);
                Swal.fire('Transmission Fault', 'An unexpected network error occurred executing updates.', 'error');
            }
        });
    });
});
</script> 