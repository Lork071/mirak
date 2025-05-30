<script>
import { getLanguage, setLanguage } from '@/service/i18n.js';
import { ref } from 'vue';

export default {
    name: 'LanguageConfigurator',
    setup() {
        const multiselectValues = ref([
            { name: 'Czech', code: 'CZ', value: 'cs' },
            { name: 'Slovakia', code: 'SK', value: 'sk' },
            { name: 'United Kingdom', code: 'GB', value: 'en' }
        ]);
        const multiselectValue = ref(multiselectValues.value.find((item) => item.value === getLanguage()));

        return {
            multiselectValue,
            multiselectValues
        };
    },
    methods: {
        LanguageSelector() {
            setLanguage(this.multiselectValue.value);
        }
    }
};
</script>

<template>
    <Select v-model="multiselectValue" :options="multiselectValues" optionLabel="name" @change="LanguageSelector">
        <template #dropdownicon>
            <i class="fa-solid fa-language"></i>
        </template>
        <template #value="slotProps">
            <div v-if="slotProps.value" class="flex items-center">
                <img :alt="slotProps.value.label" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`mr-2 flag flag-${slotProps.value.code.toLowerCase()}`" style="width: 18px" />
            </div>
            <span v-else>
                {{ slotProps.placeholder }}
            </span>
        </template>
        <template #option="slotProps">
            <div class="flex items-center">
                <img :alt="slotProps.option.label" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`mr-2 flag flag-${slotProps.option.code.toLowerCase()}`" style="width: 18px" />
            </div>
        </template>
    </Select>
</template>
