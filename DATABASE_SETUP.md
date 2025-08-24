# Medical Clinic Management - Database Setup Guide

## 🚀 Quick Start Instructions

### 1. Install Dependencies
```bash
cd backend
composer install
```

### 2. Database Setup
Create a MySQL database named `medical_clinic`:
```bash
mysql -u root -p
CREATE DATABASE medical_clinic;
exit
```

### 3. Configure Environment
The `.env` file is already created with default settings. Update the database credentials if needed:
```bash
# Edit backend/.env if your MySQL settings are different
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### 4. Run Migrations
```bash
# From the backend directory
composer migrate
```

### 5. Seed Sample Data
```bash
composer seed
```

### 6. Start Development Server
```bash
composer start
```

### 7. Test the API
Open your browser or use curl to test:
```bash
curl http://localhost:8000/api/health
```

---

## 📊 Sample Login Credentials

After seeding, you can use these credentials to test the system:

| Role | Username | Password | Email |
|------|----------|----------|-------|
| Admin | admin | admin123 | admin@clinic.com |
| Doctor | dr.smith | doctor123 | smith@clinic.com |
| Doctor | dr.johnson | doctor123 | johnson@clinic.com |
| Nurse | nurse.williams | nurse123 | williams@clinic.com |
| Receptionist | reception | reception123 | reception@clinic.com |

---

## 🗄️ Database Schema Overview

### Core Tables Created:
1. **users** - System users (staff) with role-based access
2. **doctors** - Doctor-specific information and schedules
3. **patients** - Patient records and contact information
4. **appointments** - Appointment scheduling with conflict detection
5. **questionnaires** - Dynamic health questionnaires with branching logic
6. **questionnaire_responses** - Patient questionnaire submissions
7. **reminders** - Automated email/SMS reminder system
8. **inventory** - Medical equipment and supply tracking
9. **bills** - Patient billing and payment tracking

### Sample Data Included:
- ✅ 5 staff users (admin, 2 doctors, 1 nurse, 1 receptionist)
- ✅ 2 doctors with specialties (Cardiology, Pediatrics)
- ✅ 3 sample patients
- ✅ 2 sample questionnaires with branching logic
- ✅ 3 inventory items (medication, equipment, supplies)

---

## 🧪 Testing the Smart Calendar

### Test Available Slots Endpoint:
```bash
# Get available slots for Dr. Smith (doctor_id=1) on today's date
curl "http://localhost:8000/api/appointments/available-slots?doctor_id=1&date=2024-01-15&duration=30"
```

### Test Patient Management:
```bash
# Get all patients
curl http://localhost:8000/api/patients

# Get specific patient
curl http://localhost:8000/api/patients/1
```

---

## 🔧 Migration Commands

| Command | Description |
|---------|-------------|
| `composer migrate` | Run all pending migrations |
| `composer rollback` | Rollback last migration |
| `composer seed` | Populate database with sample data |

### Advanced Migration Commands:
```bash
# Rollback multiple migrations
php database/migrate.php rollback 3

# Check migration status
php database/migrate.php status  # (to be implemented)
```

---

## 🚨 Troubleshooting

### Common Issues:

**1. Database Connection Error**
- Check MySQL is running
- Verify database credentials in `.env`
- Ensure `medical_clinic` database exists

**2. Permission Denied**
- Make sure migration files are executable
- Check file ownership and permissions

**3. Class Not Found**
- Run `composer install` to ensure autoloading is set up
- Check PSR-4 namespace mapping

**4. Migration Already Exists**
- Migrations track what's been run in the `migrations` table
- Use rollback if you need to re-run migrations

### Reset Everything:
```bash
# Drop database and recreate (if needed)
mysql -u root -p -e "DROP DATABASE medical_clinic; CREATE DATABASE medical_clinic;"

# Re-run migrations and seeding
composer migrate
composer seed
```

---

## ✅ Next Steps

1. **Test API Endpoints** - Verify all controllers work correctly
2. **Implement JWT Authentication** - Complete the auth system
3. **Build Email/SMS Services** - Add PHPMailer and Twilio integration
4. **Create Vue.js Frontend** - Build the user interface
5. **Add Advanced Features** - Reports, analytics, file uploads

Your database is now ready for development! 🎉
