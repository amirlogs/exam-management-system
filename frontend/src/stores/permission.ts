import type { WorkspaceState } from '@/modules/instructor/pages/question-bank/types'
import trueCouter from '@/shared/utils/trueCounter'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Router } from 'vue-router'
import { useAuthStore } from './auth'

export const usePermissionsStore = defineStore('permissions', () => {
  const permissions = ref<string[]>([])
  const workspaces = ref<WorkspaceState>()
  const activeWorkspace = ref<string>()
  const setPermissions = (newPermissions: string[]) => {
    permissions.value = [...newPermissions]
  }
  const setWorkspaces = (newWorkspaces: WorkspaceState) => {
    workspaces.value = newWorkspaces
  }

  // error.value = 'Invalid email or password. Please try again.'
  function can(permission: string) {
    return permissions.value.includes(permission)
  }

  function clear() {
    permissions.value = []
  }

  function setActiveWorkspace(workspace: string) {
    activeWorkspace.value = workspace
  }

  const routeToWorkspace = async (router: Router) => {
    if (!workspaces.value) {
      useAuthStore().initializeAuth()
    }
    if (!workspaces.value) {
      return router.push({ name: 'no-access' })
    }
    const workspace = trueCouter(workspaces.value)
    if (typeof workspace === 'string') {
      activeWorkspace.value = workspace
      return router.push(`/${workspace}/dashboard`)
    } else {
      return router.push({
        name: 'select-workspace',
        state: {
          workspaces: workspaces.value,
        },
      })
    }
  }

  return {
    permissions,
    setPermissions,
    can,
    clear,
    activeWorkspace,
    setActiveWorkspace,
    setWorkspaces,
    routeToWorkspace,
    workspaces,
  }
})
