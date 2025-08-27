<template>
  <div
    v-if="isOpen && record"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
  >
    <!-- Background overlay -->
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="handleClose"
      ></div>

      <!-- Modal panel -->
      <div
        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
      >
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                {{ record.filename }}
              </h3>
              <p class="text-sm text-gray-500">
                {{ recordTypeLabel }} • {{ formatFileSize(record.size) }} • {{ formatDate(record.createdAt) }}
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <button
                type="button"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                @click="downloadRecord"
              >
                <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                Download
              </button>
              <button
                type="button"
                class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                @click="handleClose"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="px-4 py-5 sm:p-6">
          <!-- Record Information -->
          <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Description</label>
              <p class="mt-1 text-sm text-gray-900">{{ record.description || 'No description provided' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Uploaded By</label>
              <p class="mt-1 text-sm text-gray-900">{{ record.uploadedBy || 'Unknown' }}</p>
            </div>
            <div v-if="record.tags && record.tags.length">
              <label class="text-sm font-medium text-gray-500">Tags</label>
              <div class="mt-1 flex flex-wrap gap-1">
                <span
                  v-for="tag in record.tags"
                  :key="tag"
                  class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-primary-100 text-primary-800"
                >
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>

          <!-- File Preview/Viewer -->
          <div class="border border-gray-200 rounded-lg">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
              <h4 class="text-sm font-medium text-gray-900">Preview</h4>
            </div>
            <div class="p-4">
              <!-- PDF Viewer -->
              <div v-if="isPDF" class="text-center">
                <DocumentIcon class="mx-auto h-16 w-16 text-gray-400 mb-4" />
                <p class="text-sm text-gray-600 mb-4">PDF documents require download to view</p>
                <button
                  type="button"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
                  @click="downloadRecord"
                >
                  <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                  Download PDF
                </button>
              </div>

              <!-- Image Viewer -->
              <div v-else-if="isImage" class="text-center">
                <img
                  :src="record.url"
                  :alt="record.filename"
                  class="max-w-full max-h-96 mx-auto rounded-lg shadow"
                  @error="imageLoadError = true"
                />
                <div v-if="imageLoadError" class="text-center py-8">
                  <PhotoIcon class="mx-auto h-16 w-16 text-gray-400 mb-4" />
                  <p class="text-sm text-gray-600">Unable to load image preview</p>
                </div>
              </div>

              <!-- Document Viewer -->
              <div v-else class="text-center py-8">
                <DocumentIcon class="mx-auto h-16 w-16 text-gray-400 mb-4" />
                <p class="text-sm text-gray-600 mb-4">{{ record.filename }}</p>
                <p class="text-xs text-gray-500 mb-4">This file type cannot be previewed in the browser</p>
                <button
                  type="button"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
                  @click="downloadRecord"
                >
                  <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                  Download File
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto sm:text-sm"
            @click="handleClose"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { format } from 'date-fns'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  DocumentIcon,
  PhotoIcon
} from '@heroicons/vue/24/outline'
import type { MedicalRecord } from '@/types/api.types'

interface Props {
  isOpen: boolean
  record: MedicalRecord | null
}

interface Emits {
  (e: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const imageLoadError = ref(false)

// Computed
const recordTypeLabel = computed(() => {
  if (!props.record) return ''
  
  const typeLabels = {
    'lab-result': 'Lab Result',
    'imaging': 'Imaging',
    'prescription': 'Prescription',
    'report': 'Report',
    'referral': 'Referral',
    'insurance': 'Insurance',
    'other': 'Other'
  }
  
  return typeLabels[props.record.type] || 'Unknown'
})

const isPDF = computed(() => {
  return props.record?.filename.toLowerCase().includes('.pdf')
})

const isImage = computed(() => {
  if (!props.record) return false
  const filename = props.record.filename.toLowerCase()
  return filename.includes('.jpg') || filename.includes('.jpeg') || filename.includes('.png') || filename.includes('.gif')
})

// Methods
const handleClose = () => {
  imageLoadError.value = false
  emit('close')
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString: string): string => {
  try {
    return format(new Date(dateString), 'MMM dd, yyyy')
  } catch {
    return 'Invalid date'
  }
}

const downloadRecord = () => {
  if (props.record?.url) {
    const link = document.createElement('a')
    link.href = props.record.url
    link.download = props.record.filename
    link.click()
  }
}
</script>
