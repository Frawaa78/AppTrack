// Choices.js Initialization Module

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

// Initialize Choices.js for Assigned To (single select with user search)
function initializeAssignedToChoice() {
  const assignedToSelect = document.getElementById('assignedTo');
  if (!assignedToSelect) {
    console.error('assignedTo element not found');
    return null;
  }
  
  console.log('Initializing Choices.js for Assigned to...');
  
  try {
    const assignedToChoices = new Choices(assignedToSelect, {
      removeItemButton: false,
      placeholder: true,
      placeholderValue: 'Søk etter brukere...',
      searchEnabled: true,
      searchChoices: false,
      searchFloor: 2,
      searchResultLimit: 10,
      renderChoiceLimit: -1,
      shouldSort: false,
      allowHTML: true,
      callbackOnCreateTemplates: function (template) {
        return {
          item: (classNames, data) => {
            // For selected items, use only the value (just the name)
            const displayText = data.value || data.label || '';
            
            return template(`
              <div class="${classNames.item} ${
              data.highlighted
                ? classNames.highlightedState
                : classNames.itemSelectable
              } ${
              data.placeholder ? classNames.placeholder : ''
            }" data-item data-id="${data.id}" data-value="${data.value}" ${
              data.active ? 'aria-selected="true"' : ''
            } ${data.disabled ? 'aria-disabled="true"' : ''}>
              ${displayText}
            </div>
            `);
          },
          choice: (classNames, data) => {
            // For dropdown choices, show full info (name + role + email)
            return template(`
              <div class="${classNames.item} ${classNames.itemChoice} ${
              data.disabled ? classNames.itemDisabled : classNames.itemSelectable
            }" data-select-text="${this.config.itemSelectText}" data-choice ${
              data.disabled
                ? 'data-choice-disabled aria-disabled="true"'
                : 'data-choice-selectable'
            } data-id="${data.id}" data-value="${data.value}" ${
              data.groupId > 0 ? 'role="treeitem"' : 'role="option"'
            }>
              ${data.label}
            </div>
            `);
          }
        };
      }
    });

    // Clear search results after selection
    assignedToSelect.addEventListener('choice', function(e) {
      console.log('User selected:', e.detail);
      assignedToChoices.clearChoices();
    });

    // Search functionality for users
    let userSearchTimeout;
    assignedToSelect.addEventListener('search', function(e) {
      const query = e.detail.value;
      console.log('User search query:', query);
      
      if (query.length < 2) {
        assignedToChoices.clearChoices();
        return;
      }

      clearTimeout(userSearchTimeout);
      userSearchTimeout = setTimeout(() => {
        const url = `api/search_users.php?q=${encodeURIComponent(query)}`;
        
        console.log('Fetching users from:', url);
        
        fetch(url)
          .then(response => {
            console.log('User search response status:', response.status);
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
          })
          .then(data => {
            console.log('User search results received:', data);
            if (data.error) {
              console.error('User API Error:', data);
              return;
            }
            
            // Get currently selected value to exclude from results  
            const selectedValue = assignedToChoices.getValue(true);
            console.log('Currently selected user value:', selectedValue);
            
            // Filter out the currently selected user from search results
            const filteredData = data.filter(item => {
              const isFiltered = selectedValue && item.value === selectedValue;
              if (isFiltered) {
                console.log('Filtering out selected user:', item.label);
              }
              return !isFiltered;
            });
            
            console.log('Filtered user search results:', filteredData);
            
            assignedToChoices.clearChoices();
            assignedToChoices.setChoices(filteredData, 'value', 'label', true);
            
            // Apply hover effects specifically for "Assigned to" field
            applyAssignedToHoverEffects();
          })
          .catch(error => {
            console.error('User search error:', error);
            assignedToChoices.clearChoices();
            assignedToChoices.setChoices([{
              value: '',
              label: 'Feil ved søking: ' + error.message,
              disabled: true
            }], 'value', 'label', true);
          });
      }, 300);
    });
    
    console.log('Choices.js for Assigned to initialized successfully');
    return assignedToChoices;
    
  } catch (error) {
    console.error('Error initializing Choices.js for Assigned to:', error);
    return null;
  }
}

// Apply hover effects specifically for "Assigned to" field
function applyAssignedToHoverEffects() {
  setTimeout(() => {
    console.log('Applying hover effects for Assigned to field...');
    
    // Find the Choices.js container for the assignedTo select
    const assignedToElement = document.getElementById('assignedTo');
    const assignedToChoicesContainer = assignedToElement ? assignedToElement.closest('.choices') : null;
    
    if (assignedToChoicesContainer) {
      console.log('Found assigned to choices container');
      
      // Style the dropdown container
      const dropdown = assignedToChoicesContainer.querySelector('.choices__list--dropdown');
      if (dropdown) {
        console.log('Styling dropdown container');
        dropdown.style.cssText = `
          padding: 12px 10px !important;
          background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%) !important;
          border: 2px solid #e0e0e0 !important;
          border-radius: 12px !important;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        `;
      }
      
      // Apply hover effects to all choice items
      const choiceItems = assignedToChoicesContainer.querySelectorAll('.choices__item--choice');
      console.log(`Found ${choiceItems.length} choice items to style`);
      
      choiceItems.forEach((item, itemIndex) => {
        console.log(`Setting up hover for item ${itemIndex}:`, item.textContent.substring(0, 50));
        
        // Set base styles immediately
        item.style.cssText = `
          padding: 18px 22px !important;
          margin: 8px 12px !important;
          border-radius: 12px !important;
          min-height: 68px !important;
          background: white !important;
          border-bottom: none !important;
          transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
          cursor: pointer !important;
        `;
        
        // Remove any existing hover handlers
        if (item._customHoverEnter) {
          item.removeEventListener('mouseenter', item._customHoverEnter);
          item.removeEventListener('mouseleave', item._customHoverLeave);
        }
        
        // Define and attach new hover handlers
        item._customHoverEnter = function(e) {
          console.log('🎯 HOVER ENTER - Item:', this.textContent.substring(0, 30));
          
          this.style.cssText = `
            padding: 18px 22px !important;
            margin: 8px 8px !important;
            border-radius: 14px !important;
            min-height: 68px !important;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
            color: #1565c0 !important;
            border-left: 5px solid #2196f3 !important;
            padding-left: 19px !important;
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3) !important;
            transform: translateX(6px) scale(1.03) !important;
            border-bottom: none !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
          `;
          
          // Style the text elements inside
          const strong = this.querySelector('strong');
          const small = this.querySelector('small');
          if (strong) {
            strong.style.cssText = `
              color: #0d47a1 !important;
              font-weight: 700 !important;
              font-size: 1rem !important;
            `;
          }
          if (small) {
            small.style.cssText = `
              color: #1976d2 !important;
              font-weight: 500 !important;
            `;
          }
          
          console.log('✅ Hover styles applied successfully');
        };
        
        item._customHoverLeave = function(e) {
          console.log('🎯 HOVER LEAVE - Item:', this.textContent.substring(0, 30));
          
          this.style.cssText = `
            padding: 18px 22px !important;
            margin: 8px 12px !important;
            border-radius: 12px !important;
            min-height: 68px !important;
            background: white !important;
            color: inherit !important;
            border-left: none !important;
            padding-left: 22px !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
            transform: none !important;
            border-bottom: none !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
          `;
          
          // Reset text elements
          const strong = this.querySelector('strong');
          const small = this.querySelector('small');
          if (strong) strong.style.cssText = '';
          if (small) small.style.cssText = '';
          
          console.log('✅ Hover reset completed');
        };
        
        // Attach the event listeners
        item.addEventListener('mouseenter', item._customHoverEnter, { passive: true });
        item.addEventListener('mouseleave', item._customHoverLeave, { passive: true });
      });
    } else {
      console.error('Could not find assigned to choices container');
    }
  }, 150);
}

// Initialize all Choices.js instances
function initializeAllChoices() {
  if (typeof Choices === 'undefined') {
    console.error('Choices.js is not loaded');
    return {
      relationshipChoices: null,
      assignedToChoices: null
    };
  }
  
  return {
    relationshipChoices: initializeRelatedApplicationsChoice(),
    assignedToChoices: initializeAssignedToChoice()
  };
}
