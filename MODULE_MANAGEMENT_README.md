# Module Management System - Documentation

## Overview
This system provides complete CRUD (Create, Read, Update, Delete) functionality for managing security training modules through an admin interface.

## Features

### 1. Module Management API (`modules.php`)
RESTful API endpoints for module operations:

- **List Modules**: `GET/POST modules.php?action=list`
  - Returns all active modules from database
  - Response format: JSON
  
- **Add Module**: `POST modules.php?action=add`
  - Required fields: title, category
  - Optional fields: description, duration, image, page
  
- **Update Module**: `POST modules.php?action=update`
  - Required: id, title
  - Optional: description, category, duration, image, page, active
  
- **Delete Module**: `POST modules.php?action=delete`
  - Required: id

### 2. Admin Dashboard (`admin_dashboard.php`)
Complete module management interface with:
- **Event Delegation**: Uses `addEventListener` on parent container instead of inline `onclick` handlers
- **Dynamic Loading**: Modules loaded via fetch API with JSON responses
- **Modal Forms**: Add/Edit modules through modal dialogs
- **Status Toggle**: Enable/disable modules without page reload
- **Delete Confirmation**: Modal confirmation before deletion

### 3. Module Display (`all_modules.php`)
Public-facing module catalog:
- Displays all active modules in card format
- Consistent styling with existing modules (Phishing, Ransomware, etc.)
- Shows: title, category, duration, description, image
- Admin badges and actions for authorized users

## Technical Implementation

### Event Delegation Pattern
Instead of using `onclick` attributes, the system uses event delegation:

```javascript
moduleList.addEventListener('click', function(e) {
    const target = e.target;
    const moduleItem = target.closest('.module-item');
    
    if (!moduleItem) return;
    
    const moduleId = moduleItem.dataset.moduleId;
    
    // Handle different button clicks
    if (target.classList.contains('btn-edit') || target.closest('.btn-edit')) {
        e.preventDefault();
        editModule(moduleId);
    }
    // ... other handlers
});
```

**Benefits:**
- Works with dynamically added elements
- Better performance (single listener vs many)
- Cleaner separation of concerns
- Easier maintenance

### JSON/Stringify Usage
All API communication uses JSON format:

```javascript
// Fetch modules
fetch('modules.php?action=list')
    .then(response => response.json())
    .then(function(data) {
        if (data.success) {
            renderModules(data.modules);
        }
    });

// Send form data (URL-encoded, similar to JSON.stringify for forms)
const formData = {
    title: document.getElementById('moduleTitle').value,
    category: document.getElementById('moduleCategory').value,
    // ... other fields
};

fetch('modules.php?action=add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: Object.keys(formData).map(function(key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(formData[key]);
    }).join('&')
});
```

### Database Structure
Modules table (`secura_modules.modules`):
- `id` (int, primary key, auto_increment)
- `title` (varchar) - Module name
- `category` (varchar) - Security category
- `duration` (int) - Duration in minutes
- `description` (text) - Detailed description
- `image` (varchar) - URL to module image
- `page` (varchar) - Associated HTML page
- `active` (tinyint) - Status (1=active, 0=inactive)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## Module Display Format

Modules are displayed in cards matching the existing style:

```html
<div class="cyber-card text-center">
    <img src="[module.image]" alt="[module.title]" 
         style="width: 240px; height: 150px; border-radius: 16px;">
    <h5 class="mt-4 mb-3">[module.title]</h5>
    <p class="text-muted mb-4">[module.description]</p>
    <a href="pages/[module.page]" class="btn btn-primary">
        <i class="bi bi-play-circle me-2"></i> Accéder au cours
    </a>
</div>
```

## Usage Examples

### Adding a New Module
1. Navigate to Admin Dashboard
2. Click "Nouveau Module" button
3. Fill in the form:
   - Title (required)
   - Category (required)
   - Duration (optional, default 30)
   - Image URL (optional)
   - Page filename (optional)
   - Description (optional)
   - Active status (default: checked)
4. Click "Enregistrer"

### Editing a Module
1. Find module in the list
2. Click "Modifier" button
3. Update fields in modal
4. Click "Enregistrer"

### Deleting a Module
1. Find module in the list
2. Click "Supprimer" button
3. Confirm in modal dialog
4. Module is removed (soft delete via active flag)

### Toggling Module Status
1. Find module in the list
2. Click "Activer" or "Désactiver" button
3. Status updates immediately

## Security Features

1. **Admin Authentication**: All API endpoints verify admin role
2. **Session Validation**: Checks `$_SESSION['user_role'] === 'admin'`
3. **Input Sanitization**: Uses `htmlspecialchars()` for output
4. **XSS Prevention**: `escapeHtml()` function in JavaScript
5. **SQL Injection Protection**: PDO prepared statements
6. **CSRF Protection**: Session-based authentication

## API Response Format

All API responses use JSON:

```json
// Success
{
    "success": true,
    "message": "Module added successfully",
    "id": 5,
    "modules": [...]
}

// Error
{
    "success": false,
    "error": "Title and category are required"
}
```

## File Structure

```
Secura/
├── modules.php              # Module management API
├── admin_dashboard.php      # Admin interface with module management
├── all_modules.php          # Public module catalog
├── config.php               # Database configuration
├── pages/                   # Module HTML pages
│   ├── phishing.html
│   ├── ransomware.html
│   ├── passwords.html
│   └── cloud.html
└── css/
    ├── style.css
    └── cyberaware.css
```

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires JavaScript enabled
- Fetch API support (or polyfill for older browsers)

## Notes

- The system uses event delegation instead of `onclick` attributes for better performance and maintainability
- JSON is used for all API communication
- Module cards match the existing design system
- Admin features are only visible to users with 'admin' role
- Soft delete via `active` flag preserves data integrity
