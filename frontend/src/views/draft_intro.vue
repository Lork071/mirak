<script setup>
import config from '@/config';
import { useApi } from '@/service/api';
import i18n from '@/service/i18n';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const toast = useToast();
const { api_post } = useApi();

const isMobileMenuOpen = ref(false);
const video = ref(null);
const played = ref(false);
const paused = ref(true);

const configuration = ref(null);

onMounted(() => {
    load_static_cfg();
});

function toggleMobileMenu() {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

function closeMobileMenu() {
    isMobileMenuOpen.value = false;
}

function playVideo() {
    if (video.value) {
        video.value.play().catch((error) => {
            console.warn('Video playback was blocked:', error);
        });
        played.value = true;
        paused.value = false;
    }
}

function toggleVideo() {
    if (video.value) {
        if (video.value.paused) {
            video.value.play().catch((error) => {
                console.warn('Video playback was blocked:', error);
            });
            paused.value = false;
        } else {
            video.value.pause();
            paused.value = true;
        }
    }
}

async function load_static_cfg() {
    const api = await api_post(config.endpoint_static, {
        method: 'get_intro_cfg',
        parameters: {}
    });
    if (config.debug) {
        console.log('API [get_intro_cfg]: ');
        console.log(api);
    }
    if (api.result) {
        configuration.value = api.response;

        // Set all boolean false values to true
        if (configuration.value) {
            for (const key in configuration.value) {
                if (configuration.value[key] === false) {
                    configuration.value[key] = true;
                    if (config.debug) {
                        console.log(`Changed configuration property ${key} from false to true`);
                    }
                }
            }
        }
        configuration.value.OnlyIntroPage = false;
    } else {
        toast.add({ severity: 'error', summary: i18n.global.t('error'), detail: i18n.global.t(api.response.desc), life: config.toast_lifetime });
    }
}
</script>

<template>
    <div v-if="configuration">
        <div class="intro-page-first-part">
            <div class="intro-page-first-part-line">
                <svg width="100%" height="850" viewBox="0 0 1383 850" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        id="intro-page-first-part-line-path"
                        d="M1381 -6C1203.39 77.8596 1015.64 250.235 932.605 418.844C849.564 587.452 796.611 805.438 932.605 756.06C1068.6 706.682 1159.41 526.826 1203.39 439.317C1291.5 264 1143.19 181.156 1016 429.5C910.107 636.256 915.434 847 0 847"
                        stroke="var(--primary-500)"
                        stroke-width="5"
                    />
                </svg>
            </div>
            <div class="intro-page-first-part-text">{{ $t('intro-page-mirak-text') }}</div>
            <div class="intro-page-first-part-date">31. 10 - 1. 11 2025</div>
            <div v-if="!configuration.OnlyIntroPage" class="intro-page-scroll-icon">
                <span><i class="fa-solid fa-arrow-down"></i> {{ $t('intro_page_scroll_down') }} <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>

        <header v-if="!configuration.OnlyIntroPage" class="intro-page-navbar">
            <div class="intro-page-navbar-container">
                <div class="intro-page-navbar-left">
                    <svg width="60" height="54" id="mirak_logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600">
                        <rect style="fill: var(--primary-color)" class="cls-1" x="190.92" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-115.96 167.22) rotate(-30)" />
                        <rect style="fill: var(--primary-color)" class="cls-1" x="373.29" y="146.85" width="126.25" height="306.29" rx="63.13" ry="63.13" transform="translate(-91.53 258.4) rotate(-30)" />
                        <circle style="fill: var(--primary-color)" class="cls-1" cx="117.98" cy="378.56" r="62.54" />
                    </svg>
                    <span class="intro-page-navbar-left-title">{{ $t('name_event') }}</span>
                </div>
                <nav class="intro-page-navbar-menu" :class="{ open: isMobileMenuOpen }">
                    <ul>
                        <li v-if="configuration.UseIntroVideo || configuration.UseIntroFeatures">
                            <a href="#intro-page-intro-content" @click="closeMobileMenu">{{ $t('intro_page_menu_intro') }}</a>
                        </li>
                        <li v-if="configuration.UseVenue">
                            <a href="#intro-page-place-content" @click="closeMobileMenu">{{ $t('intro_page_menu_place') }}</a>
                        </li>
                        <li v-if="configuration.isLive" class="intro-page-ahref-live">
                            <a href="#intro-page-live-content" @click="closeMobileMenu">{{ $t('intro_page_menu_stream') }}</a>
                        </li>
                        <li v-if="configuration.UseTickets">
                            <a href="#pricing" @click="closeMobileMenu">{{ $t('intro_page_menu_ticket') }}</a>
                        </li>
                        <li v-if="configuration.UseContentVolunteer">
                            <a href="#intro-page-volunteer-content" @click="closeMobileMenu">{{ $t('intro_page_menu_volunteer') }}</a>
                        </li>
                        <li v-if="configuration.UseContact">
                            <a href="#intro-page-contact-content" @click="closeMobileMenu">{{ $t('intro_page_menu_contact') }}</a>
                        </li>
                        <li v-if="configuration.UseFaq">
                            <a href="#intro-page-fqa-content" @click="closeMobileMenu">{{ $t('intro_page_menu_faq') }}</a>
                        </li>
                    </ul>
                    <Button v-if="configuration.IntroBar" @click="() => router.push('/mirak-bar')" rounded
                        ><b>{{ $t('bars_menu') }}</b></Button
                    >
                </nav>
                <div class="intro-page-navbar-lang my-right">
                    <LanguageConfigurator />
                </div>
                <button class="intro-page-navbar-hamburger" @click="toggleMobileMenu">
                    <template v-if="isMobileMenuOpen">
                        <i class="fa-solid fa-x"></i>
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-bars"></i>
                    </template>
                </button>
            </div>
            <div class="intro-page-navbar-overlay" :class="{ visible: isMobileMenuOpen }" @click="closeMobileMenu"></div>
        </header>
        <div v-if="!configuration.OnlyIntroPage" class="intro-page-wraper">
            <div v-if="configuration.UseIntroVideo" id="intro-page-intro-content" class="intro-page-video">
                <div class="intro-page-video-overlay" v-if="paused"></div>
                <div class="intro-page-video-play-button" v-if="paused" @click="toggleVideo">
                    <i class="fa-solid fa-pause"></i>
                </div>
                <div class="intro-page-video-play-button" v-if="!played" @click="playVideo">
                    <i class="fa-solid fa-play"></i>
                </div>
                <video ref="video" width="100%" @click="toggleVideo" @play="played = true">
                    <source src="/public/demo/images/intro/intro_video.mp4" type="video/mp4" />
                    {{ $t('intro_page_video_not_supported') }}
                </video>
            </div>
            <div v-if="configuration.UseIntroFeatures" id="features" class="intro-page-titles px-6 lg:px-20 mt-8 mx-0 lg:mx-20">
                <div class="grid grid-cols-12 gap-4 justify-center">
                    <div class="col-span-12 text-center mb-6">
                        <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">{{ $t('intro_page_features_title') }}</div>
                        <span class="text-muted-color text-1xl">{{ $t('intro_page_features_desc') }}</span>
                    </div>

                    <div class="col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 lg:mt-0">
                        <div
                            style="
                                height: 200px;
                                padding: 2px;
                                border-radius: 10px;
                                background: linear-gradient(90deg, var(--primary-color), var(--primary-contrast-color)), linear-gradient(180deg, var(--primary-color), var(--primary-contrast-color));
                            "
                        >
                            <div class="p-4 bg-surface-200 h-full" style="border-radius: 8px">
                                <div class="flex items-center justify-center bg-primary mb-4" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                    <i class="fa-solid fa-book-open" style="color: #000000"></i>
                                </div>
                                <h5 class="mt-6 mb-1 text-surface-900 text-xl font-semibold">{{ $t('intro_page_features_speach_title') }}</h5>
                                <span class="text-muted-color">{{ $t('intro_page_features_speach_small_desc') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 lg:mt-0">
                        <div
                            style="
                                height: 200px;
                                padding: 2px;
                                border-radius: 10px;
                                background: linear-gradient(90deg, var(--primary-color), var(--primary-contrast-color)), linear-gradient(180deg, var(--primary-color), var(--primary-contrast-color));
                            "
                        >
                            <div class="p-4 bg-surface-200 h-full" style="border-radius: 8px">
                                <div class="flex items-center justify-center bg-primary mb-4" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                    <i class="fa-solid fa-music" style="color: #000000"></i>
                                </div>
                                <h5 class="mt-6 mb-1 text-surface-900 text-xl font-semibold">{{ $t('intro_page_features_worship_title') }}</h5>
                                <span class="text-muted-color">{{ $t('intro_page_features_worship_text') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pb-8 mt-6 lg:mt-0">
                        <div
                            style="
                                height: 200px;
                                padding: 2px;
                                border-radius: 10px;
                                background: linear-gradient(90deg, var(--primary-color), var(--primary-contrast-color)), linear-gradient(180deg, var(--primary-color), var(--primary-contrast-color));
                            "
                        >
                            <div class="p-4 bg-surface-200 h-full" style="border-radius: 8px">
                                <div class="flex items-center justify-center bg-primary mb-4" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                    <i class="fa-solid fa-church" style="color: #000000"></i>
                                </div>
                                <div class="mt-6 mb-1 text-surface-900 text-xl font-semibold">{{ $t('intro_page_features_chapel_title') }}</div>
                                <span class="text-muted-color">{{ $t('intro_page_features_chapel_text') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 lg:mt-0">
                        <div
                            style="
                                height: 200px;
                                padding: 2px;
                                border-radius: 10px;
                                background: linear-gradient(90deg, var(--primary-color), var(--primary-contrast-color)), linear-gradient(180deg, var(--primary-color), var(--primary-contrast-color));
                            "
                        >
                            <div class="p-4 bg-surface-200 h-full relative" style="border-radius: 8px">
                                <Button v-if="configuration.IntroBar" class="absolute top-4 right-4 z-10" @click="() => router.push('/mirak-bar')" outlined>{{ $t('bars_menu') }}</Button>
                                <div class="flex items-center justify-center bg-primary mb-4" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                    <i class="fa-solid fa-martini-glass-citrus" style="color: #000000"></i>
                                </div>
                                <div class="mt-6 mb-1 text-surface-900 text-xl font-semibold">{{ $t('intro_page_features_bar_title') }}</div>
                                <span class="text-muted-color">{{ $t('intro_page_features_bar_text') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pr-8 lg:pb-8 mt-6 lg:mt-0">
                        <div
                            style="
                                height: 200px;
                                padding: 2px;
                                border-radius: 10px;
                                background: linear-gradient(90deg, var(--primary-color), var(--primary-contrast-color)), linear-gradient(180deg, var(--primary-color), var(--primary-contrast-color));
                            "
                        >
                            <div class="p-4 bg-surface-200 h-full" style="border-radius: 8px">
                                <div class="flex items-center justify-center bg-primary mb-4" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                    <i class="fa-solid fa-person-chalkboard" style="color: #000000"></i>
                                </div>
                                <div class="mt-6 mb-1 text-surface-900 text-xl font-semibold">{{ $t('intro_page_features_workshops_title') }}</div>
                                <span class="text-muted-color">{{ $t('intro_page_features_workshops_text') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-12 lg:col-span-4 p-0 lg:pb-8 mt-6 lg:mt-0">
                        <div
                            style="
                                height: 200px;
                                padding: 2px;
                                border-radius: 10px;
                                background: linear-gradient(90deg, var(--primary-color), var(--primary-contrast-color)), linear-gradient(180deg, var(--primary-color), var(--primary-contrast-color));
                            "
                        >
                            <div class="p-4 bg-surface-200 h-full" style="border-radius: 8px">
                                <div class="flex items-center justify-center bg-primary mb-4" style="width: 3.5rem; height: 3.5rem; border-radius: 10px">
                                    <i class="fa-solid fa-shirt" style="color: #000000"></i>
                                </div>
                                <div class="mt-6 mb-1 text-surface-900 text-xl font-semibold">{{ $t('intro_page_features_merche_title') }}</div>
                                <span class="text-muted-color">{{ $t('intro_page_features_merche_text') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="configuration.UseIntroVision" class="col-span-12 mt-20 p-2 md:p-20" style="border-radius: 20px; background: radial-gradient(77.36% 256.97% at 77.36% 57.52%, var(--primary-700) 0%, var(--primary-500) 100%)">
                <div class="flex flex-col justify-center items-center text-center px-2 py-2 md:py-0">
                    <div class="text-gray-900 mb-2 text-3xl font-semibold">{{ $t('intro_page_info_title') }}</div>
                    <span class="text-gray-800 text-2xl">{{ $t('intro_page_info_desc') }}</span>
                    <p class="text-gray-900 sm:line-height-2 md:line-height-4 text-2xl mt-6" style="max-width: 800px">
                        {{ $t('intro_page_info_text') }}
                    </p>
                    <br />
                    <svg
                        width="60"
                        height="54"
                        viewBox="0 0 14.2875 14.2875"
                        version="1.1"
                        id="svg1"
                        xml:space="preserve"
                        sodipodi:docname="Logo.svg"
                        inkscape:version="1.4 (86a8ad7, 2024-10-11)"
                        inkscape:export-filename="Logo1.svg"
                        inkscape:export-xdpi="96"
                        inkscape:export-ydpi="96"
                        xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
                        xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:svg="http://www.w3.org/2000/svg"
                    >
                        <defs id="defs1" />
                        <g inkscape:label="Layer 1" inkscape:groupmode="layer" id="layer1" transform="translate(76.27383,-67.512647)">
                            <path
                                style="fill: black"
                                d="m -74.039133,78.065249 c -0.280795,-0.02117 -0.559603,-0.113775 -0.792729,-0.263292 -0.251874,-0.161542 -0.448942,-0.377923 -0.580737,-0.637649 -0.07963,-0.156925 -0.125617,-0.300021 -0.154188,-0.479779 -0.01632,-0.102667 -0.0196,-0.329177 -0.0062,-0.43057 0.08901,-0.675265 0.585965,-1.216545 1.264714,-1.37753 0.144352,-0.03424 0.230835,-0.04336 0.408507,-0.04307 0.166432,2.65e-4 0.244117,0.0078 0.376594,0.03644 0.51476,0.11135 0.957852,0.467588 1.16696,0.938216 0.08197,0.184486 0.124078,0.368656 0.132898,0.58127 0.01573,0.379111 -0.103606,0.744514 -0.341355,1.045252 -0.06307,0.07979 -0.197321,0.212732 -0.278724,0.276025 -0.2385,0.185441 -0.53346,0.307661 -0.832971,0.34515 -0.09476,0.01186 -0.270627,0.01649 -0.36274,0.0095 z m 4.816373,2.6e-4 c -0.29142,-0.02207 -0.568199,-0.113786 -0.808985,-0.268062 -0.193566,-0.124021 -0.379525,-0.309333 -0.499555,-0.497817 -0.06953,-0.10918 -2.44337,-4.076053 -2.471655,-4.130325 -0.123835,-0.237611 -0.183956,-0.511429 -0.173314,-0.789346 0.01336,-0.348852 0.126336,-0.657509 0.343119,-0.9374 0.06468,-0.08351 0.234462,-0.245828 0.325348,-0.311046 0.249318,-0.178902 0.512685,-0.280543 0.826072,-0.318806 0.09294,-0.01135 0.326719,-0.0097 0.424464,0.003 0.262563,0.03415 0.507135,0.122032 0.72127,0.259172 0.180717,0.115738 0.365526,0.295618 0.48017,0.467366 0.04621,0.06923 2.457768,4.097134 2.489494,4.158081 0.109575,0.210494 0.165871,0.427307 0.174885,0.673535 0.01478,0.403616 -0.114983,0.784092 -0.372351,1.091804 -0.0656,0.07844 -0.21085,0.214429 -0.291308,0.272749 -0.201938,0.146374 -0.435995,0.249556 -0.67243,0.296437 -0.146188,0.02899 -0.35021,0.0416 -0.495224,0.03062 z m 4.890962,0.0023 c -0.256773,-0.01709 -0.479884,-0.0763 -0.695738,-0.184627 -0.240613,-0.120755 -0.455526,-0.305082 -0.612309,-0.525167 -0.03235,-0.04541 -2.41982,-4.023802 -2.493459,-4.155005 -0.08505,-0.151532 -0.148324,-0.340642 -0.179456,-0.536336 -0.01537,-0.09659 -0.01525,-0.353545 2.09e-4,-0.453976 0.05812,-0.377599 0.231798,-0.70453 0.509598,-0.95925 0.130271,-0.119448 0.260029,-0.206336 0.423311,-0.283453 0.210858,-0.09959 0.401142,-0.148095 0.641382,-0.163505 0.599661,-0.03846 1.173288,0.231278 1.512306,0.711144 0.06445,0.09123 2.472107,4.112935 2.52244,4.21344 0.07292,0.145614 0.121007,0.298478 0.146078,0.464401 0.09486,0.627796 -0.188769,1.247354 -0.730754,1.596266 -0.227895,0.146711 -0.457291,0.230232 -0.725029,0.263975 -0.07536,0.0095 -0.255072,0.01632 -0.318579,0.01209 z"
                                id="path1"
                            />
                        </g>
                    </svg>
                </div>
            </div>

            <div v-if="configuration.UseVenue" id="intro-page-place-content" class="intro-page-titles">
                <div class="col-span-12 text-center mb-6 mt-6">
                    <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">{{ $t('intro_page_place_title') }}</div>
                    <span class="text-muted-color text-1xl">{{ $t('intro_page_place_desc') }}</span>
                </div>
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/2">
                        <div class="card flex flex-col gap-4">
                            <div class="font-semibold text-xl">{{ $t('intro_page_place_address_name') }}</div>
                            <br />
                            <p>{{ $t('intro_page_place_address_address') }}</p>
                            <p>{{ $t('intro_page_place_address_gps') }}</p>
                        </div>
                    </div>
                    <div class="md:w-1/2">
                        <div class="card flex flex-col gap-4">
                            <div class="font-semibold text-xl">{{ $t('intro_page_place_address_map') }}</div>
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1286.8111412387116!2d18.1622012!3d49.830764!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4713e152395e731d%3A0xd48e541438e4f255!2sAula%20V%C5%A0B-TUO!5e0!3m2!1scs!2scz!4v1736871734199!5m2!1scs!2scz"
                                width="100%"
                                height="350px"
                                style="border: 0"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="configuration.isLive" id="intro-page-live-content" class="intro-page-titles">
                <div class="col-span-12 text-center mb-6">
                    <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl"><i class="fa-solid fa-video"></i> {{ $t('intro_page_live_title') }}</div>
                    <span class="text-muted-color text-1xl">{{ $t('intro_page_live_description') }}</span>
                </div>
                <div id="intro-page-live-content-video">
                    <iframe
                        width="100%"
                        height="auto"
                        :src="configuration.LiveLink"
                        title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>

            <div v-if="configuration.UseTickets" id="pricing" class="intro-page-titles px-6 my-2 md:my-6">
                <div class="text-center mb-6">
                    <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">{{ $t('intro_page_pricing_title') }}</div>
                    <span class="text-muted-color text-1xl">{{ $t('intro_page_pricing_description') }}</span>
                </div>

                <div class="grid grid-cols-12 gap-4 justify-between md:mt-0">
                    <div class="col-span-12 lg:col-span-4 p-0 md:p-4 mt-6 md:mt-0">
                        <div class="p-4 flex flex-col border-surface-200 dark:border-surface-600 pricing-card cursor-pointer border-2 hover:border-primary duration-300 transition-all" style="border-radius: 10px">
                            <div class="text-surface-900 dark:text-surface-0 text-center my-8 text-3xl">{{ $t('intro_page_pricing_only_allmeal_title') }}</div>
                            <img src="/demo/images/landing/enterprise.svg" class="w-10/12 mx-auto" alt="enterprise" />
                            <div class="my-8 flex flex-col items-center gap-4">
                                <div class="flex items-center">
                                    <span class="text-5xl font-bold mr-2 text-surface-900 dark:text-surface-0">{{ $t('intro_page_pricing_only_allmeal_price') }}</span>
                                </div>
                                <Button class="p-button-rounded border-0 ml-4 font-bold leading-tight bg-blue-500 text-white" @click="() => router.push({ path: '/get-ticket', query: { only_friday: 'false', meal: 'true' } })">{{
                                    $t('intro_page_pricing_button_text')
                                }}</Button>
                            </div>
                            <Divider class="w-full bg-surface-200"></Divider>
                            <ul class="my-8 list-none p-0 flex text-surface-900 dark:text-surface-0 flex-col px-8">
                                <li class="py-2">
                                    <i class="fa-solid fa-info text-xl text-cyan-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_allmeal_info') }}</span>
                                </li>
                                <li class="py-2">
                                    <i class="fa-solid fa-check text-xl text-cyan-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_allmeal_accommodation') }}</span>
                                </li>
                                <li class="py-2">
                                    <i class="fa-solid fa-check text-xl text-cyan-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_allmeal_meal') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-4 p-0 md:p-4 mt-6 md:mt-0">
                        <div class="p-4 flex flex-col border-surface-200 dark:border-surface-600 pricing-card cursor-pointer border-2 hover:border-primary duration-300 transition-all" style="border-radius: 10px">
                            <div class="text-surface-900 dark:text-surface-0 text-center my-8 text-3xl">{{ $t('intro_page_pricing_only_all_title') }}</div>
                            <img src="/demo/images/landing/startup.svg" class="w-10/12 mx-auto" alt="startup" />
                            <div class="my-8 flex flex-col items-center gap-4">
                                <div class="flex items-center">
                                    <span class="text-5xl font-bold mr-2 text-surface-900 dark:text-surface-0">{{ $t('intro_page_pricing_only_all_price') }}</span>
                                </div>
                                <Button class="p-button-rounded border-0 ml-4 font-bold leading-tight bg-blue-500 text-white" @click="() => router.push('/get-ticket?only_friday=false&meal=false')">{{ $t('intro_page_pricing_button_text') }}</Button>
                            </div>
                            <Divider class="w-full bg-surface-200"></Divider>
                            <ul class="my-8 list-none p-0 flex text-surface-900 dark:text-surface-0 flex-col px-8">
                                <li class="py-2">
                                    <i class="fa-solid fa-info text-xl text-cyan-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_all_info') }}</span>
                                </li>
                                <li class="py-2">
                                    <i class="fa-solid fa-check text-xl text-cyan-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_all_accommodation') }}</span>
                                </li>
                                <li class="py-2">
                                    <i class="fa-solid fa-x text-xl text-red-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_all_meal') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-4 p-0 md:p-4">
                        <div class="p-4 flex flex-col border-surface-200 dark:border-surface-600 pricing-card cursor-pointer border-2 hover:border-primary duration-300 transition-all" style="border-radius: 10px">
                            <div class="text-surface-900 dark:text-surface-0 text-center my-8 text-3xl">{{ $t('intro_page_pricing_only_friday_title') }}</div>
                            <img src="/demo/images/landing/free.svg" class="w-10/12 mx-auto" alt="free" />
                            <div class="my-8 flex flex-col items-center gap-4">
                                <div class="flex items-center">
                                    <span class="text-5xl font-bold mr-2 text-surface-900 dark:text-surface-0">{{ $t('intro_page_pricing_only_friday_price') }}</span>
                                </div>
                                <Button class="p-button-rounded border-0 ml-4 font-bold leading-tight bg-blue-500 text-white" @click="() => router.push('/get-ticket?only_friday=true&meal=false')">{{ $t('intro_page_pricing_button_text') }}</Button>
                            </div>
                            <Divider class="w-full bg-surface-200"></Divider>
                            <ul class="my-8 list-none p-0 flex text-surface-900 dark:text-surface-0 flex-col px-8">
                                <li class="py-2">
                                    <i class="fa-solid fa-info text-xl text-cyan-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_friday_info') }}</span>
                                </li>
                                <li class="py-2">
                                    <i class="fa-solid fa-x text-xl text-red-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_friday_accommodation') }}</span>
                                </li>
                                <li class="py-2">
                                    <i class="fa-solid fa-x text-xl text-red-500 mr-2"></i>
                                    <span class="text-xl leading-normal">{{ $t('intro_page_pricing_only_friday_meal') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="configuration.UseContentVolunteer" id="intro-page-volunteer-content" class="intro-page-titles mt-10 intro-page-volunteer-content-all">
                <div class="col-span-12 text-center mb-6">
                    <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">{{ $t('intro_page_volunteer_title') }}</div>
                    <span class="text-muted-color text-1xl">{{ $t('intro_page_volunteer_description') }}</span>
                </div>
                <div class="flex my-center">
                    <Button class="p-button-rounded border-0 ml-4 font-bold mt-8 mb-16" @click="() => router.push('/mirak-crew')"
                        ><i class="fa-brands fa-wpforms fa-2xl"></i>
                        <div class="text-2xl">{{ $t('intro_page_volunteer_button') }}</div></Button
                    >
                </div>
            </div>

            <div v-if="configuration.UseContact" id="intro-page-contact-content" class="intro-page-titles mt-10">
                <div class="col-span-12 text-center mb-6">
                    <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">{{ $t('intro_page_soc_network_title') }}</div>
                    <span class="text-muted-color text-1xl">{{ $t('intro_page_soc_network_description') }}</span>
                </div>
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="my-center md:w-1/3">
                        <div class="intro-page-soc-page-circle">
                            <a href="https://www.instagram.com/mirakcz/" target="_blank"><i class="fa-brands fa-instagram fa-4x"></i></a>
                        </div>
                    </div>
                    <div class="my-center md:w-1/3">
                        <div class="intro-page-soc-page-circle">
                            <a href="https://www.facebook.com/mirakcz" target="_blank"><i class="fa-brands fa-facebook fa-4x"></i></a>
                        </div>
                    </div>
                    <div class="my-center md:w-1/3">
                        <div class="intro-page-soc-page-circle">
                            <a href="https://www.youtube.com/@mirakCZ" target="_blank"><i class="fa-brands fa-youtube fa-4x"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="configuration.UseFaq" id="intro-page-fqa-content" class="intro-page-titles col-span-12 text-center mt-20 mb-6">
                <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">{{ $t('intro_page_soc_faq_title') }}</div>
                <span class="text-muted-color text-1xl">{{ $t('intro_page_soc_faq_desc') }}</span>
            </div>
            <Accordion v-if="configuration.UseFaq">
                <AccordionPanel value="0">
                    <AccordionHeader>{{ $t('intro_page_soc_faq_1_title') }}</AccordionHeader>
                    <AccordionContent>
                        <p class="m-0">
                            {{ $t('intro_page_soc_faq_1_desc') }}
                        </p>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="1">
                    <AccordionHeader>{{ $t('intro_page_soc_faq_2_title') }}</AccordionHeader>
                    <AccordionContent>
                        <p class="m-0">
                            {{ $t('intro_page_soc_faq_2_desc') }}
                        </p>
                    </AccordionContent>
                </AccordionPanel>
                <AccordionPanel value="2">
                    <AccordionHeader>{{ $t('intro_page_soc_faq_3_title') }}</AccordionHeader>
                    <AccordionContent>
                        <p class="m-0">
                            {{ $t('intro_page_soc_faq_3_desc') }}
                        </p>
                    </AccordionContent>
                </AccordionPanel>
            </Accordion>
            <div class="layout-footer">
                &copy; Mírák {{ new Date().getFullYear() }} Supported by
                <a href="https://primevue.org" target="_blank" rel="noopener noreferrer" class="text-primary font-bold hover:underline">PrimeVue</a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.relative {
    position: relative;
}
.absolute {
    position: absolute;
}
.top-4 {
    top: 1rem;
}
.right-4 {
    right: 1rem;
}
.z-10 {
    z-index: 10;
}
</style>
