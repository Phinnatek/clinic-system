<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

/* ============================================================
 * SYSTEM CONFIG & ERROR HANDLING
 * ============================================================ */
set_time_limit(300);
ini_set('memory_limit', '1G');

// Hide errors from breaking JSON; logs are better for production
error_reporting(E_ALL & ~E_DEPRECATED); 
ini_set('display_errors', '0');

/* ============================================================
 * ROOT DIRECTORY
 * ============================================================ */
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 1));
}

/* ============================================================
 * ENVIRONMENT DETECTION
 * ============================================================ */
$isExe = (($_SERVER['HTTP_HOST'] ?? '') === 'heserver');

/**
 * HELPER: SECURE DATA RETRIEVAL
 * Pulls from ExeOutput Protected Strings or Web Environment.
 */
function get_config_val(string $exeId, string $envKey): string {
    global $isExe;
    if ($isExe && function_exists('exo_get_protstring')) {
        $val = exo_get_protstring($exeId);
        if ($val !== '') return $val;
    }
    return isset($_ENV[$envKey]) ? (string)$_ENV[$envKey] : '';
}

/* ============================================================
 * COMPOSER & ENV LOADING (WEB ONLY)
 * ============================================================ */
if (!$isExe) {
    $autoloadFile = ROOT_DIR . '/assets/libraries/secure/vendor/autoload.php';
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
            $dotenv->load();
        } catch (Throwable $e) {
            // Silently fail to keep output clean
        }
    }
}

/* ============================================================
 * CONSTANT DEFINITIONS (Zero Strings Visible)
 * ============================================================ */
if (!defined('DB_HOST'))       define('DB_HOST',       get_config_val('str0', 'DB_HOST'));
if (!defined('DB_USER'))       define('DB_USER',       get_config_val('str1', 'DB_USER'));
if (!defined('DB_PASSWORD'))   define('DB_PASSWORD',   get_config_val('str2', 'DB_PASSWORD'));
if (!defined('DB_NAME'))       define('DB_NAME',       get_config_val('str3', 'DB_NAME'));
if (!defined('DB_PORT'))       define('DB_PORT',       get_config_val('str4', 'DB_PORT'));
if (!defined('DB_DRIVER'))     define('DB_DRIVER',     get_config_val('str5', 'DB_DRIVER'));
if (!defined('DB_CHARSET'))    define('DB_CHARSET',    get_config_val('str6', 'DB_CHARSET'));

if (!defined('SMS_KEY'))       define('SMS_KEY',       get_config_val('str7', 'SMS_KEY'));
if (!defined('login_encript')) define('login_encript', get_config_val('str8', 'login_encript'));
if (!defined('dump_key'))      define('dump_key',      get_config_val('str9', 'dump_key'));
if (!defined('PublicKey'))     define('PublicKey',     get_config_val('str10', 'PublicKey'));
if (!defined('secretKey'))     define('secretKey',     get_config_val('str11', 'secretKey'));

if (!defined('SQL_DUMP_FILE')) { 
    $dumpPath = ltrim(get_config_val('str11', 'SQL_DUMP_FILE'), '/');
    define('SQL_DUMP_FILE', ROOT_DIR . '/' . $dumpPath);
}

 

/* ============================================================
 * DATABASE CONNECTION
 * ============================================================ */
$con = null;

try {

    $dsn = sprintf(
        "%s:host=%s;dbname=%s;port=%s;charset=%s",
        DB_DRIVER,
        DB_HOST,
        DB_NAME,
        DB_PORT,
        DB_CHARSET
    );

    $con = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    $con = null;
}

/* ============================================================
 * CSRF TOKEN
 * ============================================================ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} 


$infoFile    = '../assets/backend/software_info.json';

if (file_exists($infoFile)) {
    $content = file_get_contents($infoFile);
    $info = json_decode($content, true); 
}

?>
 <!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" data-theme="theme-default">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>Secure Login | Let Excellence Enterprise Microfinance</title>

    <!-- Primary SEO -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="title" content="Secure Login | Let Excellence Enterprise Microfinance">
    <meta name="description" content="Official secure login portal for Let Excellence Enterprise. Access your savings, investment, loan, and microfinance services securely online.">
    <meta name="keywords" content="Let Excellence Enterprise, Microfinance, Savings, Investment, Loans, Financial Services, Ghana Microfinance, Online Banking, Customer Portal">
    <meta name="author" content="Phinnatek I.T. Solutions">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">

    <!-- Canonical -->
    <link rel="canonical" href="https://lee.phinnatek.com">

    <!-- Language & Geo -->
    <meta name="language" content="English">
    <meta name="geo.region" content="GH">
    <meta name="geo.country" content="Ghana">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">

    <!-- Brand Identity -->
    <meta name="application-name" content="Let Excellence Enterprise">
    <meta name="apple-mobile-web-app-title" content="Let Excellence Enterprise">
    <meta name="theme-color" content="#0d6efd">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $info['software_favicon']; ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css">

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/css/core.css" class="template-customizer-core-css">
    <link rel="stylesheet" href="../assets/css/demo.css">

    <!-- Open Graph -->
    <meta property="og:locale" content="en_GH">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Let Excellence Enterprise">
    <meta property="og:title" content="Let Excellence Enterprise Microfinance">
    <meta property="og:description" content="Secure access to savings, investment, loan and microfinance services.">
    <meta property="og:url" content="https://lee.phinnatek.com">
    <meta property="og:image" content="https://lee.phinnatek.com/<?php echo $info['software_logo']; ?>">
    <meta property="og:image:alt" content="Let Excellence Enterprise Logo">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Let Excellence Enterprise Microfinance">
    <meta name="twitter:description" content="Secure access to savings, investment and financial services.">
    <meta name="twitter:image" content="https://lee.phinnatek.com/<?php echo $info['software_logo']; ?>">

    <!-- Organization Structured Data -->
    <script type="application/ld+json">
    {
      "@context":"https://schema.org",
      "@type":"FinancialService",
      "name":"Let Excellence Enterprise",
      "url":"https://lee.phinnatek.com",
      "logo":"https://lee.phinnatek.com/<?php echo $info['software_logo']; ?>",
      "description":"Microfinance institution providing savings, investment and financial services.",
      "serviceType":"Microfinance",
      "areaServed":"Ghana",
      "currenciesAccepted":"GHS",
      "founder":"Let Excellence Enterprise",
      "sameAs":[]
    }
    </script>

    <!-- Website Schema -->
    <script type="application/ld+json">
    {
      "@context":"https://schema.org",
      "@type":"WebSite",
      "name":"Let Excellence Enterprise",
      "url":"https://lee.phinnatek.com"
    }
    </script>

    <!-- Login Page Schema -->
    <script type="application/ld+json">
    {
      "@context":"https://schema.org",
      "@type":"WebPage",
      "name":"Secure Login",
      "url":"https://lee.phinnatek.com",
      "description":"Secure customer login portal for Let Excellence Enterprise."
    }
    </script>

    <?php include '../assets/backend/preloader.php'; ?>

</head>


  <!-- Custom Styles -->
  <style>
    .swal2-container {
      z-index: 2000 !important;
    }

    body {
      position: relative;
      min-height: 100vh;
      overflow: hidden;
      background-color: #f8f9fa;
    }
 
  </style> 
<body>
   
 <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y d-flex justify-content-center align-items-center min-vh-100">
        <div class="authentication-inner w-100 px-3 px-sm-0" style="max-width: 440px;">
            
            <div class="position-absolute top-0 start-50 translate-middle-x mt-2" style="z-index: 9999;"></div>
            <div id="connection" class="mb-3 w-100 justify-content-center align-items-center d-flex"></div>

            <div class="card shadow-md border-0 w-100">
                <div class="card-body px-4 py-5">
                    
                    <?php if (!$con): ?>
                        <!-- Installation Wizard View -->
                        <div class="app-brand justify-content-center mb-4 text-center">
                            <a href="#" class="app-brand-link d-inline-flex align-items-center gap-2 text-decoration-none">
                                <img src="<?php echo $info['software_logo']; ?>" alt="Logo" height="48">
                                <span class="fw-bold text-heading text-primary"><?php echo $info['brand_name']; ?></span>
                            </a>
                        </div>
                        <h4 class="mb-1 text-center fw-semibold">System Setup Wizard</h4>
                        <p class="text-center text-muted mb-4">Initializing branches... please wait.</p>

                        <!-- Installation Form -->
                        <form id="installationForm">
                            <div class="mb-3">
                                <label class="form-label">Select Your Branch</label>
                                <select id="school_select" name="school_id" class="form-control" disabled required>
                                    <option value="">Checking connection…</option>
                                </select>
                            </div>
                            
                            <!-- Hidden System Configs -->
                            <input type="hidden" name="start_installation" id="start_installation" value="true">
                            <input type="hidden" name="DB_DRIVER" value="<?= DB_DRIVER; ?>">
                            <input type="hidden" name="DB_HOST" value="<?= DB_HOST; ?>">
                            <input type="hidden" name="DB_USER" value="<?= DB_USER; ?>">
                            <input type="hidden" name="DB_NAME" value="<?= DB_NAME; ?>">
                            <input type="hidden" name="DB_PORT" value="<?= DB_PORT; ?>">
                            <input type="hidden" name="DB_PASSWORD" value="<?= DB_PASSWORD; ?>">
                            <input type="hidden" name="DB_CHARSET" value="<?= DB_CHARSET; ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                            <button class="btn btn-primary w-100 mt-4 py-2" type="submit" disabled id="continueBtn">
                                <i class="bx bx-check-circle me-1"></i> Start Installation
                            </button>
                        </form>

                    <?php else: ?>
                        <!-- Login View -->
                        <div class="app-brand justify-content-center mb-4 text-center">
                            <a href="index.php" class="app-brand-link d-inline-flex align-items-center gap-2 text-decoration-none">
                                <img src="<?php echo $info['software_logo']; ?>" alt="Logo" height="48">
                                <span class="fw-bold text-heading text-primary"><?php echo $info['brand_name']; ?></span>
                            </a>
                        </div>
                        <h4 class="mb-1 text-center fw-semibold">Welcome Back! 👋</h4>
                        <p class="text-center text-muted mb-4">Securely access your member portal</p>

                        <!-- Login Form -->
                        <form id="formAuthentication" novalidate>
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Member / Staff ID</label>
                                <input type="text" class="form-control" id="user_id" name="user_id" placeholder="Enter your ID" data-encrypt="true" autofocus required>
                            </div>
                            
                            <input type="hidden" id="csrf_token" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">Password / OTP</label>
                                <div class="password-wrapper input-group input-group-merge position-relative">
                                    <input type="password" class="form-control password-field" id="password" name="password" placeholder="Enter password or OTP" data-encrypt="true" data-masked="true" required>
                                    <span class="password-toggle input-group-text cursor-pointer" title="Show/Hide Password">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                    <div class="error-container"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember-me">
                                    <label class="form-check-label" for="remember-me">Remember Me</label>
                                </div>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-decoration-none text-primary">Forgot PIN/Password?</a>
                            </div>

                            <button class="btn btn-primary w-100 mb-4 py-2" type="submit">
                                <i class="bx bx-lock-alt me-1"></i> Secure Login
                            </button> 
                        </form>

                    <?php endif; ?>
                </div>
            </div>
            
            <footer class="text-center mt-4 small text-muted">
                &copy; <?= date('Y') ?> Phinnatek <?php echo $info['brand_name']; ?>. All rights reserved.
            </footer>
        </div>
    </div>
</div>


 
  <!-- Modal -->
  <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-3 shadow">
        <div class="modal-header">
          <h5 class="modal-title" id="forgotPasswordLabel">Forgot Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <form id="forgotPasswordForm">
            <div class="mb-3">
              <label for="userId" class="form-label">Enter your User ID</label>
              <input type="text" class="form-control" id="userId" placeholder="e.g. NDB-STF1234" required>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="submitForgot">Submit</button>
        </div>
      </div>
    </div>
  </div> 
  
  <!-- Change Password Modal -->
<div class="modal fade" id="change_password" tabindex="-1" aria-labelledby="change_password_label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 rounded-4 shadow-lg">

      <!-- Header -->
      <div class="modal-header  rounded-top-4">
        <h5 class="modal-title fw-semibold mb-0" id="change_password_label">
          <i class="bx bx-lock-alt me-2"></i> Change Password
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body p-4">
        <form id="change_password_form" autocomplete="off">
          
          <!-- User ID -->
          <div class="mb-4">
            <label class="form-label fw-semibold text-secondary mb-1">User ID</label>
            <div class="form-control bg-light border text-muted" id="text_user_id"> </div>
            <input type="hidden" id="user_id" name="user_id" />
          </div>

          <!-- New Password -->
          <div class="mb-4">
            <label for="new_password" class="form-label fw-semibold">New Password</label>
            <div class="password-wrapper input-group-merge password-toggle-wrapper position-relative">
              <input type="password" class="form-control password-field pe-5" id="new_password" name="new_password" placeholder="Enter new password" required>
              <i class="bx bx-hide toggle-pass position-absolute end-0 top-50 translate-middle-y me-3 cursor-pointer" data-target="#new_password"></i>
            </div>

            <!-- Strength Bar -->
            <div class="progress mt-3" style="height: 8px;">
              <div class="progress-bar bg-danger" id="password_strength_bar" role="progressbar" style="width:0%;"></div>
            </div>
            <small id="strength_text" class="fw-semibold text-danger">Weak</small>

            <!-- Criteria -->
            <ul class="list-unstyled small mt-3 mb-0" id="password_criteria">
              <li id="length" class="text-danger"><i class="bx bx-x"></i> Minimum 7 characters</li>
              <li id="uppercase" class="text-danger"><i class="bx bx-x"></i> At least one uppercase letter</li>
              <li id="lowercase" class="text-danger"><i class="bx bx-x"></i> At least one lowercase letter</li>
              <li id="number" class="text-danger"><i class="bx bx-x"></i> At least one number</li>
              <li id="special" class="text-danger"><i class="bx bx-x"></i> At least one special character (!@#$%^&*)</li>
              <li id="match" class="text-danger"><i class="bx bx-x"></i> Passwords must match</li>
            </ul>
          </div>

          <!-- Confirm Password -->
          <div class="mb-2">
            <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
            <div class="password-wrapper input-group-merge password-toggle-wrapper position-relative">
              <input type="password" class="form-control password-field pe-5" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
              <i class="bx bx-hide toggle-pass position-absolute end-0 top-50 translate-middle-y me-3 cursor-pointer" data-target="#confirm_password"></i>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="modal-footer border-0 p-3 pt-0">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i> Cancel
        </button>
        <button type="button" class="btn btn-primary" id="submitPass"  >
          <i class="bx bx-check-circle me-1"></i> Submit
        </button>
      </div>
    </div>
  </div>
</div>


    </section>
    <!-- Core JS -->
    <script src="../assets/js/jquery.js"></script>
    <script src="../assets/js/popper.js"></script>
    <script src="../assets/js/bootstrap.js"></script> 
    <script src="../assets/js/sweetalert2.js"></script> 
    <script src="../assets/js/crypto-js.min.js"></script> 
    <script src="../assets/js/helpers.js"></script>
</body>
</html>

<script> 
 function resetIndicators() {
    $strengthBar.css('width', '0%').removeClass('bg-warning bg-success').addClass('bg-danger');
    $strengthText.text('Weak').removeClass('text-warning text-success').addClass('text-danger');
    $.each($criteria, function (key, el) {
      el.removeClass('text-success').addClass('text-danger');
    });
  }
 $(document).ready(function () {
  // === Variables ===
  const $newPass = $('#new_password');
  const $confirmPass = $('#confirm_password');
  const $strengthBar = $('#password_strength_bar');
  const $submitBtn = $('#submitPass');
  const $strengthText = $('#strength_text');

  const $criteria = {
    length: $('#length'),
    uppercase: $('#uppercase'),
    lowercase: $('#lowercase'),
    number: $('#number'),
    special: $('#special'),
    match: $('#match')
  };

  console.log("Change password script loaded");

  // === Validate Password Strength & Matching ===
  function validatePassword() {
    const val = $newPass.val().trim();
    const confirmVal = $confirmPass.val().trim();
    let strength = 0;

    const checks = {
      length: val.length >= 7,
      uppercase: /[A-Z]/.test(val),
      lowercase: /[a-z]/.test(val),
      number: /[0-9]/.test(val),
      special: /[!@#$%^&*]/.test(val),
    };

    // Update criteria UI
    $.each(checks, function (key, passed) {
      $criteria[key].toggleClass('text-success', passed)
                    .toggleClass('text-danger', !passed);
      if (passed) strength++;
    });

    // Calculate strength and update bar
    const percent = (strength / 5) * 100;
    $strengthBar.css('width', percent + '%').removeClass('bg-danger bg-warning bg-success');
    let text = 'Weak';
    if (percent <= 40) {
      $strengthBar.addClass('bg-danger');
      text = 'Weak';
      $strengthText.removeClass('text-warning text-success').addClass('text-danger');
    } else if (percent <= 80) {
      $strengthBar.addClass('bg-warning');
      text = 'Medium';
      $strengthText.removeClass('text-danger text-success').addClass('text-warning');
    } else {
      $strengthBar.addClass('bg-success');
      text = 'Strong';
      $strengthText.removeClass('text-danger text-warning').addClass('text-success');
    }
    $strengthText.text(text);

    // Check password match
    const passwordsMatch = val === confirmVal && val !== '';
    $criteria.match.toggleClass('text-success', passwordsMatch)
                   .toggleClass('text-danger', !passwordsMatch);

    // All valid?
    const allValid = Object.values(checks).every(Boolean) && passwordsMatch;
    return allValid;
  }

  // Live validation as user types
  $newPass.on('input', validatePassword);
  $confirmPass.on('input', validatePassword);

  // === Toggle password visibility ===
  $(document).on('click', '.toggle-pass', function () {
    const target = $($(this).data('target'));
    const type = target.attr('type') === 'password' ? 'text' : 'password';
    target.attr('type', type);
    $(this).toggleClass('bx-hide bx-show');
  });

  // === Submit Handler === 
  $submitBtn.on('click', function (e) {
    e.preventDefault();
    console.log("Submit button clicked");

    const user_id = $('#user_id').val().trim();
    const newPass = $newPass.val().trim();
    const confirmPass = $confirmPass.val().trim();

    console.log("User ID:", user_id);
    console.log("New Password:", newPass);
    console.log("Confirm Password:", confirmPass);

    // Validate first
    const allValid = validatePassword();
    console.log("Validation result:", allValid);

    if (!allValid) {
      Swal.fire({
        icon: 'warning',
        title: 'Check Password',
        text: 'Please ensure all password criteria are met and passwords match.',
        confirmButtonColor: '#3085d6',
        target: 'body'
      });
      return;
    }

    // === AJAX submission ===
    $.ajax({
      url: '../assets/backend/server_side.php',
      method: 'POST',
      dataType: 'json',
      headers: { 'X-CSRF-Token': $('#csrf_token').val() },
      xhrFields: { withCredentials: true },
      data: {
        reset_password: true,
        csrf_token: $('#csrf_token').val(),
        user_id: user_id,
        new_password: newPass,
        confirm_password: confirmPass
      },
      beforeSend: function () {
        console.log("AJAX request starting...");
          // ✅ Step 3: Show loading spinner
    Swal.fire({
      title: 'Processing...',
      text: 'Please wait while we processing your request.',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

        $submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i> Processing...');
      },
      success: function (response) {
        $submitBtn.prop('disabled', false);
        console.log("AJAX response:", response);
        refreshCsrfToken();
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Password Changed!',
            text: 'Your password has been updated successfully.',
            showConfirmButton: false,
            timer: 2000,
            target: 'body'
          });
          $('#change_password_form')[0].reset();
          $('#change_password').modal('hide');
          resetIndicators();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: response.message || 'Unable to update password. Please try again.'
          });
        }
      },
      error: function (xhr, status, error) {
        $submitBtn.prop('disabled', false);
        console.error("AJAX error:", status, error);
        refreshCsrfToken();
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
      },
      complete: function () {
        console.log("AJAX request complete");
        refreshCsrfToken();
        $submitBtn.prop('disabled', false)
          .html('<i class="bx bx-check-circle me-1"></i> Submit');
      }
    });
  }); 
    
    $('#submitForgot').on('click', async function (e) {
  e.preventDefault();

  try {
    // Optionally close modal immediately
    const modal = bootstrap.Modal.getInstance($('#forgotPasswordModal')[0]);
    if (modal) modal.hide();

    // 🔒 CSRF validation
    let csrfToken = $('#csrf_token').val();
    if (!csrfToken || csrfToken.length < 32) {
      Swal.fire('Security Error', 'Missing or invalid CSRF token.', 'error');
      return;
    }

    // ✅ Get and validate user input
    let userId = $('#userId').val().trim();
    if (userId === '') {
      Swal.fire({
        icon: 'warning',
        title: 'Missing User ID',
        text: 'Please enter your User ID before continuing.',
      });
      return;
    }

    // ✅ Step 2: Ask for confirmation
    const confirmResult = await Swal.fire({
      title: 'Confirm Action',
      text: `Send password reset request for User ID: ${userId}?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, send it',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
    });

    if (!confirmResult.isConfirmed) return;

    // ✅ Step 3: Show loading spinner
    Swal.fire({
      title: 'Processing...',
      text: 'Please wait while we verify your request.',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    // ✅ Step 4: AJAX request
    $.ajax({
      url: '../assets/backend/server_side.php',
      method: 'POST',
      dataType: 'json',
      headers: { 'X-CSRF-Token': csrfToken },
      xhrFields: { withCredentials: true },
      data: {
        user_id: userId,
        csrf_token: csrfToken,
        reset_pass: true,
      },
      beforeSend: function () {
        console.log('📡 Sending request...');
      },
    })
      .done(function (response) {
        try {
          refreshCsrfToken();
          Swal.close();

          // Handle JSON parsing issues
          if (typeof response !== 'object' || !response.status) {
            throw new Error('Invalid JSON response from server.');
          }

          if (response.status == 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Success',
              text: response.message || 'Password reset OTP has been sent!',
            });
                $('#user_id').val(response.user_id);
                $('#text_user_id').html(response.user_id);

            $('#forgotPasswordForm')[0].reset();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Failed',
              text: response.message || 'Unable to process request.',
            });
          }
        } catch (innerErr) {
          console.error('Response Handling Error:', innerErr);
          Swal.fire({
            icon: 'error',
            title: 'Processing Error',
            text: 'An unexpected error occurred while handling the server response.',
          });
        }
      })
      .fail(function (xhr, status, error) {
        try {
          refreshCsrfToken();
          Swal.close();

          console.error('AJAX Error:', { xhr, status, error });
          let message = 'An unexpected error occurred. Please try again later.';

          // Attempt to extract server error details
          if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
          } else if (xhr.responseText) {
            try {
              const parsed = JSON.parse(xhr.responseText);
              if (parsed.message) message = parsed.message;
            } catch (parseErr) {
              console.warn('Response not valid JSON');
            }
          }

          Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: message,
          });
        } catch (failErr) {
          console.error('Fail Block Error:', failErr);
          Swal.fire({
            icon: 'error',
            title: 'Critical Error',
            text: 'Something went wrong while processing your request.',
          });
        }
      })
      .always(function () {
        console.log('✅ Request completed.');
      });
  } catch (err) {
    console.error('Fatal JS Error:', err);
    Swal.close();
    Swal.fire({
      icon: 'error',
      title: 'Unexpected Error',
      text:
        err.message ||
        'A fatal error occurred in the application. Please reload the page and try again.',
    });
  }
});
 
    // Handle password toggle click
    $('.password-toggle').on('click', function() {
        let $wrapper = $(this).closest('.password-wrapper');       // scope to this wrapper
        let $input = $wrapper.find('.password-field');             // find the input
        let $icon = $(this).find('i');                             // find the icon

        // Toggle input type
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');                           // reveal password
            $icon.removeClass('bx-hide').addClass('bx-show');      // change icon
        } else {
            $input.attr('type', 'password');                       // hide password
            $icon.removeClass('bx-show').addClass('bx-hide');      // change icon
        }
    });

    // Optional: Prevent conflicts if user types while toggled
    $('.password-field').on('input', function() {
        // Can handle validations or real-time updates here
        $(this).siblings('.error-container').text(''); // clear errors on input
    }); 
  
// Remove error message
$('#password').on('input', function() {
    $(this).removeClass('is-invalid');
    $('#passwordDIv').siblings('.error-container').empty();
});
$('#email').on('input', function() {
    $(this).removeClass('is-invalid');
    $(this).next('.error-message').remove();
});


    $('#formAuthentication').submit(function(event) {
    event.preventDefault();

    const $form = $(this);

    // 🔒 CSRF validation
    let csrfToken = $('#csrf_token').val();
    if (!csrfToken || csrfToken.length < 32) {
        Swal.fire('Security Error', 'Missing or invalid CSRF token.', 'error');
        return;
    }

    // Collect all fields marked for encryption
    const $encryptFields = $form.find('[data-encrypt="true"]');
    if ($encryptFields.length === 0) {
        Swal.fire('Error', 'No fields marked for encryption.', 'error');
        return;
    }

    const payload = {};
    let isValid = true;

    // Validation & payload collection
    $encryptFields.each(function() {
        const $input = $(this);
        const fieldName = $input.attr('name');
        const val = $input.val() ? $input.val().trim() : '';

        if (!val) {
            $input.addClass('is-invalid');
            $input.after(`<div class="error-message" style="color:red;">Please enter ${fieldName}.</div>`);
            isValid = false;
        } else {
            $input.removeClass('is-invalid');
        }

        payload[fieldName] = val;
    });

    if (!isValid) {
        Swal.fire({ icon: 'warning', title: 'Validation Error', text: 'Please fill all required fields.' });
        return;
    }

    // ✅ Encrypt the payload
    if (typeof CryptoJS === 'undefined') {
        Swal.fire('Encryption Error', 'CryptoJS library not loaded.', 'error');
        return;
    }

    const secretKey = window._encKey || 'mySuperSecretKey123!';
    const key = CryptoJS.SHA256(secretKey);
    const iv = CryptoJS.lib.WordArray.random(16);
    const jsonPayload = JSON.stringify(payload);
    const encrypted = CryptoJS.AES.encrypt(jsonPayload, key, { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 });
    const combined = iv.concat(encrypted.ciphertext);
    const combinedBase64 = CryptoJS.enc.Base64.stringify(combined);

    // Insert into hidden field
    let $hidden = $form.find('[name="enc_payload"]');
    if (!$hidden.length) {
        $hidden = $('<input>', { type: 'hidden', name: 'enc_payload' }).appendTo($form);
    }
    $hidden.val(combinedBase64);

    // Clear plaintext fields
    $encryptFields.val('');

    // Prepare confirmation modal
    const displayName = payload.email || payload.username || '(encrypted)';
    Swal.fire({
        title: 'Confirm Login Details',
        html: `Email/Username: ${displayName}<br>Password: (encrypted)`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, login',
        cancelButtonText: 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Please wait...', html: 'Logging in...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const formData = new FormData($form[0]);
            ['password', 'email', 'remember_me'].forEach(name => formData.delete(name));
            formData.append('Systemlogin', 'true');

            $.ajax({
                type: 'POST',
                url: '../assets/backend/server_side.php',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: { 'X-CSRF-Token': csrfToken },
                xhrFields: { withCredentials: true },
                crossDomain: false,
                success: function(response) {
                refreshCsrfToken();
                console.log('response: ', response);
                
                    if (response.status === 'success') {
                      if (response.redirect === 'change_password') {
                      Swal.close();

                      const modalElement = document.getElementById('change_password');
                      let modal = bootstrap.Modal.getInstance(modalElement);
                      // Create one if it doesn't exist
                      if (!modal) modal = new bootstrap.Modal(modalElement);
                      $('#user_id').val(response.user_id);
                      $('#text_user_id').html(response.user_id);

                      modal.show();
                    } else { 
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            willClose: () => {  
                              window.location.href = response.redirect; 
                            }
                        });
                    }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Login Failed', text: response.message || 'An error occurred. Please try again.' });
                    }
                },
               error: function(xhr, status, error) {
    refreshCsrfToken();

    // Show user-friendly message
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'An error occurred during login. Please try again.'
    });

    // Log detailed info for debugging
    console.error("AJAX Error:", {
        status: status,           // e.g., "error", "timeout"
        error: error,             // e.g., "Internal Server Error"
        responseText: xhr.responseText  // <-- Full response from server
    });
}

            });
        }
    });

    return false;
});


});


$(document).ready(function() {
    $("#installationForm").on("submit", function(e) {
        e.preventDefault(); // Stop immediate reload
        e.stopPropagation(); // Stop event bubbling
        
        const form = this;
        const $continueBtn = $("#continueBtn");

        Swal.fire({
            title: "Confirm Installation",
            text: "Are you sure you want to start the installation for the selected school?",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, Install Now",
            allowOutsideClick: () => !Swal.isLoading(),
            // Important: We handle the transition in the .then() to avoid form interference
        }).then((result) => {
            if (result.isConfirmed) {
                // Trigger the streaming logic
                startStreamingInstallation(form, $continueBtn);
            }
        });

        return false; // Extreme measure to prevent reload
    });
});


async function startStreamingInstallation(form, $continueBtn) {
    Swal.fire({
       title: `<div style="font-size:18px; font-weight:600;">Installing System</div>`,

html: `
    <div style="text-align:center; padding-top:10px;">
        
        <div id="swal-status" 
             style="font-size:14px; color:#6c757d; margin-bottom:16px;">
            Preparing installation...
        </div>

        <div style="
            width:100%;
            height:8px;
            background:#f1f3f5;
            border-radius:999px;
            overflow:hidden;
        ">
            <div id="swal-progress" 
                 style="
                    width:0%;
                    height:100%;
                    background:#22c55e;
                    border-radius:999px;
                    transition:width .35s ease;
                 ">
            </div>
        </div>

        <div id="swal-perc-text" 
             style="
                margin-top:10px;
                font-size:13px;
                color:#9ca3af;
                letter-spacing:0.5px;
             ">
            0%
        </div>

    </div>
`,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: async () => {
            $continueBtn.prop("disabled", true).text("Installation in Progress...");

            try {
                const formData = new FormData(form);
                formData.append('start_installation', 'true');

                const response = await fetch("../assets/backend/others.php", {
                    method: 'POST',
                    body: formData,
                    keepalive: true 
                });

                if (!response.ok) throw new Error(`Server Error: ${response.status}`);

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = "";

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    let lines = buffer.split("\n");
                    buffer = lines.pop(); 

                    for (let line of lines) {
                        if (!line.trim()) continue;
                        
                        let data;
                        try { data = JSON.parse(line); } catch(e) { continue; }
                        console.log('server data: ', data);
                        
                        if (data.status == 'progress') {
                            const perc = parseInt(data.percentage) || 0;
                            
                            $("#swal-progress").css("width", perc + "%");
                            $("#swal-perc-text").text(perc + "%");
                            $("#swal-status").text(data.message);

                            // Text color flip for visibility
                            if(perc > 52) {
                                $("#swal-perc-text").css("color", "#fff");
                            }
                            
                        } else if (data.status === 'done') {
                            Swal.fire({ icon: "success", title: "Success", text: data.message,  showConfirmButton: false, timer: 3000, timerProgressBar: true, allowOutsideClick: false, allowEscapeKey: false });
                            $continueBtn.prop("disabled", false).text("Installation Complete");
                            location.reload();
                            return; 
                        } else if (data.status === 'error') {
                            throw new Error(data.message);
                        }
                    }
                }
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "Installation Failed",
                    text: error.message || "Could not contact the server.",
                });
                $continueBtn.prop("disabled", false).text("Continue Installation");
            }
        }
    });
}

/**
 * ---------------------------------------------------
 * Internet Monitoring & School Fetching Module
 * ---------------------------------------------------
 */

let checkingInternet   = true;
let hasFetchedSchools  = false;
let connectionTimer    = null;

/**
 * Monitor internet connection status
 */
function monitorInternetConnection(options = {}) {

    if (!checkingInternet) return;

    const config = {
        url: "../assets/backend/others.php",
        interval: 5000,
        timeout: 8000,
        enabled: true,
        ...options
    };

    const $connectionBadge = $("#connection");

    const badges = {
        online: `
            <span class="badge bg-label-success text-success">
                <i class="bx bx-wifi"></i> Internet connection is available
            </span>
        `,
        offline: `
            <span class="badge bg-label-warning text-warning">
                <i class="bx bx-wifi-off"></i> No internet connection
            </span>
        `,
        error: `
            <span class="badge bg-label-danger text-danger">
                <i class="bx bx-error-circle"></i> Connection error
            </span>
        `
    };

    const updateUI = (html) => {
        $connectionBadge.html(html);
    };

    const checkConnection = () => {

        if (!config.enabled) return;

        $.ajax({
            url: config.url,
            type: "POST",
            data: { action: "check_internet" },
            dataType: "json",
            timeout: config.timeout,

            success: (response) => {

                // Defensive validation
                if (!response || typeof response !== "object") {
                    updateUI(badges.error);
                    return;
                }

                // Server responded correctly
                if (response.status === "success") {

                    if (response.online === true) {
                        updateUI(badges.online);

                        // Fetch schools only once
                        if (!hasFetchedSchools) {
                            hasFetchedSchools = true;
                            checkingInternet  = false;
                            stopInternetMonitor();
                            fetchSchools();
                        }

                    } else {
                        updateUI(badges.offline);
                    }

                } else {
                    updateUI(badges.error);
                }
            },

            error: (xhr, status, error) => {
                console.error("Internet check failed:", {
                    status,
                    error,
                    response: xhr.responseText
                });
                updateUI(badges.error);
            }
        });
    };

    // Initial check
    checkConnection();

    // Polling
    connectionTimer = setInterval(checkConnection, config.interval);
}

/**
 * Stop monitoring internet
 */
function stopInternetMonitor() {
    if (connectionTimer) {
        clearInterval(connectionTimer);
        connectionTimer = null;
    }
}

/**
 * ---------------------------------------------------
 * Fetch Schools from Server
 * ---------------------------------------------------
 */
function fetchSchools() {

    console.log("🔍 Fetching school list...");
                Swal.fire({
                        title: "Processing...",
                        text: "Please wait while we fetch school list",
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
    const $schoolSelect = $("#school_select");
    const $continueBtn = $("#continueBtn");

    $schoolSelect
        .prop("disabled", true)
        .html(`<option>Fetching school list…</option>`);

    $continueBtn.prop("disabled", true);

    $.ajax({
        url: "../assets/backend/others.php",
        method: "POST",
        data: {
            csrf_token: $("input[name=csrf_token]").val(),
            fetch_schools: true
        },
        dataType: "json",
        timeout: 10000,

        success: (res) => {
                Swal.close();

            console.log("✅ Schools response:", res);

            if (res && res.status === "success" && Array.isArray(res.schools)) {

                $schoolSelect .prop("disabled", false) .html(`<option value="">Select School</option>`);

                res.schools.forEach(school => {
                    $schoolSelect.append(
                        `<option value="${school.id}">
                            ${school.school_name}
                         </option>`
                    );
                });

                $continueBtn.prop("disabled", false);

            } else {
                handleFetchError(res?.message || "Failed to load schools");
            }
        },

        error: (xhr, status, error) => {
            console.error("🚨 Fetch schools failed:", {
                status,
                error,
                response: xhr.responseText
            });
            handleFetchError("Could not contact the server.");
        }
    });
}

/**
 * Handle school fetch errors
 */
function handleFetchError(message) {
    $("#school_select")
        .prop("disabled", true)
        .html(`<option>${message}</option>`);

    $("#continueBtn").prop("disabled", true);
}

/**
 * ---------------------------------------------------
 * INIT
 * ---------------------------------------------------
 */
$(document).ready(() => {
  const hasPdo = <?= isset($con) && $con instanceof PDO ? 'true' : 'false' ?>;

  if (!hasPdo) {
    monitorInternetConnection();
  }
});


function refreshCsrfToken() { 
  $.ajax({
    url: '../assets/backend/login_csrf_refresh.php',
    type: 'POST',
    data: { refresh_token: true },
    dataType: 'json',
    xhrFields: { withCredentials: true }, // ✅ send PHPSESSID
    crossDomain: false,                   // ✅ safer if same domain
    success: function (response) {
      if (response.status === 'success' && response.token) {
        $('input[name="csrf_token"]').val(response.token);
        console.log('🔁 CSRF token refreshed.');
      } else {
          refreshCsrfToken();
      }
    },
    error: function () { 
       refreshCsrfToken();
    }
  });
}

  // 🔁 Refresh CSRF every 1
  setInterval(refreshCsrfToken, 1 * 60 * 1000);

  // 🔄 Initial refresh on page load
  $(document).ready(refreshCsrfToken);

 </script>

  </body>
</html>
