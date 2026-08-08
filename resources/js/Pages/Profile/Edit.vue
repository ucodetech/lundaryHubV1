<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import BankAccountForm from '@/Components/BankAccountForm.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
  bankAccount?: any;
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user);

const form = useForm({
  first_name: user.value?.first_name ?? '',
  last_name: user.value?.last_name ?? '',
  email: user.value?.email ?? '',
  phone: user.value?.phone ?? '',
});

const submit = () => {
  form.patch('/profile');
};
</script>

<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Account Settings</h1>
        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Manage your personal profile, contact information, and verified bank details</p>
      </div>

      <form @submit.prevent="submit" class="bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/60 rounded-2xl p-6 space-y-4 shadow-xl">
        <h3 class="text-sm font-bold text-gray-900 dark:text-slate-100 border-b border-gray-200 dark:border-slate-700/60 pb-3">Personal Information</h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">First Name</label>
            <input
              v-model="form.first_name"
              type="text"
              required
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Last Name</label>
            <input
              v-model="form.last_name"
              type="text"
              required
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Email Address</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase mb-1">Phone Number</label>
            <input
              v-model="form.phone"
              type="text"
              required
              class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-slate-100 text-sm focus:border-sky-500"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2.5 rounded-xl bg-sky-500 text-slate-950 font-bold text-xs shadow-lg shadow-sky-500/20"
        >
          Save Changes
        </button>
      </form>

      <!-- Bank Account Verification & Settlement Component -->
      <BankAccountForm :existing-account="bankAccount" />
    </div>
  </AppLayout>
</template>
