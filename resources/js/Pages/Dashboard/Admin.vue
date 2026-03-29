<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const user = page.props.auth.user

const stats = [
    { label: 'Patients Today',     value: '--', sub: 'Live from queue',       color: '#1B4F9B' },
    { label: 'Pending Lab Results',value: '--', sub: 'Awaiting release',      color: '#2E75B6' },
    { label: 'Revenue Today',      value: '₱--', sub: 'Paid invoices',        color: '#0EA5E9' },
    { label: 'Queue Status',       value: '--', sub: 'Active patients',        color: '#0F2044' },
]
</script>

<template>
    <AppLayout title="Dashboard">

        <!-- Header -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">
                        Good day, {{ user?.name?.split(' ')[0] }}
                    </h1>
                    <p class="text-slate-500 text-sm mt-0.5">
                        Here's what's happening at St. Peter Diagnostics today.
                    </p>
                </div>
                <!-- Live indicator -->
                <div class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">
                    <span class="w-2 h-2 rounded-full animate-pulse" style="background-color:#0EA5E9" />
                    <span class="text-sm font-medium text-slate-600">Live System</span>
                </div>
            </div>
        </template>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div v-for="stat in stats" :key="stat.label"
                class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-slate-500 font-medium">{{ stat.label }}</p>
                        <p class="text-3xl font-bold mt-1" :style="{ color: stat.color }">
                            {{ stat.value }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1.5">{{ stat.sub }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                        :style="{ backgroundColor: stat.color + '15' }">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: stat.color }" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Modules card -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-base font-bold text-slate-800 mb-4">Quick Access</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <a v-for="mod in [
                        { label: 'Patients',    href: '/patients',    color: '#1B4F9B' },
                        { label: 'Reception',   href: '/reception',   color: '#2E75B6' },
                        { label: 'Laboratory',  href: '/laboratory',  color: '#0EA5E9' },
                        { label: 'X-Ray & UTZ', href: '/xray',        color: '#7C3AED' },
                        { label: 'Drug Test',   href: '/drug-test',   color: '#DC2626' },
                        { label: 'Users',       href: '/admin/users', color: '#0F2044' },
                    ]" :key="mod.label" :href="mod.href"
                        class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-slate-300 transition-colors cursor-pointer">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            :style="{ backgroundColor: mod.color + '20' }">
                            <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: mod.color }" />
                        </div>
                        <span class="text-sm font-medium text-slate-700">{{ mod.label }}</span>
                    </a>
                </div>
            </div>

            <!-- System Info card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-base font-bold text-slate-800 mb-4">System Info</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-sm text-slate-500">Logged in as</span>
                        <span class="text-sm font-semibold text-slate-800">{{ user?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-sm text-slate-500">Role</span>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full text-white"
                            style="background-color:#1B4F9B">
                            {{ user?.role }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-sm text-slate-500">System</span>
                        <span class="text-sm font-semibold text-emerald-600">● Online</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-slate-500">Version</span>
                        <span class="text-sm font-semibold text-slate-800">v4.0.0</span>
                    </div>
                </div>
            </div>

        </div>

    </AppLayout>
</template>
