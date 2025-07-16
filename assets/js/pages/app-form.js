// App Form Page JavaScript

// DOM Content Loaded event handler
document.addEventListener('DOMContentLoaded', function () {
  // Set current app ID from PHP (will be set in the page template)
  if (typeof window.currentAppId === 'undefined') {
    window.currentAppId = 0; // Default fallback
  }
  
  // Initialize form components
  initializeHandoverSlider();
  initializePopovers();
  
  // Initialize Choices.js components
  const choicesInstances = initializeAllChoices();
});
