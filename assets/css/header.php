<?php 
session_start(); 
ob_start(); 
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0); 
error_reporting(E_ALL); 
ini_set('log_errors', 1); 

require_once '../conn.php'; 
require_once 'includes/error_reporting.php'; 
?>
  <script src="../assets/js/sweetalert2.js"></script> 
    <?php
if(!isset($_SESSION['login_staff'])){
?>
   
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'You are not authorized to access this portal!',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = "../logout.php";
            }
        });
    </script>
<?php 
}elseif (isset($_SESSION['login_staff'])) { 

try {
    $login_staff = $_SESSION['login_staff'] ?? null;

    if (!$login_staff) {
        throw new Exception("No staff login found in session.");
    }

    // Fetch the maximum hierarchy value
    $maxHierarchyQuery = $con->prepare("
        SELECT 
            s.staff_id,
            s.first_name,
            s.last_name,
            s.other_name,
            s.email,
            s.school_id,
            s.photo,
            sc.class_id,
            sc.academic_year_id,
            a.yr,
            a.status
        FROM staff_info s
        INNER JOIN staff_assigned_class sc 
            ON sc.staff_id = s.staff_id
        INNER JOIN academic_term a 
            ON a.id = sc.academic_year_id
        WHERE s.email = :email
          AND a.status = 1
    ");

    $maxHierarchyQuery->bindParam(':email', $login_staff, PDO::PARAM_STR);
    $maxHierarchyQuery->execute();
    $result = $maxHierarchyQuery->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) > 0) {
        $rows = $result[0];

        // ✅ Use null coalescing to avoid undefined index
        $school_id = $rows["school_id"] ?? null;
        $staff_id = $rows["staff_id"] ?? null;

        $image = "../files/images/teachers_images/" . ($rows["photo"] ?? '');
        $no_image = "../files/images/avatar.png"; 

        // Check if the image exists
        if (!empty($rows["photo"]) && file_exists($image)) {
            $display = htmlspecialchars($image);
        } else {
            $display = htmlspecialchars($no_image);
        }
    } else {
        $rows = [];
        $school_id = null;
        $display = "../files/images/avatar.png"; 
    }
        $display = $rows["photo"]; 

    // ✅ Determine if the staff is a class teacher
    $teacher_class_id = $rows["class_id"] ?? null;

    if (!empty($teacher_class_id) && ctype_digit((string)$teacher_class_id) && (int)$teacher_class_id > 0) {
        $is_class_teacher = true;
    } else {
        $is_class_teacher = false;
    }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
} catch (Exception $e) {
    echo '<div class="alert alert-warning">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

    $secretKey = "your_secret_key_here";
function generateParameters($url) { 
    global $secretKey; 
    $base64EncodedUrl = rtrim(strtr(base64_encode($url), '+/', '-_'), '='); 
    return '?' . $base64EncodedUrl . '?' . hash_hmac('sha256', $url, $secretKey); 
}
?>


<!DOCTYPE html> 
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template" data-style="light">

<head> 

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/iconify-icons.css"> 
      
      <link rel="stylesheet" href="../assets/css/pickr-themes.css">
    
    <link rel="stylesheet" href="../assets/css/core.css">
    <link rel="stylesheet" href="../assets/css/demo.css"> 
      <link rel="stylesheet" href="../assets/css/perfect-scrollbar.css"> 
    <link rel="stylesheet" href="../assets/css/flag-icons.css">
  <link rel="stylesheet" href="../assets/css/apex-charts.css"> 
    <!-- Helpers -->
    <script src="../assets/js/helpers.js"></script>
      <script src="../assets/js/template-customizer.js"></script>
      <script src="../assets/js/config.js"></script>
    
    <link rel="stylesheet" href="../assets/vendor/libs/datatables/datatables.bootstrap5.css">




        <!-- Icons & Stylesheets (Grouped for efficiency) -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css">
    <link rel="stylesheet" href="../assets/vendor/fonts/fontawesome.css">

    <!-- <link rel="stylesheet" href="../assets/vendor/css/rtl/core.css" class="template-customizer-core-css">
    <link rel="stylesheet" href="../assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css"> -->
    <!-- <link rel="stylesheet" href="../assets/vendor/libs/typeahead-js/typeahead.css"> -->
    <!-- <link rel="stylesheet" href="../assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css"> 
    <link rel="stylesheet" href="../assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css"> -->
 
    
<style>
  .swal2-container {
    z-index: 99999 !important;
}


    th {
        text-transform: uppercase;
    }
    .highlight {
    background-color: yellow; /*Highlight color*/
}
  
    .breadcrumb {
  justify-content: flex-end;
}
.datatable-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.form-group-flex {
  display: flex;
  gap: 20px; /* Space between elements */
}

.form-group {
  flex: 1; /* Allow inputs to take available space */
}

.button {
  flex: 0 0 auto; /* Button doesn't take up extra space */
  /* You could also add a margin-left to push the button a bit away if needed: */
   margin-top: 20px; */
   margin-left: 10px; */
}

/*Optional Styling to ensure inputs don't stretch too much if the content is short*/
.form-controls{
  width: auto; /*Remove default stretching*/
}
.custom-select, .form-controls  {
  position: relative;
  width: 250px;
  margin-top: 20px; /* Added margin-top */
}

.custom-select select, .form-controls input {
  padding: 5px 10px;
  border: 1px solid rgba(8, 151, 199, 0.42);
  border-radius: 5px;
  font-size: 16px;
  background-color: #fff;
  color: #333;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  width: calc(100% - 20px);
  height: 40px;
  line-height: 20px; /* Adjusted line-height to match height */
  box-sizing: border-box; /* Important for consistent sizing */
}
.custom-select select:focus { /* Or :active, depending on preference */
  border: 1.5px solid blue;
  /* border-color: blue; */
  outline: none; /* Remove default focus outline */
}


.image-container {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 50px;
  transition: transform 0.3s;
}

.image-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
  border: none;
}

.image-container:hover img {
  transform: scale(5.0);
  z-index: 1050;
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
}
 </style>

<?php
include '../assets/backend/preloader.php'; 
?>
</head>


<body>
   
  <!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar  ">
  <div class="layout-container">

     <?php 
require_once 'includes/sidebar.php';?>
    <!-- Layout container -->
    <div class="layout-page"> 
  
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
  
  <!-- Menu Toggle (desktop only now, hidden on mobile) -->
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-none d-lg-block">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="bx bx-menu bx-md"></i>
    </a>
  </div> <!-- Desktop Header (Navbar) -->
      

      <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse"> 
      <div class="navbar-nav align-items-center">
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
        <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
             
 

 
 

           </li> 
 <!-- Style Switcher -->
 <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
         
          <!-- Language -->
          <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <i class='bx bx-globe bx-md'></i>
            </a> 
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-language="en" data-text-direction="ltr">
                  <span>English</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-language="fr" data-text-direction="ltr">
                  <span>French</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-language="ar" data-text-direction="rtl">
                  <span>Arabic</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-language="de" data-text-direction="ltr">
                  <span>German</span>
                </a>
              </li>
            </ul>
          </li>
          <!-- /Language -->
           
<!-- Language -->
        
          <!-- /Language -->
          
          <!-- Style Switcher -->
          <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <i class='bx bx-toggle-left bx-tada' ></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                  <span><i class='bx bx-sun bx-md me-3'></i>Light</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                  <span><i class="bx bx-moon bx-md me-3"></i>Dark</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                  <span><i class="bx bx-desktop bx-md me-3"></i>System</span>
                </a>
              </li>
            </ul>
          </li>
          <!-- / Style Switcher--> 
 
          <!-- Notification -->
          <!-- <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
              <span class="position-relative">
                <i class="bx bx-bell bx-md"></i>
                <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"></span>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-0">
              <li class="dropdown-menu-header border-bottom">
                <div class="dropdown-header d-flex align-items-center py-3">
                  <h6 class="mb-0 me-auto">Notification</h6>
                  <div class="d-flex align-items-center h6 mb-0">
                    <span class="badge bg-label-primary me-2">8 New</span>
                    <a href="javascript:void(0)" class="dropdown-notifications-all p-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read"><i class="bx bx-envelope-open text-heading"></i></a>
                  </div>
                </div>
              </li>
              <li class="dropdown-notifications-list scrollable-container">
                <ul class="list-group list-group-flush">
                  <li class="list-group-item list-group-item-action dropdown-notifications-item">
                    <div class="d-flex">
                      <div class="flex-shrink-0 me-3">
                        <div class="avatar">
                            
                          <img src="../assets/img/avatars/1.png" alt class="rounded-circle">
                        </div>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="small mb-0">Congratulation Lettie 🎉</h6>
                        <small class="mb-1 d-block text-body">Won the monthly best seller gold badge</small>
                        <small class="text-muted">1h ago</small>
                      </div>
                      <div class="flex-shrink-0 dropdown-notifications-actions">
                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="bx bx-x"></span></a>
                      </div>
                    </div>
                  </li>  
                </ul>
              </li>
              <li class="border-top">
                <div class="d-grid p-4">
                  <a class="btn btn-primary btn-sm d-flex" href="javascript:void(0);">
                    <small class="align-middle">View all notifications</small>
                  </a>
                </div>
              </li>
            </ul>
          </li> -->
          <!--/ Notification -->
          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar avatar-online">
                <img src="<?php echo $display; ?>" alt class="w-px-40 h-auto rounded-circle">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="profile.php">
                  <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar avatar-online"> 
                        <img src="<?php echo $display; ?>" alt class="w-px-40 h-auto rounded-circle"alt="Teacher Photo">
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-0"><?php echo $rows["last_name"].' '. $rows["first_name"].' '. $rows["other_name"];?></h6>
                      <small class="text-muted"><?php echo $rows["school_id"];?></small>
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider my-1"></div>
              </li>
              <li  >
                <a class="dropdown-item" href="?YXhlYW1yYjRyNzh6dmd6OWl5ZW5wbDNwcTB6eTQyNzBsa3Zrd2h0a29wbjY1NGRjazYyaHNiOGFiYTVucmlzNGhpNDY3N245bHZ4ZDJyemNoOGZza3dqMXI4ZGNwanRtMWF5Z2Jmc2g3d2Jr?bXlfYWNjb3VudC5waHAmUGFnZU5hbWU9TXkgQWNjb3VudA?40fee0500740fef2b392903eef2f697e2548a4bed9b8623fb198c1963bf01579?ODc1MWQzZjAtNGI3ZC00Y2YyLThhYWUtZGI0N2E4OWQwMDZk">
                  <i class="bx bx-user bx-md me-3"></i><span>My Profile</span>
                </a>
              </li>
              <li  >
                <a class="dropdown-item" href="?YXhlYW1yYjRyNzh6dmd6OWl5ZW5wbDNwcTB6eTQyNzBsa3Zrd2h0a29wbjY1NGRjazYyaHNiOGFiYTVucmlzNGhpNDY3N245bHZ4ZDJyemNoOGZza3dqMXI4ZGNwanRtMWF5Z2Jmc2g3d2Jr?bXlfYWNjb3VudC5waHAmUGFnZU5hbWU9TXkgQWNjb3VudA?40fee0500740fef2b392903eef2f697e2548a4bed9b8623fb198c1963bf01579?ODc1MWQzZjAtNGI3ZC00Y2YyLThhYWUtZGI0N2E4OWQwMDZk">
                  <i class="bx bx-cog bx-md me-3"></i><span>Settings</span>
                </a>
              </li>
              <li hidden>
                <a class="dropdown-item" href="#">
                  <span class="d-flex align-items-center align-middle">
                    <i class="flex-shrink-0 bx bx-credit-card bx-md me-3"></i><span class="flex-grow-1 align-middle">Billing Plan</span>
                    <span class="flex-shrink-0 badge rounded-pill bg-danger">2</span>
                  </span>
                </a>
              </li>
              <li>
                <div class="dropdown-divider my-1"></div>
              </li>
              <li hidden>
                <a class="dropdown-item" href="#">
                  <i class="bx bx-dollar bx-md me-3"></i><span>Pricing</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="#">
                  <i class="bx bx-help-circle bx-md me-3"></i><span>FAQ</span>
                </a>
              </li>
              <li>
                <div class="dropdown-divider my-1"></div>
              </li>
              <li>
                <a class="dropdown-item" href="../logout.php" >
                  <i class="bx bx-power-off bx-md me-3"></i><span>Log Out</span>
                </a>
              </li>
            </ul>
          </li>
          <!--/ User -->
          

        </ul>
      </div>

      
      <!-- Search Small Screens -->
      <div class="navbar-search-wrapper search-input-wrapper  d-none">
        <input type="text" class="form-control search-input container-xxl border-0" placeholder="Search..." aria-label="Search...">
        <i class="bx bx-x bx-md search-toggler cursor-pointer"></i>
      </div>
      
      
  </nav>
  

  
<!-- / Navbar -->
<?php
}


// 6. Close the connection if needed. Add this only if it is necessary to close the connection here.
// mysqli_close($con);
  ?>
