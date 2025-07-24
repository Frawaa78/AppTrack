# User Stories Module Installation Guide

## 📋 Installation Checklist

### ✅ Completed Implementation

1. **Database Structure** ✅
   - `user_stories` table with all required fields
   - `user_story_attachments` table for future file support
   - Foreign key relationships to `applications` and `users`
   - Proper indexes for performance

2. **Backend Models & Controllers** ✅
   - `UserStory.php` model with full CRUD operations
   - `UserStoryController.php` with validation and business logic
   - Integration with existing `ActivityManager` for audit logging

3. **API Endpoints** ✅
   - `GET /api/user_stories/get_stories.php` - List with filtering
   - `GET /api/user_stories/get_story.php` - Single story details
   - `POST /api/user_stories/create_story.php` - Create new story
   - `PUT /api/user_stories/update_story.php` - Update existing story
   - `DELETE /api/user_stories/delete_story.php` - Delete story
   - `GET /api/user_stories/get_form_options.php` - Dropdown options
   - `GET /api/user_stories/get_stories_by_app.php` - Stories for application

4. **Frontend Pages** ✅
   - `user_stories.php` - Main dashboard with filtering and stats
   - `user_story_form.php` - Create/edit form with validation
   - `user_story_view.php` - Detailed story view
   - Responsive design consistent with AppTrack styling

5. **JavaScript Components** ✅
   - `user-stories.js` - Dashboard functionality with real-time filtering
   - `user-story-form.js` - Form handling with preview and validation
   - Integration with `app-view.js` for application-specific stories

6. **Navigation Integration** ✅
   - Added "User Stories" to main navigation in `topbar.php`
   - User Stories section in `app_view.php` for application context
   - Cross-linking between applications and stories

7. **CSS Styling** ✅
   - Complete styling in `user-stories.css`
   - Priority and status badges
   - Responsive design
   - Consistent with existing AppTrack design system

## 🚀 Installation Steps

### Step 1: Database Setup
Run the SQL script to create the necessary tables:

```sql
-- Execute the contents of docs/database-updates.sql
-- This will create user_stories and user_story_attachments tables
-- Also includes sample data for testing
```

### Step 2: File Structure Verification
Ensure all files are in place:

```
public/
├── user_stories.php              ✅ Main dashboard
├── user_story_form.php           ✅ Create/edit form  
├── user_story_view.php           ✅ Detail view
├── api/user_stories/             ✅ API endpoints folder
│   ├── get_stories.php           ✅
│   ├── get_story.php             ✅
│   ├── create_story.php          ✅
│   ├── update_story.php          ✅
│   ├── delete_story.php          ✅
│   ├── get_form_options.php      ✅
│   └── get_stories_by_app.php    ✅
└── assets/
    ├── css/pages/user-stories.css ✅
    └── js/pages/
        ├── user-stories.js        ✅
        └── user-story-form.js     ✅

src/
├── models/UserStory.php          ✅
└── controllers/UserStoryController.php ✅
```

### Step 3: Test the Installation

1. **Access the User Stories Dashboard**
   - Navigate to `/public/user_stories.php`
   - Should see statistics cards and sample stories
   - Test filtering and search functionality

2. **Create a New Story**
   - Click "New Story" button
   - Fill out the form with test data
   - Verify preview updates in real-time
   - Submit and check database

3. **Application Integration**
   - Open any application in `/public/app_view.php?id=X`
   - Scroll down to see "User Stories" section
   - Create a story linked to the application
   - Verify it appears in both places

### Step 4: Verify Navigation
- Check that "User Stories" appears in the top navigation
- Verify all links work correctly
- Test responsive design on mobile

## 📊 Features Overview

### Dashboard Features
- **Statistics Cards**: Total stories, status breakdown, priority counts
- **Advanced Filtering**: By application, priority, status, created by
- **Search**: Full-text search across title, role, want_to, so_that
- **"Show Mine Only"**: Filter stories created by current user
- **Responsive Table**: Works on desktop and mobile

### Story Form Features
- **Structured Input**: Guided "As a... I want... So that..." format
- **Real-time Preview**: See story structure as you type
- **Smart Suggestions**: Auto-populate fields based on title keywords
- **Jira Integration**: Basic Jira ID and URL linking (expandable)
- **Rich Metadata**: Priority, status, category, and tags for organization
- **Application Linking**: Connect stories to specific applications

### Story View Features
- **Complete Story Display**: Full story with all metadata
- **External Links**: Direct links to Jira and SharePoint
- **Application Context**: Clear connection to related applications
- **Action Buttons**: Edit, delete with confirmation
- **Responsive Layout**: Sidebar with metadata on desktop

### Application Integration
- **Embedded Stories**: Stories section in application view
- **Create from App**: Direct story creation from application context
- **Bidirectional Linking**: Navigate between apps and stories
- **Activity Logging**: Story creation/updates logged in app activity

## 🔮 Future Enhancements

### Planned for Phase 2
- **File Attachments**: Upload documents, images, mockups to stories
- **Advanced Search**: Full-text search with highlighting
- **Bulk Operations**: Mass update status, priority, assignments
- **Story Dependencies**: Link related stories

### Planned for Phase 3
- **Jira API Integration**: Real-time sync with Jira issues
- **SharePoint Integration**: Import from SharePoint lists
- **Kanban Board**: Drag-and-drop story management
- **Story Templates**: Predefined story structures

### Planned for Phase 4
- **AI Analysis**: Automatic story analysis and suggestions
- **Impact Assessment**: Analyze business value and technical impact
- **Requirements Traceability**: Link stories to implementation
- **Reporting Dashboard**: Business intelligence and insights

## 🎯 Success Metrics

After installation, you should be able to:

1. ✅ **Create Stories**: Both standalone and linked to applications
2. ✅ **Filter & Search**: Find stories quickly with multiple criteria
3. ✅ **Navigate Seamlessly**: Between stories, applications, and forms
4. ✅ **Track Progress**: See status and priority across all stories
5. ✅ **Maintain Context**: Stories connected to their applications
6. ✅ **Scale Usage**: System performs well with many stories

## 🆘 Troubleshooting

### Common Issues

**Database Connection Error**
- Verify database credentials in `src/config/config.php`
- Ensure MySQL user has CREATE TABLE permissions
- Check that `applications` and `users` tables exist

**API Endpoints Not Working** 
- Verify PHP session is working (login first)
- Check file permissions on API files
- Ensure PHP error reporting is enabled for debugging

**JavaScript Errors**
- Check browser console for errors
- Verify Bootstrap and CSS are loaded correctly
- Ensure jQuery/Bootstrap JS is loaded before custom scripts

**Styling Issues**
- Clear browser cache
- Verify CSS files are loaded in correct order
- Check that Bootstrap icons are available

## 📞 Support

For issues with the User Stories module:
1. Check the browser console for JavaScript errors
2. Verify database tables were created correctly
3. Test API endpoints directly with browser dev tools
4. Ensure all required files are in place

The module follows AppTrack's existing patterns and should integrate seamlessly with the current system.
