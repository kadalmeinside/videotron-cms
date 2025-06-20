<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick } from 'vue';
import { CheckCircleIcon, XCircleIcon, ClockIcon, EyeIcon } from '@heroicons/vue/24/solid';
import Toast from '@/Components/Toast.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';

const page = usePage();
const pendingMedia = computed(() => page.props.pendingMedia || { data: [] });
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const showConfirmModal = ref(false);
const showPreviewModal = ref(false);
const mediaToAction = ref(null);
const actionType = ref('approve');

const previewPlayerEl = ref(null);
let previewPlayer = null;

const youtubeVideoId = computed(() => {
    const media = mediaToAction.value;
    if (media && media.source_type === 'youtube') {
        try {
            return new URL(media.source_value).searchParams.get('v');
        } catch (e) { return null; }
    }
    return null;
});


const openConfirmModal = (media, type) => {
    mediaToAction.value = media;
    actionType.value = type;
    showConfirmModal.value = true;
};

const openPreviewModal = (media) => {
    mediaToAction.value = media;
    showPreviewModal.value = true;
};

const closeModals = () => {
    showConfirmModal.value = false;
    showPreviewModal.value = false;
    if (previewPlayer) {
        previewPlayer.destroy();
        previewPlayer = null;
    }
    mediaToAction.value = null;
};

const executeAction = () => {
    if (!mediaToAction.value) return;
    
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModals(),
    };
    
    if (actionType.value === 'approve') {
        router.patch(route('admin.media.approve', mediaToAction.value.id), {}, options);
    } else {
        router.delete(route('admin.media.reject', mediaToAction.value.id), options);
    }
};

watch(showPreviewModal, async (isShowing) => {
    if (isShowing && mediaToAction.value) {
        await nextTick();

        if (previewPlayerEl.value) {
            previewPlayer = new Plyr(previewPlayerEl.value, {
                controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'fullscreen'],
                autoplay: true,
            });
        }
    } else {
        if (previewPlayer) {
            previewPlayer.destroy();
            previewPlayer = null;
        }
    }
});

</script>

<template>
    <Head title="Persetujuan Media" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Persetujuan Media</h2>
        </template>
        
        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                            Daftar Media Menunggu Persetujuan
                        </h3>
                        <div class="border-t border-gray-200">
                            <dl class="divide-y divide-gray-200">
                                <div v-if="!pendingMedia.data.length" class="py-10 text-center">
                                    <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak Ada Media</h3>
                                    <p class="mt-1 text-sm text-gray-500">Saat ini tidak ada media yang menunggu persetujuan.</p>
                                </div>
                                
                                <div v-for="media in pendingMedia.data" :key="media.id" class="flex flex-col md:flex-row items-center justify-between p-4 sm:p-6">
                                    <div class="flex items-center gap-4 w-full md:w-2/3">
                                        <div class="flex-shrink-0 relative group">
                                            <button @click="openPreviewModal(media)" class="block">
                                                <img v-if="media.source_type === 'local'" :src="media.preview_url" class="h-20 w-32 rounded-lg object-cover bg-gray-200" alt="preview"/>
                                                <div v-else class="h-20 w-32 rounded-lg bg-gray-800 flex items-center justify-center text-white font-bold text-2xl">
                                                    {{ media.source_type.charAt(0).toUpperCase() }}
                                                </div>
                                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity rounded-lg">
                                                    <EyeIcon class="h-8 w-8 text-white" />
                                                </div>
                                            </button>
                                        </div>
                                        <div class="flex-grow">
                                            <dt class="text-sm font-medium text-gray-900 truncate" :title="media.title">{{ media.title }}</dt>
                                            <dd class="mt-1 text-sm text-gray-500">Oleh: {{ media.client.company_name }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 mt-4 md:mt-0 md:ml-4 space-x-2">
                                        <button @click="openConfirmModal(media, 'reject')" type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                            <XCircleIcon class="h-5 w-5 mr-1.5 text-red-500"/> Tolak
                                        </button>
                                        <button @click="openConfirmModal(media, 'approve')" type="button" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                            <CheckCircleIcon class="h-5 w-5 mr-1.5"/> Setujui
                                        </button>
                                    </div>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showConfirmModal" @close="closeModals" maxWidth="md">
            <div class="p-6">
                 <h2 class="text-lg font-medium text-gray-900">
                    Konfirmasi Aksi
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                   Apakah Anda yakin ingin 
                   <span :class="actionType === 'approve' ? 'font-bold text-green-600' : 'font-bold text-red-600'">
                       {{ actionType === 'approve' ? 'MENYETUJUI' : 'MENOLAK' }}
                   </span>
                   media "{{ mediaToAction?.title }}"?
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeModals">Batal</SecondaryButton>
                    <PrimaryButton v-if="actionType === 'approve'" @click="executeAction">Ya, Setujui</PrimaryButton>
                    <DangerButton v-else @click="executeAction">Ya, Tolak & Hapus</DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showPreviewModal" @close="closeModals" maxWidth="2xl">
            <div class="p-4 bg-gray-900">
                 <h3 class="text-lg font-semibold text-white mb-4">{{ mediaToAction?.title }}</h3>
                 <div class="aspect-video bg-black rounded-lg relative main-grid-preview">
                    <img v-if="mediaToAction?.top_banner_url" :src="mediaToAction.top_banner_url" class="absolute top-0 left-0 w-full z-10" />
                    <img v-if="mediaToAction?.bottom_banner_url" :src="mediaToAction.bottom_banner_url" class="absolute bottom-0 left-0 w-full z-10" />

                    <div class="player-container">
                        <div v-if="mediaToAction?.source_type === 'local'" ref="previewPlayerEl" :data-poster="mediaToAction.preview_url"></div>
                        <div v-if="mediaToAction?.source_type === 'youtube'" ref="previewPlayerEl" class="plyr__video-embed" data-plyr-provider="youtube" :data-plyr-embed-id="youtubeVideoId"></div>
                    </div>
                    <div v-if="mediaToAction?.running_text" class="absolute bottom-10 left-0 w-full overflow-hidden z-20 bg-black bg-opacity-50">
                        <p class="marquee-content-preview text-white text-lg">{{ mediaToAction.running_text }}</p>
                    </div>
                 </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style>
.main-grid-preview {
    display: grid;
    grid-template-rows: auto 1fr auto;
}
.player-container {
    grid-row: 2;
}
.marquee-content-preview {
    animation: marquee 20s linear infinite;
    display: inline-block;
    padding-left: 100%;
}
@keyframes marquee {
    from { transform: translateX(0); }
    to { transform: translateX(-100%); }
}
</style>

