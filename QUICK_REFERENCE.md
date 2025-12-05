# Quick Reference Card

## 🔗 Navigation URLs

- **Dashboard**: `http://localhost/Email%20Adham/`
- **Departments**: `http://localhost/Email%20Adham/departments.php`
- **Employees**: `http://localhost/Email%20Adham/employees.php`
- **Subscriptions**: `http://localhost/Email%20Adham/subscriptions.php`
- **Export**: `http://localhost/Email%20Adham/export.php`
- **Import**: `http://localhost/Email%20Adham/import.php`
- **Reports**: `http://localhost/Email%20Adham/reports.php`

## 📊 Database Tables

| Table | Description | Key Fields |
|-------|-------------|------------|
| `departments` | Department information | department_id, department_name |
| `employees` | Employee records | employee_id, email, department_id |
| `email_subscriptions` | Newsletter emails | subscription_id, email, status |

## 🔑 Business Rules

- ✅ Each department has **at least 1 supervisor**
- ✅ Each department has **exactly 1 head**
- ✅ All emails stored in **one table** (email_subscriptions)
- ❌ Cannot delete department with employees
- ✅ Email addresses must be unique

## 📁 Important Files

- `database/schema.sql` - Database schema and sample data
- `config/database.php` - Database configuration
- `er-diagrams/database_er_diagram.puml` - ER diagram source
- `database/sample_import.csv` - Sample CSV for import testing

## 🎨 Status Colors

- 🟢 **Active** - Green badge
- 🔴 **Unsubscribed** - Red badge
- 🟡 **Bounced** - Orange badge
- 🔵 **Employee** - Blue badge
- 🟡 **Supervisor** - Orange badge
- 🟢 **Head** - Green badge

## ⌨️ Keyboard Shortcuts

- `Ctrl+S` - Save (in forms)
- `Esc` - Close modal dialogs
- `Enter` - Submit forms

## 🐛 Common SQL Queries

```sql
-- Count active subscriptions
SELECT COUNT(*) FROM email_subscriptions WHERE status = 'active';

-- Employees by department
SELECT d.department_name, COUNT(e.employee_id) 
FROM departments d 
LEFT JOIN employees e ON d.department_id = e.department_id 
GROUP BY d.department_id;

-- Recent subscriptions
SELECT * FROM email_subscriptions 
ORDER BY subscribed_at DESC 
LIMIT 10;
```

## 📧 CSV Import Format

```csv
email,first_name,last_name,status,source
user@example.com,First,Last,active,website
```

**Required**: Email (column 1)  
**Optional**: First Name, Last Name, Status, Source

## 🎯 Quick Actions

1. **Add Department**: Dashboard → Departments → Add Department
2. **Add Employee**: Dashboard → Employees → Add Employee
3. **Add Subscription**: Dashboard → Subscriptions → Add Subscription
4. **Export List**: Dashboard → Export to CSV/Excel
5. **Import List**: Dashboard → Import from CSV
6. **View Reports**: Dashboard → Reports & Analytics

## 🔒 Default Settings

- Database: `email_subscription_db`
- Username: `root`
- Password: `` (empty)
- Host: `localhost`
- Port: `3306` (default MySQL)

## 📞 Support Files

- `README.md` - Project overview
- `SETUP_GUIDE.md` - Installation instructions
- `PROJECT_DOCUMENTATION.md` - Complete documentation
- `QUICK_REFERENCE.md` - This file

---

**Last Updated**: December 2024

