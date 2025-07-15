// App View Page JavaScript

// View-specific functionality for readonly forms
function initializeViewMode() {
  console.log('Initializing view mode components...');
  
  // Initialize handover tooltip for view mode
  const slider = document.querySelector('input[type="range"][name="handover_status"]');
  if (slider) {
    // Initialize progress CSS property
    const value = parseInt(slider.value);
    const progress = ((value - slider.min) / (slider.max - slider.min)) * 100;
    slider.style.setProperty('--progress', `${progress}%`);
    
    updateHandoverTooltip(slider);
  }
  
  // Initialize readonly Choices.js for related applications
  initializeReadonlyChoices();
  
  // Force URL text truncation
  initializeUrlTruncation();
}

// Force text truncation for URL links
function initializeUrlTruncation() {
  const urlLinks = document.querySelectorAll('a.form-control[href]');
  console.log(`Found ${urlLinks.length} URL links to truncate`);
  
  urlLinks.forEach(link => {
    // Force CSS properties for text truncation
    link.style.setProperty('white-space', 'nowrap', 'important');
    link.style.setProperty('overflow', 'hidden', 'important');
    link.style.setProperty('text-overflow', 'ellipsis', 'important');
    link.style.setProperty('display', 'block', 'important');
    link.style.setProperty('max-width', '100%', 'important');
    link.style.setProperty('width', '100%', 'important');
    link.style.setProperty('box-sizing', 'border-box', 'important');
    
    // Ensure icon positioning
    const icon = link.querySelector('i.bi-box-arrow-up-right');
    if (icon) {
      icon.style.setProperty('position', 'absolute', 'important');
      icon.style.setProperty('right', '0.75rem', 'important');
      icon.style.setProperty('top', '50%', 'important');
      icon.style.setProperty('transform', 'translateY(-50%)', 'important');
      icon.style.setProperty('pointer-events', 'none', 'important');
    }
    
    console.log('Applied truncation styles to:', link.href);
  });
}

// Initialize Choices.js for readonly mode
function initializeReadonlyChoices() {
  const relationshipSelect = document.getElementById('relationshipYggdrasil');
  if (relationshipSelect) {
    console.log('Initializing readonly Choices.js for Related applications...');
    
    try {
      const choices = new Choices(relationshipSelect, {
        removeItemButton: false,
        placeholder: false,
        placeholderValue: '',
        shouldSort: false,
        searchEnabled: false,
        itemSelectText: '',
        renderChoiceLimit: -1,
        allowHTML: true,
        duplicateItemsAllowed: false,
        addItemFilter: null, // Prevent adding new items
        editItems: false,
        maxItemCount: -1,
        silent: false
      });
      
      // Disable the choices instance completely
      choices.disable();
      
      console.log('Readonly Choices.js initialized successfully');
    } catch (error) {
      console.error('Error initializing readonly Choices.js:', error);
    }
  }
}

// DOM Content Loaded event handler
document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM loaded, initializing app view components...');
  
  // Small delay to ensure CSS is fully loaded
  setTimeout(() => {
    // Initialize view mode components
    initializeViewMode();
    
    console.log('App view initialization complete');
  }, 100);
});
