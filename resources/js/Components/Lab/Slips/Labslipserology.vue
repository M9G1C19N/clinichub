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
    <div style="font-family:Arial,sans-serif;font-size:12px;color:#111;background:white;
                width:210mm;min-height:140mm;max-height:148mm;padding:6mm 9mm;
                box-sizing:border-box;display:flex;flex-direction:column;">

        <LabSlipHeader title="IMMUNOLOGY AND SEROLOGY" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- 2-column layout, flex:1 to fill space -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 32px;margin-top:8px;margin-bottom:6px;flex:1;">

            <!-- LEFT: Dengue + Pregnancy -->
            <table style="width:100%;border-collapse:collapse;font-size:11px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:4px 5px;border-bottom:1.5px solid #111;font-size:10.5px;width:65%;">EXAMINATION</th>
                        <th style="text-align:left;padding:4px 5px;border-bottom:1.5px solid #111;font-size:10.5px;">RESULT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in [
                        { code:'DNG_NS1', name:'Dengue NS1 Ag' },
                        { code:'DNG_IGG', name:'Dengue IgG'    },
                        { code:'DNG_IGM', name:'Dengue IgM'    },
                    ]" :key="row.code">
                        <td style="padding:7px 5px;border-bottom:1px solid #e5e5e5;">{{ row.name }}</td>
                        <td style="padding:7px 5px;border-bottom:1px solid #e5e5e5;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>

                    <tr style="height:18px;"><td colspan="2"></td></tr>

                    <tr>
                        <td style="padding:7px 5px;border-bottom:1px solid #e5e5e5;line-height:1.4;vertical-align:middle;">
                            SERUM PREGNANCY<br/>TEST
                        </td>
                        <td style="padding:7px 5px;border-bottom:1px solid #e5e5e5;vertical-align:middle;" :style="rs('PREG')">
                            {{ r('PREG') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- RIGHT: Screening Tests -->
            <div>
                <div style="font-size:11px;font-weight:900;margin-bottom:5px;padding-bottom:4px;border-bottom:1.5px solid #111;text-align:right;">
                    SCREENING TESTS ONLY
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <tbody>
                        <tr v-for="row in [
                            { code:'HBSAG', name:'HBsAg'                },
                            { code:'VDRL',  name:'SYPHILIS TEST (VDRL)' },
                            { code:'HIV1',  name:'HIV 1 Ab'             },
                            { code:'HIV2',  name:'HIV 2 Ab'             },
                        ]" :key="row.code">
                            <td style="padding:7px 5px;border-bottom:1px solid #e5e5e5;width:65%;">{{ row.name }}</td>
                            <td style="padding:7px 5px;border-bottom:1px solid #e5e5e5;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="labRequest?.remarks" style="font-size:11px;color:#555;margin-bottom:5px;">
            Note: {{ labRequest.remarks }}
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
