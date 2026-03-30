<script setup>
import { onMounted } from 'vue'
import PrintableRadiologyReport from '@/Components/XRay/PrintableRadiologyReport.vue'

const props = defineProps({
    visit:               Object,
    patient:             Object,
    imagingRequest:      Object,
    requestingPhysician: String,
})

onMounted(() => {
    setTimeout(() => triggerPrint(), 800)
})

function triggerPrint() {
    const content = document.getElementById('xray-print-content')
    if (!content) { window.print(); return }

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:none;'
    document.body.appendChild(iframe)

    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: white; font-family: Arial, sans-serif; }
        img { max-width: 100%; }
    </style>
</head>
<body>${content.innerHTML}</body>
</html>`)
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
        <div id="xray-print-content">
            <PrintableRadiologyReport
                :visit="visit"
                :patient="patient"
                :imaging-request="imagingRequest"
                :requesting-physician="requestingPhysician"
            />
        </div>

        <div style="position:fixed; bottom:20px; right:20px; display:flex; gap:8px; z-index:9999;">
            <button @click="triggerPrint" style="background:#8B5CF6; color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.25);">
                Print / Save PDF
            </button>
            <button @click="() => window.close()" style="background:#e2e8f0; color:#475569; border:none; padding:10px 16px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer;">
                ✕ Close
            </button>
        </div>
    </div>
</template>

<style>
aside, header.border-b, .no-print { display: none !important; }
main { padding: 0 !important; margin: 0 !important; }
body { background: white !important; }
@media print {
    @page { size: A4 portrait; margin: 10mm 12mm; }
    body * { visibility: hidden; }
    #xray-print-content, #xray-print-content * { visibility: visible !important; }
    #xray-print-content { position: absolute; top: 0; left: 0; width: 100%; }
    button { display: none !important; }
}
</style>
