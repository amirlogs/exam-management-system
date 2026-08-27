import { useAuthStore } from '@/stores/auth'
import { usePermissionsStore } from '@/stores/permission'
import type { NavigationGuard } from 'vue-router'

export const authGuard: NavigationGuard = (to) => {
  const authStore = useAuthStore()
  const isLoggedIn = !!authStore.user

  if (to.meta.requiresAuth && !authStore.user) {
    return { name: 'login' }
  }
  if (to.meta.guestOnly && isLoggedIn) {
    return { name: 'select-workspace' }
  }
  return true
}

export const permissionGuard: NavigationGuard = (to) => {
  const permissionsStore = usePermissionsStore()
  const permissions = to.meta.permissions
  if (!Array.isArray(permissions) || permissions.length === 0) {
    return
  }
  const mode = to.meta.permissionMode ?? 'any'
  
  if (mode === 'all') {
    const hasPermission = permissions.every((permission) => permissionsStore.hasPermission(permission))
    if (!hasPermission) {
      return { path: '/unauthorized' }
    }
  } else {
    const hasPermission = permissions.some((permission) => permissionsStore.hasPermission(permission))

    if (!hasPermission) {
      return { path: '/unauthorized' }
    }
  }
}
