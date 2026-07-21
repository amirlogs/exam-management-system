import { useAuthStore } from '@/stores/auth'
// import { usePermissionStore } from '@/stores/permission'
import type { NavigationGuard } from 'vue-router'

export const authGuard: NavigationGuard = (to) => {
  const authStore = useAuthStore()
  if (to.meta.requiresAuth && !authStore.user) {
    return { name: 'login' }
  }
}

export const permissionGuard: NavigationGuard = (to) => {
  // const permissionStore = usePermissionStore()
  const required = to.meta.permission as string | undefined
  // if (required && !permissionStore.can(required)) {
    return { path: '/unauthorized' }
  }
}
