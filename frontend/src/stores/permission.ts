import { setWorkspace } from '@/api/auth';
import type { WorkspaceState } from '@/modules/instructor/pages/question-bank/types';
import { getSingleRoute, trueCouter } from '@/shared/utils/trueCounter';
import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Router } from 'vue-router';
import { useAuthStore } from './auth';
import { useNavigationStore } from './navigation';

export const usePermissionsStore = defineStore('permissions', () => {
  const permissions = ref<string[]>([]);
  const workspaces = ref<WorkspaceState>();
  const activeWorkspace = ref<string>();
  const setPermissions = (newPermissions: string[]) => {
    permissions.value = [...newPermissions];
  };
  const setWorkspaces = (newWorkspaces: WorkspaceState) => {
    workspaces.value = newWorkspaces;
  };

  function hasPermission(permission: string) {
    return permissions.value.includes(permission);
  }

  function hasAnyPermission(required: string[]): boolean {
    return required.some((permission) => permissions.value.includes(permission));
  }

  function hasAllPermissions(required: string[]): boolean {
    return required.every((permission) => permissions.value.includes(permission));
  }

  function clear() {
    permissions.value = [];
  }

  const setActiveWorkspace = async (workspace: string) => {
    await setWorkspace(workspace);
    activeWorkspace.value = workspace;
    useNavigationStore().fetchNavigation(workspace);
  };

  const routeToWorkspace = async (router: Router) => {
    if (!workspaces.value) {
      useAuthStore().initializeAuth(router);
    }
    // if (!workspaces.value) {
    //   return router.push({ name: 'no-access' });
    // }

    const workspaceNumber = trueCouter(workspaces.value);

    console.log(workspaceNumber);
    if (workspaceNumber === 0) {
      return router.push({ name: 'no-access' });
    } else if (workspaceNumber === 1) {
      if (activeWorkspace.value) {
        return router.push(`/${activeWorkspace.value}/dashboard`);
      }
      const workspace = getSingleRoute(workspaces.value);
      setActiveWorkspace(workspace);
      return router.push(`/${workspace}/dashboard`);
    } else {
      if (activeWorkspace.value) {
        return router.push(`/${activeWorkspace.value}/dashboard`);
      }
      return router.push({ name: 'select-workspace' });
    }
  };

  return {
    permissions,
    setPermissions,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    clear,
    activeWorkspace,
    setActiveWorkspace,
    setWorkspaces,
    routeToWorkspace,
    workspaces,
  };
});
