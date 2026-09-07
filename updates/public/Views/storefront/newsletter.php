<?php
/** Mirrors src/components/Newsletter.tsx. Submit handled in assets/js/app.js. */
?>
<section class="bg-black text-white py-16 px-4 sm:px-6 lg:px-8 border-y border-neutral-300">
  <div class="max-w-4xl mx-auto text-center space-y-6">
    <div class="inline-flex items-center space-x-2 bg-[#0a0a0a] border border-neutral-400/40 px-3 py-1 rounded-full text-white text-xs font-semibold uppercase tracking-widest">
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
      <span>JOIN THE PENTAGON CIRCLE</span>
    </div>

    <h2 class="font-serif-heading text-2xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-white">RECEIVE 10% OFF YOUR INITIAL ORDER</h2>

    <p class="text-neutral-100 text-xs sm:text-sm font-light max-w-xl mx-auto leading-relaxed">
      Subscribe to enjoy early access to new collections, exclusive furniture drops, and member-only privileges.
    </p>

    <div id="newsletter-success" class="hidden bg-[#0a0a0a] border border-neutral-400/50 text-white p-4 rounded-lg text-xs font-semibold max-w-md mx-auto items-center justify-center space-x-2 animate-fade-in shadow-lg">
      <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
      <span>Welcome! Your 10% discount code is <strong>PENTAGON10</strong></span>
    </div>

    <form id="newsletter-form" class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto pt-2">
      <div class="relative flex-1">
        <svg class="w-4 h-4 text-neutral-400 absolute left-3.5 top-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        <input type="email" required id="newsletter-email" placeholder="Enter your personal email address..." class="w-full bg-[#0a0a0a] border border-neutral-300 text-white text-xs rounded-lg pl-10 pr-4 py-3 focus:outline-none focus:border-neutral-400" />
      </div>
      <button type="submit" class="bg-white hover:bg-neutral-300 text-black text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest transition-colors flex items-center justify-center space-x-1.5 cursor-pointer shadow-md">
        <span>SUBSCRIBE</span>
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>
    </form>

    <p class="text-[10px] text-neutral-500 font-light">We respect your inbox privacy. Unsubscribe at any time with one click.</p>
  </div>
</section>
