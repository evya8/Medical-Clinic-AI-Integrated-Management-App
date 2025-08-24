# 🏥 MediCore Clinic - Vue.js Frontend

A modern, minimal, and highly functional Vue.js frontend for the Medical Clinic Management System. Built with Vue 3, TypeScript, Tailwind CSS, and designed for medical professionals.

## ✨ Features

### 🎯 **Phase 1 - Foundation (COMPLETED)**
- ✅ **Authentication System**: Secure login/logout with JWT tokens
- ✅ **Responsive Layout**: Modern sidebar navigation and header
- ✅ **Dashboard**: Welcome screen with metrics and quick actions
- ✅ **Patient Management**: List, search, and filter patients
- ✅ **Design System**: Medical-grade UI components and styling
- ✅ **State Management**: Pinia stores for authentication and notifications
- ✅ **Routing**: Protected routes with role-based access
- ✅ **TypeScript**: Fully typed for better development experience
- ✅ **Notifications**: Toast notifications system

### 🚧 **Phase 2 - Core Medical Features (PLANNED)**
- 📅 **Appointment System**: Scheduling, calendar integration
- 👥 **Patient Details**: Full medical records and history
- 📋 **Forms**: Patient intake and medical forms
- 📊 **Basic Analytics**: Appointment and patient metrics

### 🤖 **Phase 3 - AI Integration (PLANNED)**
- 🧠 **AI Dashboard**: Intelligent insights and briefings
- ⚕️ **AI Triage**: Patient prioritization and risk assessment
- 📝 **AI Summaries**: Automated clinical documentation
- 🚨 **AI Alerts**: Proactive clinical notifications

### ⚙️ **Phase 4 - Advanced Features (PLANNED)**
- 👥 **User Management**: Staff and role administration
- 🔧 **System Settings**: Configuration and customization
- 📈 **Advanced Analytics**: Performance metrics and reports
- 🔌 **Integrations**: Third-party system connections

## 🛠️ Tech Stack

- **Frontend Framework**: Vue 3 with Composition API
- **Language**: TypeScript for type safety
- **Styling**: Tailwind CSS with custom medical design system
- **State Management**: Pinia for reactive state management
- **Routing**: Vue Router 4 with navigation guards
- **HTTP Client**: Axios with interceptors
- **Icons**: Heroicons for consistent iconography
- **Build Tool**: Vite for fast development and building
- **UI Components**: Custom medical-grade components

## 🚀 Quick Start

### Prerequisites
- Node.js 18+ 
- npm or yarn package manager

### Installation

1. **Navigate to the frontend directory**
   ```bash
   cd frontend
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Set up environment variables**
   ```bash
   cp .env.example .env.development
   ```
   
   Edit `.env.development` with your backend API URL:
   ```env
   VITE_API_BASE_URL=http://localhost:8000/api
   ```

4. **Start the development server**
   ```bash
   npm run dev
   ```

5. **Open in browser**
   ```
   http://localhost:5173
   ```

## 🏗️ Project Structure

```
src/
├── components/          # Reusable Vue components
│   ├── common/         # Layout and shared components
│   ├── dashboard/      # Dashboard-specific components
│   ├── patients/       # Patient management components
│   ├── forms/          # Form components
│   └── ai/            # AI feature components (Phase 3)
├── views/              # Page components
│   ├── auth/          # Authentication pages
│   ├── dashboard/     # Dashboard pages
│   ├── patients/      # Patient management pages
│   ├── appointments/  # Appointment pages (Phase 2)
│   ├── ai-features/   # AI feature pages (Phase 3)
│   ├── admin/         # Admin pages (Phase 4)
│   └── error/         # Error pages
├── stores/            # Pinia state management
│   ├── auth.ts        # Authentication store
│   ├── notifications.ts # Notifications store
│   └── patients.ts    # Patient data store (Phase 2)
├── services/          # API services
│   ├── api.ts         # Base API configuration
│   ├── auth.service.ts # Authentication service
│   └── patients.service.ts # Patient API service
├── types/             # TypeScript type definitions
│   └── api.types.ts   # API and data types
├── router/            # Vue Router configuration
│   └── index.ts       # Route definitions and guards
├── assets/            # Static assets
│   └── styles/        # Global styles and design system
└── utils/             # Utility functions
```

## 🎨 Design System

The frontend implements a comprehensive medical-grade design system:

### Color Palette
- **Primary Medical**: Blue tones for primary actions
- **Status Colors**: Green (success), Yellow (warning), Red (error)
- **AI Accents**: Purple tones for AI features
- **Neutral Grays**: Clean, professional backgrounds

### Components
- **Medical Cards**: Clean cards with subtle shadows
- **Medical Buttons**: Primary, secondary, and ghost variants
- **Medical Inputs**: Accessible form controls with focus states
- **Medical Tables**: Responsive data tables with hover effects

### Typography
- **Font**: Inter for clinical readability
- **Scale**: Consistent type scale for hierarchy
- **Weights**: Regular, medium, semibold, bold

## 🔐 Authentication

The app includes a complete authentication system:

### Login System
- Email/password authentication
- JWT token management
- Persistent sessions with localStorage
- Automatic token refresh
- Role-based access control

### Demo Credentials (Development)
```
Admin: admin@clinic.com / password123
Doctor: doctor@clinic.com / password123
```

### Protected Routes
- Automatic redirect to login for unauthenticated users
- Role-based route protection
- Redirect to intended page after login

## 📱 Responsive Design

- **Mobile First**: Designed for mobile and scaled up
- **Breakpoints**: sm (640px), md (768px), lg (1024px), xl (1280px)
- **Adaptive Navigation**: Collapsible sidebar for mobile
- **Touch-Friendly**: Proper touch targets and gestures

## 🧪 Development

### Available Scripts

```bash
# Development server
npm run dev

# Type checking
npm run type-check

# Build for production
npm run build

# Preview production build
npm run preview

# Run tests
npm run test

# Lint and fix
npm run lint
```

### Environment Variables

Create environment files based on `.env.example`:

- `.env.development` - Development settings
- `.env.production` - Production settings

Key variables:
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_NAME="MediCore Clinic"
VITE_ENABLE_AI_FEATURES=true
VITE_SHOW_DEMO_CREDENTIALS=true
```

## 🚀 Deployment

### Build for Production
```bash
npm run build
```

### Deployment Options

1. **Netlify** (Recommended)
   - Connect your GitHub repository
   - Set build command: `npm run build`
   - Set publish directory: `dist`
   - Configure environment variables

2. **GitHub Pages**
   - Use provided GitHub Actions workflow
   - Configure base URL if needed

3. **Traditional Web Server**
   - Upload `dist` folder contents
   - Configure for SPA routing (redirect all routes to index.html)

## 🔧 Configuration

### API Integration
The frontend is configured to work with the PHP backend:
- Base URL: `http://localhost:8000/api`
- JWT authentication
- CORS handled by backend
- Automatic error handling

### Customization
- Colors: Edit `tailwind.config.js`
- Components: Modify files in `src/components/`
- Layout: Update `AppLayout.vue` and related components
- Routing: Configure in `src/router/index.ts`

## 📊 Current Status

### ✅ Implemented (Phase 1)
- Complete authentication system
- Responsive dashboard with metrics
- Patient list with search and filtering
- Modern sidebar navigation
- Toast notifications
- Error handling and loading states
- Responsive design for all screen sizes
- TypeScript integration

### 🏗️ In Progress
Ready for Phase 2 implementation:
- Patient detail pages
- Appointment scheduling system
- Medical forms and records

### 🎯 Next Steps (Phase 2)
1. Implement patient detail views
2. Add appointment scheduling
3. Create medical forms
4. Integrate with backend APIs
5. Add patient records management

## 🤝 Contributing

1. Follow the existing code structure
2. Use TypeScript for all new components
3. Follow the established design system
4. Write meaningful commit messages
5. Test on mobile and desktop

## 🐛 Known Issues

- Placeholder data used for development
- Some routes lead to "coming soon" pages (planned for future phases)
- AI features are placeholders (Phase 3)

## 📚 Documentation

- Vue 3: https://vuejs.org/
- TypeScript: https://www.typescriptlang.org/
- Tailwind CSS: https://tailwindcss.com/
- Pinia: https://pinia.vuejs.org/
- Vue Router: https://router.vuejs.org/

## 📄 License

This project is part of the Medical Clinic Management System. See the main project README for license information.

---

**Ready to revolutionize medical clinic management! 🏥✨**

Built with ❤️ for healthcare professionals.
