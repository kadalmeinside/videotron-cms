<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusIcon, PencilIcon, TrashIcon, CalendarDaysIcon, ChevronLeftIcon, ChevronRightIcon, ClipboardDocumentIcon } from '@heroicons/vue/24/outline';
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
    allScheduledDates: Array,
    allMedia: Array,
    startDate: String,
});

// --- STATE MANAGEMENT ---
const showItemModal = ref(false);
const isEditMode = ref(false);
const localFlash = ref({ message: '', type: 'info', show: false });
const pageDuration = 14; // Harus sama dengan di controller

// State untuk Tab & Paginasi
const activeDate = ref(props.startDate);
const pageStartDate = ref(new Date(props.startDate + 'T00:00:00'));

// State untuk Modal Copy
const showCopyModal = ref(false);
const copySourceDate = ref(null);

// --- FORMS ---
const form = useForm({
    id: null,
    schedule_date: '',
    play_hour: '07',
    play_minute: '00',
    media_id: '',
});

const copyForm = useForm({
    target_date: '',
    error: null,
});


const dateTabs = computed(() => {
    const dates = [];
    const baseDate = new Date(pageStartDate.value);
    
    for (let i = 0; i < pageDuration; i++) {
        const current = new Date(baseDate);
        current.setDate(baseDate.getDate() + i);

        const year = current.getFullYear();
        const month = (current.getMonth() + 1).toString().padStart(2, '0');
        const day = current.getDate().toString().padStart(2, '0');
        const dateString = `${year}-${month}-${day}`;

        dates.push({
            date: dateString,
            label: current.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }),
            fullLabel: current.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }),
            hasSchedule: props.allScheduledDates.includes(dateString),
        });
    }
    return dates;
});

const activeDayItems = computed(() => props.scheduledDays[activeDate.value] || []);

const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const minutes = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'));

const changePage = (direction) => {
    const newStartDate = new Date(pageStartDate.value);
    newStartDate.setDate(pageStartDate.value.getDate() + (direction * pageDuration));

    router.get(route('admin.schedules.show', props.schedule.id), {
        start_date: newStartDate.toISOString().slice(0, 10)
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: (page) => {
            pageStartDate.value = new Date(page.props.startDate + 'T00:00:00');
            activeDate.value = page.props.startDate;
        }
    });
};

const prevPage = () => changePage(-1);
const nextPage = () => changePage(1);

const validateForm = () => {
    form.clearErrors();
    if (!form.media_id) {
        form.setError('media_id', 'Media wajib dipilih.');
        return false;
    }
    return true;
}

const openAddItemModal = (date) => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    form.schedule_date = date;
    showItemModal.value = true;
};

const openEditItemModal = (item) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    const playAtDate = new Date(item.play_at);
    form.id = item.id;
    form.schedule_date = activeDate.value;
    form.play_hour = playAtDate.getHours().toString().padStart(2, '0');
    form.play_minute = playAtDate.getMinutes().toString().padStart(2, '0');
    form.media_id = item.media_id;
    showItemModal.value = true;
};

const closeModal = () => {
    showItemModal.value = false;
};

const saveItem = async () => {
    if (!validateForm()) return;
    form.processing = true;
    const play_time = `${form.play_hour}:${form.play_minute}`;
    const dataToSend = {
        play_time: play_time,
        schedule_date: form.schedule_date,
        media_id: form.media_id,
        schedule_id: props.schedule.id,
    };
    const url = isEditMode.value ? route('api.admin.schedule-items.update', form.id) : route('api.admin.schedule-items.store');
    const method = isEditMode.value ? 'put' : 'post';

    try {
        const response = await axios[method](url, dataToSend);
        closeModal();
        router.reload({
            only: ['scheduledDays', 'allScheduledDates'],
            onSuccess: () => showToast(response.data.message, 'success'),
        });
    } catch (error) {
        if (error.response && error.response.status === 422) {
            form.setError(error.response.data.errors);
        } else {
            showToast('Terjadi kesalahan tak terduga.', 'error');
        }
    } finally {
        form.processing = false;
    }
};

const confirmAndDeleteItem = (item) => {
    // Minta konfirmasi dari pengguna sebelum menghapus
    if (!confirm(`Yakin ingin menghapus jadwal "${item.media.title}"?`)) {
        return;
    }

    // Panggil endpoint API untuk menghapus item
    axios.delete(route('api.admin.schedule-items.destroy', item.id))
        .then(() => {
            // Jika berhasil, muat ulang data jadwal dari server
            router.reload({
                only: ['scheduledDays', 'allScheduledDates'],
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Item jadwal berhasil dihapus.', 'success');
                }
            });
        })
        .catch(error => {
            // Jika gagal, tampilkan pesan error
            showToast('Gagal menghapus item.', 'error');
            console.error('Deletion error:', error);
        });
};

const openCopyModal = (date) => {
    copySourceDate.value = date;
    copyForm.reset();
    copyForm.target_date = new Date().toISOString().slice(0, 10);
    showCopyModal.value = true;
};

const closeCopyModal = () => {
    showCopyModal.value = false;
};

const submitCopy = () => {
    copyForm.processing = true;
    copyForm.error = null;
    
    axios.post(route('api.admin.copydate', props.schedule.id), {
        source_date: copySourceDate.value,
        target_date: copyForm.target_date,
    })
    .then(response => {
        closeCopyModal();
        router.reload({ 
            onSuccess: () => {
                showToast(response.data.message, 'success');
            }
        });
    })
    .catch(error => {
        if (error.response && error.response.data && error.response.data.message) {
            copyForm.error = error.response.data.message;
        } else {
            copyForm.error = 'Terjadi kesalahan tak terduga. Silakan coba lagi.';
        }
    })
    .finally(() => {
        copyForm.processing = false;
    });
};

// --- HELPER FUNCTIONS ---
const formatTime = (datetime) => {
    if (!datetime) return '';
    return new Date(datetime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const showToast = (message, type = 'info') => {
    localFlash.value = { message, type, show: true };
    setTimeout(() => { localFlash.value.show = false; }, 4000);
};

</script>

<template>
    <Head :title="`Jadwal untuk ${schedule.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.schedules.index')" class="mr-4 text-gray-500 hover:text-gray-700">&larr; Kembali</Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Jadwal untuk <span class="text-[--color-primary-600]">{{ schedule.name }}</span>
                </h2>
            </div>
        </template>
        
        <Toast :message="localFlash.message" :type="localFlash.type" :show="localFlash.show" @close="localFlash.show = false" />

        <div class="pb-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="mb-6 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm flex items-center gap-2">
                    <button @click="prevPage" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Halaman sebelumnya">
                        <ChevronLeftIcon class="h-5 w-5 text-gray-500" />
                    </button>

                    <div class="flex-grow grid grid-cols-7 md:grid-cols-14 gap-1">
                         <button v-for="tab in dateTabs" :key="tab.date" @click="activeDate = tab.date"
                            :class="[
                                'px-3 py-2 text-xs font-medium rounded-md transition-colors duration-200 text-center',
                                activeDate === tab.date
                                    ? 'bg-[--color-primary-600] text-white shadow'
                                    : tab.hasSchedule
                                        ? 'bg-green-100 dark:bg-green-800/50 text-green-800 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-700/50'
                                        : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]">
                            {{ tab.label }}
                        </button>
                    </div>

                     <button @click="nextPage" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Halaman berikutnya">
                        <ChevronRightIcon class="h-5 w-5 text-gray-500" />
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="activeDayItems.length > 0" class="p-6">
                        <div class="flex justify-between items-center border-b pb-2 mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ dateTabs.find(t => t.date === activeDate)?.fullLabel }}
                            </h3>
                             <div class="flex items-center gap-x-4">
                                <button @click="openCopyModal(activeDate)" class="text-sm font-semibold text-gray-500 hover:text-gray-700 flex items-center gap-1">
                                    <ClipboardDocumentIcon class="h-4 w-4" /> Salin Jadwal
                                </button>
                                <button @click="openAddItemModal(activeDate)" class="text-sm font-semibold text-[--color-primary-600] hover:text-[--color-primary-500] flex items-center gap-1">
                                    <PlusIcon class="h-4 w-4" /> Tambah Item
                                </button>
                            </div>
                        </div>
                        
                        <ul class="space-y-3">
                            <li v-for="item in activeDayItems" :key="item.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                <div class="flex items-center gap-4">
                                    <span class="font-mono text-lg text-[--color-primary-600] dark:text-[--color-primary-400]">{{ formatTime(item.play_at) }}</span>
                                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ item.media.title }}</p>
                                    <span class="text-xs text-gray-500">({{ item.media.duration }} detik)</span>
                                </div>
                                <div>
                                    <button @click="openEditItemModal(item)" class="text-gray-400 hover:text-gray-600 dark:hover:text-white" aria-label="Edit item">
                                        <PencilIcon class="h-5 w-5" />
                                    </button>
                                    <button @click="confirmAndDeleteItem(item)" class="p-1 text-red-400 hover:text-red-600 dark:hover:text-red-300" aria-label="Hapus item">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <div v-else class="text-center p-12">
                         <CalendarDaysIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Jadwal Kosong</h3>
                        <p class="mt-1 text-sm text-gray-500">Belum ada jadwal untuk tanggal {{ dateTabs.find(t => t.date === activeDate)?.fullLabel }}.</p>
                        <div class="mt-6">
                            <PrimaryButton @click="openAddItemModal(activeDate)">
                                <PlusIcon class="h-5 w-5 mr-2" /> Buat Jadwal untuk Tanggal Ini
                            </PrimaryButton>
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
                            <select id="play_hour" v-model="form.play_hour" class="w-full border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
                            </select>
                            <span class="font-bold">:</span>
                             <select id="play_minute" v-model="form.play_minute" class="w-full border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="m in minutes" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                         <p v-if="form.errors.play_time" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ form.errors.play_time }}</p>
                    </div>
                    
                    <div>
                        <InputLabel for="media_id" value="Pilih Media" required />
                        <select id="media_id" v-model="form.media_id" @change="validateForm" class="mt-1 block w-full border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option disabled value="">-- Pilih Media --</option>
                            <option v-for="media in allMedia" :key="media.id" :value="media.id">
                                {{ media.title }} ({{ media.duration }} detik)
                            </option>
                        </select>
                         <p v-if="form.errors.media_id" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ form.errors.media_id }}</p>
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

        <Modal :show="showCopyModal" @close="closeCopyModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Salin Jadwal
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Salin semua item dari tanggal <strong>{{ copySourceDate }}</strong> ke tanggal tujuan.
                </p>

                <div class="mt-6">
                    <InputLabel for="target_date" value="Pilih Tanggal Tujuan" required />
                    <TextInput
                        id="target_date"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="copyForm.target_date"
                        required
                    />
                </div>

                <div v-if="copyForm.error" class="mt-4 p-3 bg-red-100 border border-red-200 text-red-800 rounded-md text-sm">
                    {{ copyForm.error }}
                </div>

                <div class="mt-6 flex justify-end gap-x-2">
                    <SecondaryButton @click="closeCopyModal">Batal</SecondaryButton>
                    <PrimaryButton @click="submitCopy" :disabled="!copyForm.target_date || copyForm.processing" :class="{'opacity-25': copyForm.processing}">
                        Salin Jadwal
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>