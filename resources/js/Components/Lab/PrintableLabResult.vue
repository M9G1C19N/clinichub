<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import { IS_PE_TYPE } from '@/config/visitTypes.js'
const props = defineProps({
    visit:      Object,
    patient:    Object,
    labRequest: Object,
    tests:      Array,
})

// Build a lookup map by test_code for quick access
const testMap = Object.fromEntries(
    (props.tests ?? []).map(t => [t.test_code, t])
)

// Helper — get result display for a test code
function getResult(code) {
    return testMap[code]?.result_value ?? ''
}

function isAbnormal(code) {
    return testMap[code]?.is_abnormal ?? false
}

function getFlag(code) {
    return testMap[code]?.abnormal_flag ?? null
}

function getNormalRange(code) {
    return testMap[code]?.normal_range ?? ''
}

// ── CBC rows — always show these rows in order ──────
const cbcRows = [
    { code: 'HGB',   label: 'Hemoglobin' },
    { code: 'HCT',   label: 'Hematocrit' },
    { code: 'RBC',   label: 'RBC Count' },
    { code: 'PLT',   label: 'Platelet Count' },
    { code: 'WBC',   label: 'WBC Count' },
    { code: 'SEG',   label: 'Segmenters' },
    { code: 'LYM',   label: 'Lymphocytes' },
    { code: 'MON',   label: 'Monocytes' },
    { code: 'EOS',   label: 'Eosinophils' },
    { code: 'BAS',   label: 'Basophils' },
    { code: 'MID',   label: 'Mid' },
    { code: 'BTYPE', label: 'Blood Type' },
]

// ── Blood Chemistry rows ────────────────────────────
const chemRows = [
    { code: 'FBS',    label: 'Fasting Blood Sugar' },
    { code: 'RBS',    label: 'Random Blood Sugar' },
    { code: 'BUN',    label: 'Blood Urea Nitrogen' },
    { code: 'CREAT',  label: 'Creatinine' },
    { code: 'UA_CHEM',label: 'Uric Acid' },
    { code: 'SGPT',   label: 'SGPT (ALT)' },
    { code: 'SGOT',   label: 'SGOT (AST)' },
    { code: 'CHOL',   label: 'Total Cholesterol' },
    { code: 'TRIG',   label: 'Triglycerides' },
    { code: 'HDL',    label: 'HDL Cholesterol' },
    { code: 'LDL',    label: 'LDL Cholesterol' },
    { code: 'VLDL',   label: 'VLDL' },
]

// ── Urinalysis rows ─────────────────────────────────
const urinalysisRows = [
    // Qualitative
    { code: 'UA_COLOR', label: 'Color',             section: 'QUALITATIVE EXAMINATION' },
    { code: 'UA_TRANS', label: 'Transparency',       section: '' },
    // Microscopic
    { code: 'UA_PH',    label: 'pH',                section: 'MICROSCOPIC EXAMINATION' },
    { code: 'UA_SG',    label: 'Specific Gravity',  section: '' },
    { code: 'UA_SUGAR', label: 'Sugar',             section: '' },
    { code: 'UA_PRO',   label: 'Protein',           section: '' },
    { code: 'UA_PUS',   label: 'Pus Cells',         section: '' },
    { code: 'UA_RBC',   label: 'Red Blood Cells',   section: '' },
    { code: 'UA_EPI',   label: 'Epithelial Cells',  section: '' },
    { code: 'UA_MUC',   label: 'Mucus Threads',     section: '' },
    { code: 'UA_AMO',   label: 'Amorphous Subs.',   section: '' },
    { code: 'UA_BAC',   label: 'Bacteria',          section: '' },
    { code: 'UA_CRY',   label: 'Crystals',          section: '' },
    { code: 'UA_OTH',   label: 'Others',            section: '' },
]

// ── Stool rows ──────────────────────────────────────
const stoolRows = [
    { code: 'ST_COL', label: 'Color' },
    { code: 'ST_CON', label: 'Consistency' },
    { code: 'ST_PUS', label: 'Pus Cells' },
    { code: 'ST_RBC', label: 'Red Blood Cells' },
    { code: 'ST_OVA', label: 'Ova/Parasites' },
]

// ── Others / Serology rows ──────────────────────────
const serologyRows = [
    { code: 'HBSAG',   label: 'Hepatitis B sAg' },
    { code: 'VDRL',    label: 'VDRL (Syphilis)' },
    { code: 'HIV1',    label: 'HIV 1 Ab' },
    { code: 'HIV2',    label: 'HIV 2 Ab' },
    { code: 'PREG',    label: 'Pregnancy Test' },
    { code: 'BTYPE2',  label: 'Blood Type' },
    { code: 'PSA',     label: 'Prostatic Specific Antigen' },
    { code: 'DNG_NS1', label: 'Dengue NS1 Ag' },
    { code: 'DNG_IGG', label: 'Dengue IgG' },
    { code: 'DNG_IGM', label: 'Dengue IgM' },
]

// Style helpers
const resultStyle = (code) =>
    isAbnormal(code)
        ? 'font-weight:700; color:#dc2626;'
        : 'font-weight:600;'

const rowStyle = (code) =>
    isAbnormal(code) ? 'background:#fff0f0;' : ''
</script>

<template>
    <div style="
        font-family: Arial, sans-serif;
        font-size: 9.5px;
        color: #111;
        background: white;
        padding: 8px 10px;
        max-width: 100%;
    ">

        <!-- ── HEADER ─────────────────────────────── -->
        <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #0F2044; padding-bottom:6px; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <img :src="CLINIC_LOGO" alt="SPDL Logo" style="width:50px; height:50px; object-fit:contain;"/>
                <div>
                    <div style="font-weight:900; font-size:12px; color:#0F2044;">{{ CLINIC_INFO.name.toUpperCase() }}</div>
                    <div style="font-size:9px; color:#1B4F9B; font-weight:700; font-style:italic;">{{ CLINIC_INFO.subtitle }}</div>
                    <div style="font-size:8.5px; color:#444;">{{ CLINIC_INFO.addressFull }}</div>
                    <div style="font-size:8.5px; color:#444;">Contact No.: SMART: {{ CLINIC_INFO.phoneSmart }} · E-mail: {{ CLINIC_INFO.email }}</div>
                </div>
            </div>
            <div style="text-align:right; flex-shrink:0;">
                <div style="font-size:17px; font-weight:900; color:#0F2044;">LABORATORY RESULT</div>
                <div v-if="visit.visit_type === 'pre_employment'"
                    style="font-size:10px; font-weight:700; color:#8B5CF6;">PRE-EMPLOYMENT</div>
                <table style="margin-left:auto; border-collapse:collapse; margin-top:3px;">
                    <tr>
                        <td style="color:#777; padding-right:8px; font-size:9px;">Control No.:</td>
                        <td style="font-weight:700; font-size:9px;">{{ labRequest?.request_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#777; padding-right:8px; font-size:9px;">Exam Date:</td>
                        <td style="font-weight:700; font-size:9px;">{{ visit.visit_date }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ── PATIENT INFO ───────────────────────── -->
        <div style="padding:5px 8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:3px; margin-bottom:8px;">
            <div style="font-weight:900; font-size:12px; color:#0F2044;">{{ patient.full_name?.toUpperCase() }}</div>
            <div style="display:flex; gap:20px; margin-top:2px; font-size:9px; color:#444;">
                <span><strong>Age/Sex:</strong> {{ patient.age_sex }}</span>
                <span><strong>Birthdate:</strong> {{ patient.birthdate ?? '—' }}</span>
                <span><strong>Civil Status:</strong> {{ patient.civil_status ?? '—' }}</span>
                <span v-if="visit.employer_company"><strong>Company:</strong>
                    <strong style="color:#8B5CF6;">{{ visit.employer_company }}</strong>
                </span>
                <span><strong>Exam Type:</strong>
                    <strong style="color:#1B4F9B;">{{ IS_PE_TYPE(visit.visit_type) ? 'PRE-EMPLOYMENT' : 'OPD'}}</strong>
                </span>
            </div>
        </div>

        <!-- ── ROW 1: CBC + BLOOD CHEMISTRY ─────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">

            <!-- CBC -->
            <div>
                <div style="font-weight:900; font-size:9.5px; text-align:center; background:#0F2044; color:white; padding:3px 6px;">
                    COMPLETE BLOOD COUNT
                </div>
                <table style="width:100%; border-collapse:collapse; border:1px solid #ccc; border-top:none;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="text-align:left; padding:2px 5px; font-size:8.5px; border-bottom:1px solid #ccc; width:44%;">EXAMINATION</th>
                            <th style="text-align:center; padding:2px 4px; font-size:8.5px; border-bottom:1px solid #ccc; width:20%;">RESULT</th>
                            <th style="text-align:left; padding:2px 4px; font-size:8.5px; border-bottom:1px solid #ccc; white-space:nowrap;">REF. RANGE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in cbcRows" :key="row.code" :style="rowStyle(row.code)">
                            <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px;">{{ row.label }}</td>
                            <td style="padding:1.5px 4px; border-bottom:1px solid #f0f0f0; text-align:center; font-size:9px;">
                                <span :style="resultStyle(row.code)">{{ getResult(row.code) }}</span>
                                <sup v-if="getFlag(row.code)" style="font-size:7px; font-weight:900; color:#dc2626;">{{ getFlag(row.code) }}</sup>
                            </td>
                            <td style="padding:1.5px 4px; border-bottom:1px solid #f0f0f0; font-size:8px; color:#666; font-style:italic;">{{ getNormalRange(row.code) }}</td>
                        </tr>
                    </tbody>
                </table>

            </div>

            <!-- Blood Chemistry -->
            <div>
                <div style="font-weight:900; font-size:9.5px; text-align:center; background:#0F2044; color:white; padding:3px 6px;">
                    BLOOD CHEMISTRY
                </div>
                <table style="width:100%; border-collapse:collapse; border:1px solid #ccc; border-top:none;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="text-align:left; padding:2px 5px; font-size:8.5px; border-bottom:1px solid #ccc; width:50%;">EXAMINATION</th>
                            <th style="text-align:center; padding:2px 4px; font-size:8.5px; border-bottom:1px solid #ccc; width:20%;">RESULT</th>
                            <th style="text-align:left; padding:2px 4px; font-size:8.5px; border-bottom:1px solid #ccc; white-space:nowrap;">REF. RANGE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in chemRows" :key="row.code" :style="rowStyle(row.code)">
                            <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px;">{{ row.label }}</td>
                            <td style="padding:1.5px 4px; border-bottom:1px solid #f0f0f0; text-align:center; font-size:9px;">
                                <span :style="resultStyle(row.code)">{{ getResult(row.code) }}</span>
                                <sup v-if="getFlag(row.code)" style="font-size:7px; font-weight:900; color:#dc2626;">{{ getFlag(row.code) }}</sup>
                            </td>
                            <td style="padding:1.5px 4px; border-bottom:1px solid #f0f0f0; font-size:8px; color:#666; font-style:italic;">{{ getNormalRange(row.code) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── ROW 2: URINALYSIS + STOOL + OTHERS ─ -->
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:6px;">

            <!-- Urinalysis -->
            <div>
                <div style="font-weight:900; font-size:9.5px; text-align:center; background:#0F2044; color:white; padding:3px 6px;">URINALYSIS</div>
                <div style="font-size:7.5px; font-weight:700; text-align:center; background:#f1f5f9; border:1px solid #ccc; border-top:none; padding:1.5px; color:#555; letter-spacing:0.5px;">QUALITATIVE EXAMINATION</div>
                <table style="width:100%; border-collapse:collapse; border:1px solid #ccc; border-top:none;">
                    <tbody>
                        <tr v-for="row in urinalysisRows.filter(r => ['UA_COLOR','UA_TRANS'].includes(r.code))" :key="row.code" :style="rowStyle(row.code)">
                            <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; width:55%;">{{ row.label }}</td>
                            <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; font-weight:600;">
                                <span :style="resultStyle(row.code)">{{ getResult(row.code) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="font-size:7.5px; font-weight:700; text-align:center; background:#f1f5f9; border:1px solid #ccc; border-top:none; padding:1.5px; color:#555; letter-spacing:0.5px;">MICROSCOPIC EXAMINATION</div>
                <table style="width:100%; border-collapse:collapse; border:1px solid #ccc; border-top:none;">
                    <tbody>
                        <tr v-for="row in urinalysisRows.filter(r => !['UA_COLOR','UA_TRANS'].includes(r.code))" :key="row.code" :style="rowStyle(row.code)">
                            <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; width:55%;">{{ row.label }}</td>
                            <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; font-weight:600;">
                                <span :style="resultStyle(row.code)">{{ getResult(row.code) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <!-- UA Staff -->
                <div style="font-size:8px; margin-top:4px; border-top:1px solid #ddd; padding-top:3px; color:#555;">
                    <div style="color:#777; margin-bottom:8px;">Examined By:</div>
                    <div style="border-top:1px solid #333; padding-top:2px;">
                        <strong>{{ labRequest?.examined_by_name ?? '' }}</strong><br/>
                        <span>License No.: {{ labRequest?.examined_by_license ?? '' }}</span><br/>
                        <strong>Medical Technologist</strong>
                    </div>
                    <div style="color:#777; margin-top:5px; margin-bottom:8px;">Noted By:</div>
                    <div style="border-top:1px solid #333; padding-top:2px;">
                        <strong>{{ labRequest?.noted_by_name ?? '' }}</strong><br/>
                        <span>License No.: {{ labRequest?.noted_by_license ?? '' }}</span>
                    </div>
                </div>
            </div>

            <!-- Stool + Others -->
            <div>
                <div style="font-weight:900; font-size:9.5px; text-align:center; background:#0F2044; color:white; padding:3px 6px;">STOOL EXAM</div>
                <table style="width:100%; border-collapse:collapse; border:1px solid #ccc; border-top:none;">
                    <tbody>
                        <tr v-for="row in stoolRows" :key="row.code" :style="rowStyle(row.code)">
                            <td style="padding:2px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; width:55%;">{{ row.label }}</td>
                            <td style="padding:2px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; font-weight:600;">
                                <span :style="resultStyle(row.code)">{{ getResult(row.code) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top:6px;">
                    <div style="font-weight:900; font-size:9.5px; text-align:center; background:#0F2044; color:white; padding:3px 6px;">OTHERS</div>
                    <table style="width:100%; border-collapse:collapse; border:1px solid #ccc; border-top:none;">
                        <tbody>
                            <tr v-for="row in serologyRows.filter(r => r.code !== 'PSA')" :key="row.code" :style="rowStyle(row.code)">
                                <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; width:60%;">{{ row.label }}</td>
                                <td style="padding:1.5px 5px; border-bottom:1px solid #f0f0f0; font-size:9px; font-weight:600;">
                                    <span :style="resultStyle(row.code)">{{ getResult(row.code) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PSA + Notes -->
            <div>
                <div style="border:1px solid #ccc; border-radius:3px; padding:6px; font-size:8.5px; color:#444; margin-bottom:5px;">
                    <div style="font-weight:700; margin-bottom:3px; font-size:9px;">Prostatic Specific Antigen (PSA)</div>
                    <div>•Normal: 0 - 4 ng/mL</div>
                    <div>•Slightly Elevated: 4 - 10 ng/mL</div>
                    <div>•Moderately Elevated: 10 - 20 ng/mL</div>
                    <div>•Highly Elevated: 20+ ng/mL</div>
                </div>
                <div style="border:1px solid #ccc; border-radius:3px; padding:6px; margin-bottom:5px; font-size:8.5px;">
                    <div style="color:#777; margin-bottom:2px;">PSA Result:</div>
                    <div style="font-size:14px; font-weight:900; color:#0F2044;">{{ getResult('PSA') || '—' }} ng/mL</div>
                </div>
                <div style="border:1px solid #ccc; border-radius:3px; padding:6px; margin-bottom:5px; font-size:8.5px; min-height:32px;">
                    <div style="font-weight:700; color:#777; margin-bottom:3px; letter-spacing:0.3px;">REMARKS:</div>
                    <div>{{ labRequest?.clinical_notes ?? '' }}</div>
                </div>
                <div style="border:1px solid #ddd; border-radius:3px; padding:5px; font-size:8px; color:#555; margin-bottom:4px;">
                    <strong>NOTE:</strong> Xray films submitted for official reading will be available one week after submission.
                </div>
                <div style="border:1px solid #ddd; border-radius:3px; padding:5px; font-size:8px; color:#555;">
                    <strong>NOTE:</strong> ECG is required for person with hypertension, heart disease, and other related illnesses.
                </div>
            </div>
        </div>

        <!-- ── FOOTER ─────────────────────────────── -->
        <div style="border-top:2px solid #0F2044; padding-top:4px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:5px;">
                <img :src="CLINIC_LOGO" alt="SPDL" style="width:18px; height:18px; object-fit:contain; opacity:0.6;"/>
                <span style="font-size:8px; color:#555; font-weight:600;">SAINT PETER DIAGNOSTICS AND LABORATORY</span>
            </div>
            <span style="font-size:8px; color:#777;">{{ patient.full_name }}</span>
            <span style="font-size:8px; color:#777;">Medical Examination Report 2/2</span>
        </div>

    </div>
</template>
