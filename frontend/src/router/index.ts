import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import type { RouteMeta } from '@/types/api.types'
import { authService } from '@/services/auth.service'

// Import views (lazy loading for better performance)
const LoginView = () => import('@/views/auth/LoginView.vue')
const DashboardView = () => import('@/views/dashboard/DashboardView.vue')
const AIDashboardView = () => import('@/views/dashboard/AIDashboardView.vue')
const PatientsListView = () => import('@/views/patients/PatientsListView.vue')
const PatientDetailView = () => import('@/views/patients/detail/PatientDetailView.vue')
const AppointmentsView = () => import('@/views/appointments/AppointmentsView.vue')

// AI Features
const TriageView = () => import('@/views/ai-features/TriageView.vue')
const SummariesView = () => import('@/views/ai-features/SummariesView.vue')
const AlertsView = () => import('@/views/ai-features/AlertsView.vue')

// Reports & Analytics
const AnalyticsView = () => import('@/views/reports/AnalyticsView.vue')

// Admin views
const UsersView = () => import('@/views/admin/UsersView.vue')
const SettingsView = () => import('@/views/admin/SettingsView.vue')

// Define routes with proper typing
const routes: RouteRecordRaw[] = [
  // Default redirect to dashboard
  {
    path: '/',
    redirect: '/dashboard'
  },

  // Authentication routes
  {
    path: '/login',
    name: 'Login',
    component: LoginView,
    meta: {
      requiresAuth: false,
      title: 'Login - MediCore Clinic',
      hideInNav: true,
    } as RouteMeta
  },

  // Main Dashboard routes (protected)
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardView,
    meta: {
      requiresAuth: true,
      title: 'Dashboard - MediCore Clinic',
      icon: 'ChartBarIcon',
    } as RouteMeta
  },

  {
    path: '/ai-dashboard',
    name: 'AIDashboard',
    component: AIDashboardView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor'],
      title: 'AI Dashboard - MediCore Clinic',
      icon: 'CpuChipIcon',
    } as RouteMeta
  },

  // Patients routes
  {
    path: '/patients',
    name: 'Patients',
    component: PatientsListView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor', 'staff'],
      title: 'Patients - MediCore Clinic',
      icon: 'UsersIcon',
    } as RouteMeta
  },

  // Patient Detail route
  {
    path: '/patients/:id',
    name: 'PatientDetail',
    component: PatientDetailView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor', 'staff'],
      title: 'Patient Details - MediCore Clinic',
      hideInNav: true,
    } as RouteMeta
  },

  // Appointments routes
  {
    path: '/appointments',
    name: 'Appointments',
    component: AppointmentsView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor', 'staff'],
      title: 'Appointments - MediCore Clinic',
      icon: 'CalendarIcon',
    } as RouteMeta
  },

  // AI Features routes
  {
    path: '/ai-features',
    redirect: '/ai-dashboard',
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor'],
      title: 'AI Features - MediCore Clinic',
      icon: 'CpuChipIcon',
    } as RouteMeta
  },

  {
    path: '/ai-features/triage',
    name: 'AITriage',
    component: TriageView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor', 'staff'],
      title: 'AI Triage - MediCore Clinic',
      icon: 'HeartIcon',
    } as RouteMeta
  },

  {
    path: '/ai-features/summaries',
    name: 'AISummaries',
    component: SummariesView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor'],
      title: 'AI Summaries - MediCore Clinic',
      icon: 'DocumentTextIcon',
    } as RouteMeta
  },

  {
    path: '/ai-features/alerts',
    name: 'AIAlerts',
    component: AlertsView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor', 'staff'],
      title: 'AI Alerts - MediCore Clinic',
      icon: 'ExclamationTriangleIcon',
    } as RouteMeta
  },

  // Reports & Analytics
  {
    path: '/reports',
    redirect: '/reports/analytics'
  },

  {
    path: '/reports/analytics',
    name: 'Analytics',
    component: AnalyticsView,
    meta: {
      requiresAuth: true,
      roles: ['admin', 'doctor'],
      title: 'Analytics & Reports - MediCore Clinic',
      icon: 'ChartBarIcon',
    } as RouteMeta
  },

  // Admin routes
  {
    path: '/admin',
    redirect: '/admin/users',
    meta: {
      requiresAuth: true,
      roles: ['admin'],
      title: 'Administration - MediCore Clinic',
      icon: 'Cog6ToothIcon',
    } as RouteMeta
  },

  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: UsersView,
    meta: {
      requiresAuth: true,
      roles: ['admin'],
      title: 'User Management - MediCore Clinic',
      icon: 'UsersIcon',
    } as RouteMeta
  },

  {
    path: '/admin/settings',
    name: 'AdminSettings',
    component: SettingsView,
    meta: {
      requiresAuth: true,
      roles: ['admin'],
      title: 'System Settings - MediCore Clinic',
      icon: 'Cog6ToothIcon',
    } as RouteMeta
  },

  // 404 Not Found
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/error/NotFoundView.vue'),
    meta: {
      requiresAuth: false,
      title: 'Page Not Found - MediCore Clinic',
      hideInNav: true,
    } as RouteMeta
  }
]

// Create router instance
const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

// Global navigation guards
router.beforeEach(async (to, _from, next) => {
  const requiresAuth = to.meta?.requiresAuth !== false
  const requiredRoles = to.meta?.roles as string[] || []
  
  // Set page title
  if (to.meta?.title && typeof to.meta.title === 'string') {
    document.title = to.meta.title
  }

  // Check authentication
  if (requiresAuth) {
    const isAuthenticated = authService.isAuthenticated()
    
    if (!isAuthenticated) {
      // Redirect to login page
      next({
        name: 'Login',
        query: { redirect: to.fullPath }
      })
      return
    }

    // Check authorization (roles)
    if (requiredRoles.length > 0) {
      const hasRequiredRole = authService.hasAnyRole(requiredRoles)
      
      if (!hasRequiredRole) {
        // User doesn't have required role - redirect to dashboard or show error
        console.warn(`Access denied. Required roles: ${requiredRoles.join(', ')}`)
        next({ name: 'Dashboard' })
        return
      }
    }
  } else {
    // Route doesn't require auth, but if user is already authenticated and trying to access login,
    // redirect to dashboard
    if (to.name === 'Login' && authService.isAuthenticated()) {
      next({ name: 'Dashboard' })
      return
    }
  }

  next()
})

// Global after guards (for analytics, etc.)
router.afterEach((to) => {
  // Track page views or perform other post-navigation tasks
  console.log(`Navigated to: ${String(to.name) || 'unknown'} (${to.path})`)
})

export default router
