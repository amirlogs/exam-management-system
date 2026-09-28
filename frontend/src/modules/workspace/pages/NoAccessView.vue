<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { ShieldAlert, RefreshCw, LogOut } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import { useAuthStore } from '@/stores/auth';
import { usePermissionsStore } from '@/stores/permission';
import { useUiStore } from '@/stores/ui';
import * as authapi from '@/api/auth';

withDefaults(
  defineProps<{
    title?: string;
    message?: string;
  }>(),
  {
    title: 'No Workspace Access',
    message: "Your account does not have any active role or workspace assignments yet. If an administrator assigns you a role, click Check Again to verify your access.",
  },
);

const router = useRouter();
const authStore = useAuthStore();
const permissionsStore = usePermissionsStore();
const uiStore = useUiStore();

const checking = ref(false);
const loggingOut = ref(false);

async function checkAgain() {
  checking.value = true;
  try {
    const res = await authapi.fetchCurrentUser();
    const data = res.data.data;
    authStore.setUser(data.user);
    permissionsStore.setPermissions(data.permissions);
    permissionsStore.setWorkspaces(data.workspaces);

    const hasAccess = Object.values(data.workspaces || {}).some(Boolean);
    if (hasAccess) {
      uiStore.showToast('Workspace access verified! Redirecting…', 'success');
      await permissionsStore.routeToWorkspace(router);
    } else {
      uiStore.showToast('No active role or workspace assignments found yet.', 'info');
    }
  } catch {
    uiStore.showToast('Failed to check account status. Please try again.', 'error');
  } finally {
    checking.value = false;
  }
}

async function handleLogout() {
  loggingOut.value = true;
  try {
    await authStore.logout();
    router.push({ name: 'login' });
  } finally {
    loggingOut.value = false;
  }
}
</script>

<template>
  <section id="no-access" class="min-h-screen flex items-center justify-center p-6 bg-bg">
    <div class="max-w-md w-full text-center bg-surface border border-border rounded-2xl p-8 shadow-sm space-y-6">
      <div class="w-14 h-14 mx-auto rounded-full bg-warning/10 border border-warning/20 flex items-center justify-center text-warning">
        <ShieldAlert :size="28" />
      </div>

      <div class="space-y-2">
        <h2 class="font-display font-bold text-xl text-text">{{ title }}</h2>
        <p class="text-sm text-text/60 leading-relaxed">{{ message }}</p>
      </div>

      <div class="space-y-3 pt-2">
        <BaseButton
          block
          :disabled="checking || loggingOut"
          :loading="checking"
          @click="checkAgain">
          <template #icon>
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': checking }" />
          </template>
          Check Again
        </BaseButton>

        <BaseButton
          variant="secondary"
          block
          :disabled="checking || loggingOut"
          :loading="loggingOut"
          @click="handleLogout">
          <template #icon>
            <LogOut class="w-4 h-4" />
          </template>
          Log Out
        </BaseButton>
      </div>
    </div>
  </section>
</template>
