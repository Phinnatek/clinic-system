<?php 
session_start(); 
ob_start(); 
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL); 
ini_set('log_errors', 1); 
ini_set('error_log', '../../server_files/sedacoe clinic/logs/php-error.log');
// $_SESSION['user_id'] = $_SESSION['user_id'] ?? '1234'; // Temporary hardcoded user_id for testing
require_once 'includes/user_validation.php';
  
?>


<!DOCTYPE html> 
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>S. Clinic</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="../assets/img/avatars/logo.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />  
    
    <link rel="stylesheet" href="../assets/css/iconify-icons.css"> 
      
      <link rel="stylesheet" href="../assets/css/pickr-themes.css">
    
    <link rel="stylesheet" href="../assets/css/core.css">
    <link rel="stylesheet" href="../assets/css/demo.css"> 
      <link rel="stylesheet" href="../assets/css/perfect-scrollbar.css"> 
    <link rel="stylesheet" href="../assets/css/flag-icons.css">
  <link rel="stylesheet" href="../assets/css/apex-charts.css"> 
  <link rel="stylesheet" href="../assets/css/choice.css"> 
  <link rel="stylesheet" href="../assets/css/cropper.min.css"> 
    <!-- Helpers -->
    <script src="../assets/js/helpers.js"></script>
      <script src="../assets/js/template-customizer.js"></script>
      <script src="../assets/js/config.js"></script>
    
    <link rel="stylesheet" href="../assets/js/datatables/datatables.bootstrap5.css">
 
        <!-- Icons & Stylesheets (Grouped for efficiency) -->
    <link rel="stylesheet" href="../assets/css/boxicons.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css">
      <script src="../assets/js/jquery.js"></script>
      <script src="../assets/js/paystack.js"></script> 
      <head>E-Logs</head>

<?php include '../assets/backend/preloader.php';  ?>
<style>
  .card,
.card-body,
.dataTables_wrapper {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.table-responsive {
    overflow-x: auto !important;
}

table.dataTable {
    width: 100% !important;
}

.dataTables_scrollBody {
    overflow-x: auto !important;
}
</style>
</head>
<body>  
  <!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar  ">
  <div class="layout-container">

     <?php require_once 'includes/sidebar.php';?>
    <!-- Layout container -->
    <div class="layout-page"> 
  
     
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
  
      <!-- Menu Toggle (desktop only now, hidden on mobile) -->
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0  ">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="bx bx-menu bx-md"></i>
    </a>
  </div> <!-- Desktop Header (Navbar) -->

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse"> 
      <div hidden class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <i class="bx bx-search bx-md"></i>
                  <input
                    type="text"
                    class="form-control border-0 shadow-none ps-1 ps-sm-2"
                    placeholder="Search..."
                    aria-label="Search..." />
                </div>
              </div> 

        <ul class="navbar-nav flex-row align-items-center ms-auto">    
          
          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar avatar-online">
                <img src="<?php echo $userAvatar; ?>" id='staff_image' class="w-px-40 h-auto rounded-circle" alt="Staff Photo">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="profile.php">
                  <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar avatar-online"> 
                        <img src="<?php echo $userAvatar; ?>" id='staff_image1' class="w-px-40 h-auto rounded-circle" alt="Staff Photo">
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-0"><?php echo $user_full_name;?></h6>
                      <small class="text-muted"> </small>
                    </div>
                  </div>
                </a>
              </li> 
              <li>
                <a class="dropdown-item logout" href="javascript:void(0);" onclick="confirmLogout();" >
                  <i class="bx bx-power-off bx-md me-3"></i><span>Log Out</span>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </div> 
  </nav> 

  
  <script>
(function(){
  // set to true to see console logs while testing
  const DEBUG = false;

  const EDGE = 30;         // px from left edge to start an "open" swipe
  const MIN_SWIPE = 70;    // minimum horizontal px to count as a swipe
  const MAX_VERTICAL = 75; // max vertical movement to still be considered horizontal swipe
  const MAX_SCREEN_WIDTH = 991; // only enable on screens <= this (mobile)

  function log(...args){ if (DEBUG) console.log('[sidebar-swipe]', ...args); }

  function onReady(fn){
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      setTimeout(fn,0);
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  onReady(function(){
    const sidebar = document.getElementById('layout-menu');
    // select either the anchor inside layout-menu-toggle or the container
    const toggleEl = document.querySelector('.layout-menu-toggle a, .layout-menu-toggle');

    if (!sidebar) { log('No #layout-menu element found — aborting.'); return; }
    log('init');

    let startX = 0, startY = 0, startVisible = false;

    function isMobileWidth(){ return window.innerWidth <= MAX_SCREEN_WIDTH; }

    function isSidebarVisible(){
      try {
        const rect = sidebar.getBoundingClientRect();
        // If the menu is off-screen to left, rect.right will be <= 0 (or rect.left < 0)
        // Consider visible when its left edge is at or past 0 (tweak if needed).
        return rect.width > 0 && rect.right > 0 && rect.left >= -10;
      } catch (e) { return false; }
    }

    function openSidebar(){
      log('openSidebar()');
      if (toggleEl) {
        if (!isSidebarVisible()) {
          toggleEl.click();
          log('clicked toggle element (open)');
        } else log('already open');
        return;
      }
      if (window.Helpers && typeof window.Helpers.toggleCollapsed === 'function') {
        try { window.Helpers.toggleCollapsed(false); log('Helpers.toggleCollapsed(false) called'); return; } catch(e) { log('Helpers.open failed', e); }
      }
      // fallback
      sidebar.classList.add('active');
      sidebar.style.left = '0';
      log('fallback open: class/style applied');
    }

    function closeSidebar(){
      log('closeSidebar()');
      if (toggleEl) {
        if (isSidebarVisible()) {
          toggleEl.click();
          log('clicked toggle element (close)');
        } else log('already closed');
        return;
      }
      if (window.Helpers && typeof window.Helpers.toggleCollapsed === 'function') {
        try { window.Helpers.toggleCollapsed(true); log('Helpers.toggleCollapsed(true) called'); return; } catch(e) { log('Helpers.close failed', e); }
      }
      // fallback
      sidebar.classList.remove('active');
      sidebar.style.left = '';
      log('fallback close: removed class/style');
    }

    // Touch handlers
    document.addEventListener('touchstart', function(e){
      if (!isMobileWidth()) return;
      const t = e.changedTouches[0];
      startX = t.clientX;
      startY = t.clientY;
      startVisible = isSidebarVisible();
      log('touchstart', {startX, startY, startVisible});
    }, {passive: true});

    document.addEventListener('touchend', function(e){
      if (!isMobileWidth()) return;
      const t = e.changedTouches[0];
      const endX = t.clientX;
      const endY = t.clientY;
      const dx = endX - startX;
      const dy = Math.abs(endY - startY);
      log('touchend', {dx, dy, startX, endX, startVisible});

      // ignore mostly-vertical gestures
      if (dy > MAX_VERTICAL) { log('vertical swipe — ignored'); return; }

      // OPEN: started near left edge and swiped right
      if (!startVisible && startX <= EDGE && dx > MIN_SWIPE) {
        log('interpreted as open swipe');
        openSidebar();
        return;
      }

      // CLOSE: sidebar currently visible and swiped left enough
      if (startVisible && dx < -MIN_SWIPE) {
        log('interpreted as close swipe');
        closeSidebar();
        return;
      }
    }, {passive: true});
  });
})();
window.addEventListener('error', function (e) {
  if (e.message && e.message.includes("toUpperCase") && e.filename.includes("helpers.js")) {
    e.preventDefault(); // hide Sneat’s internal harmless bug
  }
});

</script>