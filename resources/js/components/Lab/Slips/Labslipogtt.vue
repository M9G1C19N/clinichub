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
    <div style="font-family:Arial,sans-serif;font-size:12px;color:#111;background:white;
                width:210mm;min-height:140mm;max-height:148mm;padding:6mm 9mm;
                box-sizing:border-box;display:flex;flex-direction:column;">

        <LabSlipHeader title="BLOOD CHEMISTRY" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- OGTT Title -->
        <div style="text-align:center;font-size:22px;font-weight:900;letter-spacing:1px;margin:10px 0 14px;">
            OGTT
        </div>

        <!-- Oral Load row -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;font-size:11px;">
            <span>ORAL LOAD OGTT</span>
            <div style="border:1.5px solid #aaa;min-width:110px;padding:4px 8px;font-weight:700;font-size:12px;">
                {{ (labRequest?.ogtt_load ?? r('OGTT_LOAD')) || '75GRAMS' }}
            </div>
        </div>

        <!-- Results table — 3 time points -->
        <table style="width:65%;border-collapse:collapse;font-size:11px;margin:0 auto 18px;flex:1;align-self:center;">
            <tbody>
                <tr>
                    <td style="padding:8px 10px;width:90px;text-align:right;"></td>
                    <td style="padding:8px 10px;width:70px;font-weight:700;font-size:12px;">FBS</td>
                    <td style="padding:8px 10px;width:130px;">
                        <div style="border:1.5px solid #aaa;padding:4px 8px;min-width:90px;display:inline-block;min-height:22px;font-size:12px;" :style="rs('OGTT_FBS')">
                            {{ r('OGTT_FBS') }}
                        </div>
                    </td>
                    <td style="padding:8px 10px;font-size:10.5px;color:#444;">75-115 mg/dl</td>
                </tr>
                <tr>
                    <td style="padding:8px 10px;text-align:right;"></td>
                    <td style="padding:8px 10px;font-weight:700;font-size:12px;">1 Hr</td>
                    <td style="padding:8px 10px;">
                        <div style="border:1.5px solid #aaa;padding:4px 8px;min-width:90px;display:inline-block;min-height:22px;font-size:12px;" :style="rs('OGTT_1HR')">
                            {{ r('OGTT_1HR') }}
                        </div>
                    </td>
                    <td style="padding:8px 10px;font-size:10.5px;color:#444;">&lt;180 mg/dl</td>
                </tr>
                <tr>
                    <td style="padding:8px 10px;text-align:right;"></td>
                    <td style="padding:8px 10px;font-weight:700;font-size:12px;">2 Hrs</td>
                    <td style="padding:8px 10px;">
                        <div style="border:1.5px solid #aaa;padding:4px 8px;min-width:90px;display:inline-block;min-height:22px;font-size:12px;" :style="rs('OGTT_2HR')">
                            {{ r('OGTT_2HR') }}
                        </div>
                    </td>
                    <td style="padding:8px 10px;font-size:10.5px;color:#444;">&lt;153 mg/dL</td>
                </tr>
            </tbody>
        </table>

        <div v-if="labRequest?.remarks" style="font-size:11px;color:#555;margin-bottom:6px;">
            Note: {{ labRequest.remarks }}
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
