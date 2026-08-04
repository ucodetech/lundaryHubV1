<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps<{
  referralCode: string;
  referralLink: string;
  bonusBalance: number;
  totalEarned: number;
  referrals: any;
  transactions: any[];
}>();

const copied = ref(false);

const copyLink = () => {
  navigator.clipboard.writeText(props.referralLink);
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
  }, 2500);
};

const whatsappText = encodeURIComponent(
  `Join LaundryHub with my referral link to get bonus discounts on your laundry orders!\n\n` +
  `🔗 ${props.referralLink}\n` +
  `Or type my phone number ${props.referralCode} as your referral code on signup!`
);
const whatsappShareUrl = `https://api.whatsapp.com/send?text=${whatsappText}`;
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header Banner -->
      <div class="bg-gradient-to-r from-purple-900/40 via-indigo-900/40 to-slate-900 border border-purple-500/20 rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="max-w-2xl space-y-3 relative z-10">
          <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-bold uppercase tracking-wider">
            🎁 Invite Friends & Earn Cash Rewards
          </span>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-100">LaundryHub Referral Hub</h1>
          <p class="text-xs sm:text-sm text-slate-300">
            Share your unique referral code or link with friends, family, and laundry shop owners. Earn bonus wallet credits directly towards your orders & passes!
          </p>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Current Bonus Wallet Balance</span>
          <div class="text-2xl font-black text-emerald-400">
            ₦{{ Number(bonusBalance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[10px] text-slate-400 block">Auto-deducted at order checkout / pass purchase</span>
        </div>

        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Total Referral Rewards Earned</span>
          <div class="text-2xl font-black text-purple-400">
            ₦{{ Number(totalEarned).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </div>
          <span class="text-[10px] text-slate-400 block">Lifetime earnings from referral bonuses</span>
        </div>

        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-2">
          <span class="text-xs text-slate-400 font-bold uppercase">Friends & Shops Referred</span>
          <div class="text-2xl font-black text-sky-400">
            {{ referrals?.total || referrals?.data?.length || 0 }} Users
          </div>
          <span class="text-[10px] text-slate-400 block">Registered using your referral link/phone</span>
        </div>
      </div>

      <!-- Share Box Card -->
      <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
          <span>🔗 Your Unique Referral Link & Code</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Referral Code (Phone Number) -->
          <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-1">
            <span class="text-[11px] text-slate-400 font-bold uppercase block">Your Referral Phone Code:</span>
            <div class="flex items-center justify-between">
              <span class="text-lg font-mono font-bold text-sky-400">{{ referralCode }}</span>
              <span class="text-[10px] text-slate-500 font-bold uppercase">Tell friends to enter on signup</span>
            </div>
          </div>

          <!-- Share Link & WhatsApp -->
          <div class="space-y-2">
            <span class="text-[11px] text-slate-400 font-bold uppercase block">Shareable Referral Link:</span>
            <div class="flex items-center gap-2">
              <input
                :value="referralLink"
                readonly
                class="flex-1 px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono select-all"
              />
              <button
                @click="copyLink"
                class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all shrink-0 flex items-center gap-1.5"
                :class="copied ? 'bg-emerald-500 text-slate-950' : 'bg-purple-500 text-white hover:bg-purple-600'"
              >
                <span>{{ copied ? 'Copied! ✓' : '📋 Copy Link' }}</span>
              </button>
            </div>
          </div>
        </div>

        <div class="pt-2 flex flex-wrap items-center gap-3">
          <a
            :href="whatsappShareUrl"
            target="_blank"
            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-lg shadow-emerald-600/20 transition-transform hover:scale-105"
          >
            <span>💬 Share via WhatsApp</span>
          </a>
        </div>
      </div>

      <!-- How it Works Tiers -->
      <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">How Referral Rewards Work</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div class="bg-slate-900/80 p-4 rounded-xl border border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-sky-400 font-bold text-sm">
              <span>🙋 Recommending a Customer</span>
            </div>
            <p class="text-slate-300">
              When a friend signs up with your phone number and completes their <strong>first paid laundry order</strong>:
            </p>
            <ul class="text-slate-400 space-y-1 list-disc pl-4 font-mono text-[11px]">
              <li>You receive <strong class="text-emerald-400">₦500</strong> bonus wallet credit.</li>
              <li>Your friend receives <strong class="text-emerald-400">₦200</strong> bonus wallet credit.</li>
            </ul>
          </div>

          <div class="bg-slate-900/80 p-4 rounded-xl border border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-purple-400 font-bold text-sm">
              <span>🏪 Recommending a Dry Cleaner / Shop Owner</span>
            </div>
            <p class="text-slate-300">
              When a laundry shop owner registers, completes verification, and pays for their <strong>first shop subscription</strong>:
            </p>
            <ul class="text-slate-400 space-y-1 list-disc pl-4 font-mono text-[11px]">
              <li>You receive <strong class="text-emerald-400">₦1,000</strong> bonus wallet credit.</li>
              <li>The shop owner receives <strong class="text-emerald-400">₦500</strong> subscription discount credit.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Transaction & Referrals Ledger -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Referrals List -->
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
          <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">Referred Friends & Shops</h3>

          <div class="divide-y divide-slate-700/40">
            <div
              v-for="ref in referrals.data"
              :key="ref.id"
              class="py-3 flex items-center justify-between gap-3 text-xs"
            >
              <div>
                <span class="font-bold text-slate-200 block">{{ ref.referred?.first_name }} {{ ref.referred?.last_name }}</span>
                <span class="text-[10px] text-slate-400 font-mono">{{ ref.referred?.phone }} • Joined {{ new Date(ref.created_at).toLocaleDateString() }}</span>
              </div>
              <span
                class="px-2.5 py-1 rounded-full text-[10px] font-bold border capitalize"
                :class="ref.status === 'rewarded' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
              >
                {{ ref.status === 'rewarded' ? '✓ Rewarded (+₦' + ref.referrer_reward + ')' : '⏳ Pending 1st Order' }}
              </span>
            </div>

            <div v-if="!referrals.data || referrals.data.length === 0" class="py-8 text-center text-slate-400 text-xs">
              No referrals yet. Share your link to start earning!
            </div>
          </div>
        </div>

        <!-- Bonus Wallet Ledger -->
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
          <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">Bonus Wallet Transactions</h3>

          <div class="divide-y divide-slate-700/40">
            <div
              v-for="tx in transactions"
              :key="tx.id"
              class="py-3 flex items-center justify-between gap-3 text-xs"
            >
              <div class="space-y-0.5">
                <span class="font-semibold text-slate-200 block">{{ tx.description }}</span>
                <span class="text-[10px] text-slate-400 font-mono">{{ new Date(tx.created_at).toLocaleString() }}</span>
              </div>
              <span
                class="font-mono font-bold text-xs"
                :class="tx.amount > 0 ? 'text-emerald-400' : 'text-rose-400'"
              >
                {{ tx.amount > 0 ? '+' : '' }}₦{{ Number(tx.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
              </span>
            </div>

            <div v-if="!transactions || transactions.length === 0" class="py-8 text-center text-slate-400 text-xs">
              No bonus transactions recorded yet.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
