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
const rs   = (code) => isAb(code) ? 'font-weight:900;color:#dc2626;' : ''
</script>

<template>
    <div style="font-family:Arial,sans-serif;font-size:10px;color:#111;background:white;width:210mm;padding:5mm 7mm;box-sizing:border-box;">

        <LabSlipHeader title="IMMUNOLOGY AND SEROLOGY" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- 2-column layout -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 28px;margin-top:10px;margin-bottom:8px;">

            <!-- LEFT: Dengue + Pregnancy -->
            <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:2px 4px;border-bottom:1.5px solid #111;font-size:9px;width:65%;">EXAMINATION</th>
                        <th style="text-align:left;padding:2px 4px;border-bottom:1.5px solid #111;font-size:9px;">RESULT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in [
                        { code:'DNG_NS1', name:'Dengue NS1 Ag' },
                        { code:'DNG_IGG', name:'Dengue IgG'    },
                        { code:'DNG_IGM', name:'Dengue IgM'    },
                    ]" :key="row.code">
                        <td style="padding:5px 4px;border-bottom:1px solid #e5e5e5;">{{ row.name }}</td>
                        <td style="padding:5px 4px;border-bottom:1px solid #e5e5e5;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>

                    <!-- Spacer -->
                    <tr style="height:14px;"><td colspan="2"></td></tr>

                    <!-- Serum Pregnancy Test — 2-line label matching the slip -->
                    <tr>
                        <td style="padding:5px 4px;border-bottom:1px solid #e5e5e5;line-height:1.4;vertical-align:middle;">
                            SERUM PREGNANCY<br/>TEST
                        </td>
                        <td style="padding:5px 4px;border-bottom:1px solid #e5e5e5;vertical-align:middle;" :style="rs('PREG')">
                            {{ r('PREG') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- RIGHT: Screening Tests -->
            <div>
                <!-- "SCREENING TESTS ONLY" label above the right column — bold, underlined -->
                <div style="font-size:9.5px;font-weight:900;margin-bottom:4px;padding-bottom:3px;border-bottom:1.5px solid #111;text-align:right;">
                    SCREENING TESTS ONLY
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                    <tbody>
                        <tr v-for="row in [
                            { code:'HBSAG', name:'HBsAg'                },
                            { code:'VDRL',  name:'SYPHILIS TEST (VDRL)' },
                            { code:'HIV1',  name:'HIV 1 Ab'             },
                            { code:'HIV2',  name:'HIV 2 Ab'             },
                        ]" :key="row.code">
                            <td style="padding:5px 4px;border-bottom:1px solid #e5e5e5;width:65%;">{{ row.name }}</td>
                            <td style="padding:5px 4px;border-bottom:1px solid #e5e5e5;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <div v-if="labRequest?.remarks" style="font-size:9px;color:#555;margin-bottom:4px;">
            Note: {{ labRequest.remarks }}
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
