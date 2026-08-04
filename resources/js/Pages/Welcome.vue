<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
  canLogin?: boolean;
  canRegister?: boolean;
  shops: Array<any>;
}>();
</script>

<template>
  <Head title="LaundryHub — Multi-Tenant Laundry & Dry Cleaning Platform" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-sky-500 selection:text-slate-950">
    <!-- Navbar -->
    <header class="h-20 border-b border-slate-800/80 px-6 sm:px-12 flex items-center justify-between sticky top-0 bg-slate-950/80 backdrop-blur z-50">
      <Link href="/" class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-cyan-400 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-sky-500/20">
          L
        </div>
        <span class="font-extrabold text-xl bg-gradient-to-r from-sky-400 via-cyan-300 to-teal-300 bg-clip-text text-transparent">
          LaundryHub
        </span>
      </Link>

      <div class="flex items-center gap-4">
        <template v-if="$page.props.auth?.user">
          <Link href="/dashboard" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-lg shadow-sky-500/20">
            Go to Dashboard →
          </Link>
        </template>
        <template v-else>
          <Link href="/login" class="text-sm font-semibold text-slate-300 hover:text-white transition-colors">
            Sign In
          </Link>
          <Link href="/register" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-bold text-sm shadow-lg shadow-sky-500/20">
            Get Started
          </Link>
        </template>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="px-6 sm:px-12 py-16 sm:py-24 max-w-6xl mx-auto text-center space-y-8">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-sky-500/10 border border-sky-500/20 text-sky-400 text-xs font-semibold">
        ✨ The Next-Gen Dry Cleaning Marketplace Platform
      </div>

      <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-slate-100 max-w-4xl mx-auto leading-tight">
        Every Dry Cleaner Gets Their Digital Storefront.
        <span class="bg-gradient-to-r from-sky-400 via-cyan-300 to-teal-300 bg-clip-text text-transparent">Customers & Riders Connect Seamlessly.</span>
      </h1>

      <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto">
        LaundryHub empowers dry cleaning shops to own their custom digital shopfront while providing customers with instant pricing, rider dispatching, and real-time tracking.
      </p>

      <div class="flex flex-wrap justify-center gap-4 pt-4">
        <Link href="/register?role=shop_owner" class="px-8 py-4 rounded-2xl bg-gradient-to-r from-sky-500 to-cyan-500 text-slate-950 font-black text-base shadow-xl shadow-sky-500/25 hover:scale-105 transition-transform">
          Register Your Dry Cleaning Shop
        </Link>
        <Link href="/register?role=customer" class="px-8 py-4 rounded-2xl bg-slate-900 border border-slate-800 text-slate-200 font-bold text-base hover:bg-slate-800 transition-colors">
          Book Laundry Pickup
        </Link>
      </div>
    </section>

    <!-- Dry Cleaner Marketplace Cards -->
    <section class="px-6 sm:px-12 py-12 max-w-6xl mx-auto space-y-6 w-full">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-100">Featured Dry Cleaners</h2>
        <span class="text-xs text-sky-400 font-semibold">Verified Partners</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="shop in shops"
          :key="shop.id"
          class="bg-slate-900/80 border border-slate-800 hover:border-sky-500/50 rounded-3xl p-6 space-y-4 transition-all"
        >
          <div class="flex items-center justify-between">
            <h3 class="font-extrabold text-lg text-slate-100">{{ shop.name }}</h3>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              ⭐ 4.8 Open
            </span>
          </div>

          <p class="text-xs text-slate-400 line-clamp-2">{{ shop.description }}</p>

          <div class="text-xs text-slate-400 space-y-1">
            <div class="flex items-center justify-between">
              <p class="truncate">📍 {{ shop.address }}</p>
              <span v-if="shop.distance_km" class="px-2 py-0.5 rounded-md bg-sky-500/10 text-sky-400 text-[10px] font-bold shrink-0">
                {{ shop.distance_km }} km away
              </span>
            </div>
          </div>

          <Link
            :href="`/shop/${shop.slug}`"
            class="block text-center w-full py-3 rounded-xl bg-slate-800 hover:bg-sky-500 hover:text-slate-950 font-bold text-xs text-slate-200 transition-colors"
          >
            Visit Storefront & Calculate Pricing →
          </Link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-8 px-6 text-center text-xs text-slate-500">
      © {{ new Date().getFullYear() }} LaundryHub Platform. All rights reserved. Powered by Laravel 12 & Vue 3.
    </footer>
  </div>
</template>
