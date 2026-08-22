import { usePermissionsStore } from '@/stores/permission'
import type { Directive } from 'vue'

export const can: Directive = {
  mounted(el, binding) {
    const permissionsStore = usePermissionsStore()

    if (!permissionsStore.hasPermission(binding.value)) {
      el.remove()
    }
  },
}
