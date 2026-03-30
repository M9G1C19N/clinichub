<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'

const props = defineProps({
    visit:    Object,
    patient:  Object,
    drugTest: Object,
})

const isDrug = (val) =>
    (props.drugTest?.drugs_to_test ?? []).includes(val)

const isPurpose = (val) =>
    props.drugTest?.test_purpose === val

const isResult = (val) =>
    props.drugTest?.result === val
</script>

<template>
    <div style="
        font-family: Arial, sans-serif;
        font-size: 9.5px;
        color: #111;
        background: white;
        padding: 14px 18px;
        max-width: 820px;
        margin: 0 auto;
        line-height: 1.5;
    ">

        <!-- ── FORM HEADER ────────────────────────── -->
        <div style="text-align:center; margin-bottom:10px;">
            <div style="font-weight:700; font-size:11px;">CUSTODY AND CONTROL FORM</div>
            <div style="font-size:10px;">(Form DT-002C – COPY FOR THE LABORATORY)</div>
        </div>

        <!-- Specimen ID row -->
        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:9.5px;">
            <div>
                <span style="color:#555;">SPECIMEN ID NO.: </span>
                <span style="border-bottom:1px solid #333; display:inline-block; min-width:150px;">
                    {{ drugTest?.code_number ?? '' }}
                </span>
            </div>
            <div>
                <span style="color:#555;">LAB ACCESSION NO.: </span>
                <span style="border-bottom:1px solid #333; display:inline-block; min-width:150px;">
                    {{ drugTest?.accession_number ?? '' }}
                </span>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- STEP 1 — system fills                      -->
        <!-- ══════════════════════════════════════════ -->
        <div style="border:1px solid #333; margin-bottom:6px;">
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; font-size:9.5px; border-bottom:1px solid #333;">
                STEP 1 COMPLETED BY THE COLLECTOR OR EMPLOYER REPRESENTATIVE.
            </div>
            <div style="padding:6px;">

                <!-- A — Client info -->
                <div style="margin-bottom:5px; font-size:9.5px;">
                    <span style="font-weight:700;">A. Client's/Donor's/Subject's Code.</span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:180px; margin-left:6px;">
                        {{ patient.full_name?.toUpperCase() }}
                    </span>
                    <span style="margin-left:12px;">Go-B. Address: </span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:160px; margin-left:4px;">
                        {{ patient.address?.toUpperCase() ?? '' }}
                    </span>
                    <span style="margin-left:12px;">Age: </span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:30px; margin-left:4px;">
                        {{ patient.age }}
                    </span>
                    <span style="margin-left:8px;">D-Sex: </span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:30px; margin-left:4px;">
                        {{ patient.sex?.charAt(0)?.toUpperCase() }}
                    </span>
                </div>

                <!-- B — Employer Name -->
                <div style="margin-bottom:5px; font-size:9.5px;">
                    <span style="font-weight:700;">B. Employer Name and Address:</span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:300px; margin-left:6px;">
                        {{ drugTest?.company ?? visit.employer_company ?? '' }}
                    </span>
                </div>

                <!-- F & G — Specimen type + Reason -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:5px; font-size:9.5px;">
                    <div>
                        <span style="font-weight:700;">F. Type of Specimen</span><br/>
                        <div style="display:flex; gap:12px; margin-top:3px;">
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">
                                    {{ drugTest?.specimen_type === 'urine' ? '✓' : '' }}
                                </span>
                                Urine
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">
                                    {{ drugTest?.specimen_type === 'blood' ? '✓' : '' }}
                                </span>
                                Blood
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">
                                    {{ drugTest?.specimen_type === 'other' ? '✓' : '' }}
                                </span>
                                Others (specify)
                                <span style="border-bottom:1px solid #555; display:inline-block; min-width:50px;"></span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <span style="font-weight:700;">G. Reason for Test:</span><br/>
                        <div style="display:flex; gap:10px; margin-top:3px; flex-wrap:wrap;">
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isPurpose('pre_employment') ? '✓' : '' }}</span>
                                Pre-employment
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isPurpose('random') ? '✓' : '' }}</span>
                                Random
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isPurpose('reasonable_suspicion') ? '✓' : '' }}</span>
                                Reasonable Suspicion/Cause
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isPurpose('return_to_duty') ? '✓' : '' }}</span>
                                Return-to-Duty
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isPurpose('mandatory') ? '✓' : '' }}</span>
                                Mandatory
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isPurpose('post_accident') ? '✓' : '' }}</span>
                                Post-accident
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isPurpose('follow_up') ? '✓' : '' }}</span>
                                Follow-up
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;"></span>
                                Others (specify)
                                <span style="border-bottom:1px solid #555; display:inline-block; min-width:60px;"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- H — Drugs to test -->
                <div style="font-size:9.5px;">
                    <span style="font-weight:700;">H. Drug test to be Performed:</span>
                    <div style="display:flex; gap:16px; margin-top:3px;">
                        <label style="display:flex; align-items:center; gap:3px;">
                            <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isDrug('thc') ? '✓' : '' }}</span>
                            THC
                        </label>
                        <label style="display:flex; align-items:center; gap:3px;">
                            <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isDrug('met') ? '✓' : '' }}</span>
                            MET
                        </label>
                        <label style="display:flex; align-items:center; gap:3px;">
                            <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isDrug('thc_met') ? '✓' : '' }}</span>
                            THC &amp; MET Only
                        </label>
                        <label style="display:flex; align-items:center; gap:3px;">
                            <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">{{ isDrug('thc_coc_pcp_opi_amp') ? '✓' : '' }}</span>
                            THC, COC, PCP, OPI, AMP
                        </label>
                        <label style="display:flex; align-items:center; gap:3px;">
                            <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;"></span>
                            Others (specify)
                            <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px;"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- STEP 2 — system fills                      -->
        <!-- ══════════════════════════════════════════ -->
        <div style="border:1px solid #333; margin-bottom:6px;">
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; font-size:9.5px; border-bottom:1px solid #333;">
                STEP 2 COMPLETED BY COLLECTOR
            </div>
            <div style="padding:6px; font-size:9.5px;">

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:6px;">

                    <div>
                        <span style="font-weight:700;">Specimen Collection:</span><br/>
                        <div style="display:flex; gap:8px; margin-top:3px;">
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">
                                    {{ drugTest?.specimen_collection === 'observed' ? '✓' : '' }}
                                </span>
                                Observed
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">
                                    {{ drugTest?.specimen_collection === 'unobserved' ? '✓' : '' }}
                                </span>
                                Unobserved
                            </label>
                        </div>
                    </div>

                    <div>
                        <span style="font-weight:700;">Specimen Sampling:</span><br/>
                        <div style="display:flex; gap:8px; margin-top:3px;">
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">
                                    {{ drugTest?.specimen_sampling === 'single' ? '✓' : '' }}
                                </span>
                                Single
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px;">
                                    {{ drugTest?.specimen_sampling === 'split' ? '✓' : '' }}
                                </span>
                                Split
                            </label>
                        </div>
                    </div>

                    <div style="font-size:9px; color:#555; font-style:italic;">
                        Other Observation (Enter Remark)
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:6px;">
                    <div>
                        Read specimen temperature within 4 minutes.<br/>
                        Is temperature between 32°C and 38°C?
                        <div style="display:flex; gap:12px; margin-top:3px;">
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px; font-weight:700;">
                                    {{ drugTest?.temp_in_range === true ? '✓' : '' }}
                                </span>
                                Yes
                            </label>
                            <label style="display:flex; align-items:center; gap:3px;">
                                <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:8px; font-weight:700;">
                                    {{ drugTest?.temp_in_range === false ? '✓' : '' }}
                                </span>
                                No
                            </label>
                        </div>
                    </div>
                    <div>
                        <div style="margin-bottom:3px;">
                            Specimen Volume:
                            <span style="border-bottom:1px solid #555; display:inline-block; min-width:50px; margin-left:4px;">
                                {{ drugTest?.specimen_volume ?? '' }}
                            </span>
                            mL
                        </div>
                        <div>
                            Physical Appearance: Color:
                            <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px; margin-left:4px;">
                                {{ drugTest?.specimen_appearance ?? '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <span style="font-weight:700;">REMARKS</span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:400px; margin-left:8px;">
                        {{ drugTest?.remarks ?? '' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- STEPS 3 & 4 — Chain of Custody (blank)    -->
        <!-- ══════════════════════════════════════════ -->
        <div style="border:1px solid #333; margin-bottom:6px; font-size:9px;">
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; border-bottom:1px solid #333;">
                STEP 3 Collector affixes bottle seal(s) to bottle(s). Collector dates seal(s). Donor initials seal(s). Donor completes STEP 5.
            </div>
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; border-bottom:1px solid #333;">
                STEP 4 CHAIN OF CUSTODY – INITIATED BY COLLECTOR AND COMPLETED BY LABORATORY
            </div>
            <div style="padding:6px; font-size:9px; color:#444; font-style:italic; margin-bottom:4px;">
                I certify that the specimen given to me by the donor identified in the certification section on Step 5 of this form was collected, sealed and released to the Delivery Service noted in accordance with applicable Department of Health requirements.
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:6px;">
                <!-- Collector -->
                <div>
                    <div style="margin-bottom:16px;">
                        <div style="border-bottom:1px solid #333; margin-bottom:2px; min-height:18px;"></div>
                        <div style="font-size:8.5px;">
                            X <span style="margin-left:8px;">Signature of Collector</span>
                            <span style="margin-left:20px;">Time of Collection: </span>
                            <span style="border-bottom:1px solid #555; display:inline-block; min-width:60px; margin-left:4px;">{{ drugTest?.specimen_time ?? '' }}</span>
                            AM/PM
                        </div>
                    </div>
                    <div>
                        <span style="border-bottom:1px solid #333; display:block; margin-bottom:2px; min-height:14px;">
                            {{ drugTest?.collector_name ?? '' }}
                        </span>
                        <div style="font-size:8.5px;">
                            (PRINT) Collector's Name (First, MI, Last)
                            <span style="margin-left:16px;">Date: </span>
                            <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px; margin-left:4px;">{{ visit.visit_date }}</span>
                        </div>
                    </div>
                </div>
                <!-- Specimen released to -->
                <div>
                    <div style="font-size:8.5px; color:#555; margin-bottom:6px;">SPECIMEN BOTTLE(S) RELEASED TO:</div>
                    <div style="border-bottom:1px solid #555; min-height:14px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">Name of delivery Service Transferring Specimen to Lab</div>
                </div>
            </div>

            <!-- Received at lab -->
            <div style="border-top:1px solid #ccc; padding:6px; display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px;">
                <div>
                    <div style="font-weight:700; font-size:9px; margin-bottom:4px;">RECEIVED AT LAB.:</div>
                    <div style="border-bottom:1px solid #333; min-height:18px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">X &nbsp; Signature of Accessioner</div>
                    <div style="border-bottom:1px solid #555; min-height:14px; margin-top:4px; margin-bottom:2px;">
                        {{ drugTest?.collector_name ?? '' }}
                    </div>
                    <div style="font-size:8.5px;">(PRINT) Accessioner's Name (First, MI, Last)</div>
                    <div style="font-size:8.5px; margin-top:2px;">Date: <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px;">{{ visit.visit_date }}</span></div>
                </div>
                <div>
                    <div style="font-weight:700; font-size:9px; margin-bottom:4px;">STATUS OF THE SPECIMEN</div>
                    <div style="font-size:8.5px;">(a) Seal intact: &nbsp; Yes &nbsp; No</div>
                    <div style="font-size:8.5px; margin-top:4px;">(b) Transport device:</div>
                    <div style="font-size:8.5px; margin-top:4px;">(c) Description:</div>
                </div>
                <div>
                    <div style="font-size:8.5px; color:#555; margin-bottom:4px;">SPECIMEN BOTTLE(S) RELEASED TO:</div>
                    <div style="border-bottom:1px solid #333; min-height:18px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">Signature of Receiving Person</div>
                    <div style="border-bottom:1px solid #555; min-height:14px; margin-top:4px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">(PRINT) Name (First, MI, Last) &nbsp; Date (mm/dd/yy)</div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- STEP 5 — Patient Certification (partial)   -->
        <!-- ══════════════════════════════════════════ -->
        <div style="border:1px solid #333; margin-bottom:6px; font-size:9px;">
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; border-bottom:1px solid #333;">
                STEP 5 COMPLETED BY THE DONOR
            </div>
            <div style="padding:6px; font-style:italic; color:#444; margin-bottom:8px; font-size:8.5px; line-height:1.6;">
                I certify that I provided my urine specimen to the collector; that I have not adulterated it in any manner; each specimen bottle used was sealed with a tamper-evident seal in my presence; and that the information on this form and on the bottle is correct.
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:0 6px 6px;">
                <div>
                    <div style="border-bottom:1px solid #333; min-height:22px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">X &nbsp; Signature of Donor</div>
                </div>
                <div>
                    <div style="font-size:8.5px;">Date: <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px; margin-left:4px;">{{ drugTest?.certification_date ?? '' }}</span></div>
                    <div style="font-size:8.5px; margin-top:4px;">Date of Birth: <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px; margin-left:4px;">{{ patient.birthdate }}</span></div>
                </div>
            </div>
            <div style="font-size:8px; color:#555; padding:0 6px 6px; font-style:italic;">
                Additional information may be asked from you by the laboratory particularly on drug and medications.
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- STEP 6 — Head of Screening Lab (result)    -->
        <!-- ══════════════════════════════════════════ -->
        <div style="border:1px solid #333; margin-bottom:6px; font-size:9px;">
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; border-bottom:1px solid #333;">
                STEP 6 COMPLETED BY HEAD OF SCREENING LABORATORY
            </div>
            <div style="padding:6px; font-style:italic; color:#444; margin-bottom:6px; font-size:8.5px;">
                In accordance with applicable Department of Health requirements, my determination/verification for:
            </div>

            <div style="display:flex; gap:20px; padding:0 6px 6px; flex-wrap:wrap; font-size:9.5px; font-weight:700;">
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px; font-weight:900; color:#15803d;">
                        {{ isResult('negative') ? '✓' : '' }}
                    </span>
                    <span :style="{ color: isResult('negative') ? '#15803d' : '#111', fontWeight: isResult('negative') ? '900' : '400' }">NEGATIVE</span>
                </label>
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px; font-weight:900; color:#dc2626;">
                        {{ isResult('positive') ? '✓' : '' }}
                    </span>
                    <span :style="{ color: isResult('positive') ? '#dc2626' : '#111', fontWeight: isResult('positive') ? '900' : '400' }">POSITIVE</span>
                </label>
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;">
                        {{ isResult('cancelled') ? '✓' : '' }}
                    </span>
                    TEST CANCELLED
                </label>
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;">
                        {{ isResult('refusal') ? '✓' : '' }}
                    </span>
                    REFUSAL TO TEST BECAUSE
                </label>
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;">
                        {{ isResult('diluted') ? '✓' : '' }}
                    </span>
                    DILUTED
                </label>
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;">
                        {{ isResult('substituted') ? '✓' : '' }}
                    </span>
                    SUBSTITUTED
                </label>
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;">
                        {{ isResult('adulterated') ? '✓' : '' }}
                    </span>
                    ADULTERATED
                </label>
                <label style="display:flex; align-items:center; gap:4px;">
                    <span style="width:13px; height:13px; border:1.5px solid #333; display:inline-flex; align-items:center; justify-content:center; font-size:9px;"></span>
                    Others (specify)
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px; margin-left:4px;"></span>
                </label>
            </div>

            <div style="font-size:8.5px; padding:4px 6px; border-top:1px solid #eee;">
                REMARKS:
                <span style="border-bottom:1px solid #555; display:inline-block; min-width:250px; margin-left:8px;">
                    {{ drugTest?.result_remarks ?? '' }}
                </span>
            </div>

            <!-- Signatures -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:8px 6px 6px;">
                <div>
                    <div style="border-bottom:1px solid #333; min-height:20px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">X &nbsp; (PRINT) Signature &amp; Name of Analyst (First, MI, Last)</div>
                    <div style="border-bottom:1px solid #555; min-height:14px; margin-top:4px; margin-bottom:2px;">
                        {{ drugTest?.head_of_lab_name ?? '' }}
                    </div>
                    <div style="font-size:8.5px; color:#555;">
                        Lic. No. {{ drugTest?.head_of_lab_license ?? '' }}
                    </div>
                </div>
                <div>
                    <div style="border-bottom:1px solid #333; min-height:20px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px; margin-bottom:4px;">X &nbsp; (PRINT) Signature &amp; Name of Head of Laboratory (First, MI, Last)</div>
                    <div style="font-size:8.5px;">Date (mm/dd/yy):
                        <span style="border-bottom:1px solid #555; display:inline-block; min-width:80px; margin-left:4px;">
                            {{ drugTest?.released_at ?? visit.visit_date }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- STEP 7 — Confirmatory Lab (blank)          -->
        <!-- ══════════════════════════════════════════ -->
        <div style="border:1px solid #333; margin-bottom:6px; font-size:9px;">
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; border-bottom:1px solid #333;">
                STEP 7 COMPLETED BY CONFIRMATORY LABORATORY
            </div>
            <div style="padding:6px; font-style:italic; color:#444; margin-bottom:6px; font-size:8.5px;">
                In accordance with applicable Department of Health requirements, my determination/verification for the specimen (if tested) is:
            </div>
            <div style="display:flex; gap:20px; padding:0 6px; font-size:9px;">
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="font-weight:700;">CONFIRMED FOR:</span>
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex;"></span> THC
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex;"></span> MET
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex;"></span> Others (specify)
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:60px;"></span>
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="font-weight:700; color:#dc2626;">FAILED TO CONFIRM – REASON:</span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:100px;"></span>
                </label>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:8px 6px 6px;">
                <div>
                    <div style="border-bottom:1px solid #333; min-height:20px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">X (PRINT) Signature &amp; Name of Analyst (First, MI, Last)</div>
                </div>
                <div>
                    <div style="border-bottom:1px solid #333; min-height:20px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">(PRINT) Signature &amp; Name of Head of Laboratory (First, MI, Last)
                        <span style="margin-left:12px;">Date (mm/dd/yy): ___________</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- STEP 8 — National Reference Lab (blank)    -->
        <!-- ══════════════════════════════════════════ -->
        <div style="border:1px solid #333; margin-bottom:8px; font-size:9px;">
            <div style="background:#e8e8e8; font-weight:700; padding:3px 6px; border-bottom:1px solid #333;">
                STEP 8 TO BE COMPLETED BY THE NATIONAL REFERENCE LABORATORY (NRL)
            </div>
            <div style="padding:6px; font-style:italic; color:#444; margin-bottom:6px; font-size:8.5px;">
                In accordance with applicable Department of Health requirements, my determination/verification for the specimen (if tested) is:
            </div>
            <div style="display:flex; gap:20px; padding:0 6px; font-size:9px;">
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="font-weight:700;">RECONFIRMED FOR:</span>
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex;"></span> THC
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex;"></span> MET
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="width:11px; height:11px; border:1px solid #333; display:inline-flex;"></span> Others (specify)
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:60px;"></span>
                </label>
                <label style="display:flex; align-items:center; gap:3px;">
                    <span style="font-weight:700; color:#dc2626;">FAILED TO RECONFIRM – REASON:</span>
                    <span style="border-bottom:1px solid #555; display:inline-block; min-width:100px;"></span>
                </label>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:8px 6px 6px;">
                <div>
                    <div style="border-bottom:1px solid #333; min-height:20px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">X (PRINT) Signature &amp; Name of Analyst (First, MI, Last)</div>
                </div>
                <div>
                    <div style="border-bottom:1px solid #333; min-height:20px; margin-bottom:2px;"></div>
                    <div style="font-size:8.5px;">(PRINT) Signature &amp; Name of Head of Laboratory (First, MI, Last)
                        <span style="margin-left:12px;">Date (mm/dd/yy): ___________</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── FOOTER NOTES ───────────────────────── -->
        <div style="font-size:8.5px; color:#555; margin-bottom:6px; line-height:1.8;">
            <div>1. Form DT-002A-Copy for the Donor</div>
            <div>2. Form DT-002B-Copy for the Collection Site</div>
            <div>3. Form DT-002C-Copy for the Laboratory</div>
            <div>4. Form DT-002D-Copy for the Confirmatory Laboratory (For Positive Sample)</div>
        </div>

        <!-- ── BOTTOM BORDER ─────────────────────── -->
        <div style="border-top:2px solid #0F2044; padding-top:5px; display:flex; justify-content:space-between; font-size:8.5px; color:#555; align-items:center;">
            <div style="display:flex; align-items:center; gap:6px;">
                <img :src="CLINIC_LOGO" style="width:16px; height:16px; object-fit:contain; opacity:0.6;"/>
                <strong>SAINT PETER DIAGNOSTICS AND LABORATORY</strong>
            </div>
            <span>{{ patient.full_name?.toUpperCase() }}</span>
            <span>Custody &amp; Control Form — DT-002C</span>
        </div>

    </div>
</template>
