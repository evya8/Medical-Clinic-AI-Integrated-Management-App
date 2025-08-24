# 🔔 Email/SMS Reminder Services - Setup & Testing Guide

## 🎉 **Features Overview**

Your Medical Clinic Management System now includes a **professional-grade reminder system**:

### **📧 Email Service (PHPMailer)**
- HTML and text appointment reminders
- Appointment confirmation emails
- Professional templates with clinic branding
- SMTP configuration support
- Test email functionality

### **📱 SMS Service (Twilio)**
- Appointment reminder SMS with emojis
- Appointment confirmation SMS
- Phone number validation
- Message status tracking
- Cost calculation and tracking

### **🤖 Automated Reminder System**
- **24-hour email reminders** before appointments
- **2-hour SMS reminders** before appointments
- **Instant confirmation emails** when appointments are booked
- **Failed retry logic** with configurable attempts
- **Manual reminder sending** for staff
- **Comprehensive statistics** and reporting

---

## ⚙️ **Configuration Setup**

### **1. Email Configuration (PHPMailer)**

Edit your `.env` file with your email provider settings:

```bash
# Gmail Example
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_clinic_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourclinic.com
MAIL_FROM_NAME="Your Clinic Name"
```

**For Gmail:**
1. Enable 2-factor authentication
2. Generate an "App Password" for this application
3. Use the app password in `MAIL_PASSWORD`

### **2. SMS Configuration (Twilio)**

Sign up at [Twilio.com](https://twilio.com) and get your credentials:

```bash
# Twilio Configuration
TWILIO_SID=your_account_sid_here
TWILIO_TOKEN=your_auth_token_here
TWILIO_FROM=+1234567890
```

**Twilio Setup Steps:**
1. Create a free Twilio account ($15 credit included)
2. Get a phone number from Twilio Console
3. Find your Account SID and Auth Token in dashboard
4. Add credentials to `.env`

---

## 🧪 **Testing the Reminder System**

### **Step 1: Test Service Connections**

First, make sure your server is running:
```bash
php -S localhost:8000 -t public
```

**Test Email Service:**
```bash
curl -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  http://localhost:8000/api/reminders/test-email
```

**Test SMS Service:**
```bash
curl -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  http://localhost:8000/api/reminders/test-sms
```

### **Step 2: Send Test Messages**

**Send Test Email:**
```bash
curl -X POST -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"email": "your@email.com", "name": "Test User"}' \\
  http://localhost:8000/api/reminders/test-email
```

**Send Test SMS:**
```bash
curl -X POST -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"phone": "+1234567890", "message": "Test SMS from clinic!"}' \\
  http://localhost:8000/api/reminders/test-sms
```

### **Step 3: Test Appointment Reminders**

**Schedule Reminders for an Appointment:**
```bash
curl -X POST -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"appointment_id": 1}' \\
  http://localhost:8000/api/reminders/schedule
```

**Send Manual Reminder:**
```bash
curl -X POST -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  -H "Content-Type: application/json" \\
  -d '{"appointment_id": 1, "type": "email"}' \\
  http://localhost:8000/api/reminders/send
```

### **Step 4: Process Pending Reminders**

**Manually trigger reminder processing:**
```bash
curl -X POST -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  http://localhost:8000/api/reminders/process
```

---

## 📊 **Monitoring & Statistics**

### **View Reminder Statistics:**
```bash
curl -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  "http://localhost:8000/api/reminders/stats?start_date=2024-08-01&end_date=2024-08-31"
```

### **View Recent Reminders:**
```bash
curl -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \\
  "http://localhost:8000/api/reminders?status=sent&type=email"
```

---

## 🔄 **Automated Processing (Cron Jobs)**

### **Setup Automatic Reminder Processing:**

**1. Make the script executable:**
```bash
chmod +x backend/scripts/process_reminders.php
```

**2. Add to crontab (runs every 10 minutes):**
```bash
crontab -e
```

Add this line:
```bash
*/10 * * * * /usr/bin/php /path/to/your/backend/scripts/process_reminders.php >> /var/log/reminders.log 2>&1
```

**3. Test the cron script manually:**
```bash
php backend/scripts/process_reminders.php
```

---

## 🎯 **API Endpoints Reference**

### **Testing Endpoints:**
- `GET /api/reminders/test-email` - Test email connection
- `GET /api/reminders/test-sms` - Test SMS connection
- `POST /api/reminders/test-email` - Send test email
- `POST /api/reminders/test-sms` - Send test SMS

### **Reminder Management:**
- `GET /api/reminders` - List reminders (with filters)
- `GET /api/reminders/stats` - Get reminder statistics
- `POST /api/reminders/schedule` - Schedule appointment reminders
- `POST /api/reminders/send` - Send manual reminder
- `POST /api/reminders/process` - Process pending reminders
- `DELETE /api/reminders/cancel` - Cancel appointment reminders

### **Utility Endpoints:**
- `GET /api/reminders/validate-phone?phone=+1234567890` - Validate phone number
- `GET /api/reminders/message-status?message_id=SMS123` - Get SMS delivery status

---

## 🛠 **Troubleshooting**

### **Email Issues:**

**"SMTP connection failed"**
- Check MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD in `.env`
- For Gmail, ensure 2FA is enabled and use App Password
- Try different ports: 587 (TLS) or 465 (SSL)

**"Authentication failed"**
- Verify username and password are correct
- For Gmail, ensure "Less secure app access" is disabled (use App Password instead)

### **SMS Issues:**

**"SMS service not configured"**
- Check TWILIO_SID, TWILIO_TOKEN, TWILIO_FROM in `.env`
- Verify Twilio account is active and has credit

**"Invalid phone number format"**
- Phone numbers must be in international format: +1234567890
- Use the validate-phone endpoint to check formatting

### **General Issues:**

**"Authentication required"**
- Get a valid JWT token by logging in first
- Include token in Authorization header: `Bearer YOUR_TOKEN`

**No reminders being sent:**
- Check that cron job is running
- Verify reminder scheduling with `/api/reminders/schedule`
- Process manually with `/api/reminders/process`

---

## 💡 **Best Practices**

### **Production Setup:**
1. **Use environment variables** for all sensitive credentials
2. **Set up proper logging** for email/SMS failures
3. **Monitor Twilio usage** to avoid unexpected charges
4. **Test thoroughly** before going live with patients
5. **Set up monitoring** for failed reminders

### **Cost Management:**
- SMS messages cost approximately $0.0075 per message in US/Canada
- International SMS can cost $0.05+ per message
- Monitor costs via Twilio dashboard
- Set up usage alerts in Twilio console

### **Email Deliverability:**
- Use a proper domain for sending emails
- Set up SPF, DKIM records for your domain
- Monitor spam reports and bounces
- Use a dedicated IP for high volume

---

## 🎉 **You're All Set!**

Your medical clinic now has a **professional-grade reminder system** that can:

✅ **Automatically send** 24-hour email and 2-hour SMS reminders  
✅ **Track delivery status** and costs  
✅ **Handle failures** with retry logic  
✅ **Provide detailed statistics** for monitoring  
✅ **Support manual reminders** for urgent cases  
✅ **Scale to handle** hundreds of appointments  

**Next Steps:**
1. Configure your email and SMS credentials
2. Test the system with your own contact info
3. Set up the cron job for automated processing
4. Train your staff on the manual reminder features
5. Monitor the statistics dashboard for performance

Your patients will love the professional, timely reminders! 🚀
