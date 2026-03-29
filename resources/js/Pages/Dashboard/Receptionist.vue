<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'

const { user } = usePermissions()

defineProps({ role: String })
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-navy">
                    Good morning, {{ user?.name?.split(' ')[0] }} 👋
                </h1>
                <p class="text-slate-500 text-sm mt-0.5">
                    Here's what's happening at St. Peter Diagnostics today.
                </p>
            </div>
        </template>

        <!-- Dashboard Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl shadow-card p-5 border border-sky">
                <p class="text-sm text-slate-500 font-medium">Patients Today</p>
                <p class="text-3xl font-bold text-navy mt-1">--</p>
                <p class="text-xs text-mid-blue mt-2">Live from queue</p>
            </div>
            <div class="bg-white rounded-2xl shadow-card p-5 border border-sky">
                <p class="text-sm text-slate-500 font-medium">Pending Lab Results</p>
                <p class="text-3xl font-bold text-navy mt-1">--</p>
                <p class="text-xs text-mid-blue mt-2">Awaiting release</p>
            </div>
            <div class="bg-white rounded-2xl shadow-card p-5 border border-sky">
                <p class="text-sm text-slate-500 font-medium">Revenue Today</p>
                <p class="text-3xl font-bold text-navy mt-1">₱--</p>
                <p class="text-xs text-mid-blue mt-2">Paid invoices</p>
            </div>
            <div class="bg-white rounded-2xl shadow-card p-5 border border-sky">
                <p class="text-sm text-slate-500 font-medium">Queue Status</p>
                <p class="text-3xl font-bold text-navy mt-1">--</p>
                <p class="text-xs text-accent mt-2 flex items-center gap-1">
                    <span class="inline-block w-2 h-2 bg-accent rounded-full animate-pulse" />
                    Live
                </p>
            </div>
        </div>
    </AppLayout>
</template>
