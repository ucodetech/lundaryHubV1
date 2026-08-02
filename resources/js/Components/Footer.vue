<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <footer class="mt-auto border-t border-slate-800/80 bg-slate-950/60 backdrop-blur-md px-6 py-4 text-xs text-slate-400">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <!-- Left: Branding & Copyright -->
      <div class="flex items-center gap-3">
        <span class="font-bold bg-gradient-to-r from-sky-400 to-cyan-300 bg-clip-text text-transparent">
          LaundryHub SaaS
        </span>
        <span class="text-slate-600">•</span>
        <span>© {{ new Date().getFullYear() }} All rights reserved.</span>
      </div>

      <!-- Center: Operational Status -->
      <div class="flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-medium">
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
        <span>Systems Operational</span>
      </div>

      <!-- Right: Links -->
      <div class="flex items-center gap-4 text-slate-400">
        <Link href="/" class="hover:text-sky-400 transition-colors">Marketplace</Link>
        <span class="text-slate-700">|</span>
        <Link href="/profile" class="hover:text-sky-400 transition-colors">Account</Link>
        <span class="text-slate-700">|</span>
        <a href="#" class="hover:text-sky-400 transition-colors">Help & Support</a>
      </div>
    </div>
  </footer>
</template>
