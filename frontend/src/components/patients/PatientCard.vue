<template>
  <RouterLink
    :to="`/patients/${patient.id}`"
    class="patient-card group relative medical-card p-6 cursor-pointer transition-all duration-200 hover:shadow-md hover:border-primary-200 block"
  >
    <!-- Patient Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center">
        <!-- Patient Avatar -->
        <div class="flex-shrink-0 w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mr-4">
          <span class="text-lg font-medium text-primary-700">
            {{ patientInitials }}
          </span>
        </div>
        
        <!-- Patient Name & ID -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900">
            {{ patientFullName }}
          </h3>
          <p class="text-sm text-gray-500">
            ID: {{ patient.id.toString().padStart(4, '0') }}
          </p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
        <button
          class="p-2 text-gray-400 hover:text-primary-600 rounded-full hover:bg-primary-50"
          @click.prevent.stop="handleEdit"
          title="Edit patient"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Patient Info Grid -->
    <div class="space-y-3">
      <!-- Contact Info -->
      <div class="flex items-center text-sm text-gray-600">
        <EnvelopeIcon class="w-4 h-4 mr-2 text-gray-400" />
        <span class="truncate">{{ patient.email || 'No email' }}</span>
      </div>
      
      <div class="flex items-center text-sm text-gray-600">
        <PhoneIcon class="w-4 h-4 mr-2 text-gray-400" />
        <span>{{ patient.phone || 'No phone' }}</span>
      </div>

      <!-- Demographics -->
      <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100">
        <div>
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Age</span>
          <div class="text-sm font-medium text-gray-900">{{ patientAge }}</div>
        </div>
        <div>
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</span>
          <div class="text-sm font-medium text-gray-900">{{ patientGender }}</div>
        </div>
      </div>
    </div>

    <!-- Medical Alerts -->
    <div v-if="patient.allergies && patient.allergies !== 'None known'" class="mt-4 pt-3 border-t border-gray-100">
      <div class="flex items-center">
        <ExclamationTriangleIcon class="w-4 h-4 text-yellow-500 mr-2" />
        <span class="text-sm font-medium text-yellow-700">Allergies:</span>
      </div>
      <p class="text-sm text-gray-600 mt-1 truncate">{{ patient.allergies }}</p>
    </div>

    <!-- Last Visit (Mock data) -->
    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
      <div>
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</span>
        <div class="text-sm text-gray-700">{{ lastVisitDate }}</div>
      </div>
      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
        Active
      </span>
    </div>
  </RouterLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { differenceInYears, format } from 'date-fns'
import {
  PencilIcon,
  EnvelopeIcon,
  PhoneIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import type { Patient, MedicalHistoryEntry } from '@/types/api.types'

interface Props {
  patient: PatientWithHistory
}

type PatientWithHistory = Patient & {
  medicalHistory?: MedicalHistoryEntry[]
}

interface Props {
  patient: PatientWithHistory
}

interface Emits {
  (e: 'click', patient: Patient): void
  (e: 'edit', patient: Patient): void
}

const props = defineProps<Props>()
defineEmits<Emits>()

// Router
const router = useRouter()

// Computed
const patientFullName = computed(() => {
  return `${props.patient.firstName} ${props.patient.lastName}`.trim()
})

const patientInitials = computed(() => {
  const firstName = props.patient.firstName || ''
  const lastName = props.patient.lastName || ''
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
})

const patientAge = computed(() => {
  try {
    const birthDate = new Date(props.patient.dateOfBirth)
    const age = differenceInYears(new Date(), birthDate)
    return `${age} years`
  } catch {
    return 'Unknown'
  }
})

const patientGender = computed(() => {
  if (!props.patient.gender) return 'Not specified'
  
  const genderMap = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
    prefer_not_to_say: 'Prefer not to say',
  }
  
  return genderMap[props.patient.gender] || 'Other'
})
const lastVisitDate = computed(() => {
  const history = props.patient.medicalHistory as MedicalHistoryEntry[] | undefined
  if (history && history.length > 0) {
    // Sort by date descending
    const sorted = history.slice().sort((a, b) => {
      return new Date(b.date).getTime() - new Date(a.date).getTime()
    })
    const latest = sorted[0]
    try {
      return format(new Date(latest.date), 'MMM dd, yyyy')
    } catch {
      return 'No visits'
    }
  }
  return 'No visits'
})

// Methods
const handleEdit = () => {
  // Navigate to patient detail page and open edit modal
  router.push(`/patients/${props.patient.id}?edit=true`)
}
</script>

<style lang="postcss" scoped>
/* Hover transform effect */
.patient-card:hover {
  transform: translateY(-1px);
}

/* Show quick actions on hover */
.patient-card .opacity-0 {
  transition: opacity 0.2s ease-in-out;
}

.patient-card:hover .opacity-0 {
  @apply opacity-100;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
  .patient-card {
    @apply p-4;
  }
  
  .patient-card .w-12.h-12 {
    @apply w-10 h-10 mr-3;
  }
  
  .patient-card .text-lg {
    @apply text-base;
  }
  
  .patient-card .grid-cols-2 {
    @apply grid-cols-1 gap-2;
  }
}

/* Focus states for accessibility */
.patient-card:focus-within {
  @apply ring-2 ring-primary-500 ring-opacity-50;
}

/* Subtle border animation on hover */
.patient-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border: 2px solid transparent;
  border-radius: 0.75rem;
  background: linear-gradient(45deg, transparent, rgba(14, 165, 233, 0.1), transparent);
  background-size: 200% 200%;
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.patient-card:hover::before {
  opacity: 1;
  animation: shimmer 2s linear infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 200%; }
  100% { background-position: -200% -200%; }
}

/* Status indicator animations */
.bg-green-100 {
  position: relative;
  overflow: hidden;
}

.bg-green-100::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.patient-card:hover .bg-green-100::after {
  left: 100%;
}
</style>
