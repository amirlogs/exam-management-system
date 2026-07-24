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

// import { usePermissionsStore } from '@/stores/permission' // renamed, plural now

// export const permissionGuard: NavigationGuard = (to) => {
//   const permissionsStore = usePermissionsStore()
//   if (to.meta.permission && !permissionsStore.can(to.meta.permission)) {
//     return { path: '/unauthorized' }
//   }
// }
