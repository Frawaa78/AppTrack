<?php
// shared/topbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 sticky-top" style="z-index: 1030; margin-bottom:0; padding-top:0; padding-bottom:0;">
  <div class="container-fluid px-0">
    <a class="navbar-brand" href="dashboard.php">AppTrack</a>
    <div class="flex-grow-1 d-flex justify-content-center">
      <form class="d-flex w-100" method="get" action="search.php" style="max-width:600px;">
        <input class="form-control me-2 search-bar" type="search" name="q" placeholder="Search applications, users, ..." aria-label="Search" <?php if(isset($topbar_search_disabled) && $topbar_search_disabled) echo 'readonly'; ?>>
        <button class="btn btn-outline-primary" type="submit" <?php if(isset($topbar_search_disabled) && $topbar_search_disabled) echo 'disabled'; ?>>Search</button>
      </form>
    </div>
    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_email'] ?? 'U'); ?>&background=0D8ABC&color=fff" alt="Profile" class="profile-img me-2">
          <span><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="#">Profile</a></li>
          <li><a class="dropdown-item" href="#">Account</a></li>
          <li><a class="dropdown-item" href="#">Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="logout.php">Log out</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
