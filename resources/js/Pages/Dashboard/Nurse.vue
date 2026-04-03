<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    HeartPulse, AlertCircle, CheckCircle2, ClipboardList,
    ChevronRight, Wifi, Activity, RefreshCw, Clock,
    Users, Thermometer, FlaskConical, Pill,
} from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String,
    stats: Object,
    queue: Array,
    visitTypeCounts: Object,
    backlog: Array,
})

const priorityStyle = {
    urgent:  { bg:'#FEF2F2', text:'#DC2626', label:'URGENT' },
    pregnant:{ bg:'#FDF2F8', text:'#DB2777', label:'PREGNANT' },
    pwd:     { bg:'#EFF6FF', text:'#1D4ED8', label:'PWD' },
    senior:  { bg:'#FFFBEB', text:'#D97706', label:'SENIOR' },
    regular: { bg:'#F8FAFC', text:'#475569', label:'REGULAR' },
}
const visitLabel = {
    pre_employment:'Pre-Employment', annual_pe:'Annual PE',
    exit_pe:'Exit PE', opd:'OPD', follow_up:'Follow-up', lab_only:'Lab Only',
}
const visitColor = {
    pre_employment:'#7C3AED', annual_pe:'#16A34A',
    exit_pe:'#C2410C', opd:'#1D4ED8', follow_up:'#0369A1', lab_only:'#9333EA',
}

const totalVT   = computed(() => Math.max(Object.values(props.visitTypeCounts ?? {}).reduce((s,v)=>s+v,0), 1))
const activeQ   = computed(() => (props.queue ?? []).filter(q => ['waiting','calling','serving'].includes(q.status)))
const doneQ     = computed(() => (props.queue ?? []).filter(q => q.status === 'completed').length)
const vitalsPct = computed(() => {
    const total = props.stats?.in_queue ?? 0
    return total > 0 ? Math.round(((props.stats?.vitals_taken ?? 0) / total) * 100) : 0
})

const refreshing = ref(false)
const lastRefresh = ref(new Date())
let t = null
onMounted(() => { t = setInterval(refresh, 20000) })
onUnmounted(() => clearInterval(t))
async function refresh() {
    refreshing.value = true
    await router.reload({ only: ['stats','queue','visitTypeCounts','backlog'] })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="Nurse Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">Nurse Station</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full font-semibold">
                <Wifi class="w-3 h-3"/> Live
            </span>
            <button @click="refresh" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-slate-50 rounded-lg transition-colors">
                <RefreshCw class="w-4 h-4" :class="refreshing && 'animate-spin'"/>
            </button>
            <a :href="route('nurse.index')" class="flex items-center gap-2 text-sm font-semibold border border-emerald-600 text-emerald-600 px-3 py-2 rounded-xl hover:bg-emerald-600 hover:text-white transition-colors">
                <HeartPulse class="w-4 h-4"/> Nurse Intake
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        <div class="rounded-2xl p-5 text-white col-span-2 lg:col-span-1" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">In Queue</span>
                <Users class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.in_queue }}</div>
            <div class="text-xs opacity-70 mt-1">{{ doneQ }} completed today</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#059669,#10B981)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Vitals Done</span>
                <CheckCircle2 class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.vitals_taken }}</div>
            <div class="text-xs opacity-70 mt-1">{{ vitalsPct }}% complete</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.pending ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.pending ?? 0) > 0 ? 'background:linear-gradient(135deg,#D97706,#F59E0B)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.pending ?? 0) > 0 ? 'opacity-70 text-white' : 'text-slate-400'">Need Vitals</span>
                <AlertCircle class="w-5 h-5" :class="(stats.pending ?? 0) > 0 ? 'text-white opacity-60' : 'text-amber-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.pending ?? 0) > 0 ? 'text-white' : 'text-amber-600'">{{ stats.pending }}</div>
            <div class="text-xs mt-1" :class="(stats.pending ?? 0) > 0 ? 'text-white opacity-70' : 'text-slate-400'">awaiting vitals</div>
        </div>

        <div class="rounded-2xl p-5 bg-white border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">PE Today</span>
                <ClipboardList class="w-5 h-5 text-violet-500"/>
            </div>
            <div class="text-3xl font-black text-violet-600">{{ stats.pe_today }}</div>
            <div class="text-xs text-slate-400 mt-1">PE examinations</div>
        </div>

        <div class="rounded-2xl p-5 bg-white border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Rx Today</span>
                <Pill class="w-5 h-5 text-blue-500"/>
            </div>
            <div class="text-3xl font-black text-blue-600">{{ stats.prescriptions_today }}</div>
            <div class="text-xs text-slate-400 mt-1">prescriptions</div>
        </div>

        <div class="rounded-2xl p-5 bg-white border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Lab Pending</span>
                <FlaskConical class="w-5 h-5 text-cyan-500"/>
            </div>
            <div class="text-3xl font-black text-cyan-600">{{ stats.lab_pending_today }}</div>
            <div class="text-xs text-slate-400 mt-1">awaiting results</div>
        </div>
    </div>

    <!-- Vitals Progress Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <Thermometer class="w-4 h-4 text-emerald-500"/>
                <span class="text-sm font-bold text-slate-700">Vitals Progress Today</span>
            </div>
            <span class="text-sm font-black text-emerald-600">{{ stats.vitals_taken }} / {{ stats.in_queue }} patients</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                :class="vitalsPct >= 80 ? 'bg-emerald-500' : vitalsPct >= 50 ? 'bg-blue-500' : 'bg-amber-500'"
                :style="{ width: vitalsPct + '%' }"/>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>{{ vitalsPct }}% complete</span>
            <span>{{ stats.pending }} remaining</span>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">
        <!-- Queue -->
        <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Interview Room Queue</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">{{ activeQ.length }} active</span>
                </div>
                <span class="text-xs text-slate-400">{{ doneQ }} completed</span>
            </div>
            <div class="overflow-y-auto" style="max-height:440px;">
                <div v-if="!activeQ.length" class="py-16 text-center">
                    <CheckCircle2 class="w-10 h-10 text-slate-200 mx-auto mb-2"/>
                    <p class="text-slate-400 text-sm">No active patients in queue</p>
                </div>
                <div v-else class="divide-y divide-slate-50">
                    <div v-for="q in activeQ" :key="q.id"
                        class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50/60 transition-colors">
                        <!-- Queue # -->
                        <div class="w-14 text-center flex-shrink-0">
                            <p class="text-xl font-black font-mono text-[#0F2044]">{{ q.queue_number }}</p>
                            <p :class="['text-xs font-bold',
                                q.status==='serving'   ? 'text-emerald-600' :
                                q.status==='calling'   ? 'text-blue-600 animate-pulse' :
                                'text-slate-400']">
                                {{ q.status }}
                            </p>
                        </div>
                        <div class="w-px h-10 bg-slate-100 flex-shrink-0"/>
                        <!-- Patient info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ q.patient_name }}</p>
                                <span v-if="q.priority !== 'regular'" class="text-xs font-bold px-1.5 py-0.5 rounded-full flex-shrink-0"
                                    :style="{background:priorityStyle[q.priority]?.bg, color:priorityStyle[q.priority]?.text}">
                                    {{ priorityStyle[q.priority]?.label }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">{{ q.patient_code }} &bull; {{ q.age_sex }}</p>
                            <p v-if="q.employer" class="text-xs text-violet-500 font-semibold truncate">{{ q.employer }}</p>
                        </div>
                        <!-- Visit type -->
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 flex-shrink-0">
                            {{ visitLabel[q.visit_type] ?? q.visit_type }}
                        </span>
                        <!-- Vitals status -->
                        <div class="flex-shrink-0 w-20 text-center">
                            <span v-if="q.has_vitals" class="flex items-center gap-1 text-xs text-emerald-600 font-bold justify-center">
                                <CheckCircle2 class="w-3.5 h-3.5"/> Done
                            </span>
                            <span v-else class="flex items-center gap-1 text-xs text-amber-600 font-bold justify-center">
                                <AlertCircle class="w-3.5 h-3.5"/> Needed
                            </span>
                        </div>
                        <!-- Action -->
                        <a v-if="q.visit_id" :href="route('nurse.vitals', q.visit_id)"
                            class="flex-shrink-0 text-xs font-bold px-3 py-1.5 rounded-xl text-white flex items-center gap-1 transition-colors"
                            :class="q.has_vitals ? 'bg-slate-400 hover:bg-slate-500' : 'hover:opacity-90'"
                            :style="!q.has_vitals ? 'background:#10B981' : ''">
                            {{ q.has_vitals ? 'View' : 'Enter' }} <ChevronRight class="w-3 h-3"/>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel -->
        <div class="space-y-4">

            <!-- Visit Type breakdown -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Activity class="w-4 h-4 text-slate-400"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Today's Visit Types</h3>
                </div>
                <div class="space-y-2.5">
                    <div v-for="(count, type) in visitTypeCounts" :key="type">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-600 font-semibold">{{ visitLabel[type] ?? type }}</span>
                            <span class="font-black text-slate-800">{{ count }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                :style="{width:(count/totalVT*100)+'%', background:visitColor[type]??'#94a3b8'}"/>
                        </div>
                    </div>
                    <p v-if="!Object.keys(visitTypeCounts??{}).length" class="text-xs text-slate-400 text-center py-2">No visits yet today</p>
                </div>
            </div>

            <!-- Backlog -->
            <div class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-amber-100 flex items-center gap-2" style="background:#FFFBEB">
                    <Clock class="w-4 h-4 text-amber-600"/>
                    <h3 class="text-xs font-bold text-amber-700 uppercase tracking-widest">Backlog</h3>
                    <span class="ml-auto text-xs bg-amber-200 text-amber-800 font-bold px-2 py-0.5 rounded-full">
                        {{ backlog?.length ?? 0 }} pending
                    </span>
                </div>
                <div class="divide-y divide-slate-50">
                    <p v-if="!backlog?.length" class="py-8 text-center text-xs text-slate-400">
                        No backlog — all clear!
                    </p>
                    <div v-for="b in backlog" :key="b.id" class="px-4 py-3 flex items-center justify-between hover:bg-amber-50/50 transition-colors">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-800 truncate">{{ b.patient_name }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ b.patient_code }}</p>
                            <p class="text-xs text-amber-600 font-semibold">{{ b.visit_date }} &bull; {{ b.days_ago }}d ago</p>
                        </div>
                        <a :href="route('nurse.vitals', b.id)" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 ml-2 flex-shrink-0">
                            Enter →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</AppLayout>
</template>
