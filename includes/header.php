
    <nav class="navbar">
        <div class="brand">
            <img class="logo" src="/HIWEB/includes/assets/locoput.svg" alt="Logo">
            <h1>CodePlay</h1>
        </div>
        <div class="nav-actions">
            <a href="#" id="logoutBtn" class="logout">Logout</a>
        </div>
    </nav>

    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="<?php echo $page_css; ?>">
    <?php endif; ?>

    <!-- Bootstrap 5 -->
   

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="/HIWEB/includes/css/header.css">
</head>
<script>document.getElementById("logoutBtn").addEventListener("click", function(e) {
  e.preventDefault();
  Swal.fire({
    title: 'Yakin ingin logout?',
    text: "Anda akan keluar dari sesi ini.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, logout!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = "login/logout.php";
    }
  });
});</script>