<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { usePage } from '@inertiajs/vue3'
import { ClipboardList, Activity } from 'lucide-vue-next'

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
                    <p class="text-slate-400 text-xs mt-0.5">Nurse · Interview Room</p>
                </div>
                <span class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl">
                    <Activity class="w-3.5 h-3.5"/>
                    Live System
                </span>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            <!-- Go to Nurse Intake -->
            <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <ClipboardList class="w-6 h-6 text-emerald-600"/>
                </div>
                <div class="flex-1">
                    <p class="text-base font-bold text-slate-800">Nurse Intake</p>
                    <p class="text-xs text-slate-400 mt-0.5">View queue and record patient vitals</p>
                </div>
                <Link :href="route('nurse.index')">
                    <Button style="background-color:#1B4F9B" size="sm">Open</Button>
                </Link>
            </div>

            <!-- Room Screen -->
            <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                    <Activity class="w-6 h-6 text-blue-600"/>
                </div>
                <div class="flex-1">
                    <p class="text-base font-bold text-slate-800">Interview Room Screen</p>
                    <p class="text-xs text-slate-400 mt-0.5">Live queue display for the room</p>
                </div>
                <a :href="route('queue.room', 'interview_room')" target="_blank">
                    <Button variant="outline" size="sm">Open Screen</Button>
                </a>
            </div>

        </div>
    </AppLayout>
</template>
