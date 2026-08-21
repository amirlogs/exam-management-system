import { setWorkspace } from '@/api/auth'
import type { WorkspaceState } from '@/modules/instructor/pages/question-bank/types'
import router from '@/router'
import trueCouter from '@/shared/utils/trueCounter'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Router } from 'vue-router'
import { useAuthStore } from './auth'
import { useNavigationStore } from './navigation'

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

  function can(permission: string) {
    return permissions.value.includes(permission)
  }

  function clear() {
    permissions.value = []
  }

  const setActiveWorkspace = async (workspace: string) => {
    const response = await setWorkspace(workspace)
    activeWorkspace.value = workspace
    useNavigationStore().fetchNavigation(workspace)
  }

  const routeToWorkspace = async (router: Router) => {
    console.log('routeToWorkspace')
    if (!workspaces.value) {
      useAuthStore().initializeAuth(router)
    }
    if (!workspaces.value) {
      return router.push({ name: 'no-access' })
    }
    const workspace = trueCouter(workspaces.value)
    if (typeof workspace === 'string') {
      setActiveWorkspace(workspace)
      return router.push(`/${workspace}/dashboard`)
    } else {
      return router.replace('/select-workspace')
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
