<div
    x-show="sidebarMobileOpen"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-9998 bg-gray-900/60 backdrop-blur-sm xl:hidden"
    @click="closeMobileSidebar()"
    x-cloak
></div>
