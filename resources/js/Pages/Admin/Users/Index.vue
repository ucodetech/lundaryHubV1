<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import AdvancedFilter, { FilterConfig } from '@/Components/AdvancedFilter.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  users: any;
  filters?: Record<string, any>;
}>();

const filterState = ref<Record<string, any>>({ ...props.filters });
const showCreateModal = ref(false);

const createForm = useForm({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: 'support',
  password: 'Password123!',
});

const filterConfig: FilterConfig[] = [
  {
    key: 'search',
    label: 'Search',
    type: 'text',
    placeholder: 'Search name, email, phone...',
  },
  {
    key: 'role',
    label: 'Platform Role',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Super Admin', value: 'super_admin' },
      { label: 'Support Staff', value: 'support' },
      { label: 'Shop Owner', value: 'shop_owner' },
      { label: 'Rider', value: 'rider' },
      { label: 'Customer', value: 'customer' },
    ],
  },
  {
    key: 'is_active',
    label: 'Account Status',
    type: 'select',
    defaultVisible: true,
    options: [
      { label: 'Active Account', value: '1' },
      { label: 'Suspended Account', value: '0' },
    ],
  },
];

const handleFilterChange = (newFilters: Record<string, any>) => {
  router.get('/admin/users', newFilters, {
    preserveState: true,
    replace: true,
  });
};

const toggleUserStatus = (id: number) => {
  useForm({}).post(`/admin/users/${id}/toggle-status`);
};

const generatePassword = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$';
  let pwd = '';
  for (let i = 0; i < 10; i++) {
    pwd += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  createForm.password = pwd;
};

const submitCreateUser = () => {
  createForm.post('/admin/users', {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      createForm.reset();
      createForm.password = 'Password123!';
    },
  });
};
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header Bar with Create Button -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Platform User Directory</h1>
          <p class="text-xs text-slate-400 mt-1">Manage accounts across Super Admin, Support Staff, Dry Cleaners, Riders, and Customers</p>
        </div>

        <button
          @click="showCreateModal = true"
          class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-500/20 hover:scale-105 transition-all flex items-center gap-2"
        >
          <span>➕ Create System User</span>
        </button>
      </div>

      <!-- Advanced Filter Component -->
      <AdvancedFilter
        v-model="filterState"
        :filters-config="filterConfig"
        search-placeholder="Search user name, email, or phone..."
        @filter-change="handleFilterChange"
      />

      <!-- Users Table -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden overflow-x-auto custom-scrollbar shadow-xl">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-900/80 text-xs uppercase text-slate-400 border-b border-slate-700/60">
            <tr>
              <th class="py-3.5 px-6">Name</th>
              <th class="py-3.5 px-6">Email / Phone</th>
              <th class="py-3.5 px-6">Role</th>
              <th class="py-3.5 px-6">Account Status</th>
              <th class="py-3.5 px-6 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/40">
            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="py-4 px-6 font-semibold text-slate-200">{{ user.first_name }} {{ user.last_name }}</td>
              <td class="py-4 px-6 text-slate-300 text-xs">{{ user.email }}<br><span class="text-slate-400 font-mono">{{ user.phone }}</span></td>
              <td class="py-4 px-6 text-xs capitalize font-semibold text-purple-400">
                <span class="px-2.5 py-1 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-300">
                  {{ user.role?.replace('_', ' ') }}
                </span>
              </td>
              <td class="py-4 px-6">
                <Badge :status="user.is_active ? 'active' : 'suspended'" />
              </td>
              <td class="py-4 px-6 text-right">
                <button
                  @click="toggleUserStatus(user.id)"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                  :class="user.is_active ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20'"
                >
                  {{ user.is_active ? 'Suspend Account' : 'Activate Account' }}
                </button>
              </td>
            </tr>

            <tr v-if="!users.data || users.data.length === 0">
              <td colspan="5" class="py-12 text-center text-slate-400 text-xs">
                No users matching the selected filters.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create System User Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-slate-900 border border-slate-700/80 rounded-2xl w-full max-w-lg p-6 space-y-6 shadow-2xl my-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
          <div>
            <h2 class="text-lg font-bold text-slate-100">Create System User</h2>
            <p class="text-xs text-slate-400">Add administrative, support, cleaner, or rider accounts</p>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white text-xl">✕</button>
        </div>

        <form @submit.prevent="submitCreateUser" class="space-y-4">
          <!-- First & Last Name -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">First Name *</label>
              <input
                v-model="createForm.first_name"
                type="text"
                required
                placeholder="e.g. Sarah"
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-purple-500"
              />
              <span v-if="createForm.errors.first_name" class="text-[11px] text-rose-400 mt-1 block">{{ createForm.errors.first_name }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Last Name *</label>
              <input
                v-model="createForm.last_name"
                type="text"
                required
                placeholder="e.g. Connor"
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-purple-500"
              />
              <span v-if="createForm.errors.last_name" class="text-[11px] text-rose-400 mt-1 block">{{ createForm.errors.last_name }}</span>
            </div>
          </div>

          <!-- Email & Phone -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Email Address *</label>
              <input
                v-model="createForm.email"
                type="email"
                required
                placeholder="e.g. support@laundryhub.ng"
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-purple-500"
              />
              <span v-if="createForm.errors.email" class="text-[11px] text-rose-400 mt-1 block">{{ createForm.errors.email }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Phone Number *</label>
              <input
                v-model="createForm.phone"
                type="text"
                required
                placeholder="e.g. 08012345678"
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs font-mono focus:border-purple-500"
              />
              <span v-if="createForm.errors.phone" class="text-[11px] text-rose-400 mt-1 block">{{ createForm.errors.phone }}</span>
            </div>
          </div>

          <!-- Platform Role -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Assign System Role *</label>
            <select
              v-model="createForm.role"
              required
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs focus:border-purple-500 font-bold"
            >
              <option value="super_admin">👑 Super Admin</option>
              <option value="support">🎧 Platform Support Staff</option>
              <option value="shop_owner">🧺 Dry Cleaner / Shop Owner</option>
              <option value="rider">🛵 Delivery Rider</option>
              <option value="customer">👤 Customer</option>
            </select>
          </div>

          <!-- Password -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-300 uppercase">Account Password *</label>
              <button
                type="button"
                @click="generatePassword"
                class="text-[10px] text-purple-400 font-bold hover:underline"
              >
                🎲 Auto-Generate
              </button>
            </div>
            <input
              v-model="createForm.password"
              type="text"
              required
              placeholder="Minimum 8 characters"
              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs font-mono focus:border-purple-500"
            />
            <span v-if="createForm.errors.password" class="text-[11px] text-rose-400 mt-1 block">{{ createForm.errors.password }}</span>
          </div>

          <!-- Submit Buttons -->
          <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-bold text-xs hover:text-white"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="createForm.processing"
              class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-500/20 hover:scale-105 transition-all flex items-center gap-2 disabled:opacity-60"
            >
              <svg v-if="createForm.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <span>{{ createForm.processing ? 'Creating User...' : '✨ Create System User' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
