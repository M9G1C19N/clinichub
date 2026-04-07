<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import LabSlipCBC from '@/Components/Lab/Slips/LabSlipCBC.vue'
import LabSlipUrinalysis from '@/Components/Lab/Slips/LabSlipUrinalysis.vue'
import LabSlipFecalysis from '@/Components/Lab/Slips/LabSlipFecalysis.vue'
import LabSlipChemistry from '@/Components/Lab/Slips/LabSlipChemistry.vue'
import LabSlipSerology from '@/Components/Lab/Slips/Labslipserology.vue'
import LabSlipThyroid from '@/Components/Lab/Slips/Labslipclinicalchemistry.vue'
import LabSlipHba1c from '@/Components/Lab/Slips/Labsliphba1c.vue'
import LabSlipOgtt from '@/Components/Lab/Slips/Labslipogtt.vue'
import LabSlipPSA from '@/Components/Lab/Slips/LabSlipPSA.vue'

const props = defineProps({
    visit:      Object,
    patient:    Object,
    labRequest: Object,
    results:    Object, // keyed by test_code
    categories: Array,  // which categories have results: ['hematology','urinalysis',...]
})

// Half-sheet print function — prints one slip by category id
function printSlip(slipId) {
    const content = document.getElementById(slipId)
    if (!content) return

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:148mm;border:none;'
    document.body.appendChild(iframe)

    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size: 210mm 148mm landscape; margin: 0; }
        body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        img { max-width: 100%; }
    </style></head>
    <body>${content.innerHTML}</body></html>`)
    doc.close()

    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => {
            if (document.body.contains(iframe)) document.body.removeChild(iframe)
        }, 2000)
    }
}

// Print ALL slips — one per page
function printAll() {
    const slipIds = availableSlips.value.map(s => s.id)
    if (!slipIds.length) return

    const parts = slipIds.map(id => {
        const el = document.getElementById(id)
        return el ? el.innerHTML : ''
    }).filter(Boolean)

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:148mm;border:none;'
    document.body.appendChild(iframe)

    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size: 210mm 148mm landscape; margin: 0; }
        body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        img { max-width: 100%; }
        .slip { page-break-after: always; }
        .slip:last-child { page-break-after: avoid; }
    </style></head>
    <body>${parts.map(p => `<div class="slip">${p}</div>`).join('')}</body></html>`)
    doc.close()

    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => {
            if (document.body.contains(iframe)) document.body.removeChild(iframe)
        }, 2000)
    }
}

// Determine which slips to show based on available categories
const slipConfig = [
    { id: 'slip-cbc',       category: 'hematology',  label: 'CBC',                      component: LabSlipCBC },
    { id: 'slip-urinalysis',category: 'urinalysis',  label: 'Urinalysis',               component: LabSlipUrinalysis },
    { id: 'slip-fecalysis', category: 'stool',       label: 'Fecalysis',                component: LabSlipFecalysis },
    { id: 'slip-chemistry', category: 'chemistry',   label: 'Blood Chemistry',          component: LabSlipChemistry },
    { id: 'slip-serology',  category: 'serology',    label: 'Immunology & Serology',    component: LabSlipSerology },
    { id: 'slip-thyroid',   category: 'thyroid',     label: 'Thyroid Function',         component: LabSlipThyroid },
    { id: 'slip-hba1c',     category: 'hba1c',       label: 'HbA1c',                    component: LabSlipHba1c },
    { id: 'slip-ogtt',      category: 'ogtt',        label: 'OGTT',                     component: LabSlipOgtt },
    { id: 'slip-psa',       category: 'psa',         label: 'PSA',                      component: LabSlipPSA },
]

const availableSlips = computed(() =>
    slipConfig.filter(s => props.categories?.includes(s.category))
)

const categoryColors = {
    hematology: '#3B82F6',
    urinalysis: '#8B5CF6',
    stool:      '#F59E0B',
    chemistry:  '#EF4444',
    serology:   '#10B981',
    thyroid:    '#6366F1',
    hba1c:      '#EC4899',
    ogtt:       '#14B8A6',
    psa:        '#0EA5E9',
}
</script>

<template>
    <AppLayout title="Lab Results — Print">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('laboratory.enter', visit.id)">
                    <button class="h-8 w-8 flex items-center justify-center rounded-md border border-slate-200 hover:bg-slate-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Lab Results — Print Slips</h1>
                    <p class="text-slate-400 text-xs">{{ patient.full_name }} · {{ visit.visit_date }}</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- ACTION BAR -->
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center justify-between">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest mr-2">Print Individual:</span>
                    <button v-for="slip in availableSlips" :key="slip.id"
                        @click="printSlip(slip.id)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all hover:shadow-sm"
                        :style="{
                            borderColor: categoryColors[slip.category],
                            color: categoryColors[slip.category],
                            background: categoryColors[slip.category] + '12'
                        }">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        {{ slip.label }}
                    </button>
                </div>
                <button @click="printAll"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-bold shadow-sm transition-all hover:opacity-90"
                    style="background:#0F2044;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print All Slips
                </button>
            </div>

            <!-- SLIP PREVIEWS — shown on screen for review -->
            <div v-if="availableSlips.length === 0"
                class="text-center py-16 text-slate-400">
                <p class="font-semibold">No lab results available to print.</p>
                <p class="text-sm mt-1">Enter results first from the Lab entry page.</p>
            </div>

            <div v-for="slip in availableSlips" :key="slip.id" class="space-y-2">
                <!-- Slip label -->
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full inline-block"
                        :style="{ background: categoryColors[slip.category] }"></span>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">
                        {{ slip.label }}
                    </span>
                    <button @click="printSlip(slip.id)"
                        class="ml-auto flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg border transition-all hover:shadow-sm"
                        :style="{
                            borderColor: categoryColors[slip.category],
                            color: categoryColors[slip.category],
                        }">
                        Print this slip
                    </button>
                </div>

                <!-- Slip Preview with border -->
                <div class="border-2 rounded-xl overflow-hidden shadow-sm"
                    :style="{ borderColor: categoryColors[slip.category] + '40' }">
                    <!-- Hidden div used for printing -->
                    <div :id="slip.id" style="display:none;">
                        <component :is="slip.component"
                            :patient="patient"
                            :visit="visit"
                            :lab-request="labRequest"
                            :results="results"/>
                    </div>
                    <!-- Visible preview (scaled) -->
                    <div style="transform-origin:top left;overflow:hidden;background:white;padding:8px;">
                        <component :is="slip.component"
                            :patient="patient"
                            :visit="visit"
                            :lab-request="labRequest"
                            :results="results"/>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
