<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusIcon, PencilIcon, TrashIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Toast from '@/Components/Toast.vue';
import TextInput from '@/Components/TextInput.vue';
import axios from 'axios';

const props = defineProps({
    schedule: Object,
    scheduledDays: Object,
    allMedia: Array,
});

// --- STATE MANAGEMENT ---
const showItemModal = ref(false);
const isEditMode = ref(false);
const localFlash = ref({ message: '', type: 'info', show: false });

const form = useForm({
    id: null,
    schedule_date: '',
    play_hour: '07',
    play_minute: '00',
    media_id: '',
});

const newScheduleForm = useForm({
    date: new Date().toISOString().slice(0, 10),
});

// --- OPSI UNTUK DROPDOWN WAKTU ---
const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const minutes = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'));

// --- PERBAIKAN #1: Buat fungsi validasi terpusat ---
const validateForm = () => {
    form.clearErrors();
    let isValid = true;
    if (!form.media_id) {
        form.setError('media_id', 'Media wajib dipilih.');
        isValid = false;
    }
    // Tambahkan validasi lain di sini jika perlu
    return isValid;
}

// --- FUNCTIONS ---
const openAddItemModal = (date) => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    form.schedule_date = date;
    showItemModal.value = true;
};

const openEditItemModal = (item, date) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    
    const playAtDate = new Date(item.play_at);

    form.id = item.id;
    form.schedule_date = date;
    form.play_hour = playAtDate.getHours().toString().padStart(2, '0');
    form.play_minute = playAtDate.getMinutes().toString().padStart(2, '0');
    form.media_id = item.media_id;
    showItemModal.value = true;
};

const closeModal = () => {
    showItemModal.value = false;
};

const saveItem = async () => {
    // Panggil fungsi validasi di sini. Jika tidak valid, hentikan.
    if (!validateForm()) {
        return;
    }

    form.processing = true;
    const play_time = `${form.play_hour}:${form.play_minute}`;
    const dataToSend = {
        play_time: play_time,
        schedule_date: form.schedule_date,
        media_id: form.media_id,
        schedule_id: props.schedule.id,
    };
    
    const url = isEditMode.value 
        ? route('api.admin.schedule-items.update', form.id)
        : route('api.admin.schedule-items.store');
    
    const method = isEditMode.value ? 'put' : 'post';

    try {
        const response = await axios[method](url, dataToSend);
        closeModal();
        router.reload({ 
            only: ['scheduledDays'],
            onSuccess: () => {
                showToast(response.data.message, 'success');
            }
        });
    } catch (error) {
        if (error.response && error.response.status === 422) {
            form.setError(error.response.data.errors);
        } else {
            console.error('Terjadi kesalahan tak terduga:', error);
            showToast('Terjadi kesalahan tak terduga saat menyimpan.', 'error');
        }
    } finally {
        form.processing = false;
    }
};

const deleteItem = async () => {
    if (confirm('Apakah Anda yakin ingin menghapus item jadwal ini?')) {
        form.processing = true;
        try {
            await axios.delete(route('api.admin.schedule-items.destroy', form.id));
            closeModal();
            router.reload({ 
                only: ['scheduledDays'],
                onSuccess: () => {
                    showToast('Item jadwal berhasil dihapus.', 'success');
                }
            });
        } catch (error) {
            console.error('Gagal menghapus item:', error);
            showToast('Gagal menghapus item.', 'error');
        } finally {
            form.processing = false;
        }
    }
}

const formatTime = (datetime) => {
    if (!datetime) return '';
    return new Date(datetime).toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit'
    });
};

const showToast = (message, type = 'info') => {
    localFlash.value = { message, type, show: true };
    setTimeout(() => {
        localFlash.value.show = false;
    }, 4000);
};

</script>

<template>
    <Head :title="`Jadwal untuk ${schedule.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.schedules.index')" class="mr-4 text-gray-500 hover:text-gray-700">
                    &larr; Kembali
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Jadwal untuk <span class="text-[--color-primary-600]">{{ schedule.name }}</span>
                </h2>
            </div>
        </template>
        
        <Toast :message="localFlash.message" :type="localFlash.type" :show="localFlash.show" @close="localFlash.show = false" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="space-y-8">

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Buat Jadwal untuk Tanggal Baru</h3>
                        <div class="mt-4 flex items-end gap-4">
                            <div>
                                <InputLabel for="new_date" value="Pilih Tanggal" />
                                <TextInput id="new_date" type="date" v-model="newScheduleForm.date" class="mt-1"/>
                            </div>
                            <PrimaryButton @click="openAddItemModal(newScheduleForm.date)">
                                <PlusIcon class="h-5 w-5 mr-2" /> Buat Jadwal
                            </PrimaryButton>
                        </div>
                    </div>

                    <div v-if="Object.keys(scheduledDays).length === 0" class="text-center bg-white dark:bg-gray-800 p-12 rounded-lg shadow-sm">
                        <CalendarDaysIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Jadwal Kosong</h3>
                        <p class="mt-1 text-sm text-gray-500">Belum ada jadwal yang dibuat untuk schedule ini. Gunakan form di atas untuk memulai.</p>
                    </div>

                    <div v-for="(items, date) in scheduledDays" :key="date" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center border-b pb-2 mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                                </h3>
                                <button @click="openAddItemModal(date)" class="text-sm font-semibold text-[--color-primary-600] hover:text-[--color-primary-500] flex items-center gap-1">
                                    <PlusIcon class="h-4 w-4" /> Tambah Item
                                </button>
                            </div>
                            
                            <ul class="space-y-3">
                                <li v-for="item in items" :key="item.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                    <div class="flex items-center gap-4">
                                        <span class="font-mono text-lg text-[--color-primary-600] dark:text-[--color-primary-400]">{{ formatTime(item.play_at) }}</span>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ item.media.title }}</p>
                                        <span class="text-xs text-gray-500">({{ item.media.duration }} detik)</span>
                                    </div>
                                    <div>
                                        <button @click="openEditItemModal(item, date)" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                                            <PencilIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showItemModal" @close="closeModal">
            <form @submit.prevent="saveItem" class="p-6" novalidate>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ isEditMode ? 'Edit' : 'Tambah' }} Item Jadwal
                </h2>
                <p class="text-sm text-gray-500">untuk tanggal {{ form.schedule_date }}</p>

                <div class="mt-6 space-y-4">
                    <div>
                        <InputLabel for="play_hour" value="Waktu Putar" required />
                        <div class="flex items-center gap-2 mt-1">
                            <select id="play_hour" v-model="form.play_hour" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
                            </select>
                            <span class="font-bold">:</span>
                             <select id="play_minute" v-model="form.play_minute" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="m in minutes" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                        <InputError :message="form.errors.play_time" class="mt-2" />
                        <p v-if="form.errors.play_time" class="text-sm text-red-600 mt-2">{{ form.errors.play_time }}</p>
                    </div>
                    
                    <div>
                        <InputLabel for="media_id" value="Pilih Media" required />
                        <!-- --- PERBAIKAN #2: Tambahkan @change untuk validasi instan --- -->
                        <select id="media_id" v-model="form.media_id" @change="validateForm" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option disabled value="">-- Pilih Media --</option>
                            <option v-for="media in allMedia" :key="media.id" :value="media.id">
                                {{ media.title }} ({{ media.duration }} detik)
                            </option>
                        </select>
                         <InputError :message="form.errors.media_id" class="mt-2" />
                         <p v-if="form.errors.media_id" class="text-sm text-red-600 mt-2">{{ form.errors.media_id }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <div>
                        <DangerButton v-if="isEditMode" @click="deleteItem" type="button">Hapus</DangerButton>
                    </div>
                    <div class="flex justify-end gap-x-2">
                        <SecondaryButton @click="closeModal" type="button">Batal</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Simpan
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>

    </AuthenticatedLayout>
</template>
