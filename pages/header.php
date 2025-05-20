<!-- header.php -->
<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<div class="d-flex justify-content-between align-items-center flex-wrap py-2 px-3 border-bottom bg-light">
    <div class="flex-grow-1">
        <h3 class="mb-0">Bienvenue </h3>
    </div>
    <div class="text-end">
        <img src="https://semelledor.com/cdn/shop/files/or_semmel_6_1.png"
             alt="Logo"
             style="max-height: 60px; height: auto; width: auto;"
             class="img-fluid">
    </div>
</div>
