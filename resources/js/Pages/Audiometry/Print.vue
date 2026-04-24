<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import LabSlipAudiometry from '@/Components/Lab/Slips/LabSlipAudiometry.vue'

const props = defineProps({
    visit:     Object,
    patient:   Object,
    audResult: Object,
})

function printSlip() {
    const content = document.getElementById('slip-audiometry')
    if (!content) return

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:none;'
    document.body.appendChild(iframe)

    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size: A4 portrait; margin: 0; }
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
</script>

<template>
    <AppLayout title="Audiometry — Print">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('audiometry.enter', visit.id)">
                    <button class="h-8 w-8 flex items-center justify-center rounded-md border border-slate-200 hover:bg-slate-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Screening Audiometry — Print</h1>
                    <p class="text-slate-400 text-xs">{{ patient.full_name }} · {{ visit.visit_date }}</p>
                </div>
            </div>
        </template>

        <div class="space-y-5">

            <!-- Action bar -->
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-700">{{ patient.full_name }}</p>
                    <p class="text-xs text-slate-400">{{ audResult?.request_number }} · {{ visit.visit_date }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('audiometry.enter', visit.id)">
                        <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                            Back to Form
                        </button>
                    </Link>
                    <button
                        v-if="audResult"
                        @click="printSlip"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-bold shadow-sm transition-all hover:opacity-90"
                        style="background:#0d9488;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print Audiometry Slip
                    </button>
                </div>
            </div>

            <!-- No results state -->
            <div v-if="!audResult"
                class="text-center py-16 text-slate-400">
                <p class="font-semibold">No audiometry results available.</p>
                <p class="text-sm mt-1">Enter results first from the entry form.</p>
            </div>

            <!-- Slip preview -->
            <div v-else class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full inline-block" style="background:#0d9488;"></span>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">Screening Audiometry</span>
                    <button @click="printSlip"
                        class="ml-auto flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg border transition-all hover:shadow-sm"
                        style="border-color:#0d9488; color:#0d9488;">
                        Print this slip
                    </button>
                </div>

                <div class="border-2 rounded-xl overflow-hidden shadow-sm" style="border-color:#0d948840;">
                    <!-- Hidden div for printing -->
                    <div id="slip-audiometry" style="display:none;">
                        <LabSlipAudiometry
                            :patient="patient"
                            :visit="visit"
                            :aud-result="audResult"/>
                    </div>
                    <!-- Visible preview -->
                    <div style="background:white;padding:8px;overflow:auto;">
                        <LabSlipAudiometry
                            :patient="patient"
                            :visit="visit"
                            :aud-result="audResult"/>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
