<template>
  <div
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
        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
      >
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              Upload Medical Record
            </h3>
            <button
              type="button"
              class="bg-white rounded-md text-gray-400 hover:text-gray-600"
              @click="handleClose"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit">
          <div class="px-4 pb-4 sm:px-6">
            <div class="space-y-4">
              <!-- File Upload -->
              <div>
                <label class="medical-form-label">Select File</label>
                <div
                  class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-primary-400 transition-colors"
                  :class="{ 'border-primary-400 bg-primary-50': isDragOver }"
                  @drop="handleDrop"
                  @dragover.prevent="isDragOver = true"
                  @dragleave="isDragOver = false"
                >
                  <div class="space-y-1 text-center">
                    <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <div class="flex text-sm text-gray-600">
                      <label class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500">
                        <span>Upload a file</span>
                        <input
                          ref="fileInput"
                          type="file"
                          class="sr-only"
                          accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                          @change="handleFileSelect"
                        />
                      </label>
                      <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-500">
                      PDF, DOC, DOCX, JPG, PNG up to 10MB
                    </p>
                  </div>
                </div>
                <div v-if="selectedFile" class="mt-2 text-sm text-gray-600">
                  Selected: {{ selectedFile.name }}
                </div>
              </div>

              <!-- Record Type -->
              <div>
                <label class="medical-form-label">Record Type</label>
                <select v-model="form.type" required class="medical-input">
                  <option value="">Select type</option>
                  <option value="lab-result">Lab Result</option>
                  <option value="imaging">Imaging</option>
                  <option value="prescription">Prescription</option>
                  <option value="report">Report</option>
                  <option value="referral">Referral</option>
                  <option value="insurance">Insurance</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <!-- Description -->
              <div>
                <label class="medical-form-label">Description</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="medical-input"
                  placeholder="Brief description of the record"
                ></textarea>
              </div>

              <!-- Tags -->
              <div>
                <label class="medical-form-label">Tags (optional)</label>
                <input
                  v-model="form.tags"
                  type="text"
                  class="medical-input"
                  placeholder="Enter tags separated by commas"
                />
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="isLoading || !selectedFile"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              <span v-if="!isLoading">Upload Record</span>
              <span v-else class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading...
              </span>
            </button>
            <button
              type="button"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              @click="handleClose"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { XMarkIcon, DocumentIcon } from '@heroicons/vue/24/outline'
import type { Patient, MedicalRecord } from '@/types/api.types'

interface Props {
  patient: Patient
}

interface Emits {
  (e: 'close'): void
  (e: 'uploaded', record: MedicalRecord): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const isLoading = ref(false)
const isDragOver = ref(false)
const selectedFile = ref<File | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const form = ref({
  type: '',
  description: '',
  tags: ''
})

// Methods
const handleClose = () => {
  if (!isLoading.value) {
    selectedFile.value = null
    form.value = { type: '', description: '', tags: '' }
    emit('close')
  }
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (files && files.length > 0) {
    selectedFile.value = files[0]
  }
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    selectedFile.value = files[0]
  }
}

const handleSubmit = async () => {
  if (!selectedFile.value) return

  isLoading.value = true

  try {
    // Mock API call - replace with actual upload
    await new Promise(resolve => setTimeout(resolve, 2000))

    // Parse tags into array
    const tagsArray = form.value.tags
      ? form.value.tags.split(',').map(tag => tag.trim()).filter(tag => tag)
      : []

    const uploadedRecord: MedicalRecord = {
      id: Date.now(),
      patientId: props.patient.id,
      filename: selectedFile.value.name,
      type: form.value.type as any,
      description: form.value.description || undefined,
      url: URL.createObjectURL(selectedFile.value), // Mock URL
      size: selectedFile.value.size,
      uploadedBy: 'Current User', // Would come from auth context
      tags: tagsArray.length > 0 ? tagsArray : undefined,
      metadata: {
        originalName: selectedFile.value.name,
        mimeType: selectedFile.value.type,
        uploadDate: new Date().toISOString()
      },
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }

    emit('uploaded', uploadedRecord)
    handleClose()
  } catch (error) {
    console.error('Upload failed:', error)
  } finally {
    isLoading.value = false
  }
}
</script>
