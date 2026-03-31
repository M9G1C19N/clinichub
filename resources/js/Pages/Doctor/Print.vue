<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'

const props = defineProps({
    visit:        Object,
    patient:      Object,
    vitals:       Object,
    consultation: Object,
    labResults:   Object,  // keyed by test_code
    labRequest:   Object,
    imaging:      Object,
    drugTest:     Object,
})

// Helper — get lab result value
const lab = (code) => props.labResults?.[code]?.value ?? ''
const labFlag = (code) => props.labResults?.[code]?.flag ?? ''
const labRange = (code) => props.labResults?.[code]?.normal_range ?? ''
const isAbnormal = (code) => props.labResults?.[code]?.is_abnormal ?? false

// PE Classification config
const peClassConfig = {
    A: { label: 'CLASS A', desc: 'Physically fit for all types of work. No defects noted.',                                                   rec: 'FIT TO WORK' },
    B: { label: 'CLASS B', desc: 'Physically fit for all types of work. Has minor and curable ailment that offers no handicap to the job applied.', rec: 'FIT TO WORK WITH FINDINGS' },
    C: { label: 'CLASS C', desc: 'With findings that are generally unacceptable. Employment at risk and at the discretion of management.',     rec: 'CONDITIONALLY FIT' },
    D: { label: 'CLASS D', desc: 'Not fit for employment.',                                                                                    rec: 'NOT FIT FOR EMPLOYMENT' },
    E: { label: 'CLASS E', desc: 'Lacking in requirements or need further evaluation.',                                                        rec: 'FURTHER EVALUATION NEEDED' },
}

const selectedClass = props.consultation?.pe_classification
    ? peClassConfig[props.consultation.pe_classification]
    : null

const visitTypeLabel = {
    pre_employment: 'PRE-EMPLOYMENT',
    annual_pe:      'ANNUAL PE',
    exit_pe:        'EXIT PE',
}
</script>

<template>
    <div style="
        font-family: Arial, sans-serif;
        font-size: 9px;
        color: #111;
        background: white;
        padding: 8px 10px;
        max-width: 100%;
        line-height: 1.4;
    ">

        <!-- ── HEADER ─────────────────────────────── -->
        <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #0F2044; padding-bottom:5px; margin-bottom:5px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <img :src="CLINIC_LOGO" style="width:44px; height:44px; object-fit:contain;"/>
                <div>
                    <div style="font-weight:900; font-size:11px; color:#0F2044; letter-spacing:0.5px;">
                        {{ CLINIC_INFO.name.toUpperCase() }}
                    </div>
                    <div style="font-size:8.5px; color:#1B4F9B; font-weight:700; font-style:italic;">{{ CLINIC_INFO.subtitle }}</div>
                    <div style="font-size:8px; color:#444;">{{ CLINIC_INFO.addressFull }}</div>
                    <div style="font-size:8px; color:#444;">{{ CLINIC_INFO.contact }}</div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:9px; color:#555;">Control No.:</div>
                <div style="font-size:9px; color:#555;">Control No.:</div>
                <div style="font-weight:900; font-size:13px; color:#0F2044;">{{ visit.case_number }}</div>
                <div style="font-size:8.5px; color:#555; margin-top:3px;">Exam Date:</div>
                <div style="font-weight:700; font-size:9px;">{{ visit.visit_date }}</div>
            </div>
        </div>

        <!-- Visit type ribbon -->
        <div style="text-align:center; font-weight:900; font-size:10px; letter-spacing:1.5px; color:#0F2044; margin-bottom:5px;">
            {{ visitTypeLabel[visit.visit_type] ?? 'MEDICAL EXAMINATION REPORT' }}
        </div>

        <!-- ── PATIENT INFO ROW ─────────────────── -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:5px; font-size:8.5px;">
            <tr>
                <td style="width:40%; padding:2px 0;">
                    <span style="color:#555;">Name: </span>
                    <strong style="font-size:10px; letter-spacing:0.3px;">
                        {{ patient.last_name?.toUpperCase() }}, {{ patient.first_name }}
                        {{ patient.middle_name ? patient.middle_name.charAt(0) + '.' : '' }}
                    </strong>
                </td>
                <td style="width:30%; padding:2px 0;">
                    <span style="color:#555;">Age/Sex: </span>
                    <strong>{{ patient.age_sex?.toUpperCase() }}</strong>
                </td>
                <td style="width:30%; padding:2px 0; text-align:right;">
                    <span style="color:#555;">Company: </span>
                    <strong>{{ visit.employer_company ?? '—' }}</strong>
                </td>
            </tr>
            <tr v-if="consultation?.position_applied">
                <td colspan="2" style="padding:1px 0; font-size:8px;">
                    <span style="color:#555;">Position: </span>
                    <strong>{{ consultation.position_applied }}</strong>
                </td>
                <td style="padding:1px 0; font-size:8px; text-align:right;">
                    <span style="color:#555;">Req. Physician: </span>
                    <strong>{{ consultation.requesting_physician ?? '—' }}</strong>
                </td>
            </tr>
        </table>

        <div style="border-top:1.5px solid #0F2044; margin-bottom:5px;"></div>

        <!-- ── MAIN RESULTS GRID ────────────────── -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:5px; font-size:8px; vertical-align:top;">
            <colgroup>
                <col style="width:36%"/>
                <col style="width:32%"/>
                <col style="width:32%"/>
            </colgroup>
            <thead>
                <tr>
                    <th style="text-align:center; font-weight:900; font-size:8.5px; padding:2px; border:1px solid #ccc; background:#f0f4f8; color:#0F2044;">
                        COMPLETE BLOOD COUNT
                    </th>
                    <th style="text-align:center; font-weight:900; font-size:8.5px; padding:2px; border:1px solid #ccc; background:#f0f4f8; color:#0F2044;">
                        URINALYSIS
                    </th>
                    <th style="text-align:center; font-weight:900; font-size:8.5px; padding:2px; border:1px solid #ccc; background:#f0f4f8; color:#0F2044;">
                        STOOL EXAM
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <!-- CBC Column -->
                    <td style="vertical-align:top; border:1px solid #ccc; padding:3px;">
                        <table style="width:100%; border-collapse:collapse; font-size:7.5px;">
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="font-weight:700; padding:1px 0; color:#555; width:50%;">EXAMINATION</td>
                                <td style="font-weight:700; padding:1px 0; color:#555; width:20%;">RESULT</td>
                                <td style="font-weight:700; padding:1px 0; color:#555; width:30%; font-size:7px;">REF. RANGE</td>
                            </tr>
                            <tr v-for="test in [
                                { code:'HGB',  label:'Hemoglobin',     range:'M=135-175 / F=115-155 g/L' },
                                { code:'HCT',  label:'Hct',            range:'M=40-52 / F=35-48 %' },
                                { code:'RBC',  label:'RBC Count',      range:'M=4.5-5.2 / F=3.9-5.6 x10 12/L' },
                                { code:'PLT',  label:'Platelet Count', range:'150-400 x10 9/L' },
                                { code:'WBC',  label:'WBC Count',      range:'4.0-11.0 x10 9/L' },
                                { code:'SEG',  label:'Segmenters',     range:'50-70 %' },
                                { code:'LYM',  label:'Lymphocytes',    range:'20-40 %' },
                                { code:'MON',  label:'Mid',            range:'2-10 %' },
                            ]" :key="test.code">
                                <td style="padding:1.5px 0;">{{ test.label }}</td>
                                <td :style="{
                                    padding: '1.5px 0',
                                    fontWeight: isAbnormal(test.code) ? '900' : '600',
                                    color: isAbnormal(test.code) ? '#dc2626' : '#111'
                                }">
                                    {{ lab(test.code) }}
                                    <span v-if="labFlag(test.code)"
                                        style="font-size:7px; font-weight:900; color:#dc2626;">
                                        {{ labFlag(test.code) }}
                                    </span>
                                </td>
                                <td style="padding:1.5px 0; color:#777; font-size:7px;">{{ test.range }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding-top:4px; font-size:7.5px; color:#555;">Examined By:</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding:1px 0; font-weight:700; font-size:7.5px;">
                                    {{ labRequest?.examined_by_name ?? '' }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding-top:4px; font-size:7.5px; color:#555;">Noted By:</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="border-top:1px solid #333; margin-top:8px; padding-top:2px; font-weight:700; font-size:7.5px;">
                                    {{ labRequest?.noted_by_name ?? '' }}
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- UA Column -->
                    <td style="vertical-align:top; border:1px solid #ccc; padding:3px;">
                        <table style="width:100%; border-collapse:collapse; font-size:7.5px;">
                            <tr v-for="test in [
                                { code:'UA_COL', label:'Urine Color' },
                                { code:'UA_TRN', label:'Transparency' },
                                { code:'UA_PH',  label:'pH' },
                                { code:'UA_SG',  label:'Specific Gravity' },
                                { code:'UA_SUG', label:'Sugar' },
                                { code:'UA_PRO', label:'Protein' },
                                { code:'UA_PUS', label:'Pus Cells' },
                                { code:'UA_RBC', label:'RBC' },
                                { code:'UA_EPI', label:'Epithelial Cells' },
                                { code:'UA_MUC', label:'Mucus Threads' },
                                { code:'UA_AMO', label:'Amorp Subs' },
                                { code:'UA_BAC', label:'Bacteria' },
                                { code:'UA_CRY', label:'Crystals' },
                                { code:'UA_OTH', label:'Others' },
                            ]" :key="test.code">
                                <td style="padding:1.5px 0; width:55%; color:#333;">{{ test.label }}</td>
                                <td :style="{
                                    padding: '1.5px 0',
                                    fontWeight: isAbnormal(test.code) ? '900' : '600',
                                    color: isAbnormal(test.code) ? '#dc2626' : '#111'
                                }">
                                    {{ lab(test.code) }}
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- Stool + Others Column -->
                    <td style="vertical-align:top; border:1px solid #ccc; padding:3px;">

                        <!-- Stool -->
                        <div style="font-size:7.5px; margin-bottom:4px;">
                            <div v-for="test in [
                                { code:'ST_COL', label:'Color' },
                                { code:'ST_CON', label:'Consistency' },
                                { code:'ST_PUS', label:'Pus Cells' },
                                { code:'ST_RBC', label:'Red Blood Cell' },
                                { code:'ST_OVA', label:'Ova/Parasites' },
                            ]" :key="test.code" style="display:flex; justify-content:space-between; padding:1.5px 0;">
                                <span style="color:#333;">{{ test.label }}</span>
                                <span :style="{ fontWeight: isAbnormal(test.code) ? '900' : '600', color: isAbnormal(test.code) ? '#dc2626' : '#111' }">
                                    {{ lab(test.code) }}
                                </span>
                            </div>
                        </div>

                        <!-- Others / Serology -->
                        <div style="font-weight:900; font-size:8px; margin:4px 0 3px; color:#0F2044; border-top:1px solid #ccc; padding-top:3px;">
                            OTHERS
                        </div>
                        <div style="font-size:7.5px;">
                            <div v-for="test in [
                                { code:'HBSAG', label:'Hepatitis B sAg' },
                                { code:'VDRL',  label:'VDRL' },
                                { code:'PREG',  label:'Pregnancy Test' },
                                { code:'BTYPE2',label:'Blood Type' },
                            ]" :key="test.code" style="display:flex; justify-content:space-between; padding:1.5px 0;">
                                <span style="color:#333;">{{ test.label }}</span>
                                <span :style="{ fontWeight: isAbnormal(test.code) ? '900' : '600', color: isAbnormal(test.code) ? '#dc2626' : '#111' }">
                                    {{ lab(test.code) }}
                                </span>
                            </div>
                        </div>

                        <!-- Drug Test -->
                        <div v-if="drugTest" style="border-top:1px solid #ccc; margin-top:4px; padding-top:3px; font-size:7.5px;">
                            <div style="font-weight:900; font-size:8px; color:#0F2044; margin-bottom:2px;">DRUG TEST</div>
                            <div style="display:flex; justify-content:space-between;">
                                <span>{{ drugTest.drugs_label }}</span>
                                <span :style="{
                                    fontWeight: '900',
                                    color: drugTest.result === 'negative' ? '#15803d' :
                                           drugTest.result === 'positive' ? '#dc2626' : '#555'
                                }">
                                    {{ drugTest.result?.toUpperCase() }}
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ── CHEST XRAY + ECG ROW ─────────────── -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:4px; font-size:8px;">
            <tr>
                <td style="width:55%; border:1px solid #ccc; padding:4px; vertical-align:top;">
                    <div style="font-weight:900; font-size:8.5px; color:#0F2044; margin-bottom:2px;">
                        CHEST XRAY RESULT
                        <span v-if="imaging?.is_provisional"> (Provisional Reading):</span>
                        <span v-else>:</span>
                        <span style="font-weight:400; color:#555; margin-left:8px; font-size:7.5px;">
                            Case No.: {{ imaging?.request_number ?? '—' }}
                        </span>
                    </div>
                    <div v-if="imaging?.is_provisional"
                        style="font-weight:700; color:#b45309; font-size:8px; margin-bottom:3px;">
                        **SEE ATTACHED CXR OFFICIAL READING**
                    </div>
                    <div style="font-size:7.5px; color:#333; min-height:30px;">
                        <span style="font-weight:600; color:#555;">Radiographic Findings: </span>
                        {{ imaging?.radiographic_findings ?? '' }}
                    </div>
                    <div style="margin-top:4px; font-size:7.5px; border-top:1px solid #eee; padding-top:3px;">
                        <span style="color:#555;">NOTE: Xray films submitted for official reading will be available one week after submission.</span>
                    </div>
                    <div style="margin-top:5px; font-size:7.5px;">
                        Noted By:
                        <span style="border-bottom:1px solid #333; display:inline-block; min-width:120px; margin-left:4px;">
                            {{ imaging?.rad_tech_name ?? '' }}
                        </span>
                    </div>
                </td>
                <td style="width:45%; border:1px solid #ccc; padding:4px; vertical-align:top;">
                    <div style="font-weight:900; font-size:8.5px; color:#0F2044; margin-bottom:2px;">
                        ELECTROCARDIOGRAPHY (ECG):
                        <span style="font-weight:400; color:#555; font-size:7.5px; margin-left:4px;">Case No.: —</span>
                    </div>
                    <div style="font-size:7.5px; color:#333; min-height:30px;">
                        <span style="font-weight:600; color:#555;">Impression: </span>
                    </div>
                    <div style="margin-top:4px; font-size:7.5px; border-top:1px solid #eee; padding-top:3px; color:#555;">
                        NOTE: ECG is required for person with hypertension, heart disease, and other related illnesses.
                    </div>
                    <div style="margin-top:5px; font-size:7.5px;">
                        Noted By:
                        <span style="border-bottom:1px solid #333; display:inline-block; min-width:120px; margin-left:4px;"></span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ── DIAGNOSIS + CLASSIFICATION ROW ───── -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:4px; font-size:8px;">
            <tr>
                <td style="width:50%; border:1px solid #ccc; padding:6px; vertical-align:top;">
                    <div style="font-weight:900; font-size:8.5px; color:#0F2044; margin-bottom:4px;">
                        DIAGNOSIS / TREATMENT / PLAN
                    </div>
                    <div v-if="consultation?.essentially_normal"
                        style="font-weight:900; font-size:9px; color:#15803d; letter-spacing:0.5px;">
                        ***ESSENTIALLY NORMAL FINDINGS***
                    </div>
                    <div v-if="consultation?.pe_findings"
                        style="font-size:8px; color:#333; margin-top:3px; white-space:pre-line;">
                        {{ consultation.pe_findings }}
                    </div>
                    <div style="margin-top:8px; font-size:7.5px;">
                        Note: <span style="border-bottom:1px solid #aaa; display:inline-block; min-width:180px;"></span>
                    </div>
                </td>
                <td style="width:50%; border:1px solid #ccc; padding:6px; vertical-align:top;">
                    <div style="font-weight:900; font-size:8.5px; color:#0F2044; margin-bottom:4px;">
                        CLASSIFICATION:
                    </div>
                    <!-- Class A-E checkboxes — matching actual form -->
                    <div v-for="cls in ['A','B','C','D','E']" :key="cls"
                        style="display:flex; align-items:flex-start; gap:5px; margin-bottom:4px; font-size:7.5px;">
                        <span style="width:12px; height:12px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; font-weight:900; font-size:9px; background: consultation?.pe_classification === cls ? '#0F2044' : 'white'; color: consultation?.pe_classification === cls ? 'white' : 'transparent';">
                            {{ consultation?.pe_classification === cls ? '✓' : '' }}
                        </span>
                        <span :style="{ fontWeight: consultation?.pe_classification === cls ? '900' : '400', color: consultation?.pe_classification === cls ? '#0F2044' : '#333' }">
                            <strong>CLASS {{ cls }}</strong>
                            <span v-if="cls === 'A'"> &nbsp; Physically fit for all types of work. No defects noted.</span>
                            <span v-if="cls === 'B'"> &nbsp; Physically fit for all types of work. Has minor and curable ailment that offers no handicap to the job applied.</span>
                            <span v-if="cls === 'C'"> &nbsp; With findings that are generally unacceptable. Employment at risk and at the discretion of management.</span>
                            <span v-if="cls === 'D'"> &nbsp; Not fit for employment.</span>
                            <span v-if="cls === 'E'"> &nbsp; Lacking in requirements or need further evaluation.</span>
                        </span>
                    </div>

                    <!-- Recommendation -->
                    <div v-if="selectedClass"
                        style="margin-top:6px; border-top:1px solid #ccc; padding-top:5px; display:flex; align-items:center; gap:6px;">
                        <span style="font-size:7.5px; color:#555;">Recommendation:</span>
                        <span style="font-weight:900; font-size:12px; color:#0F2044; letter-spacing:0.5px;">
                            {{ selectedClass.rec }}
                        </span>
                    </div>

                    <!-- Doctor Signature -->
                    <div style="margin-top:10px; border-top:1px solid #ccc; padding-top:6px; text-align:center;">
                        <div style="min-height:24px;"></div>
                        <div style="border-top:1px solid #333; padding-top:3px;">
                            <div style="font-weight:900; font-size:8.5px;">{{ consultation?.doctor_name?.toUpperCase() }}, M.D.</div>
                            <div style="font-size:7.5px; color:#555;">
                                Lic. No.: {{ consultation?.doctor_prc }} · PTR No.: {{ consultation?.doctor_ptr }}
                            </div>
                            <div style="font-size:7.5px; font-style:italic; margin-top:1px;">Examining Physician</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- ── FOOTER ─────────────────────────────── -->
        <div style="border-top:2px solid #0F2044; padding-top:4px; display:flex; justify-content:space-between; align-items:center; font-size:7.5px; color:#555;">
            <div style="display:flex; align-items:center; gap:5px;">
                <img :src="CLINIC_LOGO" style="width:14px; height:14px; object-fit:contain; opacity:0.6;"/>
                <strong style="color:#0F2044;">SAINT PETER DIAGNOSTICS AND LABORATORY</strong>
            </div>
            <span>{{ patient.last_name }}, {{ patient.first_name }}</span>
            <span>Medical Examination Report</span>
        </div>

    </div>
</template>
