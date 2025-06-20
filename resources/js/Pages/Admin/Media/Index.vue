<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Toast from '@/Components/Toast.vue';
import ImagePreview from '@/Components/ImagePreview.vue'; // <-- IMPORT KOMPONEN BARU
import { PlusIcon, PencilIcon, TrashIcon, VideoCameraIcon, ArrowUpOnSquareIcon } from '@heroicons/vue/24/outline';
import { GlobeAltIcon } from '@heroicons/vue/24/solid';


// Props & State
const page = usePage();
const mediaList = computed(() => page.props.mediaList || { data: [] });
const filters = computed(() => page.props.filters || { search: '' });
const clients = computed(() => page.props.clients || []);
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const showMediaModal = ref(false);
const isEditMode = ref(false); // Nanti digunakan untuk edit
const showDeleteConfirmModal = ref(false);
const mediaToDelete = ref(null);
const searchQuery = ref(filters.value.search);

// Form dengan field virtual `..._file` yang sudah diperbaiki
const form = useForm({
    title: '',
    client_id: '',
    source_type: 'local',
    source_value: '',
    source_file: null, // Untuk upload file utama
    duration: 30,
    top_banner_file: null, // Untuk upload banner atas
    bottom_banner_file: null, // Untuk upload banner bawah
    running_text: '',
    theme_type: 'solid',
    theme_color_1: '#000000',
    theme_color_2: '#FFFFFF',
});

const existingTopBannerUrl = ref(null);
const existingBottomBannerUrl = ref(null);
// Watcher untuk search
watch(searchQuery, debounce((value) => {
    router.get(route('admin.media.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['mediaList', 'filters'],
    });
}, 300));

const handleVideoFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.source_file = file;
        const videoElement = document.createElement('video');
        videoElement.preload = 'metadata';
        videoElement.onloadedmetadata = () => {
            window.URL.revokeObjectURL(videoElement.src);
            // Bulatkan durasi dan set ke form
            form.duration = Math.round(videoElement.duration);
        };
        videoElement.src = URL.createObjectURL(file);
    }
};

// Fungsi untuk menghapus banner yang dipilih
const clearBanner = (type) => {
    if (type === 'top') {
        form.top_banner_file = null;
        existingTopBannerUrl.value = null;
    } else if (type === 'bottom') {
        form.bottom_banner_file = null;
        existingBottomBannerUrl.value = null;
    }
};

// Fungsi Modal
const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    existingTopBannerUrl.value = null;
    existingBottomBannerUrl.value = null;
    showMediaModal.value = true;
};

// --- DITAMBAHKAN: FUNGSI UNTUK MEMBUKA MODAL EDIT ---
const openEditModal = (media) => {
    const data = media.full_data_for_edit;
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id = data.id;
    form.title = data.title;
    form.client_id = data.client_id;
    form.source_type = data.source_type;
    form.source_value = data.source_value;
    form.duration = data.duration;
    form.running_text = data.running_text;
    existingTopBannerUrl.value = data.top_banner_url;
    existingBottomBannerUrl.value = data.bottom_banner_url;
    showMediaModal.value = true;
};

const closeModal = () => {
    showMediaModal.value = false;
};

const submitMediaForm = () => {
    // Untuk Create, kita tetap gunakan form helper standar
    if (!isEditMode.value) {
        form.post(route('admin.media.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
        return;
    }

    // Untuk Update (yang mungkin berisi file), kita gunakan router.post
    // dengan method spoofing secara eksplisit. Ini cara paling andal.
    router.post(route('admin.media.update', form.id), {
        _method: 'PUT', // Memberitahu Laravel ini adalah request PUT
        
        // Kirim semua data dari form
        title: form.title,
        client_id: form.client_id,
        source_type: form.source_type,
        source_value: form.source_value,
        duration: form.duration,
        running_text: form.running_text,

        // Kirim file hanya jika ada (Inertia akan handle ini)
        source_file: form.source_file,
        top_banner_file: form.top_banner_file,
        bottom_banner_file: form.bottom_banner_file,
    }, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (errors) => {
            // Set error secara manual agar tampil di form
            form.setError(errors);
        }
    });
};

const confirmDeleteMedia = (media) => {
    mediaToDelete.value = media;
    showDeleteConfirmModal.value = true;
};

const deleteMedia = () => {
    if(!mediaToDelete.value) return;
    router.delete(route('admin.media.destroy', mediaToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteConfirmModal.value = false; mediaToDelete.value = null; }
    })
}
</script>

<template>
    <Head title="Manajemen Media" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Klien</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <TextInput type="text" v-model="searchQuery" placeholder="Cari judul media..." class="w-full md:max-w-md" />
                            <PrimaryButton @click="openCreateModal" v-if="can.manage_media">
                                <PlusIcon class="h-5 w-5 mr-2" /> Tambah Media
                            </PrimaryButton>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preview</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Judul</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Klien</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Top Banner</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bottom Banner</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!mediaList.data.length"><td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada media.</td></tr>
                                    <tr v-for="media in mediaList.data" :key="media.id">
                                        <td class="px-4 py-2">
                                            <img v-if="media.preview_url" :src="media.preview_url" class="w-24 h-14 object-cover rounded bg-gray-200" alt="preview"/>
                                            <span v-else class="text-sm text-gray-400">{{ media.source_type }}</span>
                                        </td>
                                        <td class="px-4 py-2 font-medium text-sm text-gray-900 dark:text-white">{{ media.title }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-300">{{ media.client.company_name }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <a v-if="media.top_banner_url" :href="media.top_banner_url" target="_blank" class="text-blue-600 hover:underline">Link</a>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <a v-if="media.bottom_banner_url" :href="media.bottom_banner_url" target="_blank" class="text-blue-600 hover:underline">Link</a>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', media.is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800']">
                                                {{ media.is_approved ? 'Approved' : 'Pending' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-right text-sm">
                                            <button @click="openEditModal(media)" class="p-1 mr-2 text-indigo-600 hover:text-indigo-900" title="Edit Media"><PencilIcon class="h-5 w-5" /></button>
                                            <button @click="confirmDeleteMedia(media)" class="p-1 text-red-600 hover:text-red-900" title="Hapus Media"><TrashIcon class="h-5 w-5" /></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <Modal :show="showMediaModal" @close="closeModal" maxWidth="2xl">
            <form @submit.prevent="submitMediaForm" class="p-6" novalidate>
                <h2 class="text-lg font-bold border-b pb-3 mb-6">{{ isEditMode ? 'Edit Media' : 'Tambah Media Baru' }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">

                    <!-- KOLOM KIRI -->
                    <div class="space-y-6">
                        <div>
                            <InputLabel for="title" value="Judul Media" required />
                            <TextInput id="title" v-model="form.title" class="w-full mt-1" placeholder="Cth: Iklan Lebaran" />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="client_id" value="Klien Pemilik" required />
                            <select v-model="form.client_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                <option disabled value="">Pilih Klien</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.company_name }}</option>
                            </select>
                            <InputError :message="form.errors.client_id" class="mt-2" />
                        </div>
                        <div>
                             <InputLabel value="Sumber Media" required/>
                             <div class="mt-2 grid grid-cols-3 gap-3">
                                <button type="button" @click="form.source_type = 'local'" :class="form.source_type === 'local' ? 'bg-blue-600 text-white ring-2 ring-blue-400' : 'bg-gray-200 text-gray-600 hover:bg-gray-300 opacity-60'" class="p-3 rounded-lg text-center transition">
                                    <ArrowUpOnSquareIcon class="h-6 w-6 mx-auto"/>
                                    <span class="text-xs mt-1 block">Lokal</span>
                                </button>
                                <button type="button" @click="form.source_type = 'youtube'" :class="form.source_type === 'youtube' ? 'bg-red-600 text-white ring-2 ring-red-400' : 'bg-gray-200 text-gray-600 hover:bg-gray-300 opacity-60'" class="p-3 rounded-lg text-center transition">
                                    <GlobeAltIcon class="h-6 w-6 mx-auto"/>
                                    <span class="text-xs mt-1 block">YouTube</span>
                                </button>
                                 <button type="button" @click="form.source_type = 'vimeo'" :class="form.source_type === 'vimeo' ? 'bg-sky-500 text-white ring-2 ring-sky-300' : 'bg-gray-200 text-gray-600 hover:bg-gray-300 opacity-60'" class="p-3 rounded-lg text-center transition">
                                    <VideoCameraIcon class="h-6 w-6 mx-auto"/>
                                    <span class="text-xs mt-1 block">Vimeo</span>
                                </button>
                             </div>
                        </div>

                         <div v-if="form.source_type === 'local'">
                            <InputLabel for="source_file" :value="isEditMode ? 'Ganti File (Opsional)' : 'Pilih File (Video/Gambar)'" :required="!isEditMode"/>
                            <input type="file" @change="handleVideoFileChange" accept="video/mp4,video/quicktime,image/*" class="w-full mt-1 block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"/>
                            <InputError :message="form.errors.source_file" class="mt-2"/>
                        </div>
                        <div v-if="form.source_type === 'youtube' || form.source_type === 'vimeo'">
                            <InputLabel for="source_value" value="URL Video" required/>
                            <TextInput id="source_value" v-model="form.source_value" class="w-full mt-1" placeholder="https://..."/>
                            <InputError :message="form.errors.source_value" class="mt-2"/>
                        </div>

                        <div>
                            <InputLabel for="duration" value="Durasi Tayang (detik)" required/>
                            <TextInput id="duration" v-model.number="form.duration" type="number" class="w-full mt-1" placeholder="Detik" :readonly="form.source_type === 'local' && form.source_file"/>
                            <p v-if="form.source_type !== 'local'" class="text-xs text-gray-500 mt-1">Harap isi durasi manual untuk YouTube/Vimeo.</p>
                            <InputError :message="form.errors.duration" class="mt-2"/>
                        </div>
                    </div>

                    <!-- KOLOM KANAN -->
                    <div class="space-y-6">
                        <div>
                            <InputLabel for="top_banner" value="Banner Atas (Opsional)" />
                            <ImagePreview v-if="form.top_banner_file || existingTopBannerUrl" :file="form.top_banner_file" :existing-url="existingTopBannerUrl" @clear="clearBanner('top')" class="mt-2"/>
                            <input v-else type="file" @input="form.top_banner_file = $event.target.files[0]" accept="image/*" class="w-full mt-1 block text-sm ..."/>
                            <InputError :message="form.errors.top_banner_file" class="mt-2"/>
                        </div>
                        <div>
                            <InputLabel for="bottom_banner" value="Banner Bawah (Opsional)" />
                            <ImagePreview v-if="form.bottom_banner_file || existingBottomBannerUrl" :file="form.bottom_banner_file" :existing-url="existingBottomBannerUrl" @clear="clearBanner('bottom')" class="mt-2"/>
                            <input v-else type="file" @input="form.bottom_banner_file = $event.target.files[0]" accept="image/*" class="w-full mt-1 block text-sm ..."/>
                            <InputError :message="form.errors.bottom_banner_file" class="mt-2"/>
                        </div>
                        <div>
                            <InputLabel for="running_text" value="Running Text (Opsional)"/>
                            <textarea id="running_text" v-model="form.running_text" class="w-full mt-1 border-gray-300 rounded-md" rows="4"></textarea>
                            <InputError :message="form.errors.running_text" class="mt-2"/>
                        </div>
                    </div>

                </div>

                <div class="mt-6 flex justify-end pt-6 border-t dark:border-gray-700">
                    <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mr-4 w-48 self-center" />
                    <SecondaryButton @click="closeModal" type="button">Batal</SecondaryButton>
                    <PrimaryButton class="ml-3" :disabled="form.processing" :class="{'opacity-25': form.processing}">{{ isEditMode ? 'Update Media' : 'Simpan Media' }}</PrimaryButton>
                </div>
            </form>
        </Modal>
        
        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal=false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium">Konfirmasi Hapus</h2>
                <p class="mt-2 text-sm text-gray-600">Yakin ingin menghapus media "{{ mediaToDelete?.title }}" beserta semua filenya?</p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDeleteConfirmModal=false">Batal</SecondaryButton>
                    <DangerButton @click="deleteMedia" class="ml-3">Ya, Hapus</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>