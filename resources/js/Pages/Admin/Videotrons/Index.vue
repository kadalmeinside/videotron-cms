<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';


// Computed Props
const page = usePage();
const videotronList = computed(() => page.props.videotronList || { data: [] });
const filters = computed(() => page.props.filters || { search: '' });
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const props = defineProps({
    allPlaylists: Array, 
    allSchedules: Array, 
});

// State
const showVideotronModal = ref(false);
const isEditMode = ref(false);
const showDeleteConfirmModal = ref(false);
const videotronToDelete = ref(null);
const searchQuery = ref(filters.value.search);

const form = useForm({
    id: null,
    name: '',
    location_name: '',
    latitude: '',
    longitude: '',
    resolution: '',
    status: 'active',
    device_id: '',
    password: '',
    playlist_id: '',
    schedule_id: '',
});

// Watcher untuk search
watch(searchQuery, debounce((value) => {
    router.get(route('admin.videotrons.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['videotronList', 'filters'],
    });
}, 300));

const clearSearch = () => {
    searchQuery.value = '';
};

// Modal Functions
const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    showVideotronModal.value = true;
};

const openEditModal = (videotron) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id = videotron.id;
    form.name = videotron.name;
    form.location_name = videotron.location_name;
    form.latitude = videotron.latitude;
    form.longitude = videotron.longitude;
    form.resolution = videotron.resolution;
    form.status = videotron.status;
    form.device_id = videotron.device_id;
    form.playlist_id = videotron.playlist_id || '';
    form.schedule_id = videotron.schedule_id || '';
    showVideotronModal.value = true;
};

const closeModal = () => {
    showVideotronModal.value = false;
};

const submitVideotronForm = () => {
    const routeName = isEditMode.value ? 'admin.videotrons.update' : 'admin.videotrons.store';
    const routeParams = isEditMode.value ? form.id : undefined;
    
    form.submit(isEditMode.value ? 'put' : 'post', route(routeName, routeParams), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const confirmDeleteVideotron = (videotron) => {
    videotronToDelete.value = videotron;
    showDeleteConfirmModal.value = true;
};

const deleteVideotron = () => {
    if (!videotronToDelete.value) return;
    router.delete(route('admin.videotrons.destroy', videotronToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteConfirmModal.value = false; },
    });
};

</script>

<template>
    <Head title="Manajemen Videotron" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Videotron</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="relative w-full md:max-w-md">
                                <TextInput 
                                    type="text" 
                                    v-model="searchQuery" 
                                    placeholder="Cari nama, lokasi, atau device ID..." 
                                    class="w-full pr-10" 
                                />
                                <button 
                                    v-if="searchQuery" 
                                    @click="clearSearch" 
                                    type="button" 
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                    aria-label="Clear search"
                                >
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>
                            <PrimaryButton @click="openCreateModal" v-if="can?.manage_videotrons">
                                <PlusIcon class="h-5 w-5 mr-2" /> Tambah Videotron
                            </PrimaryButton>
                        </div>

                        <div v-if="!videotronList.data.length" class="text-center py-12 text-gray-500">
                            Tidak ada data untuk ditampilkan.
                        </div>
                        <div v-else>
                            <div class="hidden md:block overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Device ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Lokasi</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="item in videotronList.data" :key="item.id">
                                            <td class="px-6 py-4 font-medium">{{ item.name }}</td>
                                            <td class="px-6 py-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ item.device_id || '-' }}</td>
                                            <td class="px-6 py-4">{{ item.location_name }}</td>
                                            <td class="px-6 py-4"><span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">{{ item.status }}</span></td>
                                            <td class="px-6 py-4 text-right">
                                                <button @click="openEditModal(item)" class="p-1 mr-2 text-indigo-600 hover:text-indigo-900"><PencilIcon class="h-5 w-5" /></button>
                                                <button @click="confirmDeleteVideotron(item)" class="p-1 text-red-600 hover:text-red-900"><TrashIcon class="h-5 w-5" /></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="md:hidden space-y-4">
                                <div v-for="item in videotronList.data" :key="item.id" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="font-bold text-lg pr-4">{{ item.name }}</h3>
                                        <div class="flex-shrink-0">
                                            <button @click="openEditModal(item)" class="p-1 mr-2 text-indigo-600 hover:text-indigo-900"><PencilIcon class="h-5 w-5" /></button>
                                            <button @click="confirmDeleteVideotron(item)" class="p-1 text-red-600 hover:text-red-900"><TrashIcon class="h-5 w-5" /></button>
                                        </div>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Lokasi:</span>
                                            <span class="font-medium text-right">{{ item.location_name }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">Device ID:</span>
                                            <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ item.device_id || '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">{{ item.status }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="videotronList.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex flex-wrap -mb-1">
                                <template v-for="(link, key) in videotronList.links" :key="key">
                                    <div v-if="link.url === null"
                                         class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
                                         v-html="link.label" />
                                    <Link v-else
                                          class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                                          :class="{ 'bg-blue-600 text-white': link.active }"
                                          :href="link.url"
                                          v-html="link.label"
                                          preserve-scroll />
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showVideotronModal" @close="closeModal" maxWidth="2xl">
            <form @submit.prevent="submitVideotronForm" class="p-6" novalidate>
                <h2 class="text-lg font-medium border-b pb-3 mb-6">{{ isEditMode ? 'Edit' : 'Tambah' }} Videotron</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel for="name" value="Nama Videotron" required />
                        <TextInput id="name" v-model="form.name" class="mt-1 w-full" placeholder="Cth: VTR-JKT-01" />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel for="device_id" value="Device ID (dari STB)" />
                        <TextInput id="device_id" v-model="form.device_id" class="mt-1 w-full font-mono" placeholder="ID unik dari perangkat Android" />
                        <InputError :message="form.errors.device_id" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel for="password" value="Password Player" />
                        <TextInput id="password" v-model="form.password" type="password" class="mt-1 w-full" :placeholder="isEditMode ? 'Kosongkan jika tidak ingin diubah' : 'Min. 6 karakter'" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel for="playlist_id" value="Playlist Musik Latar (Opsional)" />
                        <select v-model="form.playlist_id" id="playlist_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Tidak Ada Musik Latar --</option>
                            <option v-for="playlist in allPlaylists" :key="playlist.id" :value="playlist.id">
                                {{ playlist.name }}
                            </option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel for="schedule_id" value="Schedule (Opsional)" />
                        <select v-model="form.schedule_id" id="schedule_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Tidak Ada Schedule --</option>
                            <option v-for="schedule in allSchedules" :key="schedule.id" :value="schedule.id">
                                {{ schedule.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="location_name" value="Nama Lokasi" required />
                        <TextInput id="location_name" v-model="form.location_name" class="mt-1 w-full" placeholder="Cth: Mall Grand Indonesia" />
                        <InputError :message="form.errors.location_name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="latitude" value="Latitude (Opsional)" />
                        <TextInput id="latitude" v-model="form.latitude" type="number" step="any" class="mt-1 w-full" placeholder="Cth: -6.2013" />
                        <InputError :message="form.errors.latitude" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="longitude" value="Longitude (Opsional)" />
                        <TextInput id="longitude" v-model="form.longitude" type="number" step="any" class="mt-1 w-full" placeholder="Cth: 106.8225" />
                        <InputError :message="form.errors.longitude" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="resolution" value="Resolusi (Opsional)" />
                        <TextInput id="resolution" v-model="form.resolution" class="mt-1 w-full" placeholder="Cth: 1920x1080" />
                        <InputError :message="form.errors.resolution" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="status" value="Status" required />
                        <select v-model="form.status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 border-t pt-6">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">{{ isEditMode ? 'Update' : 'Simpan' }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium">Konfirmasi Hapus</h2>
                <p class="mt-2 text-sm text-gray-600">Yakin ingin menghapus videotron "{{ videotronToDelete?.name }}"?</p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showDeleteConfirmModal = false">Batal</SecondaryButton>
                    <DangerButton @click="deleteVideotron" class="ml-3">Ya, Hapus</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>