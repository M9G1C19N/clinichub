<script setup>
import { onMounted } from 'vue'
import PrintableLabResult from '@/Components/Lab/PrintableLabResult.vue'

const props = defineProps({
    visit:      Object,
    patient:    Object,
    labRequest: Object,
    tests:      Array,
})

onMounted(() => {
    setTimeout(() => triggerPrint(), 900)
})

function triggerPrint() {
    const content = document.getElementById('lab-print-content')
    if (!content) { window.print(); return }

    // Create hidden iframe
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
        @page { size: A4 portrait; margin: 6mm 8mm; }
        body { background: white; font-family: Arial, sans-serif; }
        table { border-collapse: collapse; }
        img { max-width: 100%; }
        sup { font-size: 7px !important; font-weight: 900; color: #dc2626; }
    </style>
</head>
<body>${content.innerHTML}</body>
</html>`)
    doc.close()

    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => {
            if (document.body.contains(iframe)) {
                document.body.removeChild(iframe)
            }
        }, 2000)
    }
}
</script>

<template>
    <div style="background:white; min-height:100vh; padding:16px;">

        <!-- The printable content — rendered on screen for iframe capture -->
        <div id="lab-print-content">
            <PrintableLabResult
                :visit="visit"
                :patient="patient"
                :lab-request="labRequest"
                :tests="tests"
            />
        </div>

        <!-- Floating action buttons — NOT inside print content div -->
        <div style="
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
            z-index: 9999;
        ">
            <button
                @click="triggerPrint"
                style="
                    background: #1B4F9B;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    font-weight: 700;
                    font-size: 13px;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
                ">
                 Print / Save PDF
            </button>
            <button
                @click="() => window.close()"
                style="
                    background: #e2e8f0;
                    color: #475569;
                    border: none;
                    padding: 10px 16px;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 13px;
                    cursor: pointer;
                ">
                ✕ Close
            </button>
        </div>
    </div>
</template>

<style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4 portrait;
            margin: 4mm 5mm;
        }

        html {
            /* Scale entire content to fit A4 width */
            zoom: 0.72;
        }

        body {
            background: white;
            font-family: Arial, sans-serif;
            width: 210mm;
        }

        table { border-collapse: collapse; }
        img { max-width: 100%; display: block; }
        sup { font-size: 7px !important; font-weight: 900; color: #dc2626; }

        /* Prevent any element from overflowing */
        * { max-width: 100% !important; }

        /* Ensure grid layouts work in print */
        [style*="display:grid"],
        [style*="display: grid"] {
            display: grid !important;
        }
 </style>
