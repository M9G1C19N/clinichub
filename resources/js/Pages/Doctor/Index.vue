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
    FileText, Printer, Search, SlidersHorizontal, X,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE as visitTypeBadge, VISIT_TYPE_LABEL as visitTypeLabel } from '@/config/visitTypes.js'

const props = defineProps({
    todayQueue: Array,
    pending:    Object,
    completed:  Array,
    summary:    Object,
    filter:     String,
    history: Object,
    search:  String,
    date:    String,
})

const searchInput = ref(props.search ?? '')
const dateInput   = ref(props.date ?? '')

function doSearch() {
    router.get(route('doctor.index'), {
        search: searchInput.value,
        date:   dateInput.value,
    }, { preserveState: true, replace: true })
}

function clearSearch() {
    searchInput.value = ''
    dateInput.value   = ''
    doSearch()
}

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

        <!-- Summary Cards -->
        <div class="grid grid-cols-5 gap-3 mb-4">

            <div @click="activeTab = 'today'" class="rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer transition-all hover:shadow-md"
                :style="activeTab === 'today'
                    ? 'background:linear-gradient(135deg,#EFF6FF,#DBEAFE);border-color:#93C5FD;'
                    : 'background:white;'">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                    :style="activeTab === 'today' ? 'background:#DBEAFE;' : 'background:#EFF6FF;'">
                    <Users class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Today's Queue</p>
                    <p class="text-2xl font-black text-slate-800 leading-none mt-0.5">{{ summary.today_queue }}</p>
                </div>
            </div>

            <div @click="activeTab = 'pending'" class="rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer transition-all hover:shadow-md"
                :style="activeTab === 'pending'
                    ? 'background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border-color:#FCD34D;'
                    : 'background:white;'">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                    :style="activeTab === 'pending' ? 'background:#FEF3C7;' : 'background:#FFFBEB;'">
                    <ClipboardList class="w-5 h-5 text-amber-500"/>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Pending</p>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <p class="text-2xl font-black text-slate-800 leading-none">{{ summary.pending_total }}</p>
                        <span v-if="summary.pending_pe > 0"
                            class="text-xs font-bold px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700">
                            {{ summary.pending_pe }} PE
                        </span>
                    </div>
                </div>
            </div>

            <div @click="activeTab = 'completed'" class="rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer transition-all hover:shadow-md"
                :style="activeTab === 'completed'
                    ? 'background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border-color:#6EE7B7;'
                    : 'background:white;'">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                    :style="activeTab === 'completed' ? 'background:#D1FAE5;' : 'background:#ECFDF5;'">
                    <CheckCircle2 class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Completed Today</p>
                    <p class="text-2xl font-black text-slate-800 leading-none mt-0.5">{{ summary.completed_today }}</p>
                </div>
            </div>

            <div class="rounded-xl border shadow-sm p-4 flex items-center gap-3 bg-white">
                <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                    <Stethoscope class="w-5 h-5 text-violet-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">PE Pending</p>
                    <p class="text-2xl font-black text-slate-800 leading-none mt-0.5">{{ summary.pending_pe }}</p>
                </div>
            </div>

            <div @click="activeTab = 'history'" class="rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer transition-all hover:shadow-md"
                :style="activeTab === 'history'
                    ? 'background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border-color:#94A3B8;'
                    : 'background:white;'">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                    :style="activeTab === 'history' ? 'background:#E2E8F0;' : 'background:#F8FAFC;'">
                    <FileText class="w-5 h-5 text-slate-500"/>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">History</p>
                    <p class="text-2xl font-black text-slate-800 leading-none mt-0.5">{{ summary.history_total ?? 0 }}</p>
                </div>
            </div>

        </div>

        <!-- Tab Navigation Bar -->
        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl mb-5">
            <button v-for="tab in [
                { key: 'today',     label: 'Today\'s Queue',    count: summary.today_queue,    color: '#1B4F9B' },
                { key: 'pending',   label: 'Pending Diagnosis', count: summary.pending_total,  color: '#D97706' },
                { key: 'completed', label: 'Completed Today',   count: summary.completed_today, color: '#059669' },
                { key: 'history',   label: 'History',           count: summary.history_total ?? 0, color: '#64748B' },
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                :style="activeTab === tab.key
                    ? 'background:white;color:' + tab.color + ';box-shadow:0 1px 3px rgba(0,0,0,0.1);'
                    : 'color:#64748B;'">
                {{ tab.label }}
                <span class="text-xs font-bold px-1.5 py-0.5 rounded-full"
                    :style="activeTab === tab.key
                        ? 'background:' + tab.color + ';color:white;'
                        : 'background:#E2E8F0;color:#64748B;'">
                    {{ tab.count }}
                </span>
            </button>
        </div>


        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 1: TODAY'S QUEUE                           -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'today'" class="space-y-3">

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
                            :style="{ color: ['pre_employment','annual_pe','exit_pe'].includes(a.visit?.visit_type) ? '#8B5CF6' : '#10B981' }">
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
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="searchInput || dateInput || (filter && filter !== 'all')"
                        @click="searchInput=''; dateInput=''; doSearch(); applyFilter('all')"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700 transition-colors">
                        <X class="w-3 h-3"/> Clear filters
                    </button>
                </div>
                <div class="p-3 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <input v-model="searchInput"
                                placeholder="Search patient name or code..."
                                class="pl-9 h-8 text-xs w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                @keyup.enter="doSearch"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <input v-model="dateInput" type="date"
                                class="border-0 bg-transparent text-xs w-32 focus:outline-none"
                                @change="doSearch"/>
                        </div>
                        <button class="h-8 text-xs gap-1.5 px-3 rounded-md font-semibold text-white flex items-center"
                            style="background-color:#1B4F9B"
                            @click="doSearch">
                            <Search class="w-3.5 h-3.5 mr-1"/> Search
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Type</span>
                        <button v-for="f in [
                            { value: 'all',            label: 'All' },
                            { value: 'pre_employment', label: 'Pre-Employment' },
                            { value: 'opd',            label: 'OPD' },
                            { value: 'annual_pe',      label: 'Annual PE' },
                            { value: 'exit_pe',        label: 'Exit PE' },
                        ]" :key="f.value"
                            @click="applyFilter(f.value)"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="filter === f.value
                                ? 'background-color:#1B4F9B;color:white;border-color:#1B4F9B;box-shadow:0 1px 4px rgba(27,79,155,0.3);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ f.label }}
                        </button>
                    </div>
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
                                    <Button size="sm" class="gap-2 text-white"
                                        :style="{ backgroundColor:
                                            ['pre_employment','annual_pe','exit_pe'].includes(visit.visit_type) ? '#8B5CF6' : '#1B4F9B' }">
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
                                <div class="flex items-center gap-1.5">
                                    <Link :href="route('doctor.consult', c.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs gap-1.5">
                                            <Stethoscope class="w-3.5 h-3.5"/>
                                            View
                                        </Button>
                                    </Link>
                                    <a :href="route('doctor.print', c.visit_id)" target="_blank">
                                        <Button size="sm" class="text-xs gap-1.5 text-white" style="background:#1B4F9B;">
                                            <Printer class="w-3.5 h-3.5"/>
                                            Print
                                        </Button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
         <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 4: HISTORY                                 -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'history'">

            <!-- Search bar -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="searchInput || dateInput" @click="clearSearch"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700 transition-colors">
                        <X class="w-3 h-3"/> Clear filters
                    </button>
                </div>
                <div class="p-3">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <input v-model="searchInput" @keyup.enter="doSearch"
                                placeholder="Search patient name or code..."
                                class="w-full h-8 pl-9 pr-4 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <input v-model="dateInput" type="date" @change="doSearch"
                                class="border-0 bg-transparent text-xs w-32 focus:outline-none"/>
                        </div>
                        <button class="h-8 text-xs px-3 rounded-md font-semibold text-white flex items-center gap-1.5"
                            style="background:#1B4F9B"
                            @click="doSearch">
                            <Search class="w-3.5 h-3.5"/> Search
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!history?.data?.length"
                class="bg-card rounded-xl border shadow-sm py-16 text-center">
                <FileText class="w-12 h-12 text-slate-200 mx-auto mb-3"/>
                <p class="text-slate-500 font-semibold">No history found</p>
                <p class="text-slate-400 text-sm mt-1">Finalized consultations will appear here</p>
            </div>

            <!-- History table -->
            <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="background-color:#0F2044">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Visit Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Classification</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Diagnosis</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Finalized</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in history.data" :key="c.id"
                            class="hover:bg-slate-50 transition-colors group">
                            <td class="px-5 py-3.5">
                                <p class="font-bold text-slate-800 text-sm">{{ c.patient_name }}</p>
                                <p class="text-xs font-mono text-slate-400">{{ c.patient_code }} · {{ c.age_sex }}</p>
                                <p v-if="c.employer_company" class="text-xs text-purple-600 font-semibold mt-0.5">
                                    {{ c.employer_company }}
                                </p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', visitTypeBadge[c.visit_type]]">
                                    {{ visitTypeLabel[c.visit_type] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span v-if="c.pe_classification"
                                    class="text-xs font-bold px-2 py-1 rounded-lg"
                                    :class="peClassColor[c.pe_classification]">
                                    CLASS {{ c.pe_classification }}
                                </span>
                                <span v-else-if="c.essentially_normal"
                                    class="text-xs text-emerald-600 font-semibold">
                                    Normal
                                </span>
                                <span v-else class="text-xs text-slate-400">OPD</span>
                            </td>
                            <td class="px-4 py-3.5 max-w-xs">
                                <p v-if="c.icd10_code" class="text-xs font-mono text-slate-600">{{ c.icd10_code }}</p>
                                <p v-if="c.icd10_description" class="text-xs text-slate-500 truncate">{{ c.icd10_description }}</p>
                                <p v-if="!c.icd10_code && !c.icd10_description" class="text-xs text-slate-300">—</p>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                {{ c.finalized_at }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <Link :href="route('doctor.consult', c.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                            <Stethoscope class="w-3 h-3"/> View
                                        </Button>
                                    </Link>
                                    <a :href="route('doctor.print', c.visit_id)" target="_blank">
                                        <Button size="sm" class="text-xs h-7 gap-1 text-white" style="background:#1B4F9B;">
                                            <Printer class="w-3 h-3"/> Print
                                        </Button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-5 py-3 border-t flex items-center justify-between text-xs text-slate-500">
                    <span>{{ history.from }}–{{ history.to }} of {{ history.total }} records</span>
                    <div class="flex items-center gap-1">
                        <template v-for="link in history.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 rounded border text-xs transition-colors"
                                :class="link.active
                                    ? 'text-white border-transparent'
                                    : 'border-slate-200 hover:border-slate-300'"
                                :style="link.active ? 'background:#1B4F9B;' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2 py-1 text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>


    </AppLayout>
</template>
