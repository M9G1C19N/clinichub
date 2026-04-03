<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FlaskConical, AlertTriangle, CheckCircle2, ChevronRight, Wifi, Clock } from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String,
    stats: Object,
    queue: Array,
    testVolume: Object,
    recentReleases: Array,
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
const labStatusStyle = {
    pending:    { bg:'#FFFBEB', text:'#D97706' },
    processing: { bg:'#EFF6FF', text:'#1D4ED8' },
    released:   { bg:'#F0FDF4', text:'#16A34A' },
}
const catColor = { hematology:'#3b82f6', urinalysis:'#8b5cf6', chemistry:'#f59e0b', stool:'#84cc16', serology:'#ec4899', other:'#94a3b8' }
const totalVol = computed(() => Math.max(Object.values(props.testVolume??{}).reduce((s,v)=>s+Number(v),0),1))
const activeQ  = computed(() => (props.queue??[]).filter(q=>['waiting','calling','serving'].includes(q.status)))
const doneQ    = computed(() => (props.queue??[]).filter(q=>q.status==='completed').length)

let t = null
onMounted(() => { t = setInterval(()=>router.reload({only:['stats','queue','testVolume','recentReleases','backlog']}),20000) })
onUnmounted(() => clearInterval(t))
</script>

<template>
<AppLayout title="Laboratory Dashboard">
    <template #header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Laboratory</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 text-xs text-emerald-600 font-semibold"><Wifi class="w-3.5 h-3.5"/> Live</span>
                <a :href="route('laboratory.index')" class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg text-white" style="background:#3b82f6">
                    <FlaskConical class="w-3.5 h-3.5"/> Lab Requests
                </a>
            </div>
        </div>
    </template>

    <div class="space-y-5">
        <!-- KPI -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#EFF6FF">
                    <FlaskConical class="w-5 h-5" style="color:#3b82f6"/>
                </div>
                <div><p class="text-3xl font-black text-slate-800">{{ stats.in_queue }}</p><p class="text-xs text-slate-400">In Queue</p></div>
            </div>
            <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FFFBEB">
                    <Clock class="w-5 h-5" style="color:#D97706"/>
                </div>
                <div><p class="text-3xl font-black" style="color:#D97706">{{ stats.pending_entry }}</p><p class="text-xs text-slate-400">Pending Entry</p></div>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#F0FDF4">
                    <CheckCircle2 class="w-5 h-5" style="color:#16A34A"/>
                </div>
                <div><p class="text-3xl font-black" style="color:#16A34A">{{ stats.released_today }}</p><p class="text-xs text-slate-400">Released Today</p></div>
            </div>
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEF2F2">
                    <AlertTriangle class="w-5 h-5" style="color:#DC2626"/>
                </div>
                <div><p class="text-3xl font-black text-red-600">{{ stats.abnormal_today }}</p><p class="text-xs text-slate-400">Abnormal Results</p></div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Queue -->
            <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-blue-500"></span>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Lab Queue</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold">{{ activeQ.length }} active</span>
                    </div>
                    <span class="text-xs text-slate-400">{{ doneQ }} done today</span>
                </div>
                <div class="overflow-y-auto" style="max-height:400px;">
                    <p v-if="!activeQ.length" class="py-12 text-center text-slate-400 text-sm">No active patients</p>
                    <div v-else class="divide-y divide-slate-50">
                        <div v-for="q in activeQ" :key="q.id" class="px-4 py-2.5 flex items-center gap-3 hover:bg-slate-50/50 transition-colors">
                            <div class="w-14 text-center flex-shrink-0">
                                <p class="text-xl font-black font-mono text-slate-800">{{ q.queue_number }}</p>
                                <p :class="['text-xs font-bold',q.status==='serving'?'text-emerald-600':q.status==='calling'?'text-blue-600 animate-pulse':'text-slate-400']">{{ q.status }}</p>
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
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span v-for="s in q.services?.slice(0,5)" :key="s"
                                        class="text-xs px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold">{{ s }}</span>
                                </div>
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                                :style="{background:labStatusStyle[q.lab_status]?.bg??'#f1f5f9',color:labStatusStyle[q.lab_status]?.text??'#64748b'}">
                                {{ q.lab_status }}
                            </span>
                            <a v-if="q.visit_id" :href="route('laboratory.enter',q.visit_id)"
                                class="flex-shrink-0 text-xs font-bold px-2.5 py-1.5 rounded-lg text-white flex items-center gap-1" style="background:#3b82f6">
                                Enter <ChevronRight class="w-3 h-3"/>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right -->
            <div class="space-y-4">
                <!-- Test volume -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Test Volume by Category</h3>
                    <div class="space-y-2.5">
                        <div v-for="(count,cat) in testVolume" :key="cat">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold capitalize text-slate-600">{{ cat }}</span>
                                <span class="font-black text-slate-800">{{ count }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" :style="{width:(count/totalVol*100)+'%',background:catColor[cat]??'#94a3b8'}"/>
                            </div>
                        </div>
                        <p v-if="!Object.keys(testVolume??{}).length" class="text-xs text-slate-400 text-center py-2">No results yet</p>
                    </div>
                </div>

                <!-- Recent Releases -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recent Releases</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <p v-if="!recentReleases?.length" class="py-6 text-center text-xs text-slate-400">None today</p>
                        <div v-for="r in recentReleases" :key="r.request_number" class="px-4 py-2.5">
                            <div class="flex justify-between items-start">
                                <p class="text-xs font-bold text-slate-800 truncate max-w-[120px]">{{ r.patient_name }}</p>
                                <span v-if="r.has_abnormal" class="flex items-center gap-0.5 text-xs text-red-600 font-bold">
                                    <AlertTriangle class="w-3 h-3"/> Abnormal
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 font-mono">{{ r.request_number }} · {{ r.released_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Backlog -->
                <div v-if="backlog?.length" class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-amber-400"></span>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Backlog</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <div v-for="b in backlog" :key="b.id" class="px-4 py-2.5 flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ b.patient_name }}</p>
                                <p class="text-xs text-amber-500 font-semibold">{{ b.date }}</p>
                            </div>
                            <a :href="route('laboratory.enter',b.visit_id)" class="text-xs text-blue-500 hover:underline flex-shrink-0 ml-2">Enter</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>
