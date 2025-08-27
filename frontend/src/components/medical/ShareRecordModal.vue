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
        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
      >
        <!-- Header -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-primary-100">
                <ShareIcon class="h-6 w-6 text-primary-600" />
              </div>
              <div class="ml-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                  Share Medical Record
                </h3>
                <p class="text-sm text-gray-500">
                  {{ record.filename }}
                </p>
              </div>
            </div>
            <button
              type="button"
              class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
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
              <!-- Share Method -->
              <div>
                <label class="medical-form-label">Share Method</label>
                <div class="mt-2 space-y-2">
                  <label class="flex items-center">
                    <input
                      v-model="form.method"
                      type="radio"
                      value="email"
                      class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Email</span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="form.method"
                      type="radio"
                      value="link"
                      class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Secure Link</span>
                  </label>
                </div>
              </div>

              <!-- Email Recipients (if email method) -->
              <div v-if="form.method === 'email'">
                <label class="medical-form-label">Recipients</label>
                <textarea
                  v-model="form.recipients"
                  rows="3"
                  required
                  class="medical-input"
                  placeholder="Enter email addresses separated by commas"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">
                  Enter multiple email addresses separated by commas
                </p>
              </div>

              <!-- Link Expiration (if link method) -->
              <div v-if="form.method === 'link'">
                <label class="medical-form-label">Link Expires In</label>
                <select v-model="form.expiration" class="medical-input">
                  <option value="1">1 hour</option>
                  <option value="24">24 hours</option>
                  <option value="168">7 days</option>
                  <option value="720">30 days</option>
                </select>
              </div>

              <!-- Access Level -->
              <div>
                <label class="medical-form-label">Access Level</label>
                <select v-model="form.accessLevel" required class="medical-input">
                  <option value="">Select access level</option>
                  <option value="view">View Only</option>
                  <option value="download">View & Download</option>
                </select>
              </div>

              <!-- Message -->
              <div>
                <label class="medical-form-label">Message (Optional)</label>
                <textarea
                  v-model="form.message"
                  rows="3"
                  class="medical-input"
                  placeholder="Add a message for the recipients"
                ></textarea>
              </div>

              <!-- Password Protection -->
              <div>
                <label class="flex items-center">
                  <input
                    v-model="form.passwordProtected"
                    type="checkbox"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">Password protect this share</span>
                </label>
              </div>

              <!-- Password (if password protection enabled) -->
              <div v-if="form.passwordProtected">
                <label class="medical-form-label">Password</label>
                <input
                  v-model="form.password"
                  type="password"
                  required
                  class="medical-input"
                  placeholder="Enter password"
                  minlength="6"
                />
                <p class="mt-1 text-xs text-gray-500">
                  Minimum 6 characters
                </p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              <span v-if="!isLoading">
                {{ form.method === 'email' ? 'Send Email' : 'Generate Link' }}
              </span>
              <span v-else class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
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

  <!-- Success Modal -->
  <div
    v-if="showSuccess"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="success-modal-title"
    role="dialog"
    aria-modal="true"
  >
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex items-center">
            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-green-100">
              <CheckIcon class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="success-modal-title">
                {{ form.method === 'email' ? 'Email Sent!' : 'Link Generated!' }}
              </h3>
              <p class="text-sm text-gray-500">
                {{ successMessage }}
              </p>
            </div>
          </div>
          
          <!-- Generated Link (if link method) -->
          <div v-if="form.method === 'link' && generatedLink" class="mt-4">
            <label class="block text-xs font-medium text-gray-700 mb-1">Secure Link</label>
            <div class="flex">
              <input
                :value="generatedLink"
                readonly
                class="flex-1 text-xs p-2 border border-gray-300 rounded-l-md bg-gray-50"
              />
              <button
                type="button"
                class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 hover:bg-gray-100"
                @click="copyLink"
              >
                <ClipboardIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 px-4 py-3 sm:px-6">
          <button
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:text-sm"
            @click="closeSuccess"
          >
            Done
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  XMarkIcon,
  ShareIcon,
  CheckIcon,
  ClipboardIcon
} from '@heroicons/vue/24/outline'
import type { MedicalRecord } from '@/types/api.types'

interface Props {
  isOpen: boolean
  record: MedicalRecord | null
}

interface Emits {
  (e: 'close'): void
  (e: 'shared'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const isLoading = ref(false)
const showSuccess = ref(false)
const successMessage = ref('')
const generatedLink = ref('')

const form = ref({
  method: 'email',
  recipients: '',
  expiration: '24',
  accessLevel: '',
  message: '',
  passwordProtected: false,
  password: ''
})

// Methods
const handleClose = () => {
  if (!isLoading.value) {
    resetForm()
    emit('close')
  }
}

const resetForm = () => {
  form.value = {
    method: 'email',
    recipients: '',
    expiration: '24',
    accessLevel: '',
    message: '',
    passwordProtected: false,
    password: ''
  }
  showSuccess.value = false
  generatedLink.value = ''
}

const handleSubmit = async () => {
  if (!props.record) return

  isLoading.value = true

  try {
    // Mock API call
    await new Promise(resolve => setTimeout(resolve, 2000))

    if (form.value.method === 'email') {
      successMessage.value = `Record shared with ${form.value.recipients.split(',').length} recipient(s)`
    } else {
      generatedLink.value = `https://clinic.example.com/shared/${Math.random().toString(36).substr(2, 9)}`
      successMessage.value = 'Secure link generated and ready to share'
    }

    showSuccess.value = true
    emit('shared')
  } catch (error) {
    console.error('Share failed:', error)
  } finally {
    isLoading.value = false
  }
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(generatedLink.value)
  } catch (error) {
    console.error('Failed to copy link:', error)
  }
}

const closeSuccess = () => {
  showSuccess.value = false
  handleClose()
}
</script>
