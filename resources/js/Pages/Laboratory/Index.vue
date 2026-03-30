<script setup>
import { onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    FlaskConical, CheckCircle2, AlertTriangle,
    Clock, Activity, Users,
} from 'lucide-vue-next'

const props = defineProps({
    queue:    Array,
    released: Array,
    summary:  Object,
})

let refreshTimer = null
onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only: ['queue', 'released', 'summary'], preserveScroll: true })
    }, 10000)
})
onUnmounted(() => clearInterval(refreshTimer))

const priorityConfig = {
    urgent:   { class: 'bg-red-500 text-white',       label: 'URGENT'   },
    pregnant: { class: 'bg-pink-100 text-pink-700',   label: 'PREGNANT' },
    pwd:      { class: 'bg-blue-100 text-blue-700',   label: 'PWD'      },
    senior:   { class: 'bg-amber-100 text-amber-700', label: 'SENIOR'   },
}

const visitTypeBadge = {
    opd:            'bg-blue-100 text-blue-700',
    pre_employment: 'bg-purple-100 text-purple-700',
}

const visitTypeLabel = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
}
</script>

<template>
    <AppLayout title="Laboratory">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Laboratory</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        {{ queue.length }} patient(s) in queue today
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"/>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"/>
                        </span>
                        <span class="text-xs text-emerald-600 font-medium">Live · 10s</span>
                    </div>
                    <a :href="route('queue.room', 'laboratory')" target="_blank">
                        <Button variant="outline" size="sm" class="gap-2">
                            <Activity class="w-4 h-4"/>
                            Room Screen
                        </Button>
                    </a>
                </div>
            </div>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-5">
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <Users class="w-5 h-5 text-amber-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Waiting</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.waiting }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <FlaskConical class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Processing</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.serving }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <CheckCircle2 class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Released Today</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.released }}</p>
                </div>
            </div>
        </div>

        <!-- Queue -->
        <div class="space-y-3 mb-6">
            <div class="flex items-center gap-2 px-1">
                <span class="w-1 h-4 rounded-full inline-block bg-blue-500"></span>
                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                    Today's Lab Queue
                </h3>
            </div>

            <div v-if="queue.length === 0"
                class="bg-card rounded-2xl border shadow-sm py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mx-auto mb-3">
                    <FlaskConical class="w-7 h-7 text-blue-300"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No patients in lab queue</p>
            </div>

            <div v-for="(a, i) in queue" :key="a.id"
                class="bg-card rounded-2xl border shadow-sm overflow-hidden"
                :class="a.status === 'serving' ? 'ring-2 ring-blue-400' : ''">

                <div class="h-1 w-full"
                    :style="{ background: a.status === 'serving' ? '#3B82F6' :
                                          a.status === 'calling' ? '#60A5FA' : '#E2E8F0' }"/>

                <div class="flex items-center p-5 gap-0">

                    <!-- Number -->
                    <div class="flex flex-col items-center justify-center w-24 flex-shrink-0 pr-5 border-r border-slate-100">
                        <p class="text-xs text-muted-foreground mb-1">#{{ i + 1 }}</p>
                        <p class="text-3xl font-black font-mono leading-none text-blue-600">
                            {{ a.queue_number }}
                        </p>
                        <span :class="['mt-2 text-xs font-semibold px-2 py-0.5 rounded-full',
                            a.status === 'serving' ? 'bg-blue-100 text-blue-700' :
                            a.status === 'calling' ? 'bg-sky-100 text-sky-700' :
                                                     'bg-slate-100 text-slate-600']">
                            {{ a.status }}
                        </span>
                    </div>

                    <!-- Patient -->
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
                            <span v-if="a.visit"
                                :class="['text-xs font-semibold px-2.5 py-1 rounded-full',
                                    visitTypeBadge[a.visit.visit_type]]">
                                {{ visitTypeLabel[a.visit.visit_type] }}
                            </span>
                            <span v-if="a.visit?.employer_company"
                                class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                {{ a.visit.employer_company }}
                            </span>
                            <!-- Services chips -->
                            <span v-for="svc in (a.visit?.services ?? [])" :key="svc.code"
                                class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                                {{ svc.code }}
                            </span>
                            <!-- Result status -->
                            <span v-if="a.visit?.is_released"
                                class="flex items-center gap-1 text-xs text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold">
                                <CheckCircle2 class="w-3 h-3"/> Released
                            </span>
                            <span v-else-if="a.visit?.has_results"
                                class="flex items-center gap-1 text-xs text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full font-semibold">
                                <Clock class="w-3 h-3"/> Results Pending Release
                            </span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="flex-shrink-0 pl-5">
                        <Link v-if="a.visit?.id"
                            :href="route('laboratory.enter', a.visit.id)">
                            <Button class="gap-2 text-white"
                                :style="{ backgroundColor:
                                    a.visit?.is_released ? '#10B981' : '#3B82F6' }">
                                <FlaskConical class="w-4 h-4"/>
                                {{ a.visit?.is_released ? 'View Results' :
                                   a.visit?.has_results ? 'Update Results' : 'Enter Results' }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Released Today -->
        <div v-if="released.length > 0">
            <div class="flex items-center gap-2 px-1 mb-3">
                <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                    Released Today ({{ released.length }})
                </h3>
            </div>
            <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="background-color:#0F2044">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-white/70 uppercase">Patient</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase">Request No.</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase">Released</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="r in released" :key="r.id"
                            class="hover:bg-slate-50 transition-colors group">
                            <td class="px-5 py-3.5">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-muted-foreground font-mono">{{ r.patient_code }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                    {{ r.request_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5 text-xs text-emerald-700">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    {{ r.released_at }}
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <Link :href="route('laboratory.enter', r.visit_id)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="outline" size="sm" class="text-xs gap-1.5">
                                        <FlaskConical class="w-3.5 h-3.5"/>
                                        View / Print
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AppLayout>
</template>
