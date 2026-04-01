<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import QueueBadge from '@/Components/Dashboard/QueueBadge.vue'
import VisitTypeBadge from '@/Components/Dashboard/VisitTypeBadge.vue'
import {
    Activity, Heart, ClipboardList, Users, Clock,
    CheckCircle2, AlertTriangle, RefreshCw, Wifi,
    ChevronRight, ArrowRight, Star
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    role: String,
    stats: Object,
    queue: Array,
    visitTypeCounts: Object,
    backlog: Array,
})

const visitTypeLabels = {
    opd: 'OPD', pre_employment: 'Pre-Employment', annual_pe: 'Annual PE',
    exit_pe: 'Exit PE', follow_up: 'Follow-Up', lab_only: 'Lab Only',
}

const priorityConfig = {
    urgent:   { label: 'Urgent',   bg: 'bg-red-100',   text: 'text-red-700',   dot: 'bg-red-500' },
    senior:   { label: 'Senior',   bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    pwd:      { label: 'PWD',      bg: 'bg-purple-100',text: 'text-purple-700',dot: 'bg-purple-500' },
    pregnant: { label: 'Pregnant', bg: 'bg-pink-100',  text: 'text-pink-700',  dot: 'bg-pink-500' },
    regular:  { label: 'Regular',  bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-400' },
}

const formatWait = (mins) => {
    if (mins < 60) return `${mins}m`
    return `${Math.floor(mins / 60)}h ${mins % 60}m`
}

let timer = null
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['stats', 'queue'] })
    }, 10000)
})
onUnmounted(() => clearInterval(timer))

const refresh = () => router.reload({ only: ['stats', 'queue', 'backlog'] })
</script>

<template>
    <AppLayout title="Nurse Dashboard">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#0F2044]">Nurse Intake</h1>
                <p class="text-sm text-slate-400 mt-0.5">Patient vitals and medical history</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
                    <Wifi class="w-3 h-3" /> <span class="font-semibold">Auto-refresh 10s</span>
                </div>
                <button @click="refresh" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg transition-colors">
                    <RefreshCw class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard label="In Queue" :value="stats.in_queue" :icon="Activity" color="#1B4F9B" sub="Interview room today" />
            <StatCard label="Vitals Taken" :value="stats.vitals_taken" :icon="Heart" color="#059669" :success="stats.vitals_taken > 0" sub="Completed today" />
            <StatCard label="Pending Vitals" :value="stats.pending" :icon="ClipboardList" color="#D97706" :warning="stats.pending > 0" sub="Need intake today" />
            <StatCard label="PE Patients" :value="stats.pe_today" :icon="Star" color="#7C3AED" sub="Pre-employ / Annual / Exit" />
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Queue (main panel) -->
            <div class="col-span-3 lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50 flex items-center gap-3">
                        <h3 class="font-bold text-[#0F2044] flex-1">Today's Interview Room Queue</h3>
                        <span class="text-xs text-slate-400">{{ queue?.length ?? 0 }} patient(s)</span>
                    </div>

                    <!-- Queue list -->
                    <div class="divide-y divide-slate-50">
                        <div
                            v-for="entry in queue" :key="entry.id"
                            class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors"
                        >
                            <!-- Queue number + status -->
                            <div class="flex-shrink-0 text-center w-16">
                                <p class="text-lg font-black text-[#0F2044]">{{ entry.queue_number }}</p>
                                <QueueBadge :status="entry.status" size="xs" />
                            </div>

                            <!-- Priority badge -->
                            <div class="flex-shrink-0">
                                <span
                                    class="text-xs font-semibold px-2 py-1 rounded-full"
                                    :class="[priorityConfig[entry.priority]?.bg, priorityConfig[entry.priority]?.text]"
                                >
                                    {{ priorityConfig[entry.priority]?.label ?? entry.priority }}
                                </span>
                            </div>

                            <!-- Patient info -->
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-[#0F2044]">{{ entry.patient_name }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="text-xs text-slate-400 font-mono">{{ entry.patient_code }}</p>
                                    <span class="text-slate-300">·</span>
                                    <p class="text-xs text-slate-400">{{ entry.age_sex }}</p>
                                    <span v-if="entry.employer" class="text-slate-300">·</span>
                                    <p v-if="entry.employer" class="text-xs text-slate-400 truncate">{{ entry.employer }}</p>
                                </div>
                            </div>

                            <!-- Visit type -->
                            <div class="flex-shrink-0">
                                <VisitTypeBadge :type="entry.visit_type" size="xs" />
                            </div>

                            <!-- Vitals status -->
                            <div class="flex-shrink-0 text-center w-20">
                                <div v-if="entry.has_vitals" class="flex items-center gap-1 text-emerald-600">
                                    <CheckCircle2 class="w-4 h-4" />
                                    <span class="text-xs font-semibold">Done</span>
                                </div>
                                <div v-else class="flex items-center gap-1 text-amber-500">
                                    <AlertTriangle class="w-4 h-4" />
                                    <span class="text-xs font-semibold">Needed</span>
                                </div>
                            </div>

                            <!-- Wait time -->
                            <div class="flex-shrink-0 text-right w-12">
                                <p class="text-xs font-semibold text-slate-500">{{ formatWait(entry.wait_mins) }}</p>
                                <p class="text-xs text-slate-300">wait</p>
                            </div>

                            <!-- Action -->
                            <div class="flex-shrink-0">
                                <Link
                                    v-if="entry.visit_id"
                                    :href="route('nurse.vitals', entry.visit_id)"
                                    class="text-xs font-semibold px-3 py-2 rounded-xl transition-colors"
                                    :class="entry.has_vitals
                                        ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                        : 'bg-[#1B4F9B] text-white hover:bg-[#0F2044]'"
                                >
                                    {{ entry.has_vitals ? 'Update' : 'Record' }}
                                </Link>
                            </div>
                        </div>

                        <div v-if="!queue?.length" class="py-16 text-center">
                            <CheckCircle2 class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                            <p class="text-slate-400 font-medium">No patients in queue right now</p>
                            <p class="text-sm text-slate-300 mt-1">New patients will appear here when assigned</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-span-3 lg:col-span-1 space-y-4">
                <!-- Visit type breakdown -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Today's Visit Breakdown</h3>
                    <div class="space-y-3">
                        <div v-for="(count, type) in visitTypeCounts" :key="type" class="flex items-center gap-3">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-slate-600">{{ visitTypeLabels[type] ?? type }}</span>
                                    <span class="text-sm font-black text-[#0F2044]">{{ count }}</span>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-[#1B4F9B] rounded-full transition-all duration-500"
                                        :style="{ width: `${(count / (stats.in_queue || 1)) * 100}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-if="!Object.keys(visitTypeCounts ?? {}).length" class="text-sm text-slate-400 text-center py-4">
                            No data yet
                        </div>
                    </div>
                </div>

                <!-- Backlog from previous days -->
                <div v-if="backlog?.length" class="bg-amber-50 border border-amber-100 rounded-2xl p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <AlertTriangle class="w-4 h-4 text-amber-600" />
                        <h3 class="font-bold text-amber-800">Pending from Previous Days</h3>
                    </div>
                    <div class="space-y-3">
                        <div v-for="v in backlog" :key="v.id" class="bg-white rounded-xl p-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ v.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ v.visit_date }} · {{ visitTypeLabels[v.visit_type] }}</p>
                            </div>
                            <span class="text-xs text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded-full">
                                {{ v.days_ago }}d ago
                            </span>
                            <Link :href="route('nurse.vitals', v.id)" class="text-[#1B4F9B] hover:text-[#0F2044] flex-shrink-0">
                                <ArrowRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="bg-[#0F2044] rounded-2xl p-5 text-white">
                    <h4 class="font-bold text-sm mb-3">Priority Guide</h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full bg-red-500 text-white font-bold">Urgent</span>
                            <span class="text-white/70">Serve immediately</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full bg-pink-500 text-white font-bold">Pregnant</span>
                            <span class="text-white/70">Priority seating</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full bg-purple-500 text-white font-bold">PWD</span>
                            <span class="text-white/70">Assisted service</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white font-bold">Senior</span>
                            <span class="text-white/70">Before regular</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
