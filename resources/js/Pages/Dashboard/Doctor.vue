<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Stethoscope, AlertTriangle, CheckCircle2,
    ChevronRight, Wifi, Clock, AlertCircle,
    RefreshCw, Users, Pill, Activity, TrendingUp,
} from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String,
    stats: Object,
    pending: Array,
    completedToday: Array,
    classificationSummary: Object,
})

const statusDot  = { none:'#cbd5e1', pending:'#f59e0b', processing:'#3b82f6', released:'#22c55e' }
const visitLabel = {
    pre_employment:'Pre-Emp', annual_pe:'Annual PE',
    exit_pe:'Exit PE', opd:'OPD', follow_up:'Follow-up',
}
const classLabel = {
    fit:'FIT', unfit:'UNFIT', fit_with_remarks:'Fit w/ Remarks',
    deferred:'Deferred', for_treatment:'For Treatment',
}
const classColor = {
    fit:'#16A34A', unfit:'#DC2626', fit_with_remarks:'#D97706',
    deferred:'#7C3AED', for_treatment:'#0369A1',
}
const classBg = {
    fit:'#F0FDF4', unfit:'#FEF2F2', fit_with_remarks:'#FFFBEB',
    deferred:'#FAF5FF', for_treatment:'#F0F9FF',
}

const readyList   = computed(() => (props.pending ?? []).filter(p => p.all_results_in))
const waitingList = computed(() => (props.pending ?? []).filter(p => !p.all_results_in))
const totalClass  = computed(() => Math.max(Object.values(props.classificationSummary ?? {}).reduce((s,v) => s + Number(v), 0), 1))
const reviewPct   = computed(() => {
    const total = props.stats?.pending_total ?? 0
    return total > 0 ? Math.round(((props.stats?.ready_for_review ?? 0) / total) * 100) : 0
})

const refreshing = ref(false)
const lastRefresh = ref(new Date())
let t = null
onMounted(() => { t = setInterval(refresh, 25000) })
onUnmounted(() => clearInterval(t))
async function refresh() {
    refreshing.value = true
    await router.reload({ only: ['stats','pending','completedToday','classificationSummary'] })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="Doctor Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">Doctor's Station</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full font-semibold">
                <Wifi class="w-3 h-3"/> Live — 25s
            </span>
            <button @click="refresh" class="p-2 text-slate-400 hover:text-violet-600 hover:bg-slate-50 rounded-lg transition-colors">
                <RefreshCw class="w-4 h-4" :class="refreshing && 'animate-spin'"/>
            </button>
            <a :href="route('doctor.index')" class="flex items-center gap-2 text-sm font-semibold border border-violet-600 text-violet-600 px-3 py-2 rounded-xl hover:bg-violet-600 hover:text-white transition-colors">
                <Stethoscope class="w-4 h-4"/> Open Consultations
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        <div class="rounded-2xl p-5 text-white col-span-2 lg:col-span-1"
            :style="(stats.ready_for_review ?? 0) > 0 ? 'background:linear-gradient(135deg,#059669,#10B981)' : 'background:linear-gradient(135deg,#94a3b8,#cbd5e1)'">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Ready to Review</span>
                <CheckCircle2 class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.ready_for_review }}</div>
            <div class="text-xs opacity-70 mt-1">all results in</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Total Pending</span>
                <Users class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.pending_total }}</div>
            <div class="text-xs opacity-70 mt-1">{{ waitingList.length }} awaiting results</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#1D4ED8,#3b82f6)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Completed</span>
                <Activity class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.completed_today }}</div>
            <div class="text-xs opacity-70 mt-1">consultations today</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.abnormal_today ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.abnormal_today ?? 0) > 0 ? 'background:linear-gradient(135deg,#DC2626,#ef4444)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.abnormal_today ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Abnormal Results</span>
                <AlertTriangle class="w-5 h-5" :class="(stats.abnormal_today ?? 0) > 0 ? 'opacity-60' : 'text-red-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.abnormal_today ?? 0) > 0 ? '' : 'text-red-600'">{{ stats.abnormal_today }}</div>
            <div class="text-xs mt-1" :class="(stats.abnormal_today ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">lab abnormalities</div>
        </div>

        <div class="rounded-2xl p-5 bg-white border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">PE Completed</span>
                <Stethoscope class="w-5 h-5 text-violet-500"/>
            </div>
            <div class="text-3xl font-black text-violet-600">{{ stats.pe_done_today }}</div>
            <div class="text-xs text-slate-400 mt-1">PE exams done</div>
        </div>

        <div class="rounded-2xl p-5 bg-white border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">My Rx Today</span>
                <Pill class="w-5 h-5 text-blue-500"/>
            </div>
            <div class="text-3xl font-black text-blue-600">{{ stats.my_prescriptions_today }}</div>
            <div class="text-xs text-slate-400 mt-1">All: {{ stats.all_prescriptions_today }}</div>
        </div>
    </div>

    <!-- Review Progress Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <TrendingUp class="w-4 h-4 text-emerald-500"/>
                <span class="text-sm font-bold text-slate-700">Consultation Progress Today</span>
            </div>
            <span class="text-sm font-black text-emerald-600">{{ stats.completed_today }} / {{ (stats.completed_today ?? 0) + (stats.pending_total ?? 0) }} completed</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                :class="reviewPct >= 80 ? 'bg-emerald-500' : reviewPct >= 50 ? 'bg-blue-500' : 'bg-amber-500'"
                :style="{ width: Math.min(100, Math.round((stats.completed_today ?? 0) / Math.max((stats.completed_today ?? 0) + (stats.pending_total ?? 0), 1) * 100)) + '%' }"/>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>{{ stats.pending_total }} still pending</span>
            <span v-if="(stats.ready_for_review ?? 0) > 0" class="text-emerald-600 font-semibold">{{ stats.ready_for_review }} ready for review now</span>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">
        <!-- Pending patients -->
        <div class="col-span-2 space-y-4">

            <!-- Ready for Review -->
            <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-emerald-100 flex items-center gap-2" style="background:#F0FDF4">
                    <CheckCircle2 class="w-4 h-4 text-emerald-600"/>
                    <h3 class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Ready for Review</h3>
                    <span class="ml-auto text-xs font-black text-emerald-700 bg-emerald-200 px-2 py-0.5 rounded-full">{{ readyList.length }}</span>
                </div>
                <div v-if="!readyList.length" class="py-10 text-center">
                    <CheckCircle2 class="w-8 h-8 text-slate-200 mx-auto mb-2"/>
                    <p class="text-slate-400 text-sm">No patients ready yet</p>
                </div>
                <div v-else class="divide-y divide-slate-50">
                    <div v-for="p in readyList" :key="p.id"
                        class="px-4 py-3 flex items-center gap-3 hover:bg-emerald-50/30 transition-colors">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ p.patient_name }}</p>
                                <span v-if="p.abnormal_count > 0" class="flex items-center gap-0.5 text-xs font-bold text-red-600 flex-shrink-0">
                                    <AlertTriangle class="w-3 h-3"/>{{ p.abnormal_count }} abnormal
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">{{ p.patient_code }} &bull; {{ p.age_sex }}</p>
                            <p v-if="p.employer" class="text-xs text-violet-500 font-semibold truncate">{{ p.employer }}</p>
                        </div>
                        <!-- Visit type -->
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 flex-shrink-0">
                            {{ visitLabel[p.visit_type] ?? p.visit_type }}
                        </span>
                        <!-- Module status dots -->
                        <div class="flex items-center gap-1.5 flex-shrink-0" title="Lab · X-Ray · Drug">
                            <span class="w-2.5 h-2.5 rounded-full" :style="{background:statusDot[p.lab_status]}"/>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{background:statusDot[p.xray_status]}"/>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{background:statusDot[p.drug_status]}"/>
                        </div>
                        <!-- Wait time -->
                        <span class="text-xs text-slate-400 flex-shrink-0">{{ p.wait_mins }}m</span>
                        <!-- Action -->
                        <a :href="route('doctor.enter', p.id)"
                            class="flex-shrink-0 text-xs font-bold px-3 py-1.5 rounded-xl text-white flex items-center gap-1 hover:opacity-90 transition-opacity"
                            style="background:#7C3AED">
                            Consult <ChevronRight class="w-3 h-3"/>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Awaiting Results -->
            <div v-if="waitingList.length" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b flex items-center gap-2">
                    <Clock class="w-4 h-4 text-amber-500"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Awaiting Results</h3>
                    <span class="ml-auto text-xs font-black text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">{{ waitingList.length }}</span>
                </div>
                <div class="divide-y divide-slate-50">
                    <div v-for="p in waitingList" :key="p.id"
                        class="px-4 py-2.5 flex items-center gap-3 hover:bg-amber-50/20 transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ p.patient_name }}</p>
                            <p class="text-xs text-slate-400">{{ p.patient_code }} &bull; {{ p.age_sex }}</p>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 flex-shrink-0">
                            {{ visitLabel[p.visit_type] ?? p.visit_type }}
                        </span>
                        <div class="flex items-center gap-1.5 flex-shrink-0" title="Lab · X-Ray · Drug">
                            <span class="w-2.5 h-2.5 rounded-full" :style="{background:statusDot[p.lab_status]}"/>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{background:statusDot[p.xray_status]}"/>
                            <span class="w-2.5 h-2.5 rounded-full" :style="{background:statusDot[p.drug_status]}"/>
                        </div>
                        <span class="text-xs text-amber-600 font-semibold flex-shrink-0">{{ p.wait_mins }}m</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="space-y-4">

            <!-- PE Classification -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Activity class="w-4 h-4 text-slate-400"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">PE Classification Today</h3>
                </div>
                <div class="space-y-2.5">
                    <div v-for="(count, cls) in classificationSummary" :key="cls">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-bold" :style="{ color: classColor[cls] ?? '#475569' }">{{ classLabel[cls] ?? cls }}</span>
                            <span class="font-black text-slate-800">{{ count }}</span>
                        </div>
                        <div class="h-2.5 rounded-full overflow-hidden"
                            :style="{ background: classBg[cls] ?? '#f1f5f9' }">
                            <div class="h-full rounded-full transition-all"
                                :style="{ width: (Number(count) / totalClass * 100) + '%', background: classColor[cls] ?? '#94a3b8' }"/>
                        </div>
                    </div>
                    <p v-if="!Object.keys(classificationSummary ?? {}).length" class="text-xs text-slate-400 text-center py-2">No PE completed today</p>
                </div>
            </div>

            <!-- Completed Today -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-blue-500"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Completed Today</h3>
                    <span class="ml-auto text-xs text-blue-700 bg-blue-100 font-bold px-2 py-0.5 rounded-full">{{ completedToday?.length ?? 0 }}</span>
                </div>
                <div class="divide-y divide-slate-50 overflow-y-auto" style="max-height:300px;">
                    <p v-if="!completedToday?.length" class="py-6 text-center text-xs text-slate-400">None finalized yet</p>
                    <div v-for="c in completedToday" :key="c.id" class="px-4 py-2.5 hover:bg-slate-50/50 transition-colors">
                        <p class="text-xs font-bold text-slate-800 truncate">{{ c.patient_name }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span v-if="c.pe_classification" class="text-xs font-bold"
                                :style="{ color: classColor[c.pe_classification] ?? '#475569' }">
                                {{ classLabel[c.pe_classification] ?? c.pe_classification }}
                            </span>
                            <span v-else-if="c.icd10_code" class="text-xs text-blue-600 font-mono">{{ c.icd10_code }}</span>
                            <span class="text-xs text-slate-400 ml-auto">{{ c.finalized_at }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dot legend -->
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                <p class="text-xs font-bold text-slate-400 mb-2">Module Status Dots</p>
                <div class="flex flex-wrap gap-y-1.5 gap-x-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-300"/>&nbsp;None</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"/>&nbsp;Pending</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"/>&nbsp;Processing</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"/>&nbsp;Released</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">Order: Lab &bull; X-Ray &bull; Drug Test</p>
            </div>
        </div>
    </div>
</div>
</AppLayout>
</template>
