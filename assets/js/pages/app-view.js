// App View Page JavaScript

console.log('🚀 App-view.js loaded successfully!');

// App View Page JavaScript

console.log('🚀 App-view.js loaded successfully!');

// View-specific functionality for readonly forms
function initializeViewMode() {
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
  
  // Load user stories for this application
  loadUserStories();
}

// Load user stories for the current application
async function loadUserStories() {
  console.log('🔍 loadUserStories called, currentAppId:', window.currentAppId);
  if (!window.currentAppId) {
    console.log('❌ No currentAppId, returning early');
    return;
  }
  
  try {
    console.log('🌐 Fetching:', `api/user_stories/get_stories_by_app.php?application_id=${window.currentAppId}`);
    const response = await fetch(`api/user_stories/get_stories_by_app.php?application_id=${window.currentAppId}`);
    console.log('📡 Response received:', response.status, response.statusText);
    
    if (!response.ok) {
      console.error('❌ Response not OK:', response.status, response.statusText);
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const result = await response.json();
    console.log('📦 API Response:', result);
    
    if (result.success) {
      renderUserStories(result.data);
    } else {
      document.getElementById('userStoriesContainer').innerHTML = 
        '<p class="text-muted">Failed to load user stories</p>';
    }
  } catch (error) {
    console.error('Error loading user stories:', error);
    document.getElementById('userStoriesContainer').innerHTML = 
      '<p class="text-muted">Error loading user stories</p>';
  }
}

// Render user stories in the container
function renderUserStories(stories) {
  console.log('🎨 renderUserStories called with:', stories.length, 'stories');
  const container = document.getElementById('userStoriesContainer');
  console.log('📍 Container element:', container);
  
  if (stories.length === 0) {
    container.innerHTML = `
      <div class="text-center text-muted">
        <i class="bi bi-journal-x" style="font-size: 2rem; opacity: 0.5;"></i>
        <p class="mt-2 mb-0">No user stories found for this application</p>
        <small>Click "New Story" to create the first user story</small>
      </div>
    `;
    return;
  }
  
  container.innerHTML = stories.map(story => `
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div class="flex-grow-1">
            <h6 class="card-title mb-2">
              <a href="user_story_view.php?id=${story.id}" class="text-decoration-none">
                ${escapeHtml(story.title)}
              </a>
              ${story.jira_id ? `<span class="badge bg-primary ms-2">${escapeHtml(story.jira_id)}</span>` : ''}
            </h6>
            <div class="user-story-preview-small">
              <p class="mb-1"><strong>As a</strong> ${escapeHtml(story.role)}</p>
              <p class="mb-1"><strong>I want to</strong> ${truncateText(story.want_to, 80)}</p>
              <p class="mb-2"><strong>So that</strong> ${truncateText(story.so_that, 80)}</p>
            </div>
            <div class="d-flex gap-2">
              <span class="badge priority-badge priority-${story.priority.toLowerCase()}">${story.priority}</span>
              <span class="badge status-badge status-${story.status}">${formatStatus(story.status)}</span>
            </div>
          </div>
          <div class="ms-3">
            <a href="user_story_view.php?id=${story.id}" class="btn btn-outline-primary btn-sm" title="View Story">
              <i class="bi bi-eye"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}

// Utility functions for user stories
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function truncateText(text, maxLength) {
  if (!text) return '';
  if (text.length <= maxLength) return escapeHtml(text);
  return escapeHtml(text.substring(0, maxLength)) + '...';
}

function formatStatus(status) {
  const statusMap = {
    'backlog': 'Backlog',
    'in_progress': 'In Progress',
    'review': 'Review',
    'done': 'Done',
    'cancelled': 'Cancelled'
  };
  return statusMap[status] || status;
}

// Force text truncation for URL links
function initializeUrlTruncation() {
  const urlLinks = document.querySelectorAll('a.form-control[href]');
  
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
  });
}

// Initialize Choices.js for readonly mode
function initializeReadonlyChoices() {
  const relationshipSelect = document.getElementById('relationshipYggdrasil');
  if (relationshipSelect) {
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
    } catch (error) {
      console.error('Error initializing readonly Choices.js:', error);
    }
  }
}

// DOM Content Loaded event handler
document.addEventListener('DOMContentLoaded', function () {
  // Small delay to ensure CSS is fully loaded
  setTimeout(() => {
    // Initialize view mode components
    initializeViewMode();
  }, 100);
});
