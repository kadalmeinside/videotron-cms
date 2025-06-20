<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';

// Computed Props
const page = usePage();
const playlists = computed(() => page.props.playlists || { data: [] });
const filters = computed(() => page.props.filters || { search: '' });
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// State
const searchQuery = ref(filters.value.search);
const showDeleteConfirmModal = ref(false);
const playlistToDelete = ref(null);

// Watcher untuk search
watch(searchQuery, debounce((value) => {
    router.get(route('admin.playlists.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['playlists', 'filters'],
    });
}, 300));

// Fungsi Hapus
const confirmDeletePlaylist = (playlist) => {
    playlistToDelete.value = playlist;
    showDeleteConfirmModal.value = true;
};

const deletePlaylist = () => {
    if (!playlistToDelete.value) return;
    router.delete(route('admin.playlists.destroy', playlistToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmModal.value = false;
            playlistToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Manajemen Playlist" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Playlist</h2>
        </template>
        
        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">

                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <TextInput type="text" v-model="searchQuery" placeholder="Cari nama playlist..." class="w-full md:max-w-md" />
                            <PrimaryButton v-if="can.manage_playlists" :href="route('admin.playlists.create')">
                                <PlusIcon class="h-5 w-5 mr-2" /> Buat Playlist
                            </PrimaryButton>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Playlist</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jumlah Media</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!playlists.data.length"><td colspan="3" class="px-6 py-4 text-center text-gray-500">Playlist tidak ditemukan.</td></tr>
                                    <tr v-for="item in playlists.data" :key="item.id">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ item.name }}</td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-300">{{ item.media_count }} item</td>
                                        <td class="px-6 py-4 text-right text-sm">
                                            <Link :href="route('admin.playlists.edit', item.id)" class="p-1 mr-2 text-indigo-600 hover:text-indigo-900" title="Edit">
                                                <PencilIcon class="h-5 w-5 inline" />
                                            </Link>
                                            <button @click="confirmDeletePlaylist(item)" class="p-1 text-red-600 hover:text-red-900" title="Hapus">
                                                <TrashIcon class="h-5 w-5 inline" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginasi -->
                        <div v-if="playlists.links.length > 3" class="mt-4 flex flex-wrap justify-center">
                            <template v-for="(link, key) in playlists.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm text-gray-400 border rounded" v-html="link.label" />
                                <Link v-else 
                                      :href="link.url" 
                                      class="mr-1 mb-1 px-3 py-2 text-sm border rounded hover:bg-gray-100" 
                                      :class="{ 'bg-blue-600 text-white': link.active }" 
                                      v-html="link.label" 
                                      preserve-scroll />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    
    <!-- Modal Konfirmasi Hapus -->
    <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Konfirmasi Hapus</h2>
            <p class="mt-2 text-sm text-gray-600">
                Apakah Anda yakin ingin menghapus playlist "{{ playlistToDelete?.name }}"? Aksi ini tidak dapat dibatalkan.
            </p>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="showDeleteConfirmModal = false">Batal</SecondaryButton>
                <DangerButton @click="deletePlaylist" class="ml-3">Ya, Hapus</DangerButton>
            </div>
        </div>
    </Modal>
</template>