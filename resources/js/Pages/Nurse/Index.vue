<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    HeartPulse, CheckCircle2, Clock,
    Activity, AlertTriangle, Search,
    Calendar, FileText, ChevronRight,
    ClipboardList, User,
} from 'lucide-vue-next'

const props = defineProps({
    todayQueue: Array,
    pending:    Object,
    history:    Object,
    filters:    Object,
    summary:    Object,
})

const activeTab = ref('today')
const search    = ref(props.filters?.search ?? '')
const date      = ref(props.filters?.date ?? '')

let refreshTimer = null
onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({
            only: ['todayQueue','pending','history','summary'],
            preserveScroll: true,
        })
    }, 10000)
})
onUnmounted(() => clearInterval(refreshTimer))

function applyFilters() {
    router.get(route('nurse.index'), {
        search: search.value,
        date:   date.value,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    date.value   = ''
    router.get(route('nurse.index'), {}, { preserveState: true, replace: true })
}

const queueStatusConfig = {
    waiting:   { label: 'Waiting',   dot: '#f59e0b', color: '#92400e' },
    calling:   { label: 'Calling',   dot: '#3b82f6', color: '#1e40af' },
    serving:   { label: 'Serving',   dot: '#10b981', color: '#065f46' },
    completed: { label: 'Completed', dot: '#94a3b8', color: '#475569' },
}

const priorityConfig = {
    urgent:   { label: 'URGENT',   bg: '#fef2f2', color: '#dc2626' },
    pregnant: { label: 'PREGNANT', bg: '#fdf2f8', color: '#be185d' },
    pwd:      { label: 'PWD',      bg: '#eff6ff', color: '#1d4ed8' },
    senior:   { label: 'SENIOR',   bg: '#fffbeb', color: '#b45309' },
}

const visitTypeBadge = {
    opd:            { bg: '#eff6ff', color: '#1d4ed8', label: 'OPD' },
    pre_employment: { bg: '#faf5ff', color: '#7c3aed', label: 'Pre-Employment' },
    follow_up:      { bg: '#fffbeb', color: '#b45309', label: 'Follow-up' },
}
</script>

<template>
    <AppLayout title="Nurse Intake">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <HeartPulse class="w-5 h-5 text-emerald-600"/>
                        Nurse Intake
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">Interview Room · Vitals & Patient Assessment</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"/>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"/>
                        </span>
                        Live · 10s
                    </div>
                    <a :href="route('queue.room', 'interview_room')" target="_blank">
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7">
                            <Activity class="w-3.5 h-3.5"/>
                            Room Screen
                        </Button>
                    </a>
                </div>
            </div>
        </template>

        <!-- ── TAB NAV ────────────────────────────────── -->
        <div class="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-xl w-fit">
            <button v-for="tab in [
                { key: 'today',   label: 'Today\'s Queue', count: summary.today         },
                { key: 'pending', label: 'No Vitals Yet',  count: summary.pending       },
                { key: 'history', label: 'History',         count: summary.done_today   },
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                    activeTab === tab.key
                        ? 'bg-white shadow-sm text-slate-800'
                        : 'text-slate-500 hover:text-slate-700'
                ]">
                <HeartPulse v-if="tab.key === 'today'"   class="w-3.5 h-3.5"/>
                <AlertTriangle v-if="tab.key === 'pending'" class="w-3.5 h-3.5"/>
                <FileText v-if="tab.key === 'history'" class="w-3.5 h-3.5"/>
                {{ tab.label }}
                <span :class="[
                    'px-1.5 py-0.5 rounded-md text-xs font-black min-w-[20px] text-center',
                    activeTab === tab.key
                        ? tab.key === 'pending'
                            ? 'bg-amber-500 text-white'
                            : 'bg-emerald-600 text-white'
                        : 'bg-slate-200 text-slate-600'
                ]">{{ tab.count }}</span>
            </button>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 1: TODAY'S QUEUE                           -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'today'">

            <div v-if="!todayQueue.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <CheckCircle2 class="w-6 h-6 text-emerald-400"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No patients in interview room today</p>
                <p class="text-xs text-slate-400 mt-1">Queue is clear</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">Queue</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Queue Status</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Vitals Status</th>
                            <th class="px-4 py-2.5 w-36"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in todayQueue" :key="item.id"
                            class="hover:bg-slate-50/60 transition-colors group"
                            :class="item.status === 'serving' ? 'bg-emerald-50/30' : ''">

                            <!-- Queue Number -->
                            <td class="px-4 py-3">
                                <span class="text-base font-black font-mono text-slate-700">
                                    {{ item.queue_number }}
                                </span>
                            </td>

                            <!-- Patient -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                        style="background-color:#059669">
                                        {{ item.patient.full_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 leading-tight">
                                            {{ item.patient.full_name }}
                                        </p>
                                        <p class="text-xs text-slate-400 font-mono">
                                            {{ item.patient.patient_code }} · {{ item.patient.age_sex }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Visit type + priority inline -->
                                <div class="flex items-center gap-1.5 mt-1.5 ml-9">
                                    <span v-if="item.visit"
                                        class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{
                                            background: visitTypeBadge[item.visit.visit_type]?.bg,
                                            color: visitTypeBadge[item.visit.visit_type]?.color
                                        }">
                                        {{ visitTypeBadge[item.visit.visit_type]?.label }}
                                    </span>
                                    <span v-if="item.visit?.employer_company"
                                        class="text-xs text-slate-400">
                                        · {{ item.visit.employer_company }}
                                    </span>
                                    <!-- Priority badge -->
                                    <span v-if="priorityConfig[item.priority]"
                                        class="text-xs font-bold px-1.5 py-0.5 rounded"
                                        :style="{
                                            background: priorityConfig[item.priority].bg,
                                            color: priorityConfig[item.priority].color
                                        }">
                                        {{ priorityConfig[item.priority].label }}
                                    </span>
                                </div>
                            </td>

                            <!-- Queue Status -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5"
                                    :style="{ color: queueStatusConfig[item.status]?.color }">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                        :style="{ background: queueStatusConfig[item.status]?.dot }"/>
                                    <span class="text-xs font-semibold">
                                        {{ queueStatusConfig[item.status]?.label ?? item.status }}
                                    </span>
                                </div>
                                <div v-if="item.status === 'completed' && !item.visit?.has_vitals"
                                    class="flex items-center gap-1 mt-1">
                                    <AlertTriangle class="w-3 h-3 text-amber-500"/>
                                    <span class="text-xs text-amber-600">Awaiting vitals</span>
                                </div>
                            </td>

                            <!-- Vitals Status -->
                            <td class="px-4 py-3">
                                <div v-if="item.visit?.has_vitals"
                                    class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-semibold">Recorded</span>
                                </div>
                                <div v-else class="flex items-center gap-1.5 text-amber-600">
                                    <Clock class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-semibold">Not yet taken</span>
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-3 text-right">
                                <Link v-if="item.visit?.id"
                                    :href="route('nurse.vitals', item.visit.id)">
                                    <Button size="sm"
                                        class="text-xs h-7 gap-1.5 text-white opacity-80 group-hover:opacity-100 transition-opacity"
                                        :style="{
                                            backgroundColor: item.visit?.has_vitals ? '#10b981' : '#1B4F9B'
                                        }">
                                        <ClipboardList class="w-3.5 h-3.5"/>
                                        {{ item.visit?.has_vitals ? 'Update' : 'Record Vitals' }}
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 2: PENDING — Visits with no vitals         -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'pending'">

            <!-- Info note -->
            <div class="flex items-center gap-2 mb-4 px-3 py-2.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700">
                <AlertTriangle class="w-4 h-4 flex-shrink-0"/>
                <span>These are visits from <strong>any date</strong> where vitals have not been recorded yet.</span>
            </div>

            <!-- Search -->
            <div class="flex items-center gap-2 mb-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                    <Input v-model="search" placeholder="Search patient name or code..."
                        class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                </div>
                <Button size="sm" class="h-8 text-xs gap-1.5"
                    style="background-color:#1B4F9B; color:white;" @click="applyFilters">
                    <Search class="w-3.5 h-3.5"/> Search
                </Button>
                <Button v-if="search" size="sm" variant="outline" class="h-8 text-xs"
                    @click="clearFilters">Clear</Button>
            </div>

            <div v-if="!pending.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No pending vitals</p>
                <p class="text-xs text-slate-400 mt-1">All patients have vitals recorded</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Visit Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Visit Type</th>
                            <th class="px-4 py-2.5 w-32"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in pending.data" :key="r.id"
                            class="hover:bg-amber-50/30 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <span v-if="r.employer" class="text-xs text-purple-600">{{ r.employer }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ r.visit_date }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                    :style="{
                                        background: visitTypeBadge[r.visit_type]?.bg,
                                        color: visitTypeBadge[r.visit_type]?.color
                                    }">
                                    {{ visitTypeBadge[r.visit_type]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('nurse.vitals', r.visit_id)">
                                    <Button size="sm"
                                        class="text-xs h-7 gap-1 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                        style="background-color:#1B4F9B;">
                                        <ClipboardList class="w-3 h-3"/> Record
                                        <ChevronRight class="w-3 h-3"/>
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">
                        {{ pending.from }}–{{ pending.to }} of {{ pending.total }} records
                    </p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in pending.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#1B4F9B' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 3: HISTORY — Searchable vitals records     -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'history'">

            <!-- Search + Date -->
            <div class="flex items-center gap-2 mb-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                    <Input v-model="search" placeholder="Search patient name or code..."
                        class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                </div>
                <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                    <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                    <Input v-model="date" type="date"
                        class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32"
                        @change="applyFilters"/>
                </div>
                <Button size="sm" class="h-8 text-xs gap-1.5"
                    style="background-color:#1B4F9B; color:white;" @click="applyFilters">
                    <Search class="w-3.5 h-3.5"/> Search
                </Button>
                <Button v-if="search || date" size="sm" variant="outline" class="h-8 text-xs"
                    @click="clearFilters">Clear</Button>
            </div>

            <div v-if="!history.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <FileText class="w-10 h-10 text-slate-300 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No records found</p>
                <p class="text-xs text-slate-400 mt-1">Try a different name or date</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Visit Date</th>
                            <!-- Key vitals summary -->
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Key Vitals</th>
                            <th class="px-4 py-2.5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in history.data" :key="r.id"
                            class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{
                                            background: visitTypeBadge[r.visit_type]?.bg,
                                            color: visitTypeBadge[r.visit_type]?.color
                                        }">
                                        {{ visitTypeBadge[r.visit_type]?.label }}
                                    </span>
                                    <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-medium">{{ r.visit_date }}</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5 ml-5">{{ r.recorded_at }}</p>
                            </td>
                            <!-- Vitals snapshot -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 flex-wrap text-xs">
                                    <div v-if="r.bp" class="flex items-center gap-1">
                                        <span class="text-slate-400">BP</span>
                                        <span class="font-mono font-bold text-slate-700">{{ r.bp }}</span>
                                    </div>
                                    <div v-if="r.weight_kg" class="flex items-center gap-1">
                                        <span class="text-slate-400">Wt</span>
                                        <span class="font-mono font-bold text-slate-700">{{ r.weight_kg }}kg</span>
                                    </div>
                                    <div v-if="r.bmi" class="flex items-center gap-1">
                                        <span class="text-slate-400">BMI</span>
                                        <span class="font-mono font-bold"
                                            :class="
                                                r.bmi_category === 'Normal' ? 'text-emerald-600' :
                                                r.bmi_category === 'Obese' || r.bmi_category === 'Underweight' ? 'text-red-600' :
                                                'text-amber-600'">
                                            {{ r.bmi }} ({{ r.bmi_category }})
                                        </span>
                                    </div>
                                    <div v-if="r.temperature_celsius" class="flex items-center gap-1">
                                        <span class="text-slate-400">Temp</span>
                                        <span class="font-mono font-bold"
                                            :class="r.temperature_celsius >= 37.5 ? 'text-red-600' : 'text-slate-700'">
                                            {{ r.temperature_celsius }}°C
                                        </span>
                                    </div>
                                    <div v-if="r.pulse_rate" class="flex items-center gap-1">
                                        <span class="text-slate-400">PR</span>
                                        <span class="font-mono font-bold text-slate-700">{{ r.pulse_rate }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('nurse.vitals', r.visit_id)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                        <ClipboardList class="w-3 h-3"/> View
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">
                        {{ history.from }}–{{ history.to }} of {{ history.total }} records
                    </p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in history.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#1B4F9B' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
