import * as authapi from '@/api/auth';
import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Router } from 'vue-router';
import { usePermissionsStore } from './permission';

export const useAuthStore = defineStore('userAuth', () => {
  const user = ref<Record<string, any> | null>(null);
  const token = ref<string | null>(localStorage.getItem('auth_token'));

  const error = ref('');
  const loading = ref(false);
  const isInitializing = ref(true);

  const login = async (email: string, password: string) => {
    error.value = '';
    loading.value = true;
    try {
      const response = await authapi.login(email, password);
      const { token: authToken, user: userData } = response.data.data;
      user.value = userData;
      token.value = authToken;
      console.log(response.data.data, 'response is came');
      usePermissionsStore().setPermissions(response.data.data.permissions);
      usePermissionsStore().setWorkspaces(response.data.data.workspaces);
      usePermissionsStore().setActiveWorkspace(response.data.data.user.default_workspace);
      localStorage.setItem('auth_token', authToken);
      return true;
    } catch (err) {
      // error.value = 'Invalid email or password. Please try again.'
      error.value = err?.response?.data?.message || 'Something went wrong. Please try again.';
      console.error(err);
      return false;
    } finally {
      loading.value = false;
    }
    //function which will take token and set to the state
  };

  async function initializeAuth(router: Router) {
    if (!token.value) {
      isInitializing.value = false;
      return;
    }
    try {
      const response = await authapi.fetchCurrentUser();
      user.value = response.data.data.user;
      usePermissionsStore().setPermissions(response.data.data.permissions);
      usePermissionsStore().setWorkspaces(response.data.data.workspaces);

      if (response.data.data.user?.default_workspace) {
        const workspace = response.data.data.user.default_workspace;
        usePermissionsStore().setActiveWorkspace(workspace);
        return true;
      } else {
        usePermissionsStore().routeToWorkspace(router);
      }
    } catch (err) {
      user.value = null;
      token.value = null;
      localStorage.removeItem('auth_token');
      console.error(err);
    } finally {
      isInitializing.value = false;
    }
  }

  async function logout() {
    try {
      await authapi.logout();
    } finally {
      user.value = null;
      token.value = null;
      localStorage.removeItem('auth_token');
      usePermissionsStore().clear();
    }
  }

  return { user, token, error, loading, isInitializing, login, logout, initializeAuth };
});
