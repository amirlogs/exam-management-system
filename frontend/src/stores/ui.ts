import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const isLoading = ref(false)
  const toasts = ref<{ id: number; message: string; variant: 'success' | 'error' | 'info' }[]>([])

  function showToast(message: string, variant: 'success' | 'error' | 'info' = 'info') {
    const id = Date.now()
    toasts.value.push({ id, message, variant })
    setTimeout(() => {
      toasts.value = toasts.value.filter((t) => t.id !== id)
    }, 3000)
  }

  return { isLoading, toasts, showToast }
})
