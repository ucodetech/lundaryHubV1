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
      { label: 'Customer', value: 'customer' },
      { label: 'Shop Owner', value: 'shop_owner' },
      { label: 'Rider', value: 'rider' },
      { label: 'Super Admin', value: 'super_admin' },
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
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-100">Platform User Directory</h1>
          <p class="text-xs text-slate-400 mt-1">Manage accounts across Super Admin, Dry Cleaners, Riders, and Customers</p>
        </div>
      </div>

      <!-- Advanced Filter Component -->
      <AdvancedFilter
        v-model="filterState"
        :filters-config="filterConfig"
        search-placeholder="Search user name, email, or phone..."
        @filter-change="handleFilterChange"
      />

      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
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
              <td class="py-4 px-6 text-slate-300 text-xs">{{ user.email }}<br><span class="text-slate-400">{{ user.phone }}</span></td>
              <td class="py-4 px-6 text-xs capitalize font-semibold text-sky-400">{{ user.role?.replace('_', ' ') }}</td>
              <td class="py-4 px-6">
                <Badge :status="user.is_active ? 'active' : 'suspended'" />
              </td>
              <td class="py-4 px-6 text-right">
                <button
                  @click="toggleUserStatus(user.id)"
                  class="px-3 py-1 rounded-lg text-xs font-bold transition-all"
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
  </AppLayout>
</template>
