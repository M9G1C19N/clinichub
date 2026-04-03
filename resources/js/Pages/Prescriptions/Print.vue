<script setup>
import { onMounted } from 'vue'
import { CLINIC_LOGO } from '@/config/clinic.js'

const props = defineProps({
    prescription: Object,
    patient:      Object,
    doctor:       Object,
})

onMounted(() => setTimeout(triggerPrint, 800))

function triggerPrint() {
    const content = document.getElementById('rx-print')
    if (!content) { window.print(); return }
    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:216mm;height:140mm;border:none;'
    document.body.appendChild(iframe)
    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        @page{size:216mm 140mm;margin:0;}
        body{background:white;font-family:Arial,sans-serif;}
        img{max-width:100%;}
    </style></head><body>${content.innerHTML}</body></html>`)
    doc.close()
    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => document.body.contains(iframe) && document.body.removeChild(iframe), 2000)
    }
}
</script>

<template>
    <div style="background:white;min-height:100vh;padding:16px;">
        <div id="rx-print">
            <!-- Half-sheet 216mm × 140mm -->
            <div style="width:216mm;min-height:140mm;padding:7mm 9mm;box-sizing:border-box;font-family:Arial,sans-serif;font-size:9px;color:#111;position:relative;">

                <!-- HEADER -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:4px;margin-bottom:4px;border-bottom:2px solid #0F2044;">
                    <div style="display:flex;align-items:center;gap:7px;">
                        <img :src="CLINIC_LOGO" style="width:38px;height:38px;object-fit:contain;flex-shrink:0;"/>
                        <div>
                            <div style="font-weight:900;font-size:10.5px;color:#0F2044;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                            <div style="font-size:7.5px;font-style:italic;color:#444;">Medical and Dental Clinic</div>
                            <div style="font-size:7.5px;color:#555;">Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410</div>
                            <div style="font-size:7.5px;color:#555;">Tel: 09204043408 / 09516832212</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:8px;color:#555;">Date: <strong>{{ prescription.rx_date }}</strong></div>
                        <div style="font-size:8px;color:#555;font-family:monospace;">{{ prescription.rx_number }}</div>
                        <div v-if="prescription.is_controlled"
                            style="font-size:7.5px;font-weight:900;color:#b45309;background:#fef3c7;padding:2px 6px;border-radius:4px;margin-top:2px;border:1px solid #fcd34d;">
                            ⚠ S2 / CONTROLLED
                        </div>
                    </div>
                </div>

                <!-- PATIENT INFO -->
                <div style="display:grid;grid-template-columns:1fr auto;gap:0 10px;margin-bottom:4px;font-size:8px;">
                    <table style="border-collapse:collapse;">
                        <tr>
                            <td style="color:#777;padding:1px 0;width:60px;">Patient:</td>
                            <td style="font-weight:900;font-size:10px;padding:1px 0;">{{ patient.full_name?.toUpperCase() }}</td>
                        </tr>
                        <tr>
                            <td style="color:#777;padding:1px 0;">Age/Sex:</td>
                            <td style="font-weight:700;padding:1px 0;">{{ patient.age_sex }}</td>
                        </tr>
                        <tr v-if="patient.address">
                            <td style="color:#777;padding:1px 0;vertical-align:top;">Address:</td>
                            <td style="padding:1px 0;">{{ patient.address }}</td>
                        </tr>
                    </table>
                    <!-- Rx symbol watermark -->
                    <div style="font-size:60px;font-weight:900;color:#0F2044;opacity:0.07;line-height:1;position:absolute;right:9mm;top:14mm;pointer-events:none;">℞</div>
                </div>

                <!-- MEDICATIONS -->
                <div style="margin:4px 0 4px 8px;">
                    <div v-for="(item, i) in prescription.items" :key="i"
                        style="margin-bottom:6px;padding-bottom:6px;border-bottom:1px dashed #e2e8f0;page-break-inside:avoid;">
                        <!-- Drug line -->
                        <div style="display:flex;align-items:baseline;gap:6px;flex-wrap:wrap;">
                            <span style="font-size:10px;font-weight:900;color:#0F2044;flex-shrink:0;">{{ i + 1 }}.</span>
                            <span style="font-size:11px;font-weight:900;">{{ item.drug }}</span>
                            <span v-if="item.dosage" style="font-size:9px;font-weight:700;color:#333;">{{ item.dosage }}</span>
                            <span v-if="item.form" style="font-size:8px;color:#666;">({{ item.form }})</span>
                            <span v-if="item.quantity" style="margin-left:auto;font-size:8.5px;font-weight:700;color:#0F2044;white-space:nowrap;">
                                Qty: {{ item.quantity }}
                            </span>
                        </div>
                        <!-- Sig line -->
                        <div style="padding-left:16px;font-size:8.5px;color:#444;margin-top:1px;display:flex;flex-wrap:wrap;gap:6px;">
                            <span v-if="item.frequency"><em>Sig:</em> <strong>{{ item.frequency }}</strong></span>
                            <span v-if="item.duration">for <strong>{{ item.duration }}</strong></span>
                            <span v-if="item.instructions" style="color:#666;font-style:italic;">— {{ item.instructions }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="prescription.notes"
                    style="font-size:8px;color:#555;margin-bottom:6px;padding:3px 8px;background:#f8fafc;border-radius:4px;border-left:2px solid #1B4F9B;font-style:italic;">
                    <strong>Note:</strong> {{ prescription.notes }}
                </div>

                <!-- DOCTOR SIGNATURE -->
                <div style="position:absolute;bottom:8mm;right:9mm;text-align:center;">
                    <div style="min-height:22px;display:flex;align-items:flex-end;justify-content:center;margin-bottom:2px;">
                        <img v-if="doctor.signature_url"
                            :src="doctor.signature_url"
                            style="max-height:40px;max-width:150px;object-fit:contain;"
                            alt="Signature"/>
                    </div>
                    <div style="border-top:1px solid #333;min-width:170px;padding-top:3px;">
                        <div style="font-weight:900;font-size:10px;">{{ doctor.name?.toUpperCase() }}, MD</div>
                        <div v-if="doctor.specialization" style="font-size:7.5px;color:#555;">{{ doctor.specialization }}</div>
                        <div style="font-size:7.5px;color:#555;">
                            PRC Lic. No.: {{ doctor.prc_number || '—' }}
                            &nbsp;·&nbsp;
                            PTR No.: {{ doctor.ptr_number || '—' }}
                        </div>
                        <div v-if="prescription.is_controlled && doctor.s2_number"
                            style="font-size:7.5px;font-weight:700;color:#b45309;">
                            S2 No.: {{ doctor.s2_number }}
                        </div>
                    </div>
                </div>

                <!-- Footer note -->
                <div style="position:absolute;bottom:8mm;left:9mm;font-size:6.5px;color:#aaa;font-style:italic;max-width:100mm;">
                    Not valid without physician's signature and dry seal.<br/>
                    Valid for 7 days from date of issue.
                    <span v-if="prescription.is_controlled"> · S2 prescription required for dispensing.</span>
                </div>
            </div>
        </div>

        <!-- Floating controls -->
        <div style="position:fixed;bottom:20px;right:20px;display:flex;gap:8px;z-index:9999;">
            <button @click="triggerPrint"
                style="background:#1B4F9B;color:white;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
                🖨 Print Prescription
            </button>
            <a :href="route('prescriptions.index')"
                style="background:#e2e8f0;color:#475569;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;display:flex;align-items:center;">
                ← Back to List
            </a>
        </div>
    </div>
</template>

<style>
aside, header.border-b { display:none !important; }
main { padding:0 !important; }
body { background:white !important; }
@media print {
    @page { size: 216mm 140mm; margin: 0; }
    body * { visibility: hidden; }
    #rx-print, #rx-print * { visibility: visible !important; }
    #rx-print { position: absolute; top: 0; left: 0; }
    button, a[style] { display: none !important; }
}
</style>
