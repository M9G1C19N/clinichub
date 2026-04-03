<script setup>
defineProps({ labRequest: Object })
</script>

<template>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;border-top:1px solid #aaa;padding-top:5px;margin-top:6px;">
        <!-- Examined By -->
        <div style="font-size:9px;">
            <div style="color:#555;margin-bottom:4px;">Examined By:</div>
            <div style="min-height:36px;display:flex;align-items:flex-end;margin-bottom:2px;">
                <img v-if="labRequest?.examined_by_signature"
                    :src="labRequest.examined_by_signature"
                    style="max-height:34px;max-width:130px;object-fit:contain;"
                    alt="Signature"/>
            </div>
            <div style="border-top:1px solid #333;padding-top:2px;">
                <div style="font-weight:900;font-size:10px;">{{ labRequest?.examined_by_name ?? 'JAN RENZ N. PLACEROS, RMT' }}</div>
                <div>License No.: {{ labRequest?.examined_by_license ?? '0136547' }}</div>
                <div>Medical Technologist</div>
            </div>
        </div>
        <!-- Noted By -->
        <div style="font-size:9px;text-align:center;">
            <div style="color:#555;margin-bottom:4px;">Noted By:</div>
            <div style="min-height:36px;display:flex;align-items:flex-end;justify-content:center;margin-bottom:2px;">
                <img v-if="labRequest?.noted_by_signature"
                    :src="labRequest.noted_by_signature"
                    style="max-height:34px;max-width:130px;object-fit:contain;"
                    alt="Signature"/>
            </div>
            <div style="border-top:1px solid #333;padding-top:2px;">
                <div style="font-weight:900;font-size:10px;">{{ labRequest?.noted_by_name ?? 'PONCIANO S. LIMCANGCO, MD, FPSP' }}</div>
                <div style="font-weight:700;">LIC. NO. {{ labRequest?.noted_by_license ?? '0058646' }}</div>
                <div>Pathologist</div>
            </div>
        </div>
    </div>
</template>
