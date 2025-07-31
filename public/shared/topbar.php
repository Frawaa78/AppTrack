<?php
// shared/topbar.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Calculate the correct path based on where this file is included from
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
$public_dir = '/public';

// Determine how many levels up we need to go to reach the public root
$path_parts = explode('/', trim($current_dir, '/'));
$public_index = array_search('public', $path_parts);
if ($public_index !== false) {
    $levels_deep = count($path_parts) - $public_index - 1;
} else {
    $levels_deep = 0; // Default to 0 if we can't determine
}

// Build the relative path to assets
$assets_path = str_repeat('../', $levels_deep + 1) . 'assets';
$dashboard_path = str_repeat('../', $levels_deep) . 'dashboard.php';
?>

<style>
/* Global Search Dropdown Styles */
.search-results-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  z-index: 1050;
  max-height: 400px;
  overflow-y: auto;
  margin-top: 2px;
}

.search-results-content {
  padding: 0.5rem 0;
}

.search-group {
  margin-bottom: 0.5rem;
}

.search-group:last-child {
  margin-bottom: 0;
}

.search-group-header {
  padding: 0.5rem 1rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: #6c757d;
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  margin: 0;
}

.search-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: background-color 0.15s ease-in-out;
}

.search-item:hover {
  background-color: #f8f9fa;
}

.search-item-focused {
  background-color: #e3f2fd !important;
  outline: 2px solid #0d6efd;
  outline-offset: -2px;
}

.search-item-content {
  flex: 1;
}

.search-item-title {
  font-weight: 500;
  color: #212529;
  margin-bottom: 0.25rem;
  font-size: 0.9rem;
}

.search-item-subtitle {
  font-size: 0.8rem;
  color: #6c757d;
  margin: 0;
}

.search-item-meta {
  font-size: 0.75rem;
  color: #9DA3A8;
  text-align: right;
  flex-shrink: 0;
  margin-left: 1rem;
}

.search-no-results {
  padding: 1rem;
  text-align: center;
  color: #6c757d;
  font-size: 0.875rem;
}

.user-search-item {
  opacity: 0.7;
}

.user-search-item .search-item-title {
  color: #6c757d;
}

/* Navigation Menu Hover Effects */
.navbar-nav .nav-link {
  transition: all 0.3s ease;
  border-radius: 0.375rem;
  margin: 0 0.25rem;
  padding: 0.5rem 0.75rem !important;
}

.navbar-nav .nav-link:hover {
  background-color: #f8f9fa;
  color: #0d6efd !important;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar-nav .nav-link i {
  transition: transform 0.3s ease;
}

.navbar-nav .nav-link:hover i {
  transform: scale(1.1);
}
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 sticky-top" style="z-index: 1030; margin-bottom:0; padding-top:0; padding-bottom:0;">
  <div class="container-fluid px-0">
    <a class="navbar-brand" href="<?php echo $dashboard_path; ?>">
      <img src="<?php echo $assets_path; ?>/logo.png" alt="AppTrack" style="height: 32px; margin-top: 5px; margin-left: 10px;">
    </a>
    <div class="flex-grow-1 d-flex justify-content-center">
      <div class="position-relative w-100" style="max-width:600px;">
        <form class="d-flex w-100" method="get" action="<?php echo str_repeat('../', $levels_deep); ?>search.php" id="search-form">
          <input class="form-control search-bar" type="search" name="q" id="global-search" placeholder="Start typing to search..." aria-label="Search" autocomplete="off" <?php if(isset($topbar_search_disabled) && $topbar_search_disabled) echo 'readonly'; ?>>
        </form>
        
        <!-- Search Results Dropdown -->
        <div class="search-results-dropdown" id="search-results" style="display: none;">
          <div class="search-results-content">
            <!-- Results will be populated by JavaScript -->
          </div>
        </div>
      </div>
    </div>
    
    <!-- Main Navigation -->
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo str_repeat('../', $levels_deep); ?>dashboard.php">
          <i class="fa-regular fa-grid-2"></i> Applications
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo str_repeat('../', $levels_deep); ?>executive_dashboard.php">
          <i class="fa-light fa-monitor-waveform"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo str_repeat('../', $levels_deep); ?>user_stories.php">
          <i class="fa-light fa-lightbulb"></i> User Stories
        </a>
      </li>
    </ul>
    
    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_display_name'] ?? $_SESSION['user_email'] ?? 'U'); ?>&background=0D8ABC&color=fff" alt="Profile" class="profile-img me-2">
          <span><?php echo htmlspecialchars($_SESSION['user_display_name'] ?? $_SESSION['user_email'] ?? ''); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="<?php echo str_repeat('../', $levels_deep); ?>profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
          <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li><a class="dropdown-item" href="<?php echo str_repeat('../', $levels_deep); ?>users_admin.php"><i class="bi bi-people me-2"></i>Users</a></li>
            <li><a class="dropdown-item" href="<?php echo str_repeat('../', $levels_deep); ?>settings_admin.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
          <?php endif; ?>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?php echo str_repeat('../', $levels_deep); ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>

<script>
// Global Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('global-search');
    const searchResults = document.getElementById('search-results');
    const searchForm = document.getElementById('search-form');
    const apiPath = '<?php echo str_repeat('../', $levels_deep); ?>api/'; // Dynamic API path
    let searchTimeout;
    let currentFocusIndex = -1;
    let searchItems = [];
    
    if (!searchInput || searchInput.hasAttribute('readonly')) {
        return; // Exit if search is disabled
    }
    
    // Prevent form submission on Enter when dropdown is open
    searchForm.addEventListener('submit', function(e) {
        if (searchResults.style.display !== 'none') {
            e.preventDefault();
        }
    });
    
    // Handle input changes
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        currentFocusIndex = -1;
        
        if (query.length < 3) {
            hideResults();
            return;
        }
        
        // Debounce search requests
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (searchResults.style.display === 'none') return;
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                navigateResults(1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                navigateResults(-1);
                break;
            case 'Enter':
                e.preventDefault();
                if (currentFocusIndex >= 0 && searchItems[currentFocusIndex]) {
                    searchItems[currentFocusIndex].click();
                }
                break;
            case 'Escape':
                hideResults();
                break;
        }
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.position-relative')) {
            hideResults();
        }
    });
    
    function navigateResults(direction) {
        // Remove current focus
        if (currentFocusIndex >= 0 && searchItems[currentFocusIndex]) {
            searchItems[currentFocusIndex].classList.remove('search-item-focused');
        }
        
        // Calculate new focus index
        if (direction === 1) { // Down
            currentFocusIndex = (currentFocusIndex + 1) % searchItems.length;
        } else { // Up
            currentFocusIndex = currentFocusIndex <= 0 ? searchItems.length - 1 : currentFocusIndex - 1;
        }
        
        // Apply new focus
        if (searchItems[currentFocusIndex]) {
            searchItems[currentFocusIndex].classList.add('search-item-focused');
            searchItems[currentFocusIndex].scrollIntoView({ block: 'nearest' });
        }
    }
    
    function performSearch(query) {
        fetch(`${apiPath}global_search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
                hideResults();
            });
    }
    
    function displayResults(data) {
        const content = searchResults.querySelector('.search-results-content');
        content.innerHTML = '';
        searchItems = [];
        currentFocusIndex = -1;
        
        const hasApplications = data.applications && data.applications.length > 0;
        const hasUsers = data.users && data.users.length > 0;
        
        if (!hasApplications && !hasUsers) {
            content.innerHTML = '<div class="search-no-results">No results found</div>';
        } else {
            // Applications section
            if (hasApplications) {
                const appGroup = document.createElement('div');
                appGroup.className = 'search-group';
                appGroup.innerHTML = '<h6 class="search-group-header">Applications</h6>';
                
                data.applications.forEach(app => {
                    const item = document.createElement('button');
                    item.className = 'search-item';
                    item.onclick = () => {
                        window.location.href = `app_view.php?id=${app.id}`;
                    };
                    
                    item.innerHTML = `
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(app.short_description)}</div>
                            <div class="search-item-subtitle">Status: ${escapeHtml(app.status)}</div>
                        </div>
                        <div class="search-item-meta">
                            Updated: ${escapeHtml(app.updated_at)}
                        </div>
                    `;
                    
                    searchItems.push(item);
                    appGroup.appendChild(item);
                });
                
                content.appendChild(appGroup);
            }
            
            // Users section
            if (hasUsers) {
                const userGroup = document.createElement('div');
                userGroup.className = 'search-group';
                userGroup.innerHTML = '<h6 class="search-group-header">Users</h6>';
                
                data.users.forEach(user => {
                    const item = document.createElement('button');
                    item.className = 'search-item user-search-item';
                    item.onclick = () => {
                        // No action for users yet - just prevent default
                        return false;
                    };
                    
                    item.innerHTML = `
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(user.name)}</div>
                            <div class="search-item-subtitle">${escapeHtml(user.email)}</div>
                        </div>
                    `;
                    
                    searchItems.push(item);
                    userGroup.appendChild(item);
                });
                
                content.appendChild(userGroup);
            }
            
            // Auto-focus first item if there are results
            if (searchItems.length > 0) {
                currentFocusIndex = 0;
                searchItems[0].classList.add('search-item-focused');
            }
        }
        
        showResults();
    }
    
    function showResults() {
        searchResults.style.display = 'block';
    }
    
    function hideResults() {
        searchResults.style.display = 'none';
        currentFocusIndex = -1;
        searchItems = [];
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
