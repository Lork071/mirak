<script setup>
import Card from 'primevue/card';
import Divider from 'primevue/divider';

const menu = [
    {
        category: 'mirak_bar.category_non_alcoholic',
        items: [
            { name: 'mirak_bar.drink_mirak', desc: 'mirak_bar.drink_mirak_desc', price: 40 },
            { name: 'mirak_bar.drink_lemonade', desc: '', price: 30 },
            { name: 'mirak_bar.drink_kofola', desc: '', price: 30 }
        ]
    },
    {
        category: 'mirak_bar.category_hot',
        items: [
            { name: 'mirak_bar.hot_chocolate', desc: 'mirak_bar.hot_chocolate_desc', price: 30 },
            { name: 'mirak_bar.tea', desc: 'mirak_bar.tea_desc', price: 20 },
            { name: 'mirak_bar.hot_juice', desc: 'mirak_bar.hot_juice_desc', price: 40 },
            { name: 'mirak_bar.espresso', desc: '', price: 30 },
            { name: 'mirak_bar.cappucino', desc: 'mirak_bar.milk_note', price: 40 },
            { name: 'mirak_bar.latte', desc: 'mirak_bar.milk_note', price: 50 }
        ]
    },
    {
        category: 'mirak_bar.category_snacks',
        items: [
            { name: 'mirak_bar.panini', desc: 'mirak_bar.panini_desc', price: 50, allergens: [1, 7] },
            { name: 'mirak_bar.wrap', desc: 'mirak_bar.wrap_desc', price: 60, allergens: [1, 7] },
            { name: 'mirak_bar.pasta_salad', desc: 'mirak_bar.pasta_salad_desc', price: 40, allergens: [1, 3, 7] },
            { name: 'mirak_bar.nachos', desc: '', price: 40, allergens: [1] }
        ]
    },
    {
        category: 'mirak_bar.category_desserts',
        items: [
            { name: 'mirak_bar.tiramisu', desc: '', price: 60, allergens: [1, 3, 7] },
            { name: 'mirak_bar.caramel_vetrnik', desc: '', price: 60, allergens: [1, 3, 7] },
            { name: 'mirak_bar.misa_rez', desc: '', price: 30, allergens: [1, 3, 7] },
            { name: 'mirak_bar.apple_cake', desc: '', price: 30, allergens: [1, 3, 7, 8] },
            { name: 'mirak_bar.cheesecake', desc: '', price: 30, allergens: [1, 3, 7] },
            { name: 'mirak_bar.cinnamon_roll', desc: '', price: 40, allergens: [1, 3, 7] },
            { name: 'mirak_bar.vegan_pannacotta', desc: 'mirak_bar.vegan_pannacotta_desc', price: 40, vegan: true }
        ]
    }
];
</script>

<template>
    <header class="intro-page-navbar">
        <div class="intro-page-navbar-container">
            <!-- Logo a Title -->
            <div class="intro-page-navbar-left">
                <svg width="60" height="54" id="mirak_logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600">
                    <rect style="fill: var(--primary-color)" class="cls-1" x="190.92" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-115.96 167.22) rotate(-30)" />
                    <rect style="fill: var(--primary-color)" class="cls-1" x="373.29" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-91.53 258.4) rotate(-30)" />
                    <circle style="fill: var(--primary-color)" class="cls-1" cx="117.98" cy="378.56" r="62.54" />
                </svg>
                <span class="intro-page-navbar-left-title">{{ $t('name_event') }}</span>
            </div>
            <div class="intro-page-navbar-lang my-right">
                <LanguageConfigurator />
            </div>
        </div>
    </header>

    <div class="intro-page-wraper">
        <div class="max-w-3xl w-full mx-auto my-6 px-2 sm:px-4">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-center mb-8 tracking-wide" style="color: var(--primary-color-500)">
                {{ $t('mirak_bar.title') }}
            </h1>
            <div class="flex flex-col gap-6">
                <Card v-for="cat in menu" :key="cat.category" class="shadow-lg">
                    <template #title>
                        <span class="text-xl sm:text-2xl font-bold tracking-wide" style="color: var(--primary-color-500)">
                            {{ $t(cat.category) }}
                        </span>
                    </template>
                    <template #content>
                        <ul>
                            <template v-for="(item, idx) in cat.items" :key="item.name">
                                <li class="flex flex-col sm:flex-row sm:items-center justify-between py-3">
                                    <div class="flex-1 min-w-0 w-full flex flex-col">
                                        <div class="flex items-center">
                                            <span class="font-bold text-lg sm:text-xl" style="color: var(--text-color)">
                                                {{ $t(item.name) }}
                                            </span>
                                            <span v-if="item.vegan" class="ml-2 align-middle" title="Vegan">
                                                <i class="fa-solid fa-leaf" style="color: #22c55e; font-size: 1.2em"></i>
                                            </span>
                                        </div>
                                        <div v-if="item.desc" class="text-base text-gray-500" style="color: var(--text-color-secondary)">
                                            {{ $t(item.desc) }}
                                        </div>
                                        <span v-if="item.allergens" class="text-xs sm:text-sm mt-1" style="color: var(--text-color-secondary)"> {{ $t('mirak_bar.allergens') }}: {{ item.allergens.join(', ') }} </span>
                                    </div>
                                    <div class="font-semibold text-base sm:text-lg ml-0 sm:ml-4 mt-2 sm:mt-0 flex-shrink-0 price-mobile-right" v-if="item.price" style="color: var(--primary-color-400)">
                                        {{ item.price }} <i class="fa-solid fa-gem"></i>
                                    </div>
                                </li>
                                <Divider v-if="idx < cat.items.length - 1" :key="item.name + '-divider'" style="margin: 0" />
                            </template>
                        </ul>
                        <div v-if="cat.note" class="pt-3 text-xs text-center text-gray-500" style="border-top: 1px solid var(--primary-color-100)">
                            {{ $t(cat.note) }}
                        </div>
                    </template>
                </Card>
            </div>
        </div>
        <Fluid />
    </div>

    <Toast />
</template>

<style scoped>
@media (max-width: 640px) {
    .max-w-3xl {
        max-width: 100vw !important;
    }
    .shadow-lg {
        box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.06);
    }
    .text-xl {
        font-size: 1.1rem !important;
    }
    .text-2xl {
        font-size: 1.3rem !important;
    }
    .text-3xl {
        font-size: 1.5rem !important;
    }
    .text-4xl {
        font-size: 1.7rem !important;
    }
    .price-mobile-right {
        margin-left: auto !important;
        align-self: flex-end !important;
    }
}
</style>
