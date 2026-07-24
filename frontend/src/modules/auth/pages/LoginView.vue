<script setup lang="ts">
import { ref } from 'vue'
import { AlertCircle } from 'lucide-vue-next'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import AlertBanner from '@/shared/components/ui/AlertBanner.vue'
import { useAuthStore } from '@/stores/auth'
import { loginSchema } from '../schemas'
import { useRouter } from 'vue-router'
import { usePermissionsStore } from '@/stores/permission'

const authStore = useAuthStore()
const permissionsStore = usePermissionsStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const workspace = ref<string[]>([])
const fieldErrors = ref<Record<string, string>>({})

async function onSubmit() {
    fieldErrors.value = {}
    authStore.error = '';

    const result = loginSchema.safeParse({ email: email.value, password: password.value })

    if (!result.success) {

        result.error.issues.forEach((issue) => {
            const field = issue.path.join('.')
            if (!fieldErrors.value[field]) fieldErrors.value[field] = issue.message
        })
        return
    }

    const success = await authStore.login(result.data.email, result.data.password)
    if (success) {
        workspace.value = permissionsStore.getVisibleWorkspaces();
        if (workspace.value.length === 1) {
            router.push(`/${workspace.value[0]}`)
        } else {
            router.push('/select-workspace')
        }
    }
}
</script>

<template>
    <div class="w-full max-w-110 bg-surface border border-border rounded-2xl shadow-sm p-10">
        <div class="flex flex-col items-center mb-8">
            <div
                class="w-16 h-16 rounded-full bg-bg flex items-center justify-center mb-4 border border-border text-accent">
                <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
                    <path
                        d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 2.67L18.09 9 12 12.33 5.91 9 12 5.67zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z" />
                </svg>
            </div>
            <h1 class="text-2xl font-display font-semibold text-accent">University Exam Management System</h1>
        </div>

        <form class="space-y-5" @submit.prevent="onSubmit">
            <div>
                <BaseInput v-model="email" label="Email Address" type="email" placeholder="example@email.com"
                    :error="fieldErrors.email" />
                <p v-if="fieldErrors.email" class="text-xs text-red-600 mt-1">{{ fieldErrors.email }}</p>
            </div>

            <div>
                <BaseInput v-model="password" label="Password" type="password" placeholder="••••••••"
                    :error="fieldErrors.password" />
                <p v-if="fieldErrors.password" class="text-xs text-red-600 mt-1">{{ fieldErrors.password }}</p>
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
                <router-link to="/forgot-password"
                    class="text-sm font-medium text-accent hover:text-accent-hover transition-colors">
                    Forgot password?
                </router-link>
            </div>
        </form>
    </div>
</template>
