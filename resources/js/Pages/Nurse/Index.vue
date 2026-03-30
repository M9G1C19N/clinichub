<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    Activity, Clock, CheckCircle2,
    ClipboardList, AlertTriangle, User,
} from 'lucide-vue-next'

defineProps({
    queue: Array,
})

const priorityConfig = {
    urgent:   { class: 'bg-red-500 text-white',       label: 'URGENT' },
    pregnant: { class: 'bg-pink-100 text-pink-700',   label: 'PREGNANT' },
    pwd:      { class: 'bg-blue-100 text-blue-700',   label: 'PWD' },
    senior:   { class: 'bg-amber-100 text-amber-700', label: 'SENIOR' },
}

const visitTypeLabel = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    follow_up:      'Follow-up',
    lab_only:       'Lab Only',
}

const visitTypeBadge = {
    opd:            'bg-blue-100 text-blue-700',
    pre_employment: 'bg-purple-100 text-purple-700',
    follow_up:      'bg-amber-100 text-amber-700',
    lab_only:       'bg-teal-100 text-teal-700',
}


// ── Auto-refresh every 10 seconds ─────────────────
let refreshTimer = null
const lastRefreshed = ref(new Date())

onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({
            only: ['queue'],
            preserveScroll: true,
            onSuccess: () => { lastRefreshed.value = new Date() }
        })
    }, 10000)
})

onUnmounted(() => {
    clearInterval(refreshTimer)
})

const timeAgo = () => {
    const secs = Math.floor((new Date() - lastRefreshed.value) / 1000)
    if (secs < 5)  return 'just now'
    if (secs < 60) return `${secs}s ago`
    return 'over a minute ago'
}
function refresh() {
    router.reload({
        only: ['queue'],
        preserveScroll: true,
        onSuccess: () => { lastRefreshed.value = new Date() }
    })
}
</script>

<template>
    <AppLayout title="Nurse Intake">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Nurse Intake</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Interview Room · {{ queue.length }} patient(s) in queue
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Live indicator with pulse animation -->
                    <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-xs text-emerald-600 font-medium">
                            Live · refreshes every 10s
                        </span>
                    </div>
                    <Button variant="outline" size="sm" class="gap-2" @click="refresh">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh Now
                    </Button>
                </div>
            </div>
        </template>

        <!-- Empty -->
        <div v-if="queue.length === 0"
            class="bg-card rounded-2xl border shadow-sm py-20 text-center">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-emerald-50">
                <CheckCircle2 class="w-8 h-8 text-emerald-400"/>
            </div>
            <p class="text-lg font-semibold text-slate-500">No patients waiting</p>
            <p class="text-sm text-slate-400 mt-1">Interview Room queue is clear</p>
        </div>

        <!-- Queue Cards -->
        <div v-else class="space-y-3">
            <div v-for="(assignment, i) in queue" :key="assignment.id"
                class="bg-card rounded-2xl border shadow-sm overflow-hidden transition-all"
                :class="assignment.status === 'serving' ? 'ring-2 ring-emerald-400' :
                        assignment.status === 'calling' ? 'ring-2 ring-blue-400' : ''">

                <!-- Status bar -->
                <div class="h-1 w-full"
                    :style="{
                        background:
                            assignment.status === 'serving' ? '#10B981' :
                            assignment.status === 'calling' ? '#3B82F6' : '#E2E8F0'
                    }"/>

                <div class="flex items-center gap-0 p-5">

                    <!-- Queue Number -->
                    <div class="flex flex-col items-center justify-center w-24 flex-shrink-0 pr-5 border-r border-slate-100">
                        <p class="text-xs text-muted-foreground mb-1"># {{ i + 1 }}</p>
                        <p class="text-3xl font-black font-mono text-emerald-600 leading-none">
                            {{ assignment.queue_number }}
                        </p>
                        <span :class="[
                            'mt-2 text-xs font-semibold px-2 py-0.5 rounded-full',
                            assignment.status === 'serving'  ? 'bg-emerald-100 text-emerald-700' :
                            assignment.status === 'calling'  ? 'bg-blue-100 text-blue-700' :
                                                               'bg-slate-100 text-slate-600'
                        ]">
                            {{ assignment.status }}
                        </span>
                    </div>

                    <!-- Patient Info -->
                    <div class="flex-1 min-w-0 px-5">
                        <div class="flex items-center gap-2 mb-1.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                style="background:#1B4F9B">
                                {{ assignment.patient.full_name.charAt(0) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-base font-bold text-slate-800">
                                        {{ assignment.patient.full_name }}
                                    </p>
                                    <span v-if="priorityConfig[assignment.priority]"
                                        :class="['text-xs font-bold px-2 py-0.5 rounded-full',
                                            priorityConfig[assignment.priority].class]">
                                        {{ priorityConfig[assignment.priority].label }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ assignment.patient.patient_code }} · {{ assignment.patient.age_sex }}
                                </p>
                            </div>
                        </div>

                        <!-- Visit type + vitals status -->
                        <div class="flex items-center gap-2 mt-2">
                            <span v-if="assignment.visit"
                                :class="['text-xs font-semibold px-2.5 py-1 rounded-full',
                                    visitTypeBadge[assignment.visit.visit_type]]">
                                {{ visitTypeLabel[assignment.visit.visit_type] }}
                            </span>

                            <!-- Vitals status -->
                            <span v-if="assignment.visit?.has_vitals"
                                class="flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-full">
                                <CheckCircle2 class="w-3 h-3"/>
                                Vitals Recorded
                            </span>
                            <span v-else
                                class="flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-full">
                                <AlertTriangle class="w-3 h-3"/>
                                Vitals Pending
                            </span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="flex-shrink-0 pl-5">
                        <Link v-if="assignment.visit?.id"
                            :href="route('nurse.vitals', assignment.visit.id)">
                            <Button
                                :class="assignment.visit?.has_vitals
                                    ? 'gap-2'
                                    : 'gap-2 text-white'"
                                :variant="assignment.visit?.has_vitals ? 'outline' : 'default'"
                                :style="!assignment.visit?.has_vitals ? 'background-color:#1B4F9B' : ''">
                                <ClipboardList class="w-4 h-4"/>
                                {{ assignment.visit?.has_vitals ? 'Update Vitals' : 'Record Vitals' }}
                            </Button>
                        </Link>
                    </div>

                </div>
            </div>
        </div>

    </AppLayout>
</template>
