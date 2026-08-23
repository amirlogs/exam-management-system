import { usePermissionsStore } from '@/stores/permission'
import type { Directive } from 'vue'

type Permission = string
type PermissionValue = Permission | Permission[]

type CanMode = 'any' | 'all' | 'none'

function hasPermission(permissionsStore: ReturnType<typeof usePermissionsStore>, value: PermissionValue, mode: CanMode = 'all'): boolean {
  const permissions = Array.isArray(value) ? value : [value]

  if (permissions.length === 0) {
    return false
  }

  if (mode === 'any') {
    return permissions.some((permission) => permissionsStore.hasPermission(permission))
  }

  if (mode === 'none') {
    return permissions.every((permission) => !permissionsStore.hasPermission(permission))
  }

  // Default: all
  return permissions.every((permission) => permissionsStore.hasPermission(permission))
}

function updateVisibility(el: HTMLElement, value: PermissionValue, mode: CanMode) {
  const permissionsStore = usePermissionsStore()

  const allowed = hasPermission(permissionsStore, value, mode)

  el.style.display = allowed ? '' : 'none'
}

export const can: Directive<HTMLElement, PermissionValue> = {
  mounted(el, binding) {
    updateVisibility(el, binding.value, (binding.arg as CanMode) || 'all')
  },

  updated(el, binding) {
    updateVisibility(el, binding.value, (binding.arg as CanMode) || 'all')
  },
}
