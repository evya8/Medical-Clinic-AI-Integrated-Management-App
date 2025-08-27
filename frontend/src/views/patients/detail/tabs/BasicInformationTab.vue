<template>
  <div class="basic-information-tab">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Personal Information -->
      <div class="info-section">
        <h3 class="section-title">Personal Information</h3>
        <div class="info-grid">
          <div class="info-item">
            <label class="info-label">Full Name</label>
            <p class="info-value">{{ fullName }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Date of Birth</label>
            <p class="info-value">{{ formattedDateOfBirth }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Age</label>
            <p class="info-value">{{ patientAge }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Gender</label>
            <p class="info-value">{{ formattedGender }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Blood Type</label>
            <p class="info-value">
              <span v-if="patient.bloodType" class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-800 text-sm font-medium">
                {{ patient.bloodType }}
              </span>
              <span v-else class="text-gray-500">Not specified</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="info-section">
        <h3 class="section-title">Contact Information</h3>
        <div class="info-grid">
          <div class="info-item">
            <label class="info-label">Email Address</label>
            <p class="info-value">
              <a v-if="patient.email" :href="`mailto:${patient.email}`" class="text-primary-600 hover:text-primary-700">
                {{ patient.email }}
              </a>
              <span v-else class="text-gray-500">Not provided</span>
            </p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Phone Number</label>
            <p class="info-value">
              <a v-if="patient.phone" :href="`tel:${patient.phone}`" class="text-primary-600 hover:text-primary-700">
                {{ patient.phone }}
              </a>
              <span v-else class="text-gray-500">Not provided</span>
            </p>
          </div>
          
          <div class="info-item col-span-full">
            <label class="info-label">Address</label>
            <p class="info-value">
              {{ patient.address || 'Not provided' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Emergency Contact -->
      <div class="info-section">
        <h3 class="section-title">Emergency Contact</h3>
        <div class="info-grid">
          <div class="info-item">
            <label class="info-label">Contact Name</label>
            <p class="info-value">{{ patient.emergencyContactName || 'Not provided' }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Contact Phone</label>
            <p class="info-value">
              <a v-if="patient.emergencyContactPhone" :href="`tel:${patient.emergencyContactPhone}`" class="text-primary-600 hover:text-primary-700">
                {{ patient.emergencyContactPhone }}
              </a>
              <span v-else class="text-gray-500">Not provided</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Insurance Information -->
      <div class="info-section">
        <h3 class="section-title">Insurance Information</h3>
        <div class="info-grid">
          <div class="info-item">
            <label class="info-label">Insurance Provider</label>
            <p class="info-value">{{ patient.insuranceProvider || 'Not provided' }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Policy Number</label>
            <p class="info-value">
              <span v-if="patient.insurancePolicyNumber" class="font-mono text-sm">
                {{ patient.insurancePolicyNumber }}
              </span>
              <span v-else class="text-gray-500">Not provided</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Medical Alerts & Notes -->
      <div class="info-section col-span-full">
        <h3 class="section-title">Medical Information</h3>
        <div class="space-y-4">
          <div class="info-item">
            <label class="info-label">Allergies</label>
            <div class="info-value">
              <div v-if="hasAllergies" class="flex items-start">
                <ExclamationTriangleIcon class="w-5 h-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" />
                <div>
                  <p class="font-medium text-yellow-800">{{ patient.allergies }}</p>
                  <p class="text-sm text-yellow-700 mt-1">Please ensure all staff are aware of these allergies before treatment.</p>
                </div>
              </div>
              <p v-else class="text-gray-500">No known allergies</p>
            </div>
          </div>
          
          <div class="info-item">
            <label class="info-label">Medical Notes</label>
            <div class="info-value">
              <p v-if="patient.medicalNotes" class="whitespace-pre-wrap">{{ patient.medicalNotes }}</p>
              <p v-else class="text-gray-500">No additional medical notes</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Registration Information -->
      <div class="info-section col-span-full border-t border-gray-200 pt-6">
        <h3 class="section-title">Registration Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="info-item">
            <label class="info-label">Registration Date</label>
            <p class="info-value">{{ formattedCreatedAt }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Last Updated</label>
            <p class="info-value">{{ formattedUpdatedAt }}</p>
          </div>
          
          <div class="info-item">
            <label class="info-label">Patient Status</label>
            <p class="info-value">
              <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-800 text-sm font-medium">
                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></span>
                Active
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Button -->
    <div class="flex justify-end mt-8 pt-6 border-t border-gray-200">
      <button
        @click="$emit('edit')"
        class="medical-button-primary flex items-center"
      >
        <PencilIcon class="w-4 h-4 mr-2" />
        Edit Patient Information
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { differenceInYears, format } from 'date-fns'
import { ExclamationTriangleIcon, PencilIcon } from '@heroicons/vue/24/outline'
import type { Patient } from '@/types/api.types'

interface Props {
  patient: Patient
}

interface Emits {
  (e: 'update', patient: Patient): void
  (e: 'edit'): void
}

const props = defineProps<Props>()
defineEmits<Emits>()

// Computed properties
const fullName = computed(() => {
  return `${props.patient.firstName} ${props.patient.lastName}`.trim()
})

const formattedDateOfBirth = computed(() => {
  if (!props.patient.dateOfBirth) return 'Not provided'
  try {
    return format(new Date(props.patient.dateOfBirth), 'MMMM dd, yyyy')
  } catch {
    return 'Invalid date'
  }
})

const patientAge = computed(() => {
  if (!props.patient.dateOfBirth) return 'Unknown'
  try {
    const birthDate = new Date(props.patient.dateOfBirth)
    const age = differenceInYears(new Date(), birthDate)
    return `${age} years old`
  } catch {
    return 'Unknown'
  }
})

const formattedGender = computed(() => {
  if (!props.patient.gender) return 'Not specified'
  
  const genderMap = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
    prefer_not_to_say: 'Prefer not to say',
  }
  
  return genderMap[props.patient.gender] || 'Other'
})

const hasAllergies = computed(() => {
  return props.patient.allergies && 
         props.patient.allergies.toLowerCase() !== 'none known' && 
         props.patient.allergies.toLowerCase() !== 'none'
})

const formattedCreatedAt = computed(() => {
  if (!props.patient.createdAt) return 'Unknown'
  try {
    return format(new Date(props.patient.createdAt), 'MMMM dd, yyyy')
  } catch {
    return 'Unknown'
  }
})

const formattedUpdatedAt = computed(() => {
  if (!props.patient.updatedAt) return 'Unknown'
  try {
    return format(new Date(props.patient.updatedAt), 'MMMM dd, yyyy')
  } catch {
    return 'Unknown'
  }
})
</script>

<style lang="postcss" scoped>
.basic-information-tab {
  @apply space-y-8;
}

.info-section {
  @apply bg-white;
}

.section-title {
  @apply text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200;
}

.info-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.info-item {
  @apply space-y-1;
}

.info-item.col-span-full {
  @apply md:col-span-2;
}

.info-label {
  @apply text-sm font-medium text-gray-500 uppercase tracking-wide;
}

.info-value {
  @apply text-base text-gray-900 font-medium;
}

/* Link styles */
.info-value a {
  @apply transition-colors duration-200;
}

.info-value a:hover {
  @apply underline;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .info-grid {
    @apply grid-cols-1;
  }
  
  .info-item.col-span-full {
    @apply col-span-1;
  }
}

/* Print styles */
@media print {
  .basic-information-tab {
    @apply text-black;
  }
  
  .section-title {
    @apply border-black;
  }
  
  .info-value a {
    @apply text-black no-underline;
  }
  
  .info-value a::after {
    content: " (" attr(href) ")";
    @apply text-sm;
  }
}
</style>
