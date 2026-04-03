<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { HeartPulse, AlertCircle, CheckCircle2, ClipboardList, FlaskConical, Pill, ChevronRight, Wifi, Activity } from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String,
    stats: Object,
    queue: Array,
    visitTypeCounts: Object,
    backlog: Array,
})

const priorityStyle = {
    urgent:  { bg:'#FEF2F2', text:'#DC2626' },
    pregnant:{ bg:'#FDF2F8', text:'#DB2777' },
    pwd:     { bg:'#EFF6FF', text:'#1D4ED8' },
    senior:  { bg:'#FFFBEB', text:'#D97706' },
    regular: { bg:'#F8FAFC', text:'#475569' },
}
const visitLabel = { pre_employment:'Pre-Emp', annual_pe:'Annual PE', exit_pe:'Exit PE', opd:'OPD', follow_up:'Follow-up', lab_only:'Lab Only' }
const visitColor  = { pre_employment:'#7C3AED', annual_pe:'#16A34A', exit_pe:'#C2410C', opd:'#1D4ED8', follow_up:'#0369A1' }
const totalVT = computed(() => Math.max(Object.values(props.visitTypeCounts ?? {}).reduce((s,v)=>s+v,0),1))
const activeQ = computed(() => (props.queue??[]).filter(q=>['waiting','calling','serving'].includes(q.status)))
const doneQ   = computed(() => (props.queue??[]).filter(q=>q.status==='completed').length)

let t = null
onMounted(() => { t = setInterval(()=>router.reload({only:['stats','queue','visitTypeCounts','backlog']}),20000) })
onUnmounted(() => clearInterval(t))
</script>

<template>
<AppLayout title="Nurse Dashboard">
    <template #header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Nurse Station</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 text-xs text-emerald-600 font-semibold"><Wifi class="w-3.5 h-3.5"/> Live</span>
                <a :href="route('nurse.index')" class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg text-white" style="background:#10B981">
                    <HeartPulse class="w-3.5 h-3.5"/> Nurse Intake
                </a>
            </div>
        </div>
    </template>

    <div class="space-y-5">
        <!-- KPI -->
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-3">
            <div v-for="(val,key,i) in { 'In Queue':stats.in_queue, 'Vitals Done':stats.vitals_taken, 'Need Vitals':stats.pending, 'PE Today':stats.pe_today, 'Rx Today':stats.prescriptions_today, 'Lab Pending':stats.lab_pending_today }" :key="key"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-2xl font-black text-slate-800">{{ val }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ key }}</p>
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
                <div class="overflow-y-auto" style="max-height:420px;">
                    <div v-if="!activeQ.length" class="py-14 text-center text-slate-400 text-sm">No active patients in queue</div>
                    <div v-else class="divide-y divide-slate-50">
                        <div v-for="q in activeQ" :key="q.id" class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50/50 transition-colors">
                            <div class="w-14 text-center flex-shrink-0">
                                <p class="text-xl font-black font-mono text-slate-800">{{ q.queue_number }}</p>
                                <p :class="['text-xs font-bold', q.status==='serving'?'text-emerald-600': q.status==='calling'?'text-blue-600 animate-pulse':'text-slate-400']">{{ q.status }}</p>
                            </div>
                            <div class="w-px h-10 bg-slate-100 flex-shrink-0"/>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-bold text-slate-800 truncate">{{ q.patient_name }}</p>
                                    <span v-if="q.priority!=='regular'" class="text-xs font-bold px-1.5 py-0.5 rounded-full flex-shrink-0"
                                        :style="{background:priorityStyle[q.priority]?.bg,color:priorityStyle[q.priority]?.text}">
                                        {{ q.priority.toUpperCase() }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400">{{ q.patient_code }} · {{ q.age_sex }}</p>
                                <p v-if="q.employer" class="text-xs text-violet-500 font-semibold truncate">{{ q.employer }}</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 flex-shrink-0">{{ visitLabel[q.visit_type]??q.visit_type }}</span>
                            <div class="flex-shrink-0 w-16 text-center">
                                <span v-if="q.has_vitals" class="flex items-center gap-1 text-xs text-emerald-600 font-bold justify-center">
                                    <CheckCircle2 class="w-3.5 h-3.5"/> Done
                                </span>
                                <span v-else class="flex items-center gap-1 text-xs text-amber-600 font-bold justify-center">
                                    <AlertCircle class="w-3.5 h-3.5"/> Needed
                                </span>
                            </div>
                            <a v-if="q.visit_id" :href="route('nurse.enter',q.visit_id)"
                                class="flex-shrink-0 text-xs font-bold px-2.5 py-1.5 rounded-lg text-white flex items-center gap-1" style="background:#0F2044">
                                Enter <ChevronRight class="w-3 h-3"/>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right -->
            <div class="space-y-4">
                <!-- Visit type bars -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Today's Visit Types</h3>
                    <div class="space-y-2.5">
                        <div v-for="(count,type) in visitTypeCounts" :key="type">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-600 font-semibold">{{ visitLabel[type]??type }}</span>
                                <span class="font-black text-slate-800">{{ count }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" :style="{width:(count/totalVT*100)+'%',background:visitColor[type]??'#94a3b8'}"/>
                            </div>
                        </div>
                        <p v-if="!Object.keys(visitTypeCounts??{}).length" class="text-xs text-slate-400 text-center py-2">No data yet</p>
                    </div>
                </div>

                <!-- Backlog -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-amber-400"></span>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Backlog</h3>
                        <span class="text-xs text-slate-400">(no vitals)</span>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <p v-if="!backlog?.length" class="py-6 text-center text-xs text-slate-400">No backlog</p>
                        <div v-for="b in backlog" :key="b.id" class="px-4 py-2.5 flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ b.patient_name }}</p>
                                <p class="text-xs text-amber-500 font-semibold">{{ b.visit_date }} · {{ b.days_ago }}d ago</p>
                            </div>
                            <a :href="route('nurse.enter',b.id)" class="text-xs text-blue-500 hover:underline ml-2 flex-shrink-0">Enter</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>
