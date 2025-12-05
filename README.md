# Email List Subscription Project

A comprehensive web-based system for managing email marketing lists, employee data, and departmental structures. Built with PHP, MySQL, and modern web technologies.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Project Structure](#project-structure)
- [Usage](#usage)
- [Screenshots](#screenshots)
- [Documentation](#documentation)
- [Credits](#credits)

## ✨ Features

### Core Features

1. **Department Management**
   - Create, Read, Update, Delete (CRUD) operations
   - Track departments with descriptions
   - View employee count per department

2. **Employee Management**
   - Complete employee CRUD operations
   - Assign employees to departments
   - Mark employees as supervisors
   - Assign head of department (one per department)
   - Track employee hire dates and contact information

3. **Email Subscription Management**
   - Store all newsletter emails in a single table
   - Track subscription status (active, unsubscribed, bounced)
   - Record subscription source (website, newsletter, campaign, etc.)
   - Track subscription metadata (IP address, user agent, timestamps)
   - Search and filter capabilities
   - Pagination for large lists

4. **Export Functionality**
   - Export email list to CSV format
   - Excel-compatible CSV export with UTF-8 support
   - Filter by subscription status before export
   - Ready for import into email marketing platforms

5. **Import Functionality (Bonus)**
   - Import emails from CSV files
   - Automatic duplicate detection
   - Option to skip or update existing emails
   - Error reporting for invalid entries

6. **Reports & Analytics**
   - Subscription statistics dashboard
   - Status distribution charts
   - Monthly subscription trends
   - Subscriptions by source analysis
   - Department employee statistics
   - Recent subscriptions overview

### Technical Features

- Modern, responsive UI design
- Professional aesthetics with gradient backgrounds
- Modal-based forms for better UX
- Real-time statistics and analytics
- Data validation and error handling
- Secure SQL queries with prepared statements
- Chart.js integration for visualizations

## 🔧 Requirements

- **Server**: XAMPP, WAMP, LAMP, or similar
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Web Browser**: Modern browser (Chrome, Firefox, Safari, Edge)
- **Optional**: PlantUML for ER diagram generation

## 📦 Installation

### Step 1: Clone or Download Project

Place the project folder in your web server directory:
- **XAMPP**: `C:\xampp\htdocs\Email Adham`
- **WAMP**: `C:\wamp64\www\Email Adham`
- **LAMP**: `/var/www/html/Email Adham`

### Step 2: Start Web Server

1. Start XAMPP/WAMP Control Panel
2. Start Apache server
3. Start MySQL server

### Step 3: Database Setup

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Create a new database named `email_subscription_db`
3. Select the database
4. Go to the "Import" tab
5. Choose the file `database/schema.sql`
6. Click "Go" to import the schema and sample data

### Step 4: Configure Database Connection

Edit `config/database.php` if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
define('DB_NAME', 'email_subscription_db');
```

### Step 5: Access the Application

Open your web browser and navigate to:
```
http://localhost/Email%20Adham/
```

## 🗄️ Database Setup

The database schema includes:

1. **departments** - Stores department information
2. **employees** - Stores employee data with department relationships
3. **email_subscriptions** - Stores all newsletter email subscriptions

### Schema Rules

✅ Each department has at least 1 supervisor  
✅ Each department has exactly 1 head of department  
✅ All newsletter emails are stored in one table  
✅ Foreign key constraints ensure data integrity

### Sample Data

The schema includes sample data:
- 4 departments (Marketing, Sales, IT, HR)
- 12 employees (with supervisors and heads)
- 5 sample email subscriptions

## 📁 Project Structure

```
Email Adham/
│
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet
│
├── config/
│   └── database.php           # Database configuration
│
├── database/
│   ├── schema.sql             # Database schema and sample data
│   └── crud_operations.sql    # Example CRUD queries
│
├── er-diagrams/
│   ├── database_er_diagram.puml  # ER diagram in PlantUML format
│   └── README.md              # Instructions for generating ER diagrams
│
├── includes/
│   └── functions.php          # Common utility functions
│
├── index.php                  # Dashboard/home page
├── departments.php            # Department management
├── employees.php              # Employee management
├── subscriptions.php          # Email subscription management
├── export.php                 # CSV/Excel export functionality
├── import.php                 # CSV import functionality (bonus)
├── reports.php                # Analytics and reports
│
└── README.md                  # This file
```

## 🚀 Usage

### Dashboard

The main dashboard (`index.php`) provides:
- Quick statistics overview
- Navigation to all features
- Real-time data counts

### Managing Departments

1. Click "Manage Departments" from dashboard
2. Use "Add Department" to create new departments
3. Edit or delete existing departments
4. View employee count per department

### Managing Employees

1. Click "Manage Employees" from dashboard
2. Add new employees with all required information
3. Assign to departments
4. Set supervisor or head of department status
5. Edit or remove employees

### Email Subscriptions

1. Navigate to "Email Subscriptions"
2. Add new subscriptions manually
3. Search and filter by status
4. Bulk unsubscribe selected emails
5. Edit subscription details
6. Delete subscriptions

### Exporting Email Lists

1. Go to "Export to CSV/Excel"
2. Select filter options (all, active only, etc.)
3. Choose export format
4. Download the CSV file
5. Import into your email marketing platform

### Importing Email Lists

1. Navigate to "Import from CSV"
2. Prepare your CSV file with required format:
   - Column 1: Email (required)
   - Column 2: First Name (optional)
   - Column 3: Last Name (optional)
   - Column 4: Status (optional)
   - Column 5: Source (optional)
3. Choose whether to skip duplicates
4. Upload and process the file

### Viewing Reports

1. Access "Reports & Analytics" from dashboard
2. View subscription statistics
3. Analyze trends and distribution
4. Review department statistics

## 📊 ER Diagrams

ER diagrams are provided in PlantUML format. To generate visual diagrams:

1. **Online**: Go to http://www.plantuml.com/plantuml/uml/ and paste the `.puml` file content
2. **VS Code**: Install PlantUML extension and preview
3. **Local**: Install PlantUML and Graphviz, then run `plantuml database_er_diagram.puml`

See `er-diagrams/README.md` for detailed instructions.

## 📝 CRUD Operations

All CRUD operations are available through the web interface. Example SQL queries are provided in `database/crud_operations.sql` for reference and can be executed directly in phpMyAdmin.

## 🎨 Design Features

- **Modern UI**: Clean, professional design with gradient backgrounds
- **Responsive**: Works on desktop, tablet, and mobile devices
- **Interactive**: Modal dialogs for forms, smooth transitions
- **Visual Feedback**: Color-coded badges, status indicators
- **Charts**: Visual analytics with Chart.js
- **Icons**: Font Awesome icons throughout

## 🔒 Security Considerations

- SQL injection protection using `real_escape_string()`
- Input validation on all forms
- Email format validation
- File upload restrictions for imports
- Prepared statements in SQL queries

## 🐛 Troubleshooting

### Database Connection Error

- Ensure MySQL is running
- Check database credentials in `config/database.php`
- Verify database `email_subscription_db` exists
- Import schema from `database/schema.sql`

### Import Not Working

- Check file size (max 10MB recommended)
- Verify CSV format matches requirements
- Ensure PHP upload limits are sufficient
- Check file permissions

### Charts Not Displaying

- Ensure internet connection (Chart.js loaded from CDN)
- Check browser console for JavaScript errors
- Verify data exists in database

## 📄 License

This project is created for educational purposes as part of an assignment submission.

## 👥 Credits

**Project**: Email List Subscription System  
**Submission Date**: December 2024  
**Built With**: PHP, MySQL, HTML, CSS, JavaScript, Chart.js, Font Awesome

---

## 📞 Support

For issues or questions regarding this project, please refer to the documentation or contact the development team.

**Happy Email Marketing! 📧**

