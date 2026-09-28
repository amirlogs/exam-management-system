<script setup lang="ts">
import { ref } from 'vue';
import { AlertCircle, CheckCircle2, KeyRound } from 'lucide-vue-next';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import AlertBanner from '@/shared/components/ui/AlertBanner.vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { firstTimePassword } from '@/api/auth';
import { loginSchema, firstTimePasswordSchema } from '../schemas';
import { useRouter } from 'vue-router';
import { usePermissionsStore } from '@/stores/permission';

const authStore = useAuthStore();
const uiStore = useUiStore();
const permissionsStore = usePermissionsStore();
const router = useRouter();

// Standard Login State
const email = ref('');
const password = ref('');
const fieldErrors = ref<Record<string, string>>({});
const successBanner = ref('');

// First-Time Password Change State
const isFirstTimeMode = ref(false);
const firstTimeEmail = ref('');
const tempPassword = ref('');
const newPassword = ref('');
const newPasswordConfirmation = ref('');
const firstTimeErrors = ref<Record<string, string>>({});
const firstTimeErrorBanner = ref('');
const submittingFirstTime = ref(false);

async function onLoginSubmit() {
  fieldErrors.value = {};
  authStore.error = '';
  successBanner.value = '';

  const result = loginSchema.safeParse({ email: email.value, password: password.value });

  if (!result.success) {
    result.error.issues.forEach((issue) => {
      const field = issue.path.join('.');
      if (!fieldErrors.value[field]) fieldErrors.value[field] = issue.message;
    });
    return;
  }

  const res = await authStore.login(result.data.email, result.data.password);
  if (!res) return;

  // Intercept first-time login: NO TOKEN ISSUED
  if (typeof res === 'object' && res.requiresPasswordChange) {
    isFirstTimeMode.value = true;
    firstTimeEmail.value = res.email || email.value;
    tempPassword.value = password.value;
    newPassword.value = '';
    newPasswordConfirmation.value = '';
    firstTimeErrors.value = {};
    firstTimeErrorBanner.value = '';
    return;
  }

  await permissionsStore.routeToWorkspace(router);
}

async function onFirstTimeSubmit() {
  firstTimeErrors.value = {};
  firstTimeErrorBanner.value = '';

  const result = firstTimePasswordSchema.safeParse({
    new_password: newPassword.value,
    new_password_confirmation: newPasswordConfirmation.value,
  });

  if (!result.success) {
    result.error.issues.forEach((issue) => {
      const field = issue.path.join('.');
      if (!firstTimeErrors.value[field]) firstTimeErrors.value[field] = issue.message;
    });
    return;
  }

  submittingFirstTime.value = true;
  try {
    await firstTimePassword({
      email: firstTimeEmail.value,
      current_password: tempPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: newPasswordConfirmation.value,
    });

    isFirstTimeMode.value = false;
    email.value = firstTimeEmail.value;
    password.value = '';
    tempPassword.value = '';
    successBanner.value = 'Password changed successfully! You can now log in with your new password.';
    uiStore.showToast('Password changed successfully! Please log in with your new password.', 'success');
  } catch (err: any) {
    firstTimeErrorBanner.value = err?.response?.data?.message || 'Failed to update password. Please check your inputs.';
  } finally {
    submittingFirstTime.value = false;
  }
}

function cancelFirstTimeMode() {
  isFirstTimeMode.value = false;
  tempPassword.value = '';
  newPassword.value = '';
  newPasswordConfirmation.value = '';
  firstTimeErrors.value = {};
  firstTimeErrorBanner.value = '';
}
</script>

<template>
  <div class="w-full max-w-110 bg-surface border border-border rounded-2xl shadow-sm p-10">
    <div class="flex flex-col items-center mb-8">
      <div class="w-16 h-16 rounded-full bg-bg flex items-center justify-center mb-4 border border-border text-accent">
        <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
          <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 2.67L18.09 9 12 12.33 5.91 9 12 5.67zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z" />
        </svg>
      </div>
      <h1 class="text-2xl font-display font-semibold text-accent text-center">University Exam Management</h1>
    </div>

    <!-- 1. Standard Login Form -->
    <form v-if="!isFirstTimeMode" class="space-y-5" @submit.prevent="onLoginSubmit">
      <!-- Success Banner (e.g. after changing password) -->
      <AlertBanner v-if="successBanner" variant="success">
        <template #icon>
          <CheckCircle2 class="w-4 h-4 shrink-0 text-success" />
        </template>
        {{ successBanner }}
      </AlertBanner>

      <div>
        <BaseInput v-model="email" label="Email Address" type="email" placeholder="example@email.com" :error="fieldErrors.email" />
      </div>
      <div>
        <BaseInput v-model="password" label="Password" type="password" placeholder="••••••••" :error="fieldErrors.password" />
      </div>

      <AlertBanner v-if="authStore.error" variant="error">
        <template #icon>
          <AlertCircle class="w-4 h-4 shrink-0" />
        </template>
        {{ authStore.error }}
      </AlertBanner>

      <BaseButton type="submit" block :disabled="authStore.loading">
        {{ authStore.loading ? 'Logging in…' : 'Log In' }}
      </BaseButton>

      <div class="text-center">
        <router-link to="/forgot-password" class="text-sm font-medium text-accent hover:text-accent-hover transition-colors">
          Forgot password?
        </router-link>
      </div>
    </form>

    <!-- 2. First-Time Password Change Form (No Token Issued) -->
    <form v-else class="space-y-5" @submit.prevent="onFirstTimeSubmit">
      <div class="rounded-xl border border-accent/20 bg-accent/5 p-4 text-xs text-text/80 space-y-1">
        <div class="font-semibold text-accent flex items-center gap-1.5">
          <KeyRound class="w-4 h-4 shrink-0" />
          First-Time Login Security
        </div>
        <p class="leading-relaxed">
          Welcome! For security purposes, you must replace your temporary initial password with a permanent private password before accessing your account.
        </p>
      </div>

      <div>
        <label class="block text-xs font-medium text-text/60 mb-1">Account Email</label>
        <div class="text-sm font-medium text-text bg-bg border border-border rounded-md px-3.5 py-2.5">
          {{ firstTimeEmail }}
        </div>
      </div>

      <div>
        <BaseInput
          v-model="newPassword"
          label="New Password"
          type="password"
          placeholder="At least 8 characters"
          :error="firstTimeErrors.new_password" />
      </div>

      <div>
        <BaseInput
          v-model="newPasswordConfirmation"
          label="Confirm New Password"
          type="password"
          placeholder="Repeat new password"
          :error="firstTimeErrors.new_password_confirmation" />
      </div>

      <AlertBanner v-if="firstTimeErrorBanner" variant="error">
        <template #icon>
          <AlertCircle class="w-4 h-4 shrink-0" />
        </template>
        {{ firstTimeErrorBanner }}
      </AlertBanner>

      <div class="space-y-2 pt-1">
        <BaseButton type="submit" block :disabled="submittingFirstTime">
          {{ submittingFirstTime ? 'Saving Password…' : 'Save New Password' }}
        </BaseButton>

        <BaseButton variant="ghost" block type="button" @click="cancelFirstTimeMode">
          Back to Login
        </BaseButton>
      </div>
    </form>
  </div>
</template>
