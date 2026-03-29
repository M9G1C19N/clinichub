<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import {
    FlaskConical, ScanLine, TestTube, Stethoscope,
    Bell, CheckCircle, XCircle, SkipForward,
    RotateCcw, ChevronRight, UserCheck, Clock,
    AlertTriangle, Users, Activity,
} from 'lucide-vue-next'
const props = defineProps({
    queue:     Array,
    room:      String,
    roomLabel: String,
})

const roomIconMap = {
    laboratory:     FlaskConical,
    xray_utz:       ScanLine,
    drug_test:      TestTube,
    interview_room: Stethoscope,
}

const roomColorMap = {
    laboratory:     '#3B82F6',
    xray_utz:       '#8B5CF6',
    drug_test:      '#F43F5E',
    interview_room: '#10B981',
}

const statusBadge = {
    waiting:  'bg-slate-100 text-slate-600',
    calling:  'bg-blue-100 text-blue-700',
    serving:  'bg-emerald-100 text-emerald-700',
    no_show:  'bg-red-100 text-red-600',
    skipped:  'bg-orange-100 text-orange-600',
}

const roomBg = {
    laboratory:     'bg-blue-50 border-blue-200',
    xray_utz:       'bg-purple-50 border-purple-200',
    drug_test:      'bg-rose-50 border-rose-200',
    interview_room: 'bg-emerald-50 border-emerald-200',
}

const statusConfig = {
    waiting:  { class: 'bg-slate-100 text-slate-600',    label: 'Waiting' },
    calling:  { class: 'bg-blue-100 text-blue-700',      label: 'Calling' },
    serving:  { class: 'bg-emerald-100 text-emerald-700',label: 'Serving' },
    no_show:  { class: 'bg-red-100 text-red-600',        label: 'No Show' },
    skipped:  { class: 'bg-orange-100 text-orange-600',  label: 'Skipped' },
}

const priorityConfig = {
    urgent:   { class: 'bg-red-500 text-white',       label: 'URGENT' },
    pregnant: { class: 'bg-pink-100 text-pink-700',   label: 'PREGNANT' },
    pwd:      { class: 'bg-blue-100 text-blue-700',   label: 'PWD' },
    senior:   { class: 'bg-amber-100 text-amber-700', label: 'SENIOR' },
}
function markServing(id) {
    router.patch(route('queue.serving', id), {}, { preserveScroll: true })
}
function markComplete(id) {
    router.patch(route('queue.complete', id), {}, { preserveScroll: true })
}
function markNoShow(id) {
    router.patch(route('queue.no-show', id), {}, { preserveScroll: true })
}
function recall(id) {
    router.patch(route('queue.recall', id), {}, { preserveScroll: true })
}
function skip(id) {
    router.patch(route('queue.skip', id), {}, { preserveScroll: true })
}
function callNext() {
    router.post(route('queue.call-next'), { room: props.room }, { preserveScroll: true })
}

const currentColor = roomColor[props.room]
</script>

<template>
    <AppLayout :title="roomLabel">
        <template #header>
            <div class="flex items-center justify-between">
                <!-- Room identity -->
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                        :style="{ background: currentColor }">
                        <component :is="roomIconMap[room]" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">{{ roomLabel }}</h1>
                        <div class="flex items-center gap-3 mt-0.5">
                            <span class="flex items-center gap-1 text-xs text-slate-400">
                                <Users class="w-3 h-3" />
                                {{ queue.length }} in queue
                            </span>
                            <span class="flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                <Activity class="w-3 h-3" />
                                Live
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Call Next -->
                <Button @click="callNext"
                    class="gap-2 font-semibold"
                    :style="{ backgroundColor: currentColor }">
                    <Bell class="w-4 h-4" />
                    Call Next Patient
                </Button>
            </div>
        </template>

        <!-- ── Empty State ─────────────────────────── -->
        <div v-if="queue.length === 0"
            class="bg-card rounded-2xl border shadow-sm py-20 text-center">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center"
                :style="{ background: currentColor + '15' }">
                <component :is="roomIconMap[room]" class="w-8 h-8" :style="{ color: currentColor }"/>
            </div>
            <p class="text-lg font-semibold text-slate-500">Queue is clear</p>
            <p class="text-sm text-slate-400 mt-1">No patients waiting in {{ roomLabel }}</p>
        </div>

        <!-- ── Queue Cards ──────────────────────────── -->
        <div v-else class="space-y-3">
            <div v-for="(assignment, i) in queue" :key="assignment.id"
                class="bg-card rounded-2xl border shadow-sm overflow-hidden transition-all"
                :class="[
                    assignment.status === 'serving' ? 'ring-2 ring-emerald-400 shadow-emerald-100' :
                    assignment.status === 'calling' ? 'ring-2 ring-blue-400 shadow-blue-100 shadow-md' : ''
                ]">

                <!-- Status bar at top -->
                <div class="h-1 w-full"
                    :style="{
                        background:
                            assignment.status === 'serving' ? '#10B981' :
                            assignment.status === 'calling' ? currentColor :
                            assignment.status === 'waiting' ? '#E2E8F0' : '#F87171'
                    }"/>

                <div class="flex items-stretch gap-0 p-5">

                    <!-- Queue Number Block -->
                    <div class="flex flex-col items-center justify-center w-24 flex-shrink-0 pr-5 border-r border-slate-100">
                        <p class="text-xs text-muted-foreground mb-1"># {{ i + 1 }}</p>
                        <p class="text-3xl font-black font-mono leading-none"
                            :style="{ color: currentColor }">
                            {{ assignment.queue_number }}
                        </p>
                        <span :class="['mt-2 text-xs font-semibold px-2 py-0.5 rounded-full', statusConfig[assignment.status]?.class]">
                            {{ statusConfig[assignment.status]?.label }}
                        </span>
                    </div>

                    <!-- Patient Info -->
                    <div class="flex-1 min-w-0 px-5">
                        <!-- Name + Priority -->
                        <div class="flex items-center gap-2 mb-1.5">
                            <p class="text-base font-bold text-slate-800 truncate">
                                {{ assignment.patient_name }}
                            </p>
                            <span v-if="priorityConfig[assignment.priority]"
                                :class="['text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0',
                                    priorityConfig[assignment.priority].class]">
                                {{ priorityConfig[assignment.priority].label }}
                            </span>
                        </div>

                        <!-- Details row -->
                        <div class="flex items-center gap-3 text-xs text-muted-foreground mb-3">
                            <span class="font-mono font-semibold text-slate-500">{{ assignment.patient_code }}</span>
                            <span>·</span>
                            <span>{{ assignment.age_sex }}</span>
                            <span>·</span>
                            <span class="flex items-center gap-1">
                                <Clock class="w-3 h-3" />
                                {{ assignment.issued_at }}
                            </span>
                        </div>

                        <!-- Services chips -->
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="svc in assignment.services" :key="svc"
                                class="inline-flex items-center text-xs font-mono font-semibold px-2.5 py-1 rounded-lg border"
                                :style="{
                                    background: currentColor + '12',
                                    borderColor: currentColor + '30',
                                    color: currentColor
                                }">
                                {{ svc }}
                            </span>
                        </div>
                    </div>

                    <Separator orientation="vertical" class="mx-1 h-auto" />

                    <!-- Action Buttons -->
                    <div class="flex flex-col justify-center gap-2 pl-5 flex-shrink-0 min-w-[140px]">

                        <!-- WAITING -->
                        <template v-if="assignment.status === 'waiting'">
                            <Button size="sm" class="gap-2 text-xs w-full"
                                :style="{ backgroundColor: currentColor }"
                                @click="callNext">
                                <Bell class="w-3.5 h-3.5" />
                                Call
                            </Button>
                            <Button size="sm" variant="outline" class="gap-2 text-xs w-full"
                                @click="skip(assignment.id)">
                                <SkipForward class="w-3.5 h-3.5" />
                                Skip
                            </Button>
                        </template>

                        <!-- CALLING -->
                        <template v-else-if="assignment.status === 'calling'">
                            <Button size="sm"
                                class="gap-2 text-xs w-full bg-emerald-500 hover:bg-emerald-600 text-white"
                                @click="markServing(assignment.id)">
                                <UserCheck class="w-3.5 h-3.5" />
                                Patient In
                            </Button>
                            <Button size="sm" variant="outline" class="gap-2 text-xs w-full"
                                @click="recall(assignment.id)">
                                <RotateCcw class="w-3.5 h-3.5" />
                                Re-call ({{ assignment.call_count }}×)
                            </Button>
                            <Button size="sm" variant="ghost"
                                class="gap-2 text-xs w-full text-red-500 hover:text-red-700 hover:bg-red-50"
                                @click="markNoShow(assignment.id)">
                                <XCircle class="w-3.5 h-3.5" />
                                No Show
                            </Button>
                        </template>

                        <!-- SERVING -->
                        <template v-else-if="assignment.status === 'serving'">
                            <Button size="sm"
                                class="gap-2 text-xs w-full"
                                :style="{ backgroundColor: currentColor }"
                                @click="markComplete(assignment.id)">
                                <CheckCircle class="w-3.5 h-3.5" />
                                Mark Complete
                            </Button>
                            <p class="text-xs text-center text-muted-foreground">
                                Currently serving
                            </p>
                        </template>

                        <!-- NO SHOW / SKIPPED -->
                        <template v-else>
                            <div class="flex items-center gap-1.5 justify-center text-xs text-slate-400">
                                <AlertTriangle class="w-3.5 h-3.5" />
                                {{ statusConfig[assignment.status]?.label }}
                            </div>
                        </template>

                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
