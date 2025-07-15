// Form Handler Functions

// Clear field function
window.clearField = function(fieldId) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.value = '';
    field.focus();
  }
};

// Phase selection handler
window.setPhase = function(value, button) {
  document.getElementById('phase_input').value = value;
  button.parentElement.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');
};

// Status selection handler
window.setStatus = function(value, button) {
  document.getElementById('status_input').value = value;
  button.parentElement.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
  button.classList.add('active');
};

// Toggle S.A. Document field based on Integrations selection
window.toggleSADocument = function(select) {
  const saDoc = document.getElementById('sa_document_group');
  saDoc.style.display = select.value === 'Yes' ? 'flex' : 'none';
};

// Handover status slider and tooltip handler
window.updateHandoverTooltip = function(slider) {
  const tooltip = document.getElementById('handoverTooltip');
  const container = slider.parentElement;
  const value = parseInt(slider.value);
  const tooltipMap = {
    0: '', 
    10: '10% - Early planning started', 
    20: '20% - Stakeholders identified', 
    30: '30% - Key data collected', 
    40: '40% - Requirements being defined', 
    50: '50% - Documentation in progress', 
    60: '60% - Infra/support needs mapped', 
    70: '70% - Ops model drafted', 
    80: '80% - Final review ongoing', 
    90: '90% - Ready for transition', 
    100: 'Completed'
  };
  
  // Update CSS custom property for progress
  const progress = ((value - slider.min) / (slider.max - slider.min)) * 100;
  slider.style.setProperty('--progress', `${progress}%`);
  
  // Calculate position based on slider thumb position
  const sliderRect = slider.getBoundingClientRect();
  const containerRect = container.getBoundingClientRect();
  const thumbPosition = ((value - slider.min) / (slider.max - slider.min)) * slider.offsetWidth;
  
  // Position tooltip relative to container
  tooltip.style.left = `${thumbPosition}px`;
  tooltip.innerText = tooltipMap[value];
  tooltip.style.display = tooltipMap[value] ? 'block' : 'none';
  
  // Update markers
  const markers = container.querySelectorAll('.range-marker');
  markers.forEach((marker, index) => {
    const markerValue = index * 10;
    if (markerValue <= value) {
      marker.classList.add('active');
    } else {
      marker.classList.remove('active');
    }
  });
};

// Initialize handover slider
function initializeHandoverSlider() {
  const slider = document.querySelector('input[type="range"][name="handover_status"]');
  if (slider) {
    // Initialize progress CSS property
    const value = parseInt(slider.value);
    const progress = ((value - slider.min) / (slider.max - slider.min)) * 100;
    slider.style.setProperty('--progress', `${progress}%`);
    
    updateHandoverTooltip(slider);
  }
}

// Initialize Bootstrap popovers
function initializePopovers() {
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
}
