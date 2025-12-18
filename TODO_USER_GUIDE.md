# Todo Management System - User Guide & Training Documentation

## 📋 Table of Contents
- [System Overview](#system-overview)
- [Getting Started](#getting-started)
- [System Configuration](#system-configuration)
- [Task Management](#task-management)
- [Advanced Features](#advanced-features)
- [User Roles & Permissions](#user-roles--permissions)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)

---

## 🎯 System Overview

The Todo Management System is a comprehensive task tracking and workflow management solution designed for organizational task coordination. It provides a structured approach to managing tasks across different departments, branches, and locations with built-in priority management, status tracking, and notification systems.

### Key Features
- **Multi-level Task Organization**: Categories, priorities, and statuses
- **Location-based Management**: Branch and department-specific task assignment
- **Real-time Notifications**: Automated alerts for task updates and comments
- **Flexible Views**: Card and table view options for different work styles
- **Comment System**: Threaded discussions on tasks with action steps
- **Advanced Filtering**: Filter tasks by branch, department, status, and more
- **Mobile Responsive**: Optimized for desktop and mobile devices

### System Architecture
- **Frontend**: Laravel Livewire with Alpine.js for reactive components
- **Backend**: Laravel 10.x with Eloquent ORM
- **Database**: MySQL with structured relationships
- **UI Framework**: Tailwind CSS with responsive design
- **Authentication**: Laravel Sanctum for secure access

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer
- Node.js and npm
- Web server (Apache/Nginx)

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd OrderDemo
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Configuration**
   - Configure your database credentials in `.env`
   - Run migrations:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build Assets**
   ```bash
   npm run build
   ```

6. **Start the Application**
   ```bash
   php artisan serve
   ```

### First Login
- Access the application at `http://localhost:8000`
- Use the default admin credentials or register a new account
- Navigate to the Todo section from the main menu

---

## ⚙️ System Configuration

The system requires initial configuration before regular use. Access the Configuration page at `/todo/config`.

### 1. Todo Categories
**Purpose**: Group tasks by type (e.g., "Maintenance", "Development", "Customer Service")

**How to Configure**:
1. Navigate to Configuration → Todo Categories
2. Click "Add New Category"
3. Enter category name and optional description
4. Save the category

**Best Practices**:
- Use clear, descriptive names
- Keep categories to 5-10 maximum for simplicity
- Categories should represent major work types

### 2. Todo Priorities
**Purpose**: Define urgency levels for tasks

**Configuration**:
1. Go to Configuration → Todo Priorities
2. Add priority levels (e.g., "Critical", "High", "Medium", "Low")
3. Assign rank numbers (lower numbers = higher priority)

**Priority Guidelines**:
- **Critical (Rank 1)**: System down, safety issues, legal deadlines
- **High (Rank 2)**: Customer impacting, urgent business needs
- **Medium (Rank 3)**: Important but not urgent
- **Low (Rank 4)**: Nice to have, future improvements

### 3. Todo Statuses
**Purpose**: Track task progress through defined workflow stages

**Setup Process**:
1. Access Configuration → Todo Statuses
2. Create statuses like: "Open", "In Progress", "Review", "Completed"
3. Assign color codes for visual identification

**Recommended Status Flow**:
```
Open → In Progress → Review → Completed
    ↓
  On Hold
```

### 4. Locations
**Purpose**: Define physical or logical locations for task assignment

**Configuration**:
1. Go to Configuration → Locations
2. Add location names and addresses
3. Locations represent major sites or regions

### 5. Branches
**Purpose**: Define organizational branches or business units

**Setup**:
1. Navigate to Configuration → Branches
2. Add branch names (e.g., "Main Office", "Warehouse", "Retail Store")
3. Branches represent operational units

### 6. Departments
**Purpose**: Define functional departments within branches

**Configuration**:
1. Access Configuration → Departments
2. Link departments to specific locations
3. Examples: "IT", "HR", "Finance", "Operations"

### 7. Todo Due Times
**Purpose**: Pre-configure common task types with default durations and priorities

**Setup**:
1. Go to Configuration → Todo Due Times
2. Link to categories and priorities
3. Set default durations (in hours)
4. Add descriptions for common task types

---

## 📝 Task Management

### Accessing Task Management
Navigate to `/todo/list` or click "Task List" in the navigation menu.

### Creating New Tasks

#### Method 1: Using Pre-configured Due Times
1. Click "Add New Todo Task" to expand the form
2. Select a job title from the dropdown (pre-configured due times)
3. The system auto-fills priority, duration, and due date
4. Select requesting branch, location, and department
5. Optionally assign to a specific user
6. Add detailed task description
7. Click "Add Task"

#### Method 2: Custom Task Creation
1. Follow steps 1-7 above but manually set all parameters
2. Useful for one-off or unique tasks

### Task Views

#### Card View (Default)
- Visual representation of tasks
- Shows key information at a glance
- Compact layout with 3 cards per row on large screens
- Includes task details, priority, due date, and actions

#### Table View
- Spreadsheet-style layout
- Good for bulk operations
- Shows all task information in columns
- Toggle between views using the "Card"/"Table" buttons

### Task Actions

#### For Active Tasks
- **Close Task**: Mark as completed (moves to status selection)
- **Comments**: Add discussions and action items
- **Archive**: Move to archived status (for completed tasks)

#### For Tasks with Status
- **Archive**: Move completed tasks to archive

### Filtering and Sorting

#### Available Filters
- **Branch**: Filter by requesting branch
- **Department**: Filter by department
- **Status**: Filter by current status
- **Daily Tasks**: Show only tasks due today

#### Sorting Options
- **Due Date**: Sort by deadline (default)
- **Created Date**: Sort by creation time
- **Priority**: Sort by priority level

### Task Details Display
Each task card shows:
- Job title and priority level
- Duration estimate
- Current status with color coding
- Due date and time
- Assigned user (or "Not Assigned")
- Created by user
- Location and department
- Task description
- Comment count

---

## 🔧 Advanced Features

### Comment System

#### Adding Comments
1. Click the "Comments" button on any task
2. Add your comment in the text area
3. Optionally create action steps
4. Submit the comment

#### Action Steps
- Create specific, actionable items within comments
- Assign to team members
- Track completion status
- Link to parent tasks

#### Threaded Discussions
- Reply to specific comments
- Maintain conversation context
- Automatic notifications for mentions

### Notification System

#### Types of Notifications
- **Task Assignment**: When assigned to a task
- **Comment Added**: New comments on your tasks
- **Status Changes**: Task status updates
- **Due Date Approaching**: Reminders for upcoming deadlines

#### Managing Notifications
1. Access via "Notifications" in the navigation
2. View unread notifications
3. Mark as read or delete
4. Click to navigate to related tasks

### Task History and Audit
- All task changes are tracked
- Comment history maintained
- Status change logs
- User action tracking

---

## 👥 User Roles & Permissions

### User Types

#### Administrators
- Full system access
- Can configure all system settings
- Manage users and permissions
- Access to all tasks and reports

#### Managers
- Create and assign tasks
- View all department tasks
- Access to reports and analytics
- Approve task completions

#### Team Members
- View assigned tasks
- Update task status
- Add comments and action steps
- Limited to own department tasks

### Permission Matrix

| Feature | Admin | Manager | Team Member |
|---------|-------|---------|-------------|
| Create Tasks | ✅ | ✅ | ❌ |
| Edit Tasks | ✅ | ✅ | Own tasks only |
| Delete Tasks | ✅ | ✅ | ❌ |
| View All Tasks | ✅ | ✅ | Department only |
| Configure System | ✅ | ❌ | ❌ |
| Manage Users | ✅ | ❌ | ❌ |

---

## 💡 Best Practices

### Task Creation
1. **Use Pre-configured Due Times**: Leverage existing templates for consistency
2. **Be Specific**: Clear, actionable task descriptions
3. **Set Realistic Deadlines**: Consider task complexity and resource availability
4. **Assign Appropriately**: Match skills and availability

### Task Management
1. **Regular Updates**: Keep task status current
2. **Use Comments**: Document progress and decisions
3. **Create Action Steps**: Break down complex tasks
4. **Review Daily**: Check for overdue or approaching deadlines

### Communication
1. **Clear Instructions**: Provide context and requirements
2. **Regular Check-ins**: For long-running tasks
3. **Document Decisions**: Use comments for important choices
4. **Acknowledge Completion**: Confirm when tasks are done

### System Maintenance
1. **Regular Cleanup**: Archive completed tasks monthly
2. **Update Configurations**: Keep categories and priorities current
3. **User Training**: Ensure team understands the system
4. **Monitor Performance**: Review completion rates and bottlenecks

---

## 🔍 Troubleshooting

### Common Issues

#### "Page Not Loading"
- Check internet connection
- Clear browser cache
- Try incognito/private browsing mode
- Check if server is running

#### "Cannot Create Tasks"
- Verify you have appropriate permissions
- Check if all required fields are filled
- Ensure system configuration is complete

#### "Filters Not Working"
- Clear all filters and try again
- Check if data exists for selected filters
- Refresh the page

#### "Notifications Not Appearing"
- Check notification settings
- Verify email configuration if applicable
- Clear browser cache

### Performance Issues
- Use filters to limit displayed tasks
- Switch to table view for large datasets
- Archive old completed tasks regularly

### Data Issues
- Contact administrator for data corrections
- Use comments to document data issues
- Avoid direct database modifications

---

## ❓ FAQ

### General Questions

**Q: How do I reset my password?**
A: Contact your system administrator or use the password reset feature if enabled.

**Q: Can I export task data?**
A: Currently, data export is not available. Contact your administrator for custom reports.

**Q: How do I delete a task?**
A: Tasks cannot be deleted once created. Use "Archive" for completed tasks instead.

**Q: Can I assign tasks to multiple people?**
A: Currently, tasks can only be assigned to one person. Use comments to coordinate with multiple team members.

### Task Management

**Q: What happens when a task is overdue?**
A: Overdue tasks remain visible but are highlighted. The system doesn't automatically escalate tasks.

**Q: Can I change a task's due date after creation?**
A: Due dates can be modified by users with appropriate permissions. Contact your manager or administrator.

**Q: How do I know if someone commented on my task?**
A: You'll receive notifications. Check the "Notifications" section regularly.

### Configuration

**Q: Who can modify system configuration?**
A: Only administrators can modify system settings. Contact your system administrator for changes.

**Q: How often should I update the configuration?**
A: Review configuration quarterly or when organizational changes occur.

**Q: Can I add custom fields to tasks?**
A: Custom fields are not currently supported. Use the task description and comments for additional information.

### Technical Support

**Q: Who do I contact for technical issues?**
A: Contact your IT department or system administrator.

**Q: How do I report bugs?**
A: Use the comment system to document issues, or contact your administrator.

**Q: Is the system mobile-friendly?**
A: Yes, the system is fully responsive and works on mobile devices.

---

## 📞 Support & Training

### Getting Help
1. Check this documentation first
2. Use the in-system help features
3. Contact your department supervisor
4. Reach out to IT support for technical issues

### Training Resources
- This user guide
- System walkthrough videos (if available)
- Department-specific procedures
- Regular training sessions

### Feedback
To suggest improvements or report issues:
1. Use the comment system on relevant tasks
2. Contact your system administrator
3. Provide specific details about the issue or suggestion

---

*This documentation is maintained by the system administrators. Please report any errors or suggestions for improvement.*