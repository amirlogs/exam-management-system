import * as authapi from '@/api/auth'
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNavigationStore = defineStore('navigation', () => {
  const items = ref([])
  const loading = ref(false)

  async function fetchNavigation(workspace: string) {
    loading.value = true

    try {
      const response = await authapi.allowedRoutes(workspace)
      items.value = response.data.data
    } finally {
      loading.value = false
    }
  }

  function clearNavigation() {
    items.value = []
  }

  return {
    items,
    loading,
    fetchNavigation,
    clearNavigation,
  }
})
