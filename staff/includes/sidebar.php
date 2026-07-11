<?php
/**
 * Sidebar Navigation for Logbook Management System
 * Includes: Dashboard, Attendance, Visitors, Key Movement, Inventory, Reports, Audit Logs, Logout
 */
?>
<aside id="layout-menu" class="layout-menu menu-vertical menu">
  <div class="app-brand demo">
    <a href="index.php" class="app-brand-link">
      <span class="app-brand-logo demo">
        <img src="../assets/img/avatars/logo.png" alt="Logo" height="40">
      </span>
      <span class="app-brand-text demo menu-text fw-bold ms-2">S. Clinic</span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
      <i class="bx bx-chevron-left bx-sm align-middle"></i>
    </a>
  </div>

  <!-- Sidebar Search -->
  <div class="px-3 py-2 mb-2 sidebar-search-wrapper">
    <div class="input-group input-group-merge shadow-none border rounded-pill bg-light">
        <span class="input-group-text bg-transparent border-0 pe-0">
            <i class="bx bx-search text-muted"></i>
        </span>
        <input 
            type="text" 
            class="form-control bg-transparent border-0 ps-2 py-2" 
            placeholder="Quick find... (Press '/')" 
            id="sidebarSearchInput"
            autocomplete="off"
        >
    </div>
  </div> 
     
  <div class="menu-inner-shadow"></div>
  <ul class="menu-inner py-1"> 
      <?php 
// 1. EXTRACT RUNTIME WORKING FILENAME SOURCE FROM PATH
$current_page = basename($_SERVER['SCRIPT_NAME']); 
?>

<!-- ========================================== HOME / DASHBOARD ========================================== -->
<li class="menu-item <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>"><a href="index.php" class="menu-link"><i class="menu-icon tf-icons bx bx-home-smile" style="color:#0d6efd;"></i><div class="text-truncate">Dashboard</div></a></li>
<?php if ($_SESSION['role'] === 'Admin'): ?>
<li class="menu-item <?php echo ($current_page === 'executive_dashboard.php') ? 'active' : ''; ?>"><a href="executive_dashboard.php" class="menu-link"><i class="menu-icon tf-icons bx bx-pie-chart-alt-2" style="color:#0d6efd;"></i><div class="text-truncate">Executive Summary</div></a></li>
<?php endif; ?>

<!-- ========================================== RECORDS DESK & OPD VITALS ========================================== -->
<?php if (in_array($_SESSION['role'], ['Admin', 'Nurse'])): ?>
<li class="menu-header small text-uppercase"><span class="menu-header-text">Records & OPD</span></li>
<li class="menu-item <?php echo ($current_page === 'patients.php') ? 'active' : ''; ?>"><a href="patients.php" class="menu-link"><i class="menu-icon tf-icons bx bx-user-pin" style="color:#02c39a;"></i><div class="text-truncate">Patient & OPD</div></a></li>
<?php endif; ?>

<!-- ========================================== CLINICAL SERVICES ========================================== -->
<?php if (in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse', 'Lab Technician'])): ?>
<li class="menu-header small text-uppercase"><span class="menu-header-text">Clinical Services</span></li>
<?php if (in_array($_SESSION['role'], ['Admin', 'Doctor'])): ?><li class="menu-item <?php echo ($current_page === 'consulting_room.php') ? 'active' : ''; ?>"><a href="consulting_room.php" class="menu-link"><i class="menu-icon tf-icons bx bx-injection" style="color:#ffb703;"></i><div class="text-truncate">Consulting Room</div></a></li><?php endif; ?>
<?php if (in_array($_SESSION['role'], ['Admin', 'Lab Technician'])): ?><li class="menu-item <?php echo ($current_page === 'laboratory.php') ? 'active' : ''; ?>"><a href="laboratory.php" class="menu-link"><i class="menu-icon tf-icons bx bx-test-tube" style="color:#00cfdd;"></i><div class="text-truncate">Laboratory</div></a></li><?php endif; ?>
<?php if (in_array($_SESSION['role'], ['Admin', 'Nurse'])): ?><li class="menu-item <?php echo ($current_page === 'nurse_injection.php') ? 'active' : ''; ?>"><a href="nurse_injection.php" class="menu-link"><i class="menu-icon tf-icons bx bx-shield-plus" style="color:#e63946;"></i><div class="text-truncate">Nurse Injection Bay</div></a></li><?php endif; ?>
<?php if (in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])): ?><li class="menu-item <?php echo ($current_page === 'wards.php') ? 'active' : ''; ?>"><a href="wards.php" class="menu-link"><i class="menu-icon tf-icons bx bx-bed" style="color:#8338ec;"></i><div class="text-truncate">Ward & Admission</div></a></li><?php endif; ?>
<?php endif; ?>

<!-- ========================================== PHARMACY & STORES ========================================== -->
<?php if (in_array($_SESSION['role'], ['Admin', 'Pharmacist', 'Storekeeper'])): ?>
<li class="menu-header small text-uppercase"><span class="menu-header-text">Pharmacy & Inventory</span></li>
<?php if (in_array($_SESSION['role'], ['Admin', 'Pharmacist'])): ?><li class="menu-item <?php echo ($current_page === 'pharmacy_dispense.php') ? 'active' : ''; ?>"><a href="pharmacy_dispense.php" class="menu-link"><i class="menu-icon tf-icons bx bx-capsule" style="color:#2a9d8f;"></i><div class="text-truncate">Dispense Drugs</div></a></li><?php endif; ?>
<?php if (in_array($_SESSION['role'], ['Admin', 'Storekeeper'])): ?>
<li class="menu-item <?php echo ($current_page === 'stores.php') ? 'active' : ''; ?>"><a href="stores.php" class="menu-link"><i class="menu-icon tf-icons bx bx-archive" style="color:#a8dadc;"></i><div class="text-truncate">Main Store Queue</div></a></li>
<li class="menu-item <?php echo ($current_page === 'stores_manage.php') ? 'active' : ''; ?>"><a href="stores_manage.php" class="menu-link"><i class="menu-icon tf-icons bx bx-wrench" style="color:#457b9d;"></i><div class="text-truncate">Manage Main Store</div></a></li>
<?php endif; ?>
<?php if (in_array($_SESSION['role'], ['Admin', 'Pharmacist'])): ?>
<li class="menu-item <?php echo ($current_page === 'pharmacy_store.php') ? 'active' : ''; ?>"><a href="pharmacy_store.php" class="menu-link"><i class="menu-icon tf-icons bx bx-store-alt" style="color:#e76f51;"></i><div class="text-truncate">Pharmacy Shelf Stock</div></a></li>
<li class="menu-item <?php echo ($current_page === 'requisitions.php') ? 'active' : ''; ?>"><a href="requisitions.php" class="menu-link"><i class="menu-icon tf-icons bx bx-git-pull-request" style="color:#457b9d;"></i><div class="text-truncate">Stock Requisitions</div></a></li>
<?php endif; ?>
<?php endif; ?>

<!-- ========================================== FINANCIALS & BILLING ========================================== -->
<?php if (in_array($_SESSION['role'], ['Admin', 'Cashier'])): ?>
<li class="menu-header small text-uppercase"><span class="menu-header-text">Accounts</span></li>
<li class="menu-item <?php echo ($current_page === 'cashier_bills.php') ? 'active' : ''; ?>"><a href="cashier_bills.php" class="menu-link"><i class="menu-icon tf-icons bx bx-wallet" style="color:#70e000;"></i><div class="text-truncate">Cashier Billing</div></a></li>
<li class="menu-item <?php echo ($current_page === 'finance_ledger.php') ? 'active' : ''; ?>"><a href="finance_ledger.php" class="menu-link"><i class="menu-icon tf-icons bx bx-calculator" style="color:#38b000;"></i><div class="text-truncate">Finance Ledger</div></a></li>
<?php endif; ?>

<!-- ========================================== ADMINISTRATION & REPORTS ========================================== -->
<?php if ($_SESSION['role'] === 'Admin'): ?>
<li class="menu-header small text-uppercase"><span class="menu-header-text">Administration</span></li>
<li class="menu-item <?php echo ($current_page === 'medical_reports.php') ? 'active' : ''; ?>"><a href="medical_reports.php" class="menu-link"><i class="menu-icon tf-icons bx bx-bar-chart-alt-2" style="color:#9b5de5;"></i><div class="text-truncate">Print Reports</div></a></li>
<li class="menu-item <?php echo ($current_page === 'staff_manage.php') ? 'active' : ''; ?>"><a href="staff_manage.php" class="menu-link"><i class="menu-icon tf-icons bx bx-group" style="color:#9b5de5;"></i><div class="text-truncate">Manage Staff</div></a></li>
<?php endif; ?>

<!-- ========================================== ACCOUNT PROFILE & SESSION ========================================== -->
<li class="menu-header small text-uppercase mt-2"><span class="menu-header-text">Account</span></li>
<li class="menu-item <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>"><a href="profile.php" class="menu-link"><i class="menu-icon tf-icons bx bx-user" style="color:#0d6efd;"></i><div class="text-truncate">My Profile</div></a></li>
<li class="menu-item"><a href="javascript:void(0);" class="menu-link logout" onclick="confirmLogout();"><i class="menu-icon tf-icons bx bx-log-out" style="color:#dc3545;"></i><div class="text-truncate">Logout</div></a></li>

  </ul>
</aside>
<script>
  // ====================================================================
// SECURE LOGOUT TERMINAL CONFIRMATION HANDLER
// ====================================================================
function confirmLogout() {
    Swal.fire({
        title: 'Do you want to leave?',
        text: 'This will lock your clinic computer dashboard until you type your secret password keys again.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', // Beautiful Red button for log out
        cancelButtonColor: '#8592a3',  // Soft grey button to cancel
        confirmButtonText: 'Yes, Close My Work',
        cancelButtonText: 'No, Keep Working'
    }).then((result) => {
        if (result.isConfirmed) {
            // Friendly loading animation before shifting windows
            Swal.fire({
                title: 'Locking your desk...',
                text: 'Saving your changes and cleaning the screen safely.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            // Jump smoothly to your logout backend path file
            window.location.href = "../logout.php";
        }
    });
}

</script>

<style>
 
/* 2. Glass Sticky Wrapper (Uses BS5 variable for background transparency) */
.sidebar-search-wrapper {
    position: sticky;
    top: 0;
    z-index: 10; 
    backdrop-filter: blur(12px) saturate(160%);
    -webkit-backdrop-filter: blur(12px) saturate(160%); 
    margin-bottom: 0.5rem !important;
    padding-top: 1rem !important;
}

/* 3. Input Group Styling */
.sidebar-search-wrapper .input-group {
    background-color: rgba(var(--bs-tertiary-bg-rgb), 0.5) !important;
    border: 1px solid var(--bs-border-color) !important;
}

.sidebar-search-wrapper .input-group:focus-within {
    border-color: var(--bs-primary) !important;
    background-color: var(--bs-body-bg) !important;
    /* Subtle shadow using BS5 dark-rgb variable */
    box-shadow: 0 4px 12px rgba(var(--bs-dark-rgb), 0.1) !important;
}

#sidebarSearchInput::placeholder {
    font-size: 0.85rem;
    color: var(--bs-secondary-color);
}

/* 4. Highlight Styling (Uses BS5 warning colors) */
mark.search-highlight {
    background-color: var(--bs-warning-bg-subtle) !important;
    color: var(--bs-warning-text-emphasis) !important;
    padding: 0;
    border-radius: 2px;
    font-weight: bold;
}

/* 5. Custom Menu Toggle (Manual Display) */
.menu-item.open > .menu-sub {
    display: block !important;
}

/* 6. Layout Cleanup */
.menu-inner {
    padding-top: 0 !important;
}

/* Scrollbar Styling (Uses BS5 gray scale) */
.layout-menu .menu-inner::-webkit-scrollbar {
    width: 4px;
}
.layout-menu .menu-inner::-webkit-scrollbar-thumb {
    background: var(--bs-border-color-translucent);
    border-radius: 10px;
}
</style>


<script>
$(document).ready(function() {
    // Read the explicit running page file directly out from the browser's window URL paths
    var runningFilenamePage = window.location.pathname.split("/").pop();
    
    // If the path breaks down to empty root, default highlight your central dashboard file link
    if (runningFilenamePage === "") { runningFilenamePage = "index.php"; }

    // Drop previous active markings from elements, then seek out the target row matching attributes link
    $('.menu-item').removeClass('active');
    $('.menu-item a[href="' + runningFilenamePage + '"]').closest('.menu-item').addClass('active');
});

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('sidebarSearchInput');
    const menuItems = document.querySelectorAll('.menu-inner .menu-item');
    const menuHeaders = document.querySelectorAll('.menu-inner .menu-header');

    // 1. Setup: Save original labels (Strictly text only)
    menuItems.forEach(item => {
        const textDiv = item.querySelector('.text-truncate');
        if (textDiv) {
            // We get only the text node, ignoring icons/badges inside the div
            const originalLabel = Array.from(textDiv.childNodes)
                .filter(node => node.nodeType === Node.TEXT_NODE)
                .map(node => node.textContent.trim())
                .join("");
            
            if(originalLabel) textDiv.setAttribute('data-orig-label', originalLabel);
        }
    });

    // 2. Keyboard Shortcut ('/')
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            searchInput.focus();
        }
    });

    // 3. Search Logic
    searchInput.addEventListener('input', function() {
        const filter = this.value.toLowerCase().trim();
        
        if (!filter) {
            // Reset Sidebar to Default
            menuItems.forEach(item => {
                item.style.display = "";
                item.classList.remove('open');
                const textDiv = item.querySelector('.text-truncate');
                if (textDiv && textDiv.hasAttribute('data-orig-label')) {
                    textDiv.innerText = textDiv.getAttribute('data-orig-label');
                }
            });
            menuHeaders.forEach(h => h.style.display = "");
            return;
        }

        menuHeaders.forEach(h => h.style.display = "none");

        menuItems.forEach(item => {
            const textDiv = item.querySelector('.text-truncate');
            if (!textDiv || !textDiv.hasAttribute('data-orig-label')) return;

            const label = textDiv.getAttribute('data-orig-label');
            const isMatch = label.toLowerCase().includes(filter);

            if (isMatch) {
                item.style.display = "";
                
                // Highlight Text
                const regex = new RegExp(`(${filter})`, 'gi');
                textDiv.innerHTML = label.replace(regex, '<mark class="search-highlight">$1</mark>');

                // Auto-Open Parents
                let parent = item.parentElement.closest('.menu-item');
                while (parent) {
                    parent.style.display = "";
                    parent.classList.add('open');
                    parent = parent.parentElement.closest('.menu-item');
                }
            } else {
                item.style.display = "none";
                item.classList.remove('open');
            }
        });

        // Ensure parent containers of matched items stay visible
        document.querySelectorAll('.menu-item').forEach(item => {
            if (item.querySelector('.menu-item:not([style*="display: none"])')) {
                item.style.display = "";
            }
        });
    });
});

</script>
 

<!-- Keep existing HTML content of sidebar -->
 <style>
  .menu-inner > .menu-item.active > .menu-link {
  /* background-color: #f0f4ff !important;   */
  border-left: 4px solid #0d6efd;
}
 /*
.menu-sub .menu-item.active > .menu-link {
  background-color: #e8f0ff !important;
  font-weight: 500;
} 
*/
 </style>

 