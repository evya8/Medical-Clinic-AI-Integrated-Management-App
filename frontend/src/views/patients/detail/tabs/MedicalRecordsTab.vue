<template>
  <div class="medical-records-tab">
    <!-- Medical Records Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Medical Records</h3>
        <p class="text-sm text-gray-600">Patient documents, test results, and medical files</p>
      </div>
      <button
        @click="showUploadModal = true"
        class="medical-button-primary flex items-center"
      >
        <DocumentArrowUpIcon class="w-4 h-4 mr-2" />
        Upload Record
      </button>
    </div>

    <!-- Filter and Search -->
    <div class="filter-section medical-card p-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <label class="medical-form-label">Search Records</label>
          <div class="relative">
            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by filename, type, or description..."
              class="medical-input pl-10"
            />
          </div>
        </div>
        
        <div class="flex-1">
          <label class="medical-form-label">Filter by Type</label>
          <select v-model="selectedType" class="medical-input">
            <option value="">All Types</option>
            <option value="lab-result">Lab Results</option>
            <option value="imaging">Imaging</option>
            <option value="prescription">Prescription</option>
            <option value="report">Medical Report</option>
            <option value="referral">Referral</option>
            <option value="insurance">Insurance</option>
            <option value="other">Other</option>
          </select>
        </div>
        
        <div class="flex-1">
          <label class="medical-form-label">Date Range</label>
          <select v-model="selectedDateRange" class="medical-input">
            <option value="">All Time</option>
            <option value="recent">Recent (30 days)</option>
            <option value="past-3-months">Past 3 Months</option>
            <option value="past-year">Past Year</option>
            <option value="older">Older than 1 Year</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Records List -->
    <div v-if="filteredRecords.length > 0" class="records-list space-y-4">
      <div
        v-for="record in filteredRecords"
        :key="record.id"
        class="record-card medical-card p-4 group hover:shadow-md transition-all duration-200"
      >
        <div class="flex items-start space-x-4">
          <!-- File Icon -->
          <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                 :class="getFileTypeIconBackground(record.type)">
              <component
                :is="getFileTypeIcon(record.type)"
                class="w-6 h-6"
                :class="getFileTypeIconColor(record.type)"
              />
            </div>
          </div>

          <!-- Record Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900 truncate">{{ record.filename }}</h4>
                <div class="flex items-center space-x-4 mt-1 text-sm text-gray-600">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="getTypeClasses(record.type)">
                    {{ getTypeText(record.type) }}
                  </span>
                  <span>{{ formatFileSize(record.size) }}</span>
                  <span>{{ formatDate(record.createdAt) }}</span>
                  <span v-if="record.uploadedBy">• Uploaded by {{ record.uploadedBy }}</span>
                </div>
                <p v-if="record.description" class="text-sm text-gray-700 mt-2">{{ record.description }}</p>
              </div>

              <!-- Record Actions -->
              <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity ml-4">
                <button
                  @click="viewRecord(record)"
                  class="p-2 text-gray-400 hover:text-blue-600 rounded"
                  title="View record"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>
                
                <button
                  @click="downloadRecord(record)"
                  class="p-2 text-gray-400 hover:text-green-600 rounded"
                  title="Download record"
                >
                  <ArrowDownTrayIcon class="w-4 h-4" />
                </button>
                
                <button
                  @click="shareRecord(record)"
                  class="p-2 text-gray-400 hover:text-purple-600 rounded"
                  title="Share record"
                >
                  <ShareIcon class="w-4 h-4" />
                </button>
                
                <button
                  @click="deleteRecord(record)"
                  class="p-2 text-gray-400 hover:text-red-600 rounded"
                  title="Delete record"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Additional Metadata -->
            <div v-if="record.metadata" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
              <div v-for="(value, key) in record.metadata" :key="key" class="flex">
                <span class="font-medium text-gray-600 w-20 flex-shrink-0">{{ formatMetadataKey(key) }}:</span>
                <span class="text-gray-800">{{ value }}</span>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="record.tags && record.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
              <span
                v-for="tag in record.tags"
                :key="tag"
                class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-800 text-xs"
              >
                {{ tag }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state medical-card p-8 text-center">
      <DocumentTextIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ records.length === 0 ? 'No Medical Records' : 'No Matching Records' }}
      </h3>
      <p class="text-gray-600 mb-6">
        {{ records.length === 0 
          ? 'Upload medical records to keep track of this patient\'s medical history.' 
          : 'Try adjusting your search or filters to find the records you\'re looking for.'
        }}
      </p>
      <button
        @click="showUploadModal = true"
        class="medical-button-primary flex items-center mx-auto"
      >
        <DocumentArrowUpIcon class="w-4 h-4 mr-2" />
        Upload First Record
      </button>
    </div>

    <!-- Records Summary -->
    <div v-if="records.length > 0" class="records-summary mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ recordStats.total }}</div>
        <div class="text-sm text-gray-600">Total Records</div>
      </div>
      
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ recordStats.labResults }}</div>
        <div class="text-sm text-gray-600">Lab Results</div>
      </div>
      
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-purple-600">{{ recordStats.imaging }}</div>
        <div class="text-sm text-gray-600">Imaging</div>
      </div>
      
      <div class="summary-card medical-card p-4 text-center">
        <div class="text-2xl font-bold text-orange-600">{{ formatFileSize(recordStats.totalSize) }}</div>
        <div class="text-sm text-gray-600">Total Size</div>
      </div>
    </div>

    <!-- Upload Modal -->
    <UploadMedicalRecordModal
      v-if="showUploadModal"
      :patient="patient"
      @close="showUploadModal = false"
      @uploaded="handleRecordUploaded"
    />

    <!-- Record Viewer Modal -->
    <MedicalRecordViewerModal
      v-if="showViewerModal && selectedRecord"
      :record="selectedRecord"
      @close="showViewerModal = false"
    />

    <!-- Share Modal -->
    <ShareRecordModal
      v-if="showShareModal && selectedRecord"
      :record="selectedRecord"
      @close="showShareModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { format, isAfter, subDays, subMonths } from 'date-fns'
import {
  DocumentTextIcon,
  DocumentArrowUpIcon,
  MagnifyingGlassIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  TrashIcon,
  BeakerIcon,
  CameraIcon,
  ClipboardDocumentListIcon,
  DocumentIcon,
  FolderIcon,
} from '@heroicons/vue/24/outline'

import UploadMedicalRecordModal from '@/components/medical/UploadMedicalRecordModal.vue'
import MedicalRecordViewerModal from '@/components/medical/MedicalRecordViewerModal.vue'
import ShareRecordModal from '@/components/medical/ShareRecordModal.vue'
import type { Patient, MedicalRecord } from '@/types/api.types'

interface Props {
  patient: Patient
  records: MedicalRecord[]
}

interface Emits {
  (e: 'update', records: MedicalRecord[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const searchQuery = ref('')
const selectedType = ref('')
const selectedDateRange = ref('')
const showUploadModal = ref(false)
const showViewerModal = ref(false)
const showShareModal = ref(false)
const selectedRecord = ref<MedicalRecord | null>(null)

// Computed
const filteredRecords = computed(() => {
  let filtered = [...props.records]

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(record =>
      record.filename.toLowerCase().includes(query) ||
      record.type.toLowerCase().includes(query) ||
      (record.description?.toLowerCase().includes(query))
    )
  }

  // Filter by type
  if (selectedType.value) {
    filtered = filtered.filter(record => record.type === selectedType.value)
  }

  // Filter by date range
  if (selectedDateRange.value) {
    const now = new Date()
    switch (selectedDateRange.value) {
      case 'recent':
        filtered = filtered.filter(record => 
          isAfter(new Date(record.createdAt), subDays(now, 30))
        )
        break
      case 'past-3-months':
        filtered = filtered.filter(record => 
          isAfter(new Date(record.createdAt), subMonths(now, 3))
        )
        break
      case 'past-year':
        filtered = filtered.filter(record => 
          isAfter(new Date(record.createdAt), subMonths(now, 12))
        )
        break
      case 'older':
        filtered = filtered.filter(record => 
          !isAfter(new Date(record.createdAt), subMonths(now, 12))
        )
        break
    }
  }

  // Sort by date (newest first)
  return filtered.sort((a, b) => 
    new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
  )
})

const recordStats = computed(() => {
  const total = props.records.length
  const labResults = props.records.filter(record => record.type === 'lab-result').length
  const imaging = props.records.filter(record => record.type === 'imaging').length
  const totalSize = props.records.reduce((sum, record) => sum + (record.size || 0), 0)

  return { total, labResults, imaging, totalSize }
})

// Methods
const formatDate = (date: string) => {
  try {
    return format(new Date(date), 'MMM dd, yyyy')
  } catch {
    return 'Invalid date'
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatMetadataKey = (key: string) => {
  return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1')
}

const getFileTypeIcon = (type: string) => {
  const iconMap: Record<string, any> = {
    'lab-result': BeakerIcon,
    'imaging': CameraIcon,
    'prescription': ClipboardDocumentListIcon,
    'report': DocumentTextIcon,
    'referral': DocumentIcon,
    'insurance': FolderIcon,
    'other': DocumentIcon,
  }
  return iconMap[type] || DocumentIcon
}

const getFileTypeIconBackground = (type: string) => {
  const colorMap: Record<string, string> = {
    'lab-result': 'bg-blue-100',
    'imaging': 'bg-purple-100',
    'prescription': 'bg-green-100',
    'report': 'bg-yellow-100',
    'referral': 'bg-indigo-100',
    'insurance': 'bg-orange-100',
    'other': 'bg-gray-100',
  }
  return colorMap[type] || 'bg-gray-100'
}

const getFileTypeIconColor = (type: string) => {
  const colorMap: Record<string, string> = {
    'lab-result': 'text-blue-600',
    'imaging': 'text-purple-600',
    'prescription': 'text-green-600',
    'report': 'text-yellow-600',
    'referral': 'text-indigo-600',
    'insurance': 'text-orange-600',
    'other': 'text-gray-600',
  }
  return colorMap[type] || 'text-gray-600'
}

const getTypeClasses = (type: string) => {
  const classMap: Record<string, string> = {
    'lab-result': 'bg-blue-100 text-blue-800',
    'imaging': 'bg-purple-100 text-purple-800',
    'prescription': 'bg-green-100 text-green-800',
    'report': 'bg-yellow-100 text-yellow-800',
    'referral': 'bg-indigo-100 text-indigo-800',
    'insurance': 'bg-orange-100 text-orange-800',
    'other': 'bg-gray-100 text-gray-800',
  }
  return classMap[type] || 'bg-gray-100 text-gray-800'
}

const getTypeText = (type: string) => {
  const textMap: Record<string, string> = {
    'lab-result': 'Lab Result',
    'imaging': 'Imaging',
    'prescription': 'Prescription',
    'report': 'Medical Report',
    'referral': 'Referral',
    'insurance': 'Insurance',
    'other': 'Other',
  }
  return textMap[type] || type
}

const viewRecord = (record: MedicalRecord) => {
  selectedRecord.value = record
  showViewerModal.value = true
}

const downloadRecord = (record: MedicalRecord) => {
  // In a real app, this would trigger a download
  const link = document.createElement('a')
  link.href = record.url
  link.download = record.filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const shareRecord = (record: MedicalRecord) => {
  selectedRecord.value = record
  showShareModal.value = true
}

const deleteRecord = async (record: MedicalRecord) => {
  if (confirm('Are you sure you want to delete this medical record? This action cannot be undone.')) {
    // In a real app, this would call an API
    const updatedRecords = props.records.filter(r => r.id !== record.id)
    emit('update', updatedRecords)
  }
}

const handleRecordUploaded = (newRecord: MedicalRecord) => {
  const updatedRecords = [newRecord, ...props.records]
  emit('update', updatedRecords)
  showUploadModal.value = false
}
</script>

<style lang="postcss" scoped>
.medical-records-tab {
  @apply space-y-6;
}

.record-card {
  @apply transition-all duration-200 hover:shadow-md;
}

.record-card:hover {
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

/* Responsive adjustments */
@media (max-width: 768px) {
  .records-summary {
    @apply grid-cols-2;
  }
  
  .filter-section .flex-row {
    @apply flex-col;
  }
}

/* Print styles */
@media print {
  .record-card {
    @apply shadow-none border border-gray-300 break-inside-avoid;
  }
  
  .group-hover\:opacity-100 {
    @apply hidden;
  }
  
  .filter-section {
    @apply hidden;
  }
}
</style>
