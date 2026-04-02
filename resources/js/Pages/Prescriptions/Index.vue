<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button,} from '@/components/ui/button'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Pill, Plus, Search, Printer, Trash2, ClipboardList, Calendar, AlertTriangle } from 'lucide-vue-next'

const props = defineProps({
    prescriptions: Object,
    search:        String,
    status:        String,
    summary:       Object,
})

const searchInput  = ref(props.search ?? '')
const statusFilter = ref(props.status ?? 'all')

function doSearch() {
    router.get(route('prescriptions.index'), {
        search: searchInput.value,
        status: statusFilter.value === 'all' ? '' : statusFilter.value,
    }, { preserveState: true, replace: true })
}

function deleteRx(id) {
    if (!confirm('Remove this prescription?')) return
    router.delete(route('prescriptions.destroy', id))
}
</script>

<template>
    <AppLayout title="Prescriptions">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Prescriptions</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Digital Rx pad — write and print prescriptions</p>
                </div>
                <Link :href="route('prescriptions.create')">
                    <Button class="gap-2 text-white" style="background:#1B4F9B;">
                        <Plus class="w-4 h-4"/> New Prescription
                    </Button>
                </Link>
            </div>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-5">
            <div v-for="card in [
                {label:'Total Written', val:summary.total,      icon:ClipboardList, color:'#1B4F9B'},
                {label:'Today',         val:summary.today,       icon:Calendar,      color:'#8B5CF6'},
                {label:'S2/Controlled', val:summary.controlled, icon:AlertTriangle, color:'#F59E0B'},
            ]" :key="card.label"
                class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                    :style="{ background: card.color + '18' }">
                    <component :is="card.icon" class="w-5 h-5" :style="{ color: card.color }"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">{{ card.label }}</p>
                    <p class="text-2xl font-black text-slate-800">{{ card.val }}</p>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="flex items-center gap-3 mb-4">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                <input v-model="searchInput" @keyup.enter="doSearch"
                    placeholder="Search patient or Rx number..."
                    class="w-full h-9 pl-9 pr-4 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"/>
            </div>
           <Select v-model="statusFilter" @update:modelValue="doSearch">
                <SelectTrigger class="h-9 px-3 text-sm border border-slate-200 rounded-xl bg-white">
                    <SelectValue placeholder="All Types" />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value="all">All Types</SelectItem>
                    <SelectItem value="regular">Regular</SelectItem>
                    <SelectItem value="controlled">S2 / Controlled</SelectItem>
                </SelectContent>
            </Select>
            <Button size="sm" class="h-9 text-xs text-white" style="background:#1B4F9B;" @click="doSearch">
                Search
            </Button>
        </div>

        <!-- Empty -->
        <div v-if="!prescriptions.data?.length"
            class="bg-card rounded-xl border shadow-sm py-20 text-center">
            <Pill class="w-12 h-12 text-slate-200 mx-auto mb-3"/>
            <p class="text-slate-500 font-semibold">No prescriptions found</p>
            <Link :href="route('prescriptions.create')" class="mt-4 inline-block">
                <Button class="gap-2 text-white mt-3" style="background:#1B4F9B;">
                    <Plus class="w-4 h-4"/> Write Prescription
                </Button>
            </Link>
        </div>

        <!-- Table -->
        <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr style="background:#0F2044">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Rx No.</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Medications</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="rx in prescriptions.data" :key="rx.id"
                        class="hover:bg-slate-50 transition-colors group"
                        :class="rx.is_controlled ? 'bg-amber-50/20' : ''">
                        <td class="px-5 py-3.5 font-mono font-bold text-blue-700 text-sm">{{ rx.rx_number }}</td>
                        <td class="px-4 py-3.5">
                            <p class="font-semibold text-slate-800 text-sm">{{ rx.patient_name }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ rx.patient_code }} · {{ rx.patient_age_sex }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="space-y-0.5">
                                <p v-for="item in rx.items?.slice(0,2)" :key="item.drug"
                                    class="text-xs font-semibold text-slate-700">
                                    {{ item.drug }} {{ item.dosage }}
                                    <span v-if="item.form" class="text-slate-400 font-normal">{{ item.form }}</span>
                                </p>
                                <p v-if="rx.items?.length > 2" class="text-xs text-slate-400">+{{ rx.items.length - 2 }} more</p>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">{{ rx.rx_date }}</td>
                        <td class="px-4 py-3.5">
                            <span v-if="rx.is_controlled"
                                class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">
                                S2 Controlled
                            </span>
                            <span v-else class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                                Regular
                            </span>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a :href="route('prescriptions.print', rx.id)" target="_blank">
                                    <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                        <Printer class="w-3 h-3"/> Print
                                    </Button>
                                </a>
                                <Button variant="outline" size="sm"
                                    class="text-xs h-7 gap-1 text-red-600 border-red-200 hover:bg-red-50"
                                    @click="deleteRx(rx.id)">
                                    <Trash2 class="w-3 h-3"/> Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="px-5 py-3 border-t flex items-center justify-between text-xs text-slate-500">
                <span>{{ prescriptions.from }}–{{ prescriptions.to }} of {{ prescriptions.total }}</span>
                <div class="flex items-center gap-1">
                    <template v-for="link in prescriptions.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-state
                            class="px-2.5 py-1 rounded border text-xs"
                            :class="link.active ? 'text-white border-transparent' : 'border-slate-200'"
                            :style="link.active ? 'background:#1B4F9B' : ''"
                            v-html="link.label"/>
                        <span v-else class="px-2 py-1 text-slate-300" v-html="link.label"/>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
