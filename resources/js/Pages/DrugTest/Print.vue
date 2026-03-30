<script setup>
import { onMounted } from 'vue'
import PrintableDrugTestCertificate from '@/Components/DrugTest/PrintableDrugTestCertificate.vue'
import PrintableCustodyForm from '@/Components/DrugTest/PrintableCustodyForm.vue'

const props = defineProps({
    visit:    Object,
    patient:  Object,
    drugTest: Object,
})

onMounted(() => setTimeout(() => triggerPrint(), 900))

function triggerPrint() {
    const content = document.getElementById('drug-test-print-content')
    if (!content) { window.print(); return }

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:none;'
    document.body.appendChild(iframe)

    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size:A4 portrait; margin:8mm 10mm; }
        body { background:white; font-family:Arial,sans-serif; }
        img { max-width:100%; }
        .page-break { page-break-after:always; height:0; overflow:hidden; }
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
    <div style="background:white; min-height:100vh; padding:16px;">

        <div id="drug-test-print-content">

            <!-- Page 1: Drug Test Certificate / Request -->
            <PrintableDrugTestCertificate
                :visit="visit"
                :patient="patient"
                :drug-test="drugTest"
            />

            <!-- Page break between documents -->
            <div class="page-break"></div>

            <!-- Page 2: Custody & Control Form DT-002C -->
            <PrintableCustodyForm
                :visit="visit"
                :patient="patient"
                :drug-test="drugTest"
            />

        </div>

        <!-- Action buttons -->
        <div style="position:fixed;bottom:20px;right:20px;display:flex;gap:8px;z-index:9999;">
            <button @click="triggerPrint"
                style="background:#e11d48;color:white;border:none;padding:10px 20px;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.25);">
                🖨 Print Both Forms
            </button>
            <button @click="() => window.close()"
                style="background:#e2e8f0;color:#475569;border:none;padding:10px 16px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;">
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
    @page { size:A4 portrait; margin:8mm 10mm; }
    body * { visibility:hidden; }
    #drug-test-print-content, #drug-test-print-content * { visibility:visible !important; }
    #drug-test-print-content { position:absolute; top:0; left:0; width:100%; }
    button { display:none !important; }
    .page-break { page-break-after:always; }
}
</style>
