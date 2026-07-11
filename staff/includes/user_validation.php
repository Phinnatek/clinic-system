<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$session_id = $_SESSION['user_id'] ?? null;
$rows = null;

if ($session_id) {
    require_once '../conn.php';
    require_once '../assets/backend/helper_functions.php';

    // FIXED: Updated to look up status = 'Active' instead of the retired is_active column
    $infoStmt = $con->prepare("SELECT * FROM users WHERE id = :id AND status = 'Active' LIMIT 1");
    $infoStmt->bindParam(':id', $session_id, PDO::PARAM_INT);
    $infoStmt->execute();
    $rows = $infoStmt->fetch(PDO::FETCH_ASSOC);
}

// SERVER-SIDE SECURITY GUARD: Terminate immediately if user validation fails
if (!$session_id || !$rows) {
    error_log("Unauthorized portal access attempt from session: " . ($session_id ?? 'None'));
    session_unset();
    session_destroy();
    header("Location: ../logout.php");
    exit; 
} 

// Authorization Successful - Map Secure Configurations Safely
$admin_id       = $rows['id'];
$admin_role     = $rows['role']; 
$user_full_name = trim($rows['full_name']);
$userAvatar     = $rows['profile_image'] ?? 'default-avatar.png'; 

$name_parts     = explode(' ', $user_full_name);
$user_last_name = end($name_parts) ?: 'User'; 
?>
<script src="../assets/js/jquery.js"></script>
<script src="../assets/js/sweetalert2.js"></script>
