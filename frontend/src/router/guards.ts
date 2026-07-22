import { useAuthStore } from '@/stores/auth'
// import { usePermissionStore } from '@/stores/permission'
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

// export const permissionGuard: NavigationGuard = (to) => {
//   const permissionStore = usePermissionStore()
//   const required = to.meta.permission as string | undefined
//   if (required && !permissionStore.can(required)) {
//     return { path: '/unauthorized' }
//   }
// }
