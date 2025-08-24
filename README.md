# Medical Clinic Management System - Backend

A comprehensive PHP/Vue.js medical clinic management system with AI-integrated scheduling, automated reminders, and health questionnaires.

## 🚀 Features

### Main Features
- **AI-Integrated Calendar** - Rule-based smart scheduling with conflict detection
- **Automated Reminders** - Email and SMS notifications via PHPMailer and Twilio
- **Online Health Questionnaires** - Dynamic forms with branching logic

### Additional Features
- **Patient Records Management** - Complete patient profile system ✅
- **Staff/Doctor Scheduling** - Doctor availability and shift management ✅
- **Billing & Payment Tracking** - Invoice generation and payment processing ✅
- **Medical Inventory Management** - Track medications and supplies ✅
- **Reports & Analytics Dashboard** - Generate insights and reports ✅
- **Email/SMS Reminder Services** - Automated appointment reminders with PHPMailer & Twilio ✅

## 🛠 Tech Stack

- **Backend:** PHP 8+ with MVC architecture
- **Database:** MySQL 8.0+
- **Authentication:** JWT tokens with role-based access
- **Email/SMS:** PHPMailer + Twilio API
- **Security:** Password hashing, input validation, basic encryption

## 📁 Project Structure

```
backend/
├── config/           # Configuration files
├── src/
│   ├── Controllers/  # API controllers
│   ├── Models/       # Database models
│   ├── Services/     # Business logic services
│   ├── Middleware/   # Authentication & validation
│   └── Utils/        # Utility classes
├── public/           # Public entry point
├── routes/           # API routing
├── database/         # Migrations and seeds
└── vendor/           # Composer dependencies
```

## ⚡ Quick Start

### 1. Install Dependencies
```bash
cd backend
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
# Edit .env with your database and service credentials
```

### 3. Database Setup
```bash
# Create MySQL database named 'medical_clinic'
mysql -u root -p -e "CREATE DATABASE medical_clinic;"

# Run migrations (coming soon)
composer migrate
```

### 4. Start Development Server
```bash
composer start
# Server will run at http://localhost:8000
```

### 5. Test API
```bash
curl http://localhost:8000/api/health
```

## 🔧 Configuration

### Database Configuration
Edit `.env` file with your MySQL credentials:
```
DB_HOST=localhost
DB_DATABASE=medical_clinic
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### JWT Configuration
```
JWT_SECRET=your_super_secret_jwt_key_here
JWT_EXPIRY=86400
```

### Email Configuration (PHPMailer)
```
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
```

### SMS Configuration (Twilio)
```
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890
```

## 🔒 User Roles & Permissions

- **Admin** - Full system access
- **Doctor** - Patient records, appointments, questionnaires
- **Nurse** - Patient records, appointments, basic functions
- **Receptionist** - Appointments, patient info, billing
- **Pharmacist** - Inventory management

## 📋 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user

### Smart Calendar
- `GET /api/appointments/available-slots` - Get smart suggestions
- `POST /api/appointments` - Create appointment
- `GET /api/appointments` - List appointments
- `PUT /api/appointments/{id}` - Update appointment

### Patients
- `GET /api/patients` - List patients
- `POST /api/patients` - Create patient
- `GET /api/patients/{id}` - Get patient details

### Questionnaires
- `GET /api/questionnaires` - List questionnaires
- `POST /api/questionnaires/{id}/responses` - Submit responses

## 🧪 Testing

```bash
# Run tests (coming soon)
composer test
```

## 🚧 Development Status

### ✅ Completed
- [x] Project structure setup
- [x] Database connection utility  
- [x] Complete database schema with migrations
- [x] JWT authentication system
- [x] Smart scheduling logic with conflict detection
- [x] Patient management endpoints
- [x] Doctor management with specialties
- [x] Role-based access control
- [x] Email/SMS reminder services
- [x] Automated reminder scheduling
- [x] Manual reminder sending
- [x] Reminder statistics and monitoring

### 🔄 In Progress
- [ ] Frontend Vue.js application
- [ ] Advanced reporting features
- [ ] File upload handling

### 📋 TODO
- [ ] Questionnaire branching logic implementation
- [ ] Advanced inventory management
- [ ] Billing system completion
- [ ] API rate limiting
- [ ] Unit tests
- [ ] Production deployment guide

## 🤝 Contributing

This is a personal learning project. Feel free to explore and suggest improvements!

## 📝 License

This project is for educational purposes.
