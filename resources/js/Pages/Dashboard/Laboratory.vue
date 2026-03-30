<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { Activity } from 'lucide-vue-next'

const page = usePage()
const user = page.props.auth.user
</script>
<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">
                        Good day, {{ user?.name?.split(' ')[0] }} 👋
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">{{ user?.role?.replace('_', ' ') }}</p>
                </div>
                <span class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl">
                    <Activity class="w-3.5 h-3.5"/>
                    Live System
                </span>
            </div>
        </template>
        <div class="bg-card rounded-xl border shadow-sm py-20 text-center">
            <p class="text-slate-400">Dashboard widgets coming soon for this role.</p>
        </div>
    </AppLayout>
</template>
