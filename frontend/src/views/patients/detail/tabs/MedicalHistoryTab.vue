<template>
  <div class="medical-history-tab">
    <!-- Medical History Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Medical History</h3>
      <button
        @click="showAddHistoryModal = true"
        class="medical-button-secondary flex items-center"
      >
        <PlusIcon class="w-4 h-4 mr-2" />
        Add Medical Record
      </button>
    </div>

    <!-- Medical History Timeline -->
    <div v-if="medicalHistory.length > 0" class="space-y-6">
      <div
        v-for="entry in sortedMedicalHistory"
        :key="entry.id"
        class="medical-history-entry group medical-card p-4 border-l-4"
        :class="getEntryBorderColor(entry.type)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <!-- Entry Header -->
            <div class="flex items-center space-x-3 mb-2">
              <component
                :is="getEntryIcon(entry.type)"
                class="w-5 h-5 flex-shrink-0"
                :class="getEntryIconColor(entry.type)"
              />
              <div>
                <h4 class="font-semibold text-gray-900">{{ entry.title }}</h4>
                <p class="text-sm text-gray-600">
                  {{ formatDate(entry.date) }}
                  <span v-if="entry.doctor" class="ml-2">• Dr. {{ entry.doctor }}</span>
                </p>
              </div>
            </div>

            <!-- Entry Content -->
            <div class="pl-8">
              <p v-if="entry.description" class="text-gray-700 mb-3">{{ entry.description }}</p>
              
              <!-- Diagnosis -->
              <div v-if="entry.diagnosis" class="mb-3">
                <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Diagnosis:</span>
                <p class="text-gray-900">{{ entry.diagnosis }}</p>
              </div>

              <!-- Treatment -->
              <div v-if="entry.treatment" class="mb-3">
                <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Treatment:</span>
                <p class="text-gray-900">{{ entry.treatment }}</p>
              </div>

              <!-- Medications -->
              <div v-if="entry.medications && entry.medications.length > 0" class="mb-3">
                <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Medications:</span>
                <div class="flex flex-wrap gap-2 mt-1">
                  <span
                    v-for="medication in entry.medications"
                    :key="medication"
                    class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-medium"
                  >
                    {{ medication }}
                  </span>
                </div>
              </div>

              <!-- Attachments -->
              <div v-if="entry.attachments && entry.attachments.length > 0" class="mb-3">
                <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Attachments:</span>
                <div class="mt-2 space-y-1">
                  <div
                    v-for="attachment in entry.attachments"
                    :key="attachment.id"
                    class="flex items-center text-sm"
                  >
                    <DocumentTextIcon class="w-4 h-4 text-gray-400 mr-2" />
                    <a
                      :href="attachment.url"
                      target="_blank"
                      class="text-primary-600 hover:text-primary-700 hover:underline"
                    >
                      {{ attachment.name }}
                    </a>
                    <span class="text-gray-400 ml-2">({{ attachment.size }})</span>
                  </div>
                </div>
              </div>

              <!-- Follow-up -->
              <div v-if="entry.followUpDate" class="mb-3">
                <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Follow-up:</span>
                <p class="text-gray-900">{{ formatDate(entry.followUpDate) }}</p>
              </div>
            </div>
          </div>

          <!-- Entry Actions -->
          <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click="editEntry(entry)"
              class="p-1 text-gray-400 hover:text-blue-600 rounded"
              title="Edit entry"
            >
              <PencilIcon class="w-4 h-4" />
            </button>
            <button
              @click="deleteEntry(entry)"
              class="p-1 text-gray-400 hover:text-red-600 rounded"
              title="Delete entry"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state medical-card p-8 text-center">
      <HeartIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">No Medical History</h3>
      <p class="text-gray-600 mb-6">
        Start building this patient's medical history by adding their first medical record.
      </p>
      <button
        @click="showAddHistoryModal = true"
        class="medical-button-primary flex items-center mx-auto"
      >
        <PlusIcon class="w-4 h-4 mr-2" />
        Add First Medical Record
      </button>
    </div>

    <!-- Medical Summary Cards -->
    <div v-if="medicalSummary" class="medical-summary mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-primary-600">{{ medicalSummary.totalVisits }}</div>
        <div class="text-sm text-gray-600">Total Visits</div>
      </div>
      
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ medicalSummary.activeConditions }}</div>
        <div class="text-sm text-gray-600">Active Conditions</div>
      </div>
      
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ medicalSummary.currentMedications }}</div>
        <div class="text-sm text-gray-600">Current Medications</div>
      </div>
      
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-purple-600">{{ medicalSummary.lastVisit }}</div>
        <div class="text-sm text-gray-600">Days Since Last Visit</div>
      </div>
    </div>

    <!-- Add Medical History Modal -->
    <AddMedicalHistoryModal
      v-if="showAddHistoryModal"
      :patient="patient"
      @close="showAddHistoryModal = false"
      @added="handleHistoryAdded"
    />

    <!-- Edit Medical History Modal -->
    <EditMedicalHistoryModal
      v-if="showEditHistoryModal && selectedEntry"
      :patient="patient"
      :entry="selectedEntry"
      @close="showEditHistoryModal = false"
      @updated="handleHistoryUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { format } from 'date-fns'
import {
  PlusIcon,
  HeartIcon,
  DocumentTextIcon,
  PencilIcon,
  TrashIcon,
  ClipboardDocumentListIcon,
  ExclamationTriangleIcon,
  CalendarIcon,
  BeakerIcon,
} from '@heroicons/vue/24/outline'

import AddMedicalHistoryModal from '@/components/medical/AddMedicalHistoryModal.vue'
import EditMedicalHistoryModal from '@/components/medical/EditMedicalHistoryModal.vue'
import type { Patient, MedicalHistoryEntry } from '@/types/api.types'

interface Props {
  patient: Patient
  medicalHistory: MedicalHistoryEntry[]
}

interface Emits {
  (e: 'update', history: MedicalHistoryEntry[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const showAddHistoryModal = ref(false)
const showEditHistoryModal = ref(false)
const selectedEntry = ref<MedicalHistoryEntry | null>(null)

// Computed
const sortedMedicalHistory = computed(() => {
  return [...props.medicalHistory].sort((a, b) => 
    new Date(b.date).getTime() - new Date(a.date).getTime()
  )
})

const medicalSummary = computed(() => {
  if (props.medicalHistory.length === 0) return null
  
  const totalVisits = props.medicalHistory.length
  const activeConditions = props.medicalHistory.filter(entry => 
    entry.type === 'diagnosis' && entry.status === 'active'
  ).length
  
  const currentMedications = props.medicalHistory
    .flatMap(entry => entry.medications || [])
    .filter((medication, index, arr) => arr.indexOf(medication) === index)
    .length
  
  const lastVisitDate = props.medicalHistory.length > 0 
    ? new Date(sortedMedicalHistory.value[0].date)
    : new Date()
  const daysSinceLastVisit = Math.floor(
    (new Date().getTime() - lastVisitDate.getTime()) / (1000 * 60 * 60 * 24)
  )
  
  return {
    totalVisits,
    activeConditions,
    currentMedications,
    lastVisit: daysSinceLastVisit,
  }
})

// Methods
const formatDate = (date: string) => {
  try {
    return format(new Date(date), 'MMMM dd, yyyy')
  } catch {
    return 'Invalid date'
  }
}

const getEntryIcon = (type: string) => {
  const iconMap: Record<string, any> = {
    visit: CalendarIcon,
    diagnosis: HeartIcon,
    treatment: ClipboardDocumentListIcon,
    surgery: BeakerIcon,
    emergency: ExclamationTriangleIcon,
    test: BeakerIcon,
  }
  return iconMap[type] || ClipboardDocumentListIcon
}

const getEntryIconColor = (type: string) => {
  const colorMap: Record<string, string> = {
    visit: 'text-blue-500',
    diagnosis: 'text-red-500',
    treatment: 'text-green-500',
    surgery: 'text-purple-500',
    emergency: 'text-yellow-500',
    test: 'text-indigo-500',
  }
  return colorMap[type] || 'text-gray-500'
}

const getEntryBorderColor = (type: string) => {
  const colorMap: Record<string, string> = {
    visit: 'border-blue-500',
    diagnosis: 'border-red-500',
    treatment: 'border-green-500',
    surgery: 'border-purple-500',
    emergency: 'border-yellow-500',
    test: 'border-indigo-500',
  }
  return colorMap[type] || 'border-gray-500'
}

const editEntry = (entry: MedicalHistoryEntry) => {
  selectedEntry.value = entry
  showEditHistoryModal.value = true
}

const deleteEntry = async (entry: MedicalHistoryEntry) => {
  if (confirm('Are you sure you want to delete this medical history entry?')) {
    // In a real app, this would call an API
    const updatedHistory = props.medicalHistory.filter(h => h.id !== entry.id)
    emit('update', updatedHistory)
  }
}

const handleHistoryAdded = (newEntry: MedicalHistoryEntry) => {
  const updatedHistory = [newEntry, ...props.medicalHistory]
  emit('update', updatedHistory)
  showAddHistoryModal.value = false
}

const handleHistoryUpdated = (updatedEntry: MedicalHistoryEntry) => {
  const updatedHistory = props.medicalHistory.map(entry =>
    entry.id === updatedEntry.id ? updatedEntry : entry
  )
  emit('update', updatedHistory)
  showEditHistoryModal.value = false
  selectedEntry.value = null
}
</script>

<style lang="postcss" scoped>
.medical-history-tab {
  @apply space-y-6;
}

.medical-history-entry {
  position: relative;
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

.medical-history-entry:hover {
  transform: translateY(-2px);
}

.summary-card {
  @apply transition-all duration-200 hover:shadow-md;
}

.summary-card:hover {
  transform: translateY(-2px);
}

.empty-state {
  @apply border-2 border-dashed border-gray-200 bg-gray-50;
}

/* Timeline connector lines */
.medical-history-entry:not(:last-child)::after {
  content: '';
  position: absolute;
  left: -4px;
  bottom: -24px;
  width: 4px;
  height: 24px;
  background: linear-gradient(to bottom, currentColor 0%, transparent 100%);
  opacity: 0.2;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .medical-summary {
    @apply grid-cols-2;
  }
}

/* Print styles */
@media print {
  .medical-history-entry {
    @apply shadow-none border border-gray-300 break-inside-avoid;
  }
  
  .group-hover\:opacity-100 {
    @apply hidden;
  }
}
</style>
