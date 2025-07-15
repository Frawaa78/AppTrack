// Choices.js Initialization Module

// Helper function to capitalize first letter
function ucfirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

// Initialize Choices.js for Related Applications (multiple select)
function initializeRelatedApplicationsChoice() {
  const relationshipSelect = document.getElementById('relationshipYggdrasil');
  if (!relationshipSelect) {
    console.error('relationshipYggdrasil element not found');
    return null;
  }
  
  console.log('Initializing Choices.js for Related applications...');
  
  try {
    const relationshipChoices = new Choices(relationshipSelect, {
      removeItemButton: true,
      placeholder: true,
      placeholderValue: 'Search for applications...',
      searchEnabled: true,
      searchChoices: false,
      searchFloor: 2,
      searchResultLimit: 20,
      renderChoiceLimit: -1,
      shouldSort: false
    });

    // Clear search results after selection
    relationshipSelect.addEventListener('choice', function(e) {
      console.log('Choice selected:', e.detail);
      relationshipChoices.clearChoices();
    });

    // Search functionality
    let searchTimeout;
    relationshipSelect.addEventListener('search', function(e) {
      const query = e.detail.value;
      console.log('Search query:', query);
      
      if (query.length < 2) {
        relationshipChoices.clearChoices();
        return;
      }

      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        const currentAppId = window.currentAppId || 0;
        
        const selectedValues = relationshipChoices.getValue(true);
        const selectedIds = selectedValues.length > 0 ? selectedValues.join(',') : '';
        
        let url = `api/search_applications.php?q=${encodeURIComponent(query)}&exclude=${currentAppId}`;
        if (selectedIds) {
          url += `&selected=${encodeURIComponent(selectedIds)}`;
        }
        
        console.log('Fetching from:', url);
        
        fetch(url)
          .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
          })
          .then(data => {
            console.log('Search results:', data);
            if (data.error) {
              console.error('API Error:', data);
              return;
            }
            relationshipChoices.clearChoices();
            relationshipChoices.setChoices(data, 'value', 'label', true);
          })
          .catch(error => {
            console.error('Search error:', error);
          });
      }, 300);
    });
    
    console.log('Choices.js for Related applications initialized successfully');
    return relationshipChoices;
  } catch (error) {
    console.error('Error initializing Choices.js for Related applications:', error);
    return null;
  }
}

// Initialize Bootstrap Dropdown for Assigned To (replacement for Choices.js)
function initializeAssignedToDropdown() {
  const dropdown = document.getElementById('assignedToDropdown');
  const searchInput = document.getElementById('userSearchInput');
  const resultsContainer = document.getElementById('userDropdownResults');
  const hiddenInput = document.getElementById('assignedToValue');
  const displaySpan = document.getElementById('assignedToDisplay');
  
  if (!dropdown || !searchInput || !resultsContainer || !hiddenInput || !displaySpan) {
    console.error('Assigned To dropdown elements not found');
    return null;
  }
  
  console.log('Initializing Bootstrap dropdown for Assigned to...');
  
  let searchTimeout;
  
  // Handle search input
  searchInput.addEventListener('input', function(e) {
    const query = e.target.value.trim();
    
    if (query.length < 2) {
      resultsContainer.innerHTML = `
        <div class="p-3 text-muted text-center">
          <small>Type at least 2 letters to search</small>
        </div>
      `;
      return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      searchUsers(query);
    }, 300);
  });
  
  // Search users function
  function searchUsers(query) {
    const url = `api/search_users.php?q=${encodeURIComponent(query)}`;
    
    console.log('Searching users with query:', query);
    
    // Show loading state
    resultsContainer.innerHTML = `
      <div class="p-3 text-muted text-center">
        <small>Searching...</small>
      </div>
    `;
    
    fetch(url)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('User search results:', data);
        
        if (data.error) {
          resultsContainer.innerHTML = `
            <div class="p-3 text-danger text-center">
              <small>Error: ${data.error}</small>
            </div>
          `;
          return;
        }
        
        if (data.length === 0) {
          resultsContainer.innerHTML = `
            <div class="p-3 text-muted text-center">
              <small>No users found</small>
            </div>
          `;
          return;
        }
        
        // Filter out currently selected user
        const currentValue = hiddenInput.value;
        const filteredUsers = data.filter(user => user.value !== currentValue);
        
        if (filteredUsers.length === 0) {
          resultsContainer.innerHTML = `
            <div class="p-3 text-muted text-center">
              <small>No other users found</small>
            </div>
          `;
          return;
        }
        
        // Build results HTML
        let resultsHTML = '';
        filteredUsers.forEach(user => {
          // Extract the display name (everything before the first <br> if it exists)
          const displayName = user.label.split('<br>')[0];
          
          // Build subtitle from customProperties
          let subtitle = '';
          if (user.customProperties) {
            const role = user.customProperties.role;
            const email = user.customProperties.email;
            const phone = user.customProperties.phone;
            
            if (role) {
              subtitle += ucfirst(role);
            }
            if (email) {
              subtitle += (subtitle ? ' • ' : '') + email;
            }
            if (phone) {
              subtitle += (subtitle ? ' • ' : '') + phone;
            }
          }
          
          resultsHTML += `
            <a href="#" class="dropdown-item user-option" data-value="${user.value}" data-label="${displayName}">
              <div class="user-name">${displayName}</div>
              ${subtitle ? `<div class="user-details">${subtitle}</div>` : ''}
            </a>
          `;
        });
        
        resultsContainer.innerHTML = resultsHTML;
        
        // Add click handlers to user options
        resultsContainer.querySelectorAll('.user-option').forEach(option => {
          option.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const value = this.getAttribute('data-value');
            const label = this.getAttribute('data-label');
            
            // Update hidden input and display
            hiddenInput.value = value;
            displaySpan.textContent = value;
            
            // Clear search
            searchInput.value = '';
            
            // Close dropdown
            const dropdownInstance = bootstrap.Dropdown.getOrCreateInstance(dropdown);
            dropdownInstance.hide();
            
            console.log('User selected:', { value, label });
          });
        });
      })
      .catch(error => {
        console.error('User search error:', error);
        resultsContainer.innerHTML = `
          <div class="p-3 text-danger text-center">
            <small>Search error</small>
          </div>
        `;
      });
  }
  
  // Clear search when dropdown opens
  dropdown.addEventListener('show.bs.dropdown', function() {
    searchInput.value = '';
    resultsContainer.innerHTML = `
      <div class="p-3 text-muted text-center">
        <small>Type at least 2 letters to search</small>
      </div>
    `;
    
    // Focus search input when dropdown opens
    setTimeout(() => {
      searchInput.focus();
    }, 100);
  });
  
  // Add clear option if user is selected
  if (hiddenInput.value) {
    dropdown.addEventListener('show.bs.dropdown', function() {
      const clearOption = `
        <div class="border-bottom">
          <a href="#" class="dropdown-item text-danger clear-selection">
            <i class="bi bi-x-circle me-2"></i>Clear selection
          </a>
        </div>
      `;
      
      resultsContainer.innerHTML = clearOption + `
        <div class="p-3 text-muted text-center">
          <small>Type at least 2 letters to search</small>
        </div>
      `;
      
      // Add clear handler
      resultsContainer.querySelector('.clear-selection')?.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        hiddenInput.value = '';
        displaySpan.textContent = 'Select user...';
        
        const dropdownInstance = bootstrap.Dropdown.getOrCreateInstance(dropdown);
        dropdownInstance.hide();
        
        console.log('Selection cleared');
      });
    });
  }
  
  console.log('Bootstrap dropdown for Assigned to initialized successfully');
  return { dropdown, searchInput, resultsContainer };
}

// Initialize all dropdown instances
function initializeAllChoices() {
  return {
    relationshipChoices: initializeRelatedApplicationsChoice(),
    assignedToDropdown: initializeAssignedToDropdown()
  };
}
