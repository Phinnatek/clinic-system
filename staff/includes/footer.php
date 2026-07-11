<?php
if(!isset($_SESSION['user_logged_in'])){
?>
   
    <script>
        Swal.fire({
            title: 'oops',
            text: 'You are not authorized to access this portal!',
            icon: 'error',
            showConfirmButton: false, // Removes the OK/Login button
            timer: 3000, // Wait for 3 seconds
            timerProgressBar: true, // Shows a visual countdown
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then(() => {
            window.location.href = '../logout.php';
        });
    </script>
<?php }
?>

<style>
  .dataTables_paginate .paginate_button {
    padding: 0.5rem 1rem; /* Adjust padding for better button size */
    border-radius: 0.25rem; /* Rounded corners */
    border: 1px solid transparent; /* Border for hover effect */
    transition: background-color 0.3s, border-color 0.3s; /* Smooth transition */
}

.dataTables_paginate .paginate_button:hover {
    background-color: #007bff; /* Change background on hover */
    color: white; /* Text color on hover */
    border-color: #007bff; /* Border color on hover */
}

.dataTables_paginate .paginate_button.current {
    background-color: #007bff; /* Highlight current page */
    color: white; /* Text color for current page */
    border-color: #007bff; /* Border color for current page */
}

 
  /* 🔍 Blur class */ 
  #mainContent.blurred {
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
  }
 

  /* Optional: disable scrolling during alert */
  body.swal-open {
    overflow: hidden;
  }
</style>


<!-- Footer -->
<footer class="footer mt-auto py-4 border-top shadow-sm bg-body fade-in-footer">
  <div class="container-xxl">
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3"> 
      <!-- Left: Copyright -->
      <div class="text-body small text-center text-md-start">
        &copy; <script>document.write(new Date().getFullYear());</script>
        <span class="fw-bold text-primary">SEDACoE Clinic</span> &mdash; Design By SEDACoE IT Team
      </div> 
    </div>
  </div>
</footer>

<!-- Styles -->
<style>
/* Smooth fade-in animation */
.fade-in-footer {
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.9s ease forwards;
}
@keyframes fadeInUp {
  to { opacity: 1; transform: translateY(0); }
}

/* Hover effect for links */
.hover-scale {
  transition: transform 0.2s ease, background-color 0.2s ease;
}
.hover-scale:hover {
  transform: scale(1.05);
  background-color: var(--bs-light-bg-subtle, rgba(0,0,0,0.05));
}
 

/* 1. Prevent the wrapper from ever exceeding the screen width */
.scrolling-text-wrapper {
  overflow: hidden;
  white-space: nowrap;
  flex: 1;
  max-width: 100%; /* Add this */
  position: relative; /* Add this */
}

/* 2. Fix the animation to prevent horizontal overflow */
.scrolling-text {
  display: inline-block;
  will-change: transform;
  animation: scrollLeft 25s linear infinite;
}

@keyframes scrollLeft {
  /* Start from the right edge of the container, not 100% padding */
  0% { transform: translateX(100vw); } 
  100% { transform: translateX(-100%); }
}

/* 3. Force the overall page to hide horizontal overflow just in case */
body, .layout-wrapper {
  overflow-x: hidden !important;
}

/* Responsive behavior */
@media (max-width: 768px) {
  .scrolling-text { 
    animation-duration: 15s; /* Faster for short screens */
    font-size: 0.85rem; 
  }
  
  /* Hide scrolling text on very small screens if it still causes issues */
  /* .scrolling-text-wrapper { display: none; } */
}

</style>

  <style>
/* Your exact mobile styles */
@media (max-width: 1200px) {
    #template-customizer {
        display: flex !important;
        visibility: visible !important;
        inline-size: 85% !important; 
    }
    #template-customizer .template-customizer-open-btn {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        touch-action: none !important; 
        cursor: grab;
        position: fixed !important;
        z-index: 999999 !important; 
        border-radius: 12px 0 0 12px !important;
        box-shadow: -2px 4px 15px rgba(0, 0, 0, 0.2) !important;
        background: var(--bs-primary) !important;
        top: 40%; 
        inset-block-start: 40%;
        transform: translateX(-100%) !important;
    }
    #template-customizer:not(.template-customizer-open) { transform: translateX(100%) !important; }
}

/* Fix for desktop dragging */
@media (min-width: 1201px) {
    #template-customizer { display: flex !important; }
    #template-customizer .template-customizer-open-btn { 
        cursor: grab; 
        touch-action: none !important;
        position: fixed !important; 
        top: 40%;
        right: 0;
        z-index: 999999;
    }
}

.is-dragging-now {
    transition: none !important;
    cursor: grabbing !important;
}
</style>
<script>
  const server_url = '../assets/backend/server_side.php';
$(document).ready(function() {
    const btn = document.querySelector('.template-customizer-open-btn');
    if (!btn) return;

    let isDragging = false;
    let startY, startTop;
    let moveCount = 0;

    const getY = (e) => e.touches ? e.touches[0].clientY : e.clientY;

    const startMove = (e) => {
        // For mouse: only allow left click (button 0)
        if (e.type === 'mousedown' && e.button !== 0) return;
        
        isDragging = true;
        moveCount = 0;
        btn.classList.add('is-dragging-now');
        
        startY = getY(e);
        startTop = btn.getBoundingClientRect().top;
        
        document.body.style.userSelect = 'none';
    };

    const onMove = (e) => {
        if (!isDragging) return;

        moveCount++;
        const currentY = getY(e);
        const deltaY = currentY - startY;
        let newTop = startTop + deltaY;

        const winH = window.innerHeight;
        const btnH = btn.offsetHeight;
        if (newTop < 0) newTop = 0;
        if (newTop > winH - btnH) newTop = winH - btnH;

        // Instant application
        btn.style.setProperty('top', newTop + 'px', 'important');
        btn.style.setProperty('inset-block-start', newTop + 'px', 'important');
        
        if (e.cancelable) e.preventDefault();
    };

    const endMove = () => {
        if (!isDragging) return;
        isDragging = false;
        btn.classList.remove('is-dragging-now');
        document.body.style.userSelect = '';
    };

    // MOUSE EVENTS (Desktop)
    btn.addEventListener('mousedown', startMove);
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', endMove); // Listen on window so it releases even if mouse moves fast

    // TOUCH EVENTS (Mobile)
    btn.addEventListener('touchstart', startMove, { passive: true });
    window.addEventListener('touchmove', onMove, { passive: false });
    window.addEventListener('touchend', endMove);

    // Prevent click if we actually dragged
    btn.addEventListener('click', (e) => {
        if (moveCount > 5) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);
});
</script>



        
        <div class="content-backdrop fade"></div>
      </div>
      <!--/ Content wrapper -->
    </div>

    <!--/ Layout container -->
  </div>
</div>

 

<div class="layout-overlay layout-menu-toggle"></div>


<!-- Drag Target Area To SlideIn Menu On Small Screens -->
<div class="drag-target"></div>

<!--/ Layout wrapper -->

    
      <!-- <div class="buy-now">
        <a
href="tel: 233552380387"
target="_blank"
class="btn btn-danger btn-buy-now"
>Contact Pinnatek</a>

      </div> -->
    

    

    <!-- Core JS -->
    <!-- build:js assets/vendor/../assets/js/theme.js  -->
    
      <script src="../assets/js/jquery.js"></script>
 <script src="../assets/js/cleave.js"></script>
    
    <script src="../assets/js/popper.js"></script>
    <script src="../assets/js/bootstrap.js"></script>
    <script src="../assets/js/autocomplete-js.js"></script>
    <script src="../assets/js/html2canvas.min.js"></script>
    
      
      <script src="../assets/js/pickr.js"></script>
      <script src="../assets/js/perfect-scrollbar.js"></script>
        <script src="../assets/js/hammer.js"></script>
      <script src="../assets/js/menu.js"></script>
    <script src="../assets/js/apexcharts.js"></script>
      <script src="../assets/js/main.js"></script>
      <script src="../assets/js/choice.js"></script>
      <script src="../assets/js/cropper.min.js"></script>
    <script src="../assets/js/dashboards-analytics.js"></script> 
<script src="../assets/js/axios.min.js"></script> 
<script src="../assets/js/chartJs.js"></script> 
 
<script src="../assets/js/jspdf.umd.min.js"></script>  
<script src="../assets/js/jspdf.plugin.autotable.min.js"></script>
<script src="../assets/js/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/js/datatables/dataTables.buttons.min.js"></script>
<script src="../assets/js/datatables/jszip.min.js"></script>
<script src="../assets/js/datatables/pdfmake.min.js"></script> 
<script src="../assets/js/datatables/buttons.html5.min.js"></script>
<script src="../assets/js/datatables/buttons.print.min.js"></script>  
<script>
  
// ------------------- DATATABLE INITIALIZATION -------------------
function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#DataTable')) {
        $('#DataTable').DataTable().destroy();
    }

    let previousSearchTerm = '';
    try {

       $('#DataTable').DataTable({
          paging: true,
          lengthChange: true,
          searching: true,
          ordering: true,
          info: true,
          autoWidth: true,
          responsive: true, 
          // Clean structural config replacement logic string setup layout path:
dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4"lBf>rtip',

            language: {
                lengthMenu: "_MENU_ records",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                search: "Search:",
            },
            lengthMenu: [
                [10, 25, 50, 100, 200, 500, 1000, -1],
                ["10", "25", "50", "100", "200", "500", "1000", "All"]
            ],
            buttons: [
                { extend: 'copy', text: '<i class="exportButtons bx bx-copy"></i> Copy', init: (api, node) => $(node).removeClass().addClass('btn btn-label-primary me-1  d-none') },
                { extend: 'csv', text: '<i class="exportButtons bx bx-file"></i> CSV', init: (api, node) => $(node).removeClass().addClass('btn btn-label-success me-1  d-none') },
                { extend: 'excel', text: '<i class="exportButtons bx bx-file"></i> Excel', init: (api, node) => $(node).removeClass().addClass('btn btn-label-danger me-1  d-none') },
                { extend: 'pdf', text: '<i class="exportButtons fas fa-file-pdf"></i> PDF', init: (api, node) => $(node).removeClass().addClass('btn btn-label-warning me-1  d-none') },
                { extend: 'print', text: '<i class="exportButtons bx bx-printer"></i> Print', init: (api, node) => $(node).removeClass().addClass('btn btn-label-info me-1  d-none') }
            ],
            drawCallback: function(settings) {
                const currentSearchTerm = this.api().search();
                if (currentSearchTerm !== previousSearchTerm) {
                    previousSearchTerm = currentSearchTerm;
                    const regex = new RegExp(currentSearchTerm.replace(/[-/\^$*+?.()|[\]{}]/g, '\\$&'), 'gi');
                    $('#DataTable tbody tr').each(function() {
                        $(this).find('td').each(function() {
                            const $cell = $(this);
                            const excluded = ['img', 'button', 'span', 'a'];
                            if (!excluded.some(el => $cell.find(el).length > 0)) {
                                const text = $cell.text();
                                $cell.html(text.replace(regex, '<strong class="highlight">$&</strong>'));
                            }
                        });
                    });
                }
            },
            initComplete: function() {
                $('.dataTables_length select').addClass('form-control');
                $('.dataTables_filter input').addClass('form-control');
                stylePaginationButtons($(this));
            }
        });

        $('#DataTable').on('page.dt', function() { stylePaginationButtons($(this)); });

    } catch (error) {
        console.error('Error initializing DataTable:', error);
    }
}

function stylePaginationButtons(table) {
    function update() {
        setTimeout(() => {
            const nextBtn = $('.dataTables_paginate .paginate_button.next');
            const prevBtn = $('.dataTables_paginate .paginate_button.previous');

            nextBtn.toggleClass('disabled', nextBtn.hasClass('paginate_button_disabled'));
            prevBtn.toggleClass('disabled', prevBtn.hasClass('paginate_button_disabled'));

            $('.dataTables_paginate .paginate_button.current').removeClass().addClass('btn btn-primary me-1 text-white');
            $('.dataTables_paginate .paginate_button.next').removeClass().addClass('btn dataTables_previous');
            $('.dataTables_paginate .paginate_button.previous').removeClass().addClass('btn dataTables_previous');
            $('.dataTables_paginate .paginate_button').not('.current, .next, .previous, .disabled').removeClass();
            $('.dataTables_paginate .paginate_button.disabled').removeClass().addClass('disabled');
        }, 0);
    }
    update();
    table.on('draw.dt', update);
}
$(document).ready(initializeDataTable); 

</script>
</body>
</html><!-- beautify ignore:end -->
 