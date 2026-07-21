<template>
    <div class="min-h-[80vh] flex items-center justify-center">
        <div class="w-full max-w-md bg-surface border border-border rounded-xl p-10 text-center">
            <div class="w-16 h-16 mx-auto mb-6 rounded-xl bg-red-50 flex items-center justify-center">
                <ShieldAlert class="w-7 h-7 text-red-600" />
            </div>

            <h1 class="text-2xl font-bold text-text mb-3">Unauthorized Access</h1>
            <div class="w-10 h-0.5 bg-accent mx-auto mb-5" />

            <p class="text-text/60 text-sm leading-relaxed mb-6">
                You do not have the necessary security clearance or administrative privileges
                to view this resource or perform this action. Error Code: 403.
            </p>

            <div class="space-y-3">
                <BaseButton block @click="$router.push('/admin/dashboard')">
                    Return to Dashboard
                </BaseButton>
                <BaseButton variant="secondary" block @click="$router.push('/support')">
                    Contact Administrator
                </BaseButton>
            </div>

            <hr class="border-border my-6" />

            <div class="text-xs text-text/40 space-y-1 text-left">
                <p>Session ID: {{ sessionId }}</p>
                <p>Timestamp: {{ timestamp }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ShieldAlert } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'

// Placeholder values — wire these to your real request/session tracking
const sessionId = 'REQ-8921-A7'
const timestamp = new Date().toISOString().replace('T', ' ').slice(0, 16) + ' UTC'
</script>
