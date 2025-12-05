# Quick Setup Guide - Email List Subscription Project

## 🚀 Fast Setup (5 Minutes)

### Prerequisites
- XAMPP installed and running
- Web browser (Chrome, Firefox, Safari, Edge)

### Step-by-Step Setup

#### 1. Place Project Files
```
Copy project folder to: C:\xampp\htdocs\Email Adham
```

#### 2. Start Services
1. Open **XAMPP Control Panel**
2. Start **Apache**
3. Start **MySQL**

#### 3. Create Database
1. Open browser: `http://localhost/phpmyadmin`
2. Click **"New"** in left sidebar
3. Database name: `email_subscription_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click **"Create"**

#### 4. Import Schema
1. Click on `email_subscription_db` database
2. Click **"Import"** tab
3. Click **"Choose File"**
4. Select: `database/schema.sql`
5. Click **"Go"** at bottom
6. ✅ Success! You should see 3 tables created

#### 5. Verify Configuration
Open `config/database.php` and verify:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Default XAMPP
define('DB_PASS', '');          // Default XAMPP (empty)
define('DB_NAME', 'email_subscription_db');
```

If you changed MySQL password, update it here.

#### 6. Access Application
Open browser: `http://localhost/Email%20Adham/`

✅ **You should see the dashboard!**

---

## 🧪 Quick Test

1. **Dashboard**: Should show statistics
2. **Departments**: Click "Manage Departments" - should see 4 departments
3. **Employees**: Click "Manage Employees" - should see 12 employees
4. **Subscriptions**: Click "Email Subscriptions" - should see 5 subscriptions

---

## 📊 Generate ER Diagram

### Option 1: Online (Easiest)
1. Go to: http://www.plantuml.com/plantuml/uml/
2. Open file: `er-diagrams/database_er_diagram.puml`
3. Copy all content
4. Paste into online editor
5. Diagram appears automatically
6. Click "Download PNG" or "Download SVG"

### Option 2: VS Code
1. Install "PlantUML" extension in VS Code
2. Open `er-diagrams/database_er_diagram.puml`
3. Press `Alt+D` to preview
4. Right-click → Export Diagram

---

## 🔧 Troubleshooting

### Database Connection Error
**Problem**: "Database Connection Error" on dashboard

**Solution**:
1. Check MySQL is running in XAMPP
2. Verify database `email_subscription_db` exists
3. Check `config/database.php` credentials
4. Try: Open phpMyAdmin → Select database → Verify tables exist

### Page Not Loading
**Problem**: 404 Error or blank page

**Solution**:
1. Verify Apache is running
2. Check URL: `http://localhost/Email%20Adham/` (note the space as %20)
3. Verify files are in correct folder
4. Check PHP error logs in XAMPP

### Import Not Working
**Problem**: CSV import fails

**Solution**:
1. Check file is CSV format
2. Verify file size < 10MB
3. Check CSV has email column first
4. Enable error reporting in PHP to see specific error

### Charts Not Showing
**Problem**: Blank charts on reports page

**Solution**:
1. Check internet connection (Chart.js from CDN)
2. Open browser console (F12) → Check for JavaScript errors
3. Verify data exists in database
4. Try refreshing page

---

## 📝 Default Credentials

### Database (phpMyAdmin)
- **Username**: `root`
- **Password**: `` (empty by default)
- **Host**: `localhost`

---

## 🎯 What's Included

✅ Database schema with sample data  
✅ Complete CRUD operations  
✅ Professional web interface  
✅ Export to CSV/Excel  
✅ Import from CSV (bonus)  
✅ Reports & Analytics  
✅ ER Diagram in PlantUML  
✅ Full documentation  

---

## 📚 Next Steps

1. **Explore the Interface**: Navigate through all pages
2. **Add Test Data**: Try creating departments, employees, subscriptions
3. **Test Export**: Export email list to CSV
4. **Test Import**: Import a CSV file
5. **View Reports**: Check analytics dashboard
6. **Generate ER Diagram**: Follow instructions above

---

## 🆘 Need Help?

1. Check `README.md` for detailed documentation
2. Check `PROJECT_DOCUMENTATION.md` for comprehensive guide
3. Review `database/crud_operations.sql` for SQL examples
4. Check PHP error logs in XAMPP for debugging

---

**Ready to go! Start managing your email lists! 📧**

