<template>
  <AppLayout>
    <div class="ai-dashboard-container">
      <!-- Page Header -->
      <div class="page-header mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
              <CpuChipIcon class="w-8 h-8 mr-3 text-purple-600" />
              AI Dashboard
            </h1>
            <p class="mt-2 text-sm text-gray-700">
              AI-powered insights and clinical decision support
            </p>
          </div>
        </div>
      </div>

      <!-- Coming Soon Card -->
      <div class="medical-card p-12 text-center">
        <div class="ai-icon-container w-20 h-20 bg-gradient-to-br from-purple-100 to-purple-200 rounded-full flex items-center justify-center mx-auto mb-6">
          <CpuChipIcon class="w-10 h-10 text-purple-600" />
        </div>
        
        <h3 class="text-xl font-semibold text-gray-900 mb-2">
          AI-Powered Clinical Intelligence
        </h3>
        <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
          The AI Dashboard will provide intelligent insights, predictive analytics, and clinical decision support. 
          This feature will be available in Phase 3 of the development roadmap.
        </p>
        
        <!-- AI Features Preview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
          <div class="ai-feature-card p-6 bg-gradient-to-br from-purple-50 to-indigo-50 rounded-lg border border-purple-100">
            <HeartIcon class="w-10 h-10 text-red-500 mx-auto mb-4" />
            <h4 class="font-semibold text-gray-900 mb-2">AI Triage</h4>
            <p class="text-sm text-gray-600">
              Intelligent patient prioritization based on symptoms and risk factors
            </p>
            <div class="mt-3">
              <span class="ai-indicator text-xs">Coming in Phase 3</span>
            </div>
          </div>
          
          <div class="ai-feature-card p-6 bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg border border-blue-100">
            <DocumentTextIcon class="w-10 h-10 text-blue-500 mx-auto mb-4" />
            <h4 class="font-semibold text-gray-900 mb-2">Smart Summaries</h4>
            <p class="text-sm text-gray-600">
              Automated generation of clinical notes and appointment summaries
            </p>
            <div class="mt-3">
              <span class="ai-indicator text-xs">Coming in Phase 3</span>
            </div>
          </div>
          
          <div class="ai-feature-card p-6 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg border border-yellow-100">
            <ExclamationTriangleIcon class="w-10 h-10 text-yellow-500 mx-auto mb-4" />
            <h4 class="font-semibold text-gray-900 mb-2">Clinical Alerts</h4>
            <p class="text-sm text-gray-600">
              Proactive alerts for drug interactions, allergies, and health risks
            </p>
            <div class="mt-3">
              <span class="ai-indicator text-xs">Coming in Phase 3</span>
            </div>
          </div>
          
          <div class="ai-feature-card p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg border border-green-100">
            <ChartBarIcon class="w-10 h-10 text-green-500 mx-auto mb-4" />
            <h4 class="font-semibold text-gray-900 mb-2">Predictive Analytics</h4>
            <p class="text-sm text-gray-600">
              Forecast patient outcomes and optimize clinic operations
            </p>
            <div class="mt-3">
              <span class="ai-indicator text-xs">Coming in Phase 3</span>
            </div>
          </div>
        </div>

        <!-- AI Capabilities List -->
        <div class="mt-12 bg-gray-50 rounded-lg p-8">
          <h4 class="text-lg font-semibold text-gray-900 mb-6">Planned AI Capabilities</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
              <span class="text-sm text-gray-700">Intelligent patient triage and risk assessment</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
              <span class="text-sm text-gray-700">Automated clinical documentation</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
              <span class="text-sm text-gray-700">Drug interaction and allergy alerts</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
              <span class="text-sm text-gray-700">Appointment optimization recommendations</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
              <span class="text-sm text-gray-700">Clinical decision support systems</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
              <span class="text-sm text-gray-700">Population health analytics</span>
            </div>
          </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="mt-8">
          <RouterLink to="/dashboard" class="medical-button-secondary">
            <ArrowLeftIcon class="w-4 h-4 mr-2" />
            Back to Dashboard
          </RouterLink>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import {
  CpuChipIcon,
  HeartIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  ChartBarIcon,
  ArrowLeftIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/components/common/AppLayout.vue'
</script>

<style scoped>
.ai-dashboard-container {
  @apply max-w-7xl mx-auto;
}

.ai-feature-card {
  @apply transition-all duration-300 hover:shadow-lg hover:scale-105;
  @apply relative overflow-hidden;
}

.ai-feature-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.6s ease;
}

.ai-feature-card:hover::before {
  left: 100%;
}

.ai-icon-container {
  @apply relative;
  animation: pulse-glow 3s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
    transform: scale(1);
  }
  50% {
    box-shadow: 0 0 30px rgba(147, 51, 234, 0.5);
    transform: scale(1.05);
  }
}

/* AI indicator styling */
.ai-indicator {
  @apply inline-flex items-center px-2 py-1 rounded-full bg-purple-100 text-purple-700 font-medium;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .grid-cols-4 {
    @apply grid-cols-1;
  }
  
  .grid-cols-2 {
    @apply grid-cols-1;
  }
  
  .page-header {
    @apply text-center;
  }
}

/* Background animation */
.medical-card {
  @apply relative;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.medical-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 20% 50%, rgba(147, 51, 234, 0.03) 0%, transparent 50%),
              radial-gradient(circle at 80% 50%, rgba(59, 130, 246, 0.03) 0%, transparent 50%);
  pointer-events: none;
}

/* Ensure content appears above background */
.medical-card > * {
  position: relative;
  z-index: 1;
}
</style>
