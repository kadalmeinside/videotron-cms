<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon, PencilSquareIcon, TrashIcon, MusicalNoteIcon, PlayIcon, PauseIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const musicList = computed(() => page.props.musicList || { data: [], links: [] });

// --- STATE BARU UNTUK MODAL KONFIRMASI ---
const showConfirmDeleteModal = ref(false);
const deleteForm = useForm({
    id: null,
    title: ''
});

const audioPlayer = ref(null);
const currentlyPlayingId = ref(null);

const playOrPause = (music) => {
    if (currentlyPlayingId.value === music.id) {
        audioPlayer.value.pause();
        currentlyPlayingId.value = null;
    } else {
        if (!audioPlayer.value) {
            audioPlayer.value = new Audio();
            audioPlayer.value.addEventListener('ended', () => {
                currentlyPlayingId.value = null;
            });
        }
        audioPlayer.value.src = `/storage/${music.file_path}`;
        audioPlayer.value.play();
        currentlyPlayingId.value = music.id;
    }
};

const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
};

// --- FUNGSI BARU UNTUK MENGELOLA MODAL ---
const openConfirmDeleteModal = (music) => {
    deleteForm.id = music.id;
    deleteForm.title = music.title;
    showConfirmDeleteModal.value = true;
};

const closeModal = () => {
    showConfirmDeleteModal.value = false;
};

const deleteMusic = () => {
    deleteForm.delete(route('admin.music.destroy', deleteForm.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Manajemen Musik" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Manajemen Musik
                </h2>
            </div>
        </template>
        
       <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <TextInput type="text" v-model="searchQuery" placeholder="Cari nama music..." class="w-full md:max-w-md" />
                             <Link :href="route('admin.music.create')">
                                <PrimaryButton>
                                    <PlusIcon class="h-5 w-5 mr-2" /> Tambah Musik Baru
                                </PrimaryButton>
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Putar</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Judul</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Penyanyi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Album</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tahun</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durasi</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!musicList.data.length">
                                        <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                            <MusicalNoteIcon class="mx-auto h-12 w-12 text-gray-400" />
                                            <h3 class="mt-2 text-sm font-semibold">Belum Ada Musik</h3>
                                            <p class="mt-1 text-sm">Silakan tambahkan musik baru.</p>
                                        </td>
                                    </tr>
                                    <tr v-for="music in musicList.data" :key="music.id">
                                        <td class="px-6 py-4">
                                            <button @click="playOrPause(music)" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                                                <PauseIcon v-if="currentlyPlayingId === music.id" class="h-5 w-5 text-blue-500" />
                                                <PlayIcon v-else class="h-5 w-5 text-gray-500" />
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ music.title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ music.singer }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ music.album || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ music.year || '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDuration(music.duration) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <Link :href="route('admin.music.edit', music.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                <PencilSquareIcon class="h-5 w-5 inline-block" />
                                            </Link>
                                            <button @click="openConfirmDeleteModal(music)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <TrashIcon class="h-5 w-5 inline-block" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="musicList.data.length > 0" class="p-4 border-t border-gray-200 dark:border-gray-700">
                            <Pagination :links="musicList.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showConfirmDeleteModal" @close="closeModal">
            <div class="p-6">
                <div class="flex items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <ExclamationTriangleIcon class="h-6 w-6 text-red-600" aria-hidden="true" />
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                            Hapus Musik
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Apakah Anda yakin ingin menghapus musik
                                <span class="font-bold">{{ deleteForm.title }}</span>?
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                    <DangerButton @click="deleteMusic" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">
                        Hapus
                    </DangerButton>
                    <SecondaryButton @click="closeModal">
                        Batal
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
