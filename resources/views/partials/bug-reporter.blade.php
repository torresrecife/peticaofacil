{{-- Botao "Reportar bug" do Kanban: so aparece quando BUG_REPORTER_SCRIPT e BUG_REPORTER_TOKEN estao no .env. --}}
@php($bugReporter = config('services.bug_reporter'))
@if(!empty($bugReporter['script']) && !empty($bugReporter['token']) && auth()->check())
<script src="{{ $bugReporter['script'] }}"
        data-token="{{ $bugReporter['token'] }}"
        data-user-name="{{ auth()->user()->nome_usu }}"
        data-user-login="{{ auth()->user()->login_usu }}"
        data-user-level="{{ auth()->user()->nivel_usu }}" defer></script>
@endif
