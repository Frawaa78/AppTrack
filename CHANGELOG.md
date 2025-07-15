# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-07-15

### Major UI/UX Redesign
This release represents a complete overhaul of the form interface, focusing on space efficiency, visual consistency, and improved user experience.

### Added
- **Horizontal Form Layout**: Complete redesign from vertical to horizontal layout
  - Labels positioned to the left of inputs for space efficiency
  - Fixed-width labels (160px) with right alignment for perfect visual consistency
  - Reduced spacing between labels and inputs by 50%
- **Enhanced Handover Status Slider**:
  - 11 visual markers showing progression from 0% to 100% (10% increments)
  - Dynamic marker highlighting with real-time progress feedback
  - Centered tooltip positioning over slider thumb with precise calculations
  - Active markers highlighted in blue, inactive in gray
- **Clear Buttons for URL Fields**:
  - X buttons added to Information Space, BA Sharepoint list, Link to Corporator, and S.A. Document fields
  - Improved user experience for quickly clearing field contents
- **Enhanced Related Applications Search**:
  - Choices.js integration with database search functionality
  - Real-time search with 2-character minimum
  - Formatted dropdown results with application names and services
  - Proper handling of selected applications

### Changed
- **Form Styling Overhaul**:
  - Removed blue glow effects from all form elements
  - Implemented static labels replacing floating labels
  - Consistent border styling (#dee2e6) across all form controls
  - White backgrounds for all form elements
- **Cross-Page Consistency**:
  - app_form.php and app_view.php now have identical layouts
  - Header with buttons positioned consistently on both pages
  - Same styling applied to read-only and editable forms
- **JavaScript Improvements**:
  - Fixed Phase/Status button functionality with proper event.preventDefault()
  - Window-scoped functions for reliable onclick handlers
  - Enhanced error handling with try/catch blocks
  - Improved tooltip positioning calculations

### Fixed
- **Form Functionality Issues**:
  - Phase and Status buttons now work correctly with proper event handling
  - Clear field buttons function reliably across all URL fields
  - Related applications search functionality restored
  - Tooltip centering over slider thumb fixed
- **Visual Consistency**:
  - All form fields now have uniform spacing and alignment
  - Labels properly aligned across different field types
  - Button groups maintain consistent height (38px)
  - Range input properly integrated with horizontal layout

### Technical Details
- Updated CSS with flexbox-based horizontal form layout
- Enhanced JavaScript functions for better reliability
- Improved form field structure for consistency
- Added visual markers and tooltip positioning logic

## [1.5.0] - 2025-01-15

### Added
- Enhanced read-only view with optimized styling
- Complete database schema with all required columns
- Form validation and error handling

### Changed
- Improved floating label positioning
- Bootstrap customization for cleaner label appearance

### Fixed
- Label movement issues in form fields
- Transparent overlapping elements

## [1.0.0] - 2024-12-01

### Added
- Initial release with core functionality
- User authentication system
- Database-driven forms
- Application CRUD operations
- Responsive Bootstrap UI
- Basic search functionality

### Security
- Password hashing implementation
- Prepared statements for database queries
- Input validation and sanitization
- Session-based authentication
