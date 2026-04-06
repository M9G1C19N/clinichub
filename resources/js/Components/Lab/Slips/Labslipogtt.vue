<script setup>
import LabSlipHeader from './LabSlipHeader.vue'
import LabSlipFooter from './LabSlipFooter.vue'

const props = defineProps({
    patient:    Object,
    visit:      Object,
    labRequest: Object,
    results:    Object,
})

const r    = (code) => props.results?.[code]?.value ?? ''
const isAb = (code) => props.results?.[code]?.is_abnormal ?? false
const rs   = (code) => isAb(code) ? 'font-weight:900;color:#dc2626;' : 'font-weight:600;'
</script>

<template>
    <div style="font-family:Arial,sans-serif;font-size:10px;color:#111;background:white;width:210mm;padding:5mm 7mm;box-sizing:border-box;">

        <LabSlipHeader title="BLOOD CHEMISTRY" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- OGTT Title -->
        <div style="text-align:center;font-size:18px;font-weight:900;letter-spacing:1px;margin:10px 0 14px;">
            OGTT
        </div>

        <!-- Oral Load row -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;font-size:9.5px;">
            <span>ORAL LOAD OGTT</span>
            <div style="border:1px solid #aaa;min-width:90px;padding:2px 6px;font-weight:700;">
                {{ (labRequest?.ogtt_load ?? r('OGTT_LOAD')) || '75GRAMS' }}
            </div>
        </div>

        <!-- Results table — 3 time points -->
        <table style="width:60%;border-collapse:collapse;font-size:9.5px;margin:0 auto 16px;">
            <tbody>
                <!-- FBS -->
                <tr>
                    <td style="padding:5px 8px;width:80px;text-align:right;"></td>
                    <td style="padding:5px 8px;width:60px;font-weight:600;">FBS</td>
                    <td style="padding:5px 8px;width:110px;">
                        <div style="border:1px solid #aaa;padding:2px 6px;min-width:80px;display:inline-block;min-height:18px;" :style="rs('OGTT_FBS')">
                            {{ r('OGTT_FBS') }}
                        </div>
                    </td>
                    <td style="padding:5px 8px;font-size:8.5px;color:#444;">75-115 mg/dl</td>
                </tr>
                <!-- 1 Hr -->
                <tr>
                    <td style="padding:5px 8px;text-align:right;"></td>
                    <td style="padding:5px 8px;font-weight:600;">1 Hr</td>
                    <td style="padding:5px 8px;">
                        <div style="border:1px solid #aaa;padding:2px 6px;min-width:80px;display:inline-block;min-height:18px;" :style="rs('OGTT_1HR')">
                            {{ r('OGTT_1HR') }}
                        </div>
                    </td>
                    <td style="padding:5px 8px;font-size:8.5px;color:#444;">&lt;180 mg/dl</td>
                </tr>
                <!-- 2 Hrs -->
                <tr>
                    <td style="padding:5px 8px;text-align:right;"></td>
                    <td style="padding:5px 8px;font-weight:600;">2 Hrs</td>
                    <td style="padding:5px 8px;">
                        <div style="border:1px solid #aaa;padding:2px 6px;min-width:80px;display:inline-block;min-height:18px;" :style="rs('OGTT_2HR')">
                            {{ r('OGTT_2HR') }}
                        </div>
                    </td>
                    <td style="padding:5px 8px;font-size:8.5px;color:#444;">&lt;153 mg/dL</td>
                </tr>
            </tbody>
        </table>

        <!-- Remarks -->
        <div v-if="labRequest?.remarks" style="font-size:9px;color:#555;margin-bottom:8px;">
            Note: {{ labRequest.remarks }}
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
