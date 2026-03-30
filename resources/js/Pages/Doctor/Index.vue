<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    Stethoscope, ClipboardList, CheckCircle2,
    AlertTriangle, Clock, Activity, Users,
    FlaskConical, Calendar, ChevronRight,
    FileText, Printer,
} from 'lucide-vue-next'

const props = defineProps({
    todayQueue: Array,
    pending:    Object,
    completed:  Array,
    summary:    Object,
    filter:     String,
})

// Active tab
const activeTab = ref('pending') // 'today' | 'pending' | 'completed'

// Auto-refresh every 15s
let refreshTimer = null
onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({
            only: ['todayQueue', 'pending', 'completed', 'summary'],
            preserveScroll: true,
        })
    }, 15000)
})
onUnmounted(() => clearInterval(refreshTimer))

function applyFilter(f) {
    router.get(route('doctor.index'), { filter: f }, {
        preserveState: true, replace: true
    })
}

const visitTypeBadge = {
    opd:            'bg-blue-100 text-blue-700',
    pre_employment: 'bg-purple-100 text-purple-700',
    follow_up:      'bg-amber-100 text-amber-700',
    lab_only:       'bg-teal-100 text-teal-700',
}

const visitTypeLabel = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    follow_up:      'Follow-up',
    lab_only:       'Lab Only',
}

const priorityConfig = {
    urgent:   { class: 'bg-red-500 text-white',       label: 'URGENT'   },
    pregnant: { class: 'bg-pink-100 text-pink-700',   label: 'PREGNANT' },
    pwd:      { class: 'bg-blue-100 text-blue-700',   label: 'PWD'      },
    senior:   { class: 'bg-amber-100 text-amber-700', label: 'SENIOR'   },
}

const peClassColor = {
    A: 'bg-emerald-100 text-emerald-700',
    B: 'bg-blue-100 text-blue-700',
    C: 'bg-amber-100 text-amber-700',
    D: 'bg-orange-100 text-orange-700',
    E: 'bg-red-100 text-red-700',
}
</script>

<template>
    <AppLayout title="Doctor Dashboard">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Doctor Dashboard</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        {{ new Date().toLocaleDateString('en-PH', {
                            weekday:'long', year:'numeric', month:'long', day:'numeric'
                        }) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"/>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"/>
                        </span>
                        <span class="text-xs text-emerald-600 font-medium">Live · 15s</span>
                    </div>
                    <a :href="route('queue.room', 'interview_room')" target="_blank">
                        <Button variant="outline" size="sm" class="gap-2">
                            <Activity class="w-4 h-4"/>
                            Room Screen
                        </Button>
                    </a>
                </div>
            </div>
        </template>

        <!-- ── Summary Cards ─────────────────────────── -->
        <div class="grid grid-cols-4 gap-4 mb-5">

            <div @click="activeTab = 'today'"
                :class="['bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer transition-all',
                    activeTab === 'today' ? 'ring-2 ring-blue-400' : 'hover:shadow-md']">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <Users class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Today's Queue</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.today_queue }}</p>
                </div>
            </div>

            <div @click="activeTab = 'pending'"
                :class="['bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer transition-all',
                    activeTab === 'pending' ? 'ring-2 ring-amber-400' : 'hover:shadow-md']">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <ClipboardList class="w-5 h-5 text-amber-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Pending Diagnosis</p>
                    <div class="flex items-center gap-2">
                        <p class="text-2xl font-black text-slate-800">{{ summary.pending_total }}</p>
                        <span v-if="summary.pending_pe > 0"
                            class="text-xs font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-700">
                            {{ summary.pending_pe }} PE
                        </span>
                    </div>
                </div>
            </div>

            <div @click="activeTab = 'completed'"
                :class="['bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer transition-all',
                    activeTab === 'completed' ? 'ring-2 ring-emerald-400' : 'hover:shadow-md']">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <CheckCircle2 class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Completed Today</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.completed_today }}</p>
                </div>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <Stethoscope class="w-5 h-5 text-purple-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Pre-Employment Pending</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.pending_pe }}</p>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 1: TODAY'S QUEUE                           -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'today'" class="space-y-3">
            <div class="flex items-center gap-2 px-1 mb-3">
                <span class="w-1 h-4 rounded-full inline-block bg-blue-500"></span>
                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                    Today's Interview Room Queue
                </h3>
            </div>

            <!-- Empty -->
            <div v-if="todayQueue.length === 0"
                class="bg-card rounded-2xl border shadow-sm py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-3">
                    <CheckCircle2 class="w-7 h-7 text-emerald-400"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No patients in queue today</p>
                <p class="text-xs text-slate-400 mt-1">Check Pending Diagnosis for carry-over patients</p>
            </div>

            <!-- Queue cards -->
            <div v-for="(a, i) in todayQueue" :key="a.id"
                class="bg-card rounded-2xl border shadow-sm overflow-hidden"
                :class="a.status === 'serving' ? 'ring-2 ring-emerald-400' :
                        a.status === 'calling' ? 'ring-2 ring-blue-400' : ''">

                <div class="h-1 w-full"
                    :style="{
                        background: a.visit?.visit_type === 'pre_employment' ? '#8B5CF6' :
                                    a.status === 'serving' ? '#10B981' :
                                    a.status === 'calling' ? '#3B82F6' : '#E2E8F0'
                    }"/>

                <div class="flex items-center p-5 gap-0">

                    <!-- Number -->
                    <div class="flex flex-col items-center justify-center w-24 flex-shrink-0 pr-5 border-r border-slate-100">
                        <p class="text-xs text-muted-foreground mb-1">#{{ i + 1 }}</p>
                        <p class="text-3xl font-black font-mono leading-none"
                            :style="{ color: a.visit?.visit_type === 'pre_employment' ? '#8B5CF6' : '#10B981' }">
                            {{ a.queue_number }}
                        </p>
                        <span :class="['mt-2 text-xs font-semibold px-2 py-0.5 rounded-full',
                            a.status === 'serving' ? 'bg-emerald-100 text-emerald-700' :
                            a.status === 'calling' ? 'bg-blue-100 text-blue-700' :
                                                     'bg-slate-100 text-slate-600']">
                            {{ a.status }}
                        </span>
                    </div>

                    <!-- Patient Info -->
                    <div class="flex-1 min-w-0 px-5">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-base font-bold text-slate-800">{{ a.patient.full_name }}</p>
                            <span v-if="priorityConfig[a.priority]"
                                :class="['text-xs font-bold px-2 py-0.5 rounded-full',
                                    priorityConfig[a.priority].class]">
                                {{ priorityConfig[a.priority].label }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground mb-2">
                            {{ a.patient.patient_code }} · {{ a.patient.age_sex }}
                        </p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <Badge v-if="a.visit"
                                :class="visitTypeBadge[a.visit.visit_type]">
                                {{ visitTypeLabel[a.visit.visit_type] }}
                            </Badge>
                            <span v-if="a.visit?.employer_company"
                                class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                {{ a.visit.employer_company }}
                            </span>
                            <span v-if="a.visit?.has_vitals"
                                class="flex items-center gap-1 text-xs text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold">
                                <CheckCircle2 class="w-3 h-3"/> Vitals ✓
                            </span>
                            <span v-else
                                class="flex items-center gap-1 text-xs text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full font-semibold">
                                <AlertTriangle class="w-3 h-3"/> No Vitals
                            </span>
                            <span v-if="a.visit?.is_finalized"
                                class="flex items-center gap-1 text-xs text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold">
                                <CheckCircle2 class="w-3 h-3"/> Done
                            </span>
                            <span v-else-if="a.visit?.has_consultation"
                                class="flex items-center gap-1 text-xs text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full font-semibold">
                                <FileText class="w-3 h-3"/> Draft
                            </span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="flex-shrink-0 pl-5">
                        <Link v-if="a.visit?.id" :href="route('doctor.consult', a.visit.id)">
                            <Button class="gap-2 text-white"
                                :style="{ backgroundColor:
                                    a.visit?.is_finalized ? '#10B981' :
                                    a.visit?.visit_type === 'pre_employment' ? '#8B5CF6' : '#1B4F9B' }">
                                <Stethoscope class="w-4 h-4"/>
                                {{ a.visit?.is_finalized ? 'View' :
                                   a.visit?.has_consultation ? 'Continue' : 'Consult' }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 2: PENDING DIAGNOSIS                       -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'pending'">

            <!-- Filter bar -->
            <div class="flex items-center gap-2 mb-4">
                <span class="w-1 h-4 rounded-full inline-block bg-amber-400"></span>
                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest mr-2">
                    Pending Diagnosis
                </h3>
                <div class="flex items-center gap-1.5">
                    <button v-for="f in [
                        { value: 'all',            label: 'All' },
                        { value: 'pre_employment', label: 'Pre-Employment' },
                        { value: 'opd',            label: 'OPD' },
                    ]" :key="f.value"
                        @click="applyFilter(f.value)"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all',
                            filter === f.value
                                ? 'text-white border-transparent'
                                : 'border-border text-muted-foreground hover:border-slate-300'
                        ]"
                        :style="filter === f.value ? 'background-color:#1B4F9B' : ''">
                        {{ f.label }}
                    </button>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="!pending.data?.length"
                class="bg-card rounded-xl border shadow-sm py-16 text-center">
                <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <CheckCircle2 class="w-7 h-7 text-emerald-400"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No pending diagnoses</p>
                <p class="text-xs text-slate-400 mt-1">All patients have been finalized</p>
            </div>

            <!-- Pending list -->
            <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="background-color:#0F2044">
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Visit Date</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Services</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="visit in pending.data" :key="visit.id"
                            class="hover:bg-slate-50 transition-colors group">

                            <!-- Patient -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                        style="background:#1B4F9B">
                                        {{ visit.patient.full_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ visit.patient.full_name }}</p>
                                        <p class="text-xs text-muted-foreground font-mono">{{ visit.patient.patient_code }}</p>
                                        <p class="text-xs text-muted-foreground">{{ visit.patient.age_sex }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Visit date -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-1.5 text-xs text-slate-600">
                                    <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                                    <div>
                                        <p class="font-semibold">{{ visit.visit_date }}</p>
                                        <p class="text-muted-foreground">{{ visit.visit_time }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Visit type -->
                            <td class="px-4 py-4">
                                <div class="space-y-1">
                                    <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full',
                                        visitTypeBadge[visit.visit_type]]">
                                        {{ visitTypeLabel[visit.visit_type] }}
                                    </span>
                                    <p v-if="visit.employer_company"
                                        class="text-xs text-muted-foreground">
                                        {{ visit.employer_company }}
                                    </p>
                                </div>
                            </td>

                            <!-- Services -->
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="svc in visit.services.slice(0, 3)" :key="svc.code"
                                        class="text-xs font-mono font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">
                                        {{ svc.code }}
                                    </span>
                                    <span v-if="visit.services.length > 3"
                                        class="text-xs text-muted-foreground">
                                        +{{ visit.services.length - 3 }} more
                                    </span>
                                </div>
                            </td>

                            <!-- Status flags -->
                            <td class="px-4 py-4">
                                <div class="space-y-1">
                                    <span v-if="visit.has_vitals"
                                        class="flex items-center gap-1 text-xs text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold w-fit">
                                        <CheckCircle2 class="w-3 h-3"/> Vitals ✓
                                    </span>
                                    <span v-else
                                        class="flex items-center gap-1 text-xs text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full font-semibold w-fit">
                                        <AlertTriangle class="w-3 h-3"/> No Vitals
                                    </span>
                                    <span v-if="visit.has_draft"
                                        class="flex items-center gap-1 text-xs text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full font-semibold w-fit">
                                        <FileText class="w-3 h-3"/> Draft Saved
                                    </span>
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-4">
                                <Link :href="route('doctor.consult', visit.id)">
                                    <Button size="sm" class="gap-2 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                        :style="{ backgroundColor:
                                            visit.visit_type === 'pre_employment' ? '#8B5CF6' : '#1B4F9B' }">
                                        <Stethoscope class="w-3.5 h-3.5"/>
                                        {{ visit.has_draft ? 'Continue' : 'Diagnose' }}
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="pending.data.length > 0"
                    class="px-5 py-4 border-t flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        Showing {{ pending.from }}–{{ pending.to }} of {{ pending.total }} patients
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="link in pending.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                :class="['px-3 py-1.5 text-sm rounded-lg transition-colors',
                                    link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100']"
                                :style="link.active ? 'background-color:#1B4F9B' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 3: COMPLETED TODAY                         -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'completed'">
            <div class="flex items-center gap-2 px-1 mb-3">
                <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                    Completed Today ({{ completed.length }})
                </h3>
            </div>

            <div v-if="completed.length === 0"
                class="bg-card rounded-xl border shadow-sm py-16 text-center">
                <p class="text-sm text-slate-400">No consultations finalized today yet</p>
            </div>

            <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="background-color:#0F2044">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Diagnosis / Class</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Finalized</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in completed" :key="c.id"
                            class="hover:bg-slate-50 transition-colors group">
                            <td class="px-5 py-3.5">
                                <p class="text-sm font-semibold text-slate-800">{{ c.patient_name }}</p>
                                <p class="text-xs text-muted-foreground font-mono">{{ c.patient_code }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full',
                                    visitTypeBadge[c.visit_type]]">
                                    {{ visitTypeLabel[c.visit_type] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span v-if="c.pe_classification"
                                    :class="['text-xs font-bold px-2.5 py-1 rounded-full',
                                        peClassColor[c.pe_classification]]">
                                    Class {{ c.pe_classification }}
                                </span>
                                <div v-else-if="c.icd10_code">
                                    <span class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                        {{ c.icd10_code }}
                                    </span>
                                    <p class="text-xs text-muted-foreground mt-0.5">{{ c.icd10_description }}</p>
                                </div>
                                <span v-else class="text-xs text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5 text-xs text-emerald-700">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    {{ c.finalized_at }}
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link :href="route('doctor.consult', c.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs gap-1.5">
                                            <Stethoscope class="w-3.5 h-3.5"/>
                                            View
                                        </Button>
                                    </Link>
                                    <!-- Print button — visible to all authorized roles -->
                                    <Button variant="outline" size="sm" class="text-xs gap-1.5"
                                        onclick="window.print()">
                                        <Printer class="w-3.5 h-3.5"/>
                                        Print
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AppLayout>
</template>
