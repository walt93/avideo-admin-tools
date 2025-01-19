<?php
// Get the current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 px-3">
    <div class="container-fluid">
        <!-- Internal Links (Management) -->
        <div class="navbar-nav me-auto">
            <a class="nav-link <?= $current_page === 'index' ? 'active fw-bold' : '' ?>"
               href="/management/users/<?= basename(dirname($_SERVER['PHP_SELF'])) ?>/index.php">
                Media
            </a>
            <a class="nav-link <?= $current_page === 'upload' ? 'active fw-bold' : '' ?>"
               href="/management/users/<?= basename(dirname($_SERVER['PHP_SELF'])) ?>/upload.php">
                Upload
            </a>
        </div>

        <!-- External Links -->
        <div class="navbar-nav">
            <a class="nav-link" href="https://conspyre.xyz" target="_blank">
                Conspyre.tv
                <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.8em;"></i>
            </a>
            <a class="nav-link" href="https://encoder1.conspyre.xyz" target="_blank">
                Encoder
                <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.8em;"></i>
            </a>
        </div>
    </div>
</nav>

<style>
.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.navbar .nav-link {
    color: rgba(255,255,255,0.8);
    transition: color 0.2s;
    padding: 0.5rem 1rem;
    margin: 0 0.25rem;
    border-radius: 4px;
}

.navbar .nav-link:hover {
    color: rgba(255,255,255,1);
    background: rgba(255,255,255,0.1);
}

.navbar .nav-link.active {
    color: white;
    background: rgba(255,255,255,0.15);
}

/* External link icons */
.navbar .bi {
    opacity: 0.7;
    transition: opacity 0.2s;
}

.navbar .nav-link:hover .bi {
    opacity: 1;
}
</style>