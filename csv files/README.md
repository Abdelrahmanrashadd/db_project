# CSV Import Files

This folder contains sample CSV files for testing the email import functionality.

## 📁 Available Files

### 1. `sample_import_basic.csv`
- **Purpose**: Basic test file with standard format
- **Records**: 12 email subscriptions
- **Format**: Complete (email, first_name, last_name, status, source)
- **Status**: All active
- **Best for**: Testing basic import functionality

### 2. `sample_import_mixed_status.csv`
- **Purpose**: Test file with different subscription statuses
- **Records**: 10 email subscriptions
- **Format**: Complete with mixed statuses
- **Status**: Active, unsubscribed, and bounced
- **Best for**: Testing status handling and filtering

### 3. `sample_import_large.csv`
- **Purpose**: Larger test file for bulk import testing
- **Records**: 25 email subscriptions
- **Format**: Complete with various sources
- **Status**: All active
- **Best for**: Testing bulk import and performance

### 4. `sample_import_minimal.csv`
- **Purpose**: Minimal format with only email addresses
- **Records**: 10 email subscriptions
- **Format**: Email only (required field)
- **Status**: Will default to 'active'
- **Best for**: Testing minimal CSV format and defaults

## 📋 CSV Format Requirements

The import system accepts CSV files with the following columns (in order):

1. **email** (REQUIRED) - Valid email address
2. **first_name** (OPTIONAL) - Subscriber's first name
3. **last_name** (OPTIONAL) - Subscriber's last name
4. **status** (OPTIONAL) - Subscription status:
   - `active` - Active subscription
   - `unsubscribed` - Unsubscribed
   - `bounced` - Email bounced
   - Default: `active` if not specified
5. **source** (OPTIONAL) - Source of subscription:
   - `website` - From website signup
   - `newsletter` - From newsletter
   - `campaign` - From marketing campaign
   - `manual` - Manual entry
   - `import` - Default if not specified

## 🚀 How to Use

1. **Go to Import Page**: Navigate to `import.php` in your browser
2. **Choose a CSV file**: Select one of the sample files from this folder
3. **Configure Options**:
   - Check "Skip duplicates" to avoid updating existing emails
   - Uncheck to update existing emails with new data
4. **Upload**: Click "Import CSV File"
5. **Review Results**: Check the success/error messages

## ✅ Example CSV Format

```csv
email,first_name,last_name,status,source
john.doe@example.com,John,Doe,active,website
jane.smith@example.com,Jane,Smith,active,newsletter
bob@example.com,Bob,Johnson,active,campaign
```

Or minimal format:
```csv
email
user1@example.com
user2@example.com
```

## 📝 Notes

- **Header Row**: The first row can contain headers (automatically detected)
- **Comma Separated**: Values must be separated by commas
- **Quotes**: Use quotes if values contain commas (e.g., "Smith, John")
- **Encoding**: Files should be UTF-8 encoded for best results
- **File Size**: Recommended maximum 10MB for smooth processing

## 🧪 Testing Scenarios

1. **Basic Import**: Use `sample_import_basic.csv`
2. **Status Testing**: Use `sample_import_mixed_status.csv`
3. **Bulk Import**: Use `sample_import_large.csv`
4. **Minimal Format**: Use `sample_import_minimal.csv`
5. **Duplicate Testing**: Import the same file twice with "Skip duplicates" checked
6. **Update Testing**: Import the same file twice without "Skip duplicates" to update records

## ⚠️ Important

- Make sure email addresses are valid
- Invalid emails will be skipped with error messages
- Duplicate emails are handled based on your selection
- Large files may take time to process

---

**Happy Testing! 📧**

