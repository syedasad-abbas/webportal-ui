<header class="connectpro-page-header border-b border-[#20364c] bg-[#071526]/95 backdrop-blur-xl">
    <!-- Top bar navigation tabs -->
    <nav class="connectpro-reference-nav hidden items-center gap-2 px-4 py-3 lg:flex" aria-label="{{ __('Contacts navigation') }}">
        <a href="{{ route('admin.contacts.index') }}" class="connectpro-reference-nav-active">{{ __('Contacts') }}</a>
        <a href="{{ route('admin.dialer.index') }}">{{ __('Dialpad') }}</a>
        <a href="{{ route('admin.contacts.call-history') }}">{{ __('History') }}</a>
        <a href="{{ route('admin.contacts.activity') }}">{{ __('Activity') }}</a>
        <a href="#">{{ __('Queues') }}</a>
        <a href="#">{{ __('Reports') }}</a>
    </nav>
</header>
