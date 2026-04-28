<script setup>
import { ref, onMounted } from 'vue'
import PrintableMedicalExamReport from '@/Components/Doctor/PrintableMedicalExamReport.vue'

const props = defineProps({
    visit:        Object,
    patient:      Object,
    vitals:       Object,
    consultation: Object,
    labResults:   Object,
    labRequest:   Object,
    imaging:      Object,
    drugTest:     Object,
})

const printPage = ref('both') // 'both' | '1' | '2'

onMounted(() => setTimeout(() => triggerPrint(), 900))

function triggerPrint() {
    const content = document.getElementById('medical-exam-print')
    if (!content) { window.print(); return }

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:594mm;border:none;'
    document.body.appendChild(iframe)

    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size:A4 portrait; margin:0; }
        body { background:white; font-family:Arial,sans-serif; }
        img { max-width:100%; }
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
    <div style="background:white;min-height:100vh;padding:12px;">
        <div id="medical-exam-print">
            <PrintableMedicalExamReport
                :visit="visit"
                :patient="patient"
                :vitals="vitals"
                :consultation="consultation"
                :lab-results="labResults"
                :lab-request="labRequest"
                :imaging="imaging"
                :drug-test="drugTest"
                :print-page="printPage"
            />
        </div>

        <div style="position:fixed;bottom:20px;right:20px;display:flex;gap:8px;z-index:9999;align-items:center;">
            <!-- Page selector -->
            <div style="display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:8px;">
                <button v-for="opt in [['both','Both Pages'],['1','Page 1 Only'],['2','Page 2 Only']]" :key="opt[0]"
                    @click="printPage = opt[0]"
                    :style="printPage===opt[0]
                        ? 'background:#1B4F9B;color:white;border:none;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;'
                        : 'background:transparent;color:#475569;border:none;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;'">
                    {{ opt[1] }}
                </button>
            </div>
            <button @click="triggerPrint"
                style="background:#1B4F9B;color:white;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.25);">
                Print / Save PDF
            </button>
            <button @click="() => window.close()"
                style="background:#e2e8f0;color:#475569;border:none;padding:10px 16px;border-radius:8px;font-weight:600;cursor:pointer;">
                ✕ Close
            </button>
        </div>
    </div>
</template>

<style>
aside, header.border-b { display:none !important; }
main { padding:0 !important; margin:0 !important; }
body { background:white !important; }
@media print {
    @page { size:A4 portrait; margin:0; }
    body * { visibility:hidden; }
    #medical-exam-print, #medical-exam-print * { visibility:visible !important; }
    #medical-exam-print { position:absolute; top:0; left:0; width:100%; }
    button { display:none !important; }
}
</style>
