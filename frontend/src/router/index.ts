import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import type { RouteMeta } from '@/types/api.types'
import { authService } from '@/services/auth.service'

// Import views (lazy loading for better performance)
const LoginView = () => import('@/views/auth/LoginView.vue')
const DashboardView = () => import('@/views/dashboard/DashboardView.vue')
const PatientsListView = () => import('@/views/patients/PatientsListView.vue')

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

  // Main application routes (protected)
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
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

// Global navigation guards
router.beforeEach(async (to, from, next) => {
  const requiresAuth = to.meta?.requiresAuth !== false
  const requiredRoles = to.meta?.roles as string[] || []
  
  // Set page title
  if (to.meta?.title) {
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
  console.log(`Navigated to: ${to.name} (${to.path})`)
})

export default router
