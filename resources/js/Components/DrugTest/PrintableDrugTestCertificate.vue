<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'

const props = defineProps({
    visit:    Object,
    patient:  Object,
    drugTest: Object,
})

const drugsMap = {
    thc:              'THC',
    met:              'MET',
    thc_met:          'THC & MET',
    thc_coc_pcp_opi_amp: 'THC, COC, PCP, OPI, AMP',
}

const purposes = {
    pre_employment:      'Pre-employment',
    return_to_duty:      'Return to Duty',
    random:              'Random',
    reasonable_suspicion:'Reasonable Suspicion/Cause',
    follow_up:           'Follow-up',
    post_accident:       'Post-accident',
    mandatory:           'Mandatory',
}

const isChecked = (drug) =>
    (props.drugTest?.drugs_to_test ?? []).includes(drug)

const isPurpose = (p) => props.drugTest?.test_purpose === p
</script>

<template>
    <div style="font-family:Arial,sans-serif; font-size:11px; color:#111; background:white; padding:20px 24px; max-width:800px; margin:0 auto;">

        <!-- ── HEADER ─────────────────────────────── -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <img :src="CLINIC_LOGO" style="width:56px; height:56px; object-fit:contain;"/>
                <div>
                    <div style="font-weight:900; font-size:13px; color:#0F2044;">{{ CLINIC_INFO.name.toUpperCase() }}</div>
                    <div style="font-size:9.5px; color:#1B4F9B; font-weight:700; font-style:italic;">{{ CLINIC_INFO.subtitle }}</div>
                    <div style="font-size:9px; color:#444;">{{ CLINIC_INFO.addressFull }}</div>
                </div>
            </div>
        </div>

        <div style="text-align:center; font-weight:900; font-size:15px; letter-spacing:1px; margin:10px 0 16px; border-top:2px solid #0F2044; border-bottom:2px solid #0F2044; padding:6px;">
            DRUG TEST REQUEST
        </div>

        <!-- ── REQUEST INFO ───────────────────────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px; font-size:10.5px;">
            <div>
                <span style="color:#555;">CODE NO.: </span>
                <strong>{{ drugTest?.code_number ?? '—' }}</strong>
            </div>
            <div>
                <span style="color:#555;">OR#: </span>
                <strong>{{ visit.visit_date }}</strong>
            </div>
            <div>
                <span style="color:#555;">Accession No.: </span>
                <strong>{{ drugTest?.accession_number ?? '—' }}</strong>
            </div>
            <div></div>
        </div>

        <!-- ── PATIENT INFO ───────────────────────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px 12px; margin-bottom:12px; font-size:10.5px;">
            <div style="grid-column:1/-1; border-bottom:1px solid #ccc; padding-bottom:4px; margin-bottom:4px;">
                <span style="color:#555; font-size:9.5px;">Name:</span>
                <span style="margin-left:8px;"></span>
                <strong style="font-size:13px; letter-spacing:0.5px;">{{ patient.last_name?.toUpperCase() }}</strong>
                <span style="margin:0 12px; color:#999; font-size:9px;">Surname</span>
                <strong style="font-size:13px;">{{ patient.first_name?.toUpperCase() }}</strong>
                <span style="margin:0 12px; color:#999; font-size:9px;">First Name</span>
                <strong style="font-size:13px;">{{ patient.middle_name?.charAt(0)?.toUpperCase() }}</strong>
                <span style="margin-left:4px; color:#999; font-size:9px;">M.I.</span>
            </div>

            <div>
                <span style="color:#555;">Age: </span>
                <strong>{{ patient.age }}</strong>
            </div>
            <div>
                <span style="color:#555;">Sex: </span>
                <strong>{{ patient.sex?.toUpperCase() }}</strong>
            </div>
            <div>
                <span style="color:#555;">Date of Birth: </span>
                <strong>{{ patient.birthdate }}</strong>
            </div>

            <div style="grid-column:1/-1; margin-top:4px;">
                <span style="color:#555;">Company: </span>
                <strong>{{ drugTest?.company ?? visit.employer_company ?? '—' }}</strong>
            </div>
        </div>

        <!-- ── PURPOSE & DRUGS ────────────────────── -->
        <div style="margin-bottom:12px; font-size:10.5px;">
            <div style="margin-bottom:6px;"><strong>Purpose:</strong></div>
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <label v-for="(label, key) in purposes" :key="key"
                    style="display:flex; align-items:center; gap:5px;">
                    <span style="width:12px; height:12px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;">
                        {{ isPurpose(key) ? '✓' : '' }}
                    </span>
                    {{ label }}
                </label>
            </div>
        </div>

        <div style="margin-bottom:16px; font-size:10.5px;">
            <div style="margin-bottom:6px;"><strong>Drugs to be performed:</strong></div>
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <label v-for="(label, key) in drugsMap" :key="key"
                    style="display:flex; align-items:center; gap:5px;">
                    <span style="width:12px; height:12px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;">
                        {{ isChecked(key) ? '✓' : '' }}
                    </span>
                    {{ label }}
                </label>
            </div>
        </div>

        <!-- ── SPECIMEN INFO ──────────────────────── -->
        <div style="border:1px solid #ccc; border-radius:4px; padding:10px; margin-bottom:16px; font-size:10px;">
            <div style="font-weight:700; margin-bottom:8px; font-size:11px; color:#0F2044;">SPECIMEN COLLECTION</div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px;">
                <div><span style="color:#555;">Type: </span><strong>{{ drugTest?.specimen_type?.toUpperCase() }}</strong></div>
                <div><span style="color:#555;">Time: </span><strong>{{ drugTest?.specimen_time }}</strong></div>
                <div><span style="color:#555;">Volume: </span><strong>{{ drugTest?.specimen_volume }} mL</strong></div>
                <div><span style="color:#555;">Appearance: </span><strong>{{ drugTest?.specimen_appearance }}</strong></div>
                <div><span style="color:#555;">Sampling: </span><strong style="text-transform:capitalize">{{ drugTest?.specimen_sampling }}</strong></div>
                <div><span style="color:#555;">Temp in range: </span>
                    <strong :style="{ color: drugTest?.temp_in_range ? '#15803d' : '#dc2626' }">
                        {{ drugTest?.temp_in_range ? 'Yes (32–38°C)' : 'No' }}
                    </strong>
                </div>
            </div>
        </div>

        <!-- ── RESULT ─────────────────────────────── -->
        <div style="border:2px solid #0F2044; border-radius:4px; padding:12px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="font-size:11px; color:#555;">RESULT:</div>
                <div style="font-size:22px; font-weight:900; letter-spacing:2px;"
                    :style="{ color: drugTest?.result === 'negative' ? '#15803d' : drugTest?.result === 'positive' ? '#dc2626' : '#64748b' }">
                    {{ drugTest?.result?.toUpperCase() ?? 'PENDING' }}
                </div>
            </div>
            <div v-if="drugTest?.result_remarks" style="font-size:10px; color:#555; margin-top:6px; border-top:1px solid #eee; padding-top:6px;">
                {{ drugTest.result_remarks }}
            </div>
            <!-- Head of Lab sign-off -->
            <div style="display:flex; justify-content:space-between; margin-top:16px;">
                <div style="text-align:center;">
                    <div style="min-height:40px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:2px;">
                        <img v-if="drugTest?.collector_signature"
                            :src="drugTest.collector_signature"
                            style="max-height:38px; max-width:150px; object-fit:contain;"
                            alt="Signature"/>
                    </div>
                    <div style="border-top:1px solid #333; padding-top:4px; min-width:200px;">
                        <strong style="font-size:10.5px;">{{ drugTest?.collector_name ?? '' }}</strong>
                        <div style="font-size:9.5px; color:#555;">Lic. No. {{ drugTest?.collector_license ?? '' }}</div>
                        <div style="font-size:9.5px; font-weight:600;">Collector</div>
                    </div>
                </div>
                <div style="text-align:center;">
                    <div style="min-height:40px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:2px;">
                        <img v-if="drugTest?.head_of_lab_signature"
                            :src="drugTest.head_of_lab_signature"
                            style="max-height:38px; max-width:150px; object-fit:contain;"
                            alt="Signature"/>
                    </div>
                    <div style="border-top:1px solid #333; padding-top:4px; min-width:200px;">
                        <strong style="font-size:10.5px;">{{ drugTest?.head_of_lab_name ?? '' }}</strong>
                        <div style="font-size:9.5px; color:#555;">Lic. No. {{ drugTest?.head_of_lab_license ?? '' }}</div>
                        <div style="font-size:9.5px; font-weight:600;">Head of Screening Laboratory</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── CERTIFICATION ──────────────────────── -->
        <div style="border:1px solid #ccc; border-radius:4px; padding:14px; margin-bottom:12px;">
            <div style="font-weight:900; font-size:13px; text-align:center; letter-spacing:1px; margin-bottom:12px;">
                CERTIFICATION
            </div>
            <p style="font-size:10px; line-height:1.7; margin-bottom:10px;">
                I certify to the best of my knowledge that <strong><u>I have not been found</u></strong> positive of any regulated drug
                by any Drug Test Laboratory for the past six (6) months.
            </p>
            <p style="font-size:10px; line-height:1.6; margin-bottom:16px; color:#444;">
                And that should be found making false statements to this regard I shall be held liable and shall be charged of perjury.
                And that all appurtenances, in case I shall be found negative by this Drug Test Laboratory, shall be revoked as a consequence of such statement.
            </p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:10.5px;">
                <div>
                    <span style="color:#555;">Name: </span>
                    <span style="border-bottom:1px solid #333; display:inline-block; min-width:200px; font-weight:700;">
                        {{ patient.full_name?.toUpperCase() }}
                    </span>
                </div>
                <div>
                    <span style="color:#555;">Date: </span>
                    <span style="border-bottom:1px solid #333; display:inline-block; min-width:120px;">
                        {{ drugTest?.certification_date ?? '—' }}
                    </span>
                </div>
                <div>
                    <span style="color:#555;">Signature: </span>
                    <span style="border-bottom:1px solid #333; display:inline-block; min-width:200px; height:20px;"></span>
                </div>
                <div>
                    <span style="color:#555;">Date of Birth: </span>
                    <span style="border-bottom:1px solid #333; display:inline-block; min-width:120px;">
                        {{ patient.birthdate }}
                    </span>
                </div>
                <div style="grid-column:1/-1;">
                    <span style="color:#555;">Complete Address: </span>
                    <span style="border-bottom:1px solid #333; display:inline-block; min-width:400px;">
                        {{ patient.address?.toUpperCase() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="border-top:2px solid #0F2044; padding-top:5px; display:flex; justify-content:space-between; font-size:8.5px; color:#555;">
            <div style="display:flex; align-items:center; gap:5px;">
                <img :src="CLINIC_LOGO" style="width:16px; height:16px; object-fit:contain; opacity:0.6;"/>
                <span style="font-weight:600;">SAINT PETER DIAGNOSTICS AND LABORATORY</span>
            </div>
            <span>{{ patient.full_name }}</span>
            <span>Drug Test Certificate</span>
        </div>

    </div>
</template>
