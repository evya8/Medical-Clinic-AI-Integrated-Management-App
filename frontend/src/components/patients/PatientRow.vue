<template>
  <tr
    class="patient-row group hover:bg-gray-50 cursor-pointer transition-colors duration-200"
    @click="navigateToDetail"
  >
    <!-- Patient Column -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="flex items-center">
        <!-- Patient Avatar -->
        <div class="flex-shrink-0 w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center mr-4">
          <span class="text-sm font-medium text-primary-700">
            {{ patientInitials }}
          </span>
        </div>
        
        <!-- Patient Details -->
        <div class="min-w-0 flex-1">
          <div class="text-sm font-medium text-gray-900">
            {{ patientFullName }}
          </div>
          <div class="text-sm text-gray-500">
            ID: {{ patient.id.toString().padStart(4, '0') }}
          </div>
        </div>
      </div>
    </td>

    <!-- Contact Column -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="text-sm text-gray-900">{{ patient.email || '-' }}</div>
      <div class="text-sm text-gray-500">{{ patient.phone || '-' }}</div>
    </td>

    <!-- Age Column -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="text-sm text-gray-900">{{ patientAge }}</div>
      <div class="text-sm text-gray-500">{{ patientGender }}</div>
    </td>

    <!-- Last Visit Column -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="text-sm text-gray-900">{{ lastVisitDate }}</div>
      <div class="text-sm text-gray-500">{{ lastVisitType }}</div>
    </td>

    <!-- Status Column -->
    <td class="px-6 py-4 whitespace-nowrap">
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></span>
        Active
      </span>
      <div v-if="hasAllergies" class="mt-1">
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
          <ExclamationTriangleIcon class="w-3 h-3 mr-1" />
          Allergies
        </span>
      </div>
    </td>

    <!-- Actions Column -->
    <td class="px-6 py-4 whitespace-nowrap text-center">
      <div class="flex items-center justify-center space-x-2">
        <button
          class="p-1 text-gray-400 hover:text-primary-600 rounded transition-colors duration-200"
          @click.stop="$emit('click', patient)"
          title="View patient details"
        >
          <EyeIcon class="w-4 h-4" />
        </button>
        <button
          class="p-1 text-gray-400 hover:text-blue-600 rounded transition-colors duration-200"
          @click.stop="$emit('edit', patient)"
          title="Edit patient"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
        <button
          class="p-1 text-gray-400 hover:text-green-600 rounded transition-colors duration-200"
          @click.stop="$emit('view-records', patient)"
          title="View medical records"
        >
          <ClipboardDocumentListIcon class="w-4 h-4" />
        </button>
      </div>
    </td>
  </tr>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { differenceInYears, format, subDays } from 'date-fns'
import {
  EyeIcon,
  PencilIcon,
  ClipboardDocumentListIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import type { Patient } from '@/types/api.types'

interface Props {
  patient: Patient
}

interface Emits {
  (e: 'click', patient: Patient): void
  (e: 'edit', patient: Patient): void
  (e: 'view-records', patient: Patient): void
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
    return `${age} years old`
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
  // Mock: Generate a recent date based on patient creation
  try {
    const mockLastVisit = subDays(new Date(), Math.floor(Math.random() * 30) + 1)
    return format(mockLastVisit, 'MMM dd, yyyy')
  } catch {
    return 'No visits'
  }
})

const lastVisitType = computed(() => {
  const visitTypes = ['Checkup', 'Follow-up', 'Consultation', 'Emergency', 'Screening']
  return visitTypes[Math.floor(Math.random() * visitTypes.length)]
})

const hasAllergies = computed(() => {
  return props.patient.allergies && 
         props.patient.allergies.toLowerCase() !== 'none known' && 
         props.patient.allergies.toLowerCase() !== 'none'
})

// Methods
const navigateToDetail = () => {
  router.push(`/patients/${props.patient.id}`)
}
</script>

<style lang="postcss" scoped>
/* Hover effects for action buttons */
.patient-row .p-1 {
  @apply opacity-70 group-hover:opacity-100;
}

/* Mobile responsive - hide some columns on smaller screens */
@media (max-width: 768px) {
  .patient-row td:nth-child(3),
  .patient-row td:nth-child(4) {
    @apply hidden;
  }
}

@media (max-width: 640px) {
  .patient-row td:nth-child(2) {
    @apply hidden;
  }
  
  .patient-row .flex.items-center.justify-center.space-x-2 {
    @apply space-x-1;
  }
}

/* Accessibility improvements */
.patient-row:focus-within {
  @apply bg-primary-50 ring-1 ring-primary-200;
}

.patient-row button:focus {
  @apply outline-none ring-2 ring-primary-500 ring-offset-1;
}

/* Subtle animation for status indicators */
.bg-green-100 {
  @apply relative overflow-hidden;
}

.bg-green-100::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  transition: left 0.6s ease;
}

.patient-row:hover .bg-green-100::before {
  left: 100%;
}

/* Smooth row highlight */
.patient-row {
  border-left: 3px solid transparent;
  transition: all 0.2s ease;
}

.patient-row:hover {
  border-left-color: #0ea5e9;
  background-color: #f8fafc;
}
</style>
