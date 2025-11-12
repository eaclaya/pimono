<template>
  <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-end sm:p-6">
    <div class="flex w-full flex-col items-center space-y-4 sm:items-center">
      <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="modelValue"
          class="pointer-events-auto w-full max-w-sm rounded-lg bg-white shadow-lg outline outline-1 outline-black/5  dark:-outline-offset-1 dark:outline-white/10"
        >
          <div class="p-4">
            <div class="flex items-start">
              <div class="shrink-0">
                <CheckCircleIcon class="size-6 text-green-500" aria-hidden="true" />
              </div>
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900 ">{{ title }}</p>
                <p class="mt-1 text-sm text-gray-600 ">{{ message }}</p>
              </div>
              <div class="ml-4 flex shrink-0">
                <button
                  type="button"
                  class="inline-flex rounded-md text-gray-400 hover:text-gray-600 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600"
                  @click="closeToast"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="size-5" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { CheckCircleIcon } from '@heroicons/vue/24/outline'
import { XMarkIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Success',
  },
  message: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const closeToast = () => {
  emit('update:modelValue', false)
}
</script>
