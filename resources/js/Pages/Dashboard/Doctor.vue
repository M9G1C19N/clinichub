<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Stethoscope, AlertTriangle, CheckCircle2, FlaskConical, ScanLine, TestTube, Pill, ChevronRight, Wifi, Clock, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String,
    stats: Object,
    pending: Array,
    completedToday: Array,
    classificationSummary: Object,
})

const statusDot  = { none:'#cbd5e1', pending:'#f59e0b', processing:'#3b82f6', released:'#22c55e' }
const visitLabel = { pre_employment:'Pre-Emp', annual_pe:'Annual PE', exit_pe:'Exit PE', opd:'OPD', follow_up:'Follow-up' }
const classLabel = { fit:'FIT', unfit:'UNFIT', fit_with_remarks:'Fit w/ Remarks', deferred:'Deferred', for_treatment:'For Treatment' }
const classColor = { fit:'#16A34A', unfit:'#DC2626', fit_with_remarks:'#D97706', deferred:'#7C3AED', for_treatment:'#0369A1' }
const classBg    = { fit:'#F0FDF4', unfit:'#FEF2F2', fit_with_remarks:'#FFFBEB', deferred:'#FAF5FF', for_treatment:'#F0F9FF' }

const readyList    = computed(() => (props.pending??[]).filter(p=>p.all_results_in))
const waitingList  = computed(() => (props.pending??[]).filter(p=>!p.all_results_in))
const totalClass   = computed(() => Math.max(Object.values(props.classificationSummary??{}).reduce((s,v)=>s+Number(v),0),1))

let t = null
onMounted(() => { t = setInterval(()=>router.reload({only:['stats','pending','completedToday','classificationSummary']}),25000) })
onUnmounted(() => clearInterval(t))
</script>

<template>
<AppLayout title="Doctor Dashboard">
    <template #header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Doctor's Station</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 text-xs text-emerald-600 font-semibold"><Wifi class="w-3.5 h-3.5"/> Live</span>
                <a :href="route('doctor.index')" class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg text-white" style="background:#7C3AED">
                    <Stethoscope class="w-3.5 h-3.5"/> Open Consultations
                </a>
            </div>
        </div>
    </template>

    <div class="space-y-5">
        <!-- KPI -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-4 col-span-1">
                <p class="text-3xl font-black text-emerald-600">{{ stats.ready_for_review }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-semibold">Ready to Review</p>
                <p class="text-xs text-slate-400">All results in</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-3xl font-black text-slate-800">{{ stats.pending_total }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-semibold">Total Pending</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-3xl font-black" style="color:#1D4ED8">{{ stats.completed_today }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-semibold">Completed</p>
                <p class="text-xs text-slate-400">today</p>
            </div>
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-4">
                <p class="text-3xl font-black text-red-600">{{ stats.abnormal_today }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-semibold">Abnormal Results</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-3xl font-black" style="color:#7C3AED">{{ stats.pe_done_today }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-semibold">PE Completed</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-3xl font-black" style="color:#16A34A">{{ stats.my_prescriptions_today }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-semibold">My Rx Today</p>
                <p class="text-xs text-slate-400">All: {{ stats.all_prescriptions_today }}</p>
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
                    <div v-if="!readyList.length" class="py-8 text-center text-sm text-slate-400">No patients ready yet</div>
                    <div v-else class="divide-y divide-slate-50">
                        <div v-for="p in readyList" :key="p.id" class="px-4 py-2.5 flex items-center gap-3 hover:bg-emerald-50/30 transition-colors">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-bold text-slate-800 truncate">{{ p.patient_name }}</p>
                                    <span v-if="p.abnormal_count>0" class="flex items-center gap-0.5 text-xs font-bold text-red-600">
                                        <AlertTriangle class="w-3 h-3"/>{{ p.abnormal_count }} abnormal
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400">{{ p.patient_code }} · {{ p.age_sex }}</p>
                                <p v-if="p.employer" class="text-xs text-violet-500 truncate">{{ p.employer }}</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 flex-shrink-0">{{ visitLabel[p.visit_type]??p.visit_type }}</span>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <span title="Lab" class="w-2 h-2 rounded-full" :style="{background:statusDot[p.lab_status]}"/>
                                <span title="X-Ray" class="w-2 h-2 rounded-full" :style="{background:statusDot[p.xray_status]}"/>
                                <span title="Drug" class="w-2 h-2 rounded-full" :style="{background:statusDot[p.drug_status]}"/>
                            </div>
                            <a :href="route('doctor.enter',p.id)" class="flex-shrink-0 text-xs font-bold px-2.5 py-1.5 rounded-lg text-white flex items-center gap-1" style="background:#7C3AED">
                                Consult <ChevronRight class="w-3 h-3"/>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Waiting (results pending) -->
                <div v-if="waitingList.length" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b flex items-center gap-2">
                        <Clock class="w-4 h-4 text-amber-500"/>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Awaiting Results</h3>
                        <span class="ml-auto text-xs font-black text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">{{ waitingList.length }}</span>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <div v-for="p in waitingList" :key="p.id" class="px-4 py-2.5 flex items-center gap-3 hover:bg-slate-50/30">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ p.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ p.patient_code }} · {{ p.age_sex }}</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 flex-shrink-0">{{ visitLabel[p.visit_type]??p.visit_type }}</span>
                            <div class="flex items-center gap-1.5 flex-shrink-0 text-xs text-slate-500">
                                <span title="Lab"   class="w-2 h-2 rounded-full" :style="{background:statusDot[p.lab_status]}"/>
                                <span title="X-Ray" class="w-2 h-2 rounded-full" :style="{background:statusDot[p.xray_status]}"/>
                                <span title="Drug"  class="w-2 h-2 rounded-full" :style="{background:statusDot[p.drug_status]}"/>
                            </div>
                            <span class="text-xs text-amber-600 font-semibold flex-shrink-0">{{ p.wait_mins }}m</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="space-y-4">
                <!-- PE Classification -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">PE Classification Today</h3>
                    <div class="space-y-2">
                        <div v-for="(count,cls) in classificationSummary" :key="cls">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-bold" :style="{color:classColor[cls]??'#475569'}">{{ classLabel[cls]??cls }}</span>
                                <span class="font-black text-slate-800">{{ count }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" :style="{width:(Number(count)/totalClass*100)+'%',background:classColor[cls]??'#94a3b8'}"/>
                            </div>
                        </div>
                        <p v-if="!Object.keys(classificationSummary??{}).length" class="text-xs text-slate-400 text-center py-2">No PE done today</p>
                    </div>
                </div>

                <!-- Completed Today -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-blue-500"></span>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Completed Today</h3>
                    </div>
                    <div class="divide-y divide-slate-50 overflow-y-auto" style="max-height:280px;">
                        <p v-if="!completedToday?.length" class="py-6 text-center text-xs text-slate-400">None yet</p>
                        <div v-for="c in completedToday" :key="c.id" class="px-4 py-2.5">
                            <p class="text-xs font-bold text-slate-800 truncate">{{ c.patient_name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span v-if="c.pe_classification" class="text-xs font-bold" :style="{color:classColor[c.pe_classification]??'#475569'}">
                                    {{ classLabel[c.pe_classification]??c.pe_classification }}
                                </span>
                                <span v-else-if="c.icd10_code" class="text-xs text-blue-600 font-mono">{{ c.icd10_code }}</span>
                                <span class="text-xs text-slate-400 ml-auto">{{ c.finalized_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>
