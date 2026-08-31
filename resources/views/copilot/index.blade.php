@extends('layouts.app')

@section('title', 'Asistente IA · SuHomes')

@php($showCopilotCosts = (bool) config('services.openai.show_costs', true))

@push('styles')
<style>
    .suhomes-ai {
        --ai-brand: #c23b0a;
        --ai-brand-deep: #9d2f08;
        --ai-ink: #17233d;
        --ai-muted: #77839a;
        --ai-line: #e7eaf0;
        --ai-surface: #fff;
        --ai-soft: #f7f8fb;
        display: grid;
        grid-template-columns: 272px minmax(0, 1fr);
        min-height: calc(100vh - 96px);
        margin: -1.5rem;
        background: var(--ai-surface);
        border: 1px solid var(--ai-line);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 38px rgba(31, 42, 68, .06);
    }

    .suhomes-ai__sidebar {
        display: flex;
        flex-direction: column;
        min-width: 0;
        padding: 18px 13px;
        background: #fbfbfd;
        border-right: 1px solid var(--ai-line);
    }

    .suhomes-ai__brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 5px 9px 16px;
        color: var(--ai-ink);
        font-size: 15px;
        font-weight: 800;
    }

    .suhomes-ai__brand-mark,
    .suhomes-ai__assistant-avatar {
        display: inline-grid;
        place-items: center;
        flex: 0 0 auto;
        color: #fff;
        background: linear-gradient(135deg, #df4d13, #a92e05);
        box-shadow: 0 6px 14px rgba(194, 59, 10, .2);
    }

    .suhomes-ai__brand-mark { width: 30px; height: 30px; border-radius: 10px; }

    .suhomes-ai__new-chat {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 10px 12px;
        color: var(--ai-ink);
        background: #fff;
        border: 1px solid #dfe3eb;
        border-radius: 11px;
        font-weight: 700;
        transition: .18s ease;
    }

    .suhomes-ai__new-chat:hover { color: var(--ai-brand); border-color: #e7b7a4; background: #fff8f5; }
    .suhomes-ai__history-label { padding: 23px 9px 8px; color: #9aa3b3; font-size: 10px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
    .suhomes-ai__history { display: flex; flex-direction: column; gap: 3px; min-height: 0; overflow-y: auto; }
    .suhomes-ai__history-empty { padding: 12px 9px; color: var(--ai-muted); font-size: 12px; line-height: 1.45; }

    .suhomes-ai__conversation {
        display: flex;
        align-items: center;
        gap: 7px;
        width: 100%;
        min-width: 0;
        padding: 9px 8px 9px 10px;
        color: #59657a;
        background: transparent;
        border: 0;
        border-radius: 9px;
        text-align: left;
        transition: .18s ease;
    }

    .suhomes-ai__conversation:hover,
    .suhomes-ai__conversation.is-active { color: var(--ai-ink); background: #f0f2f6; }
    .suhomes-ai__conversation i { color: #9ba5b5; font-size: 14px; }
    .suhomes-ai__conversation-title { overflow: hidden; flex: 1; font-size: 13px; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
    .suhomes-ai__conversation time { color: #a1a9b6; font-size: 10px; white-space: nowrap; }
    .suhomes-ai__costs { margin-top: auto; padding: 16px 9px 0; color: #8a95a6; font-size: 11px; }
    .suhomes-ai__costs strong { display: block; margin-top: 2px; color: #536075; font-size: 12px; }

    .suhomes-ai__main { display: flex; flex-direction: column; min-width: 0; min-height: 0; background: var(--ai-surface); }
    .suhomes-ai__topbar { display: flex; align-items: center; justify-content: space-between; min-height: 68px; padding: 0 28px; border-bottom: 1px solid var(--ai-line); }
    .suhomes-ai__topbar h1 { margin: 0; color: var(--ai-ink); font-size: 15px; font-weight: 800; }
    .suhomes-ai__topbar p { margin: 2px 0 0; color: var(--ai-muted); font-size: 12px; }
    .suhomes-ai__delete { display: inline-flex; align-items: center; gap: 7px; padding: 7px 10px; color: #8e96a4; background: transparent; border: 0; border-radius: 8px; font-size: 12px; }
    .suhomes-ai__delete:hover { color: #bd3535; background: #fff5f5; }
    .suhomes-ai__delete[hidden] { display: none; }

    .suhomes-ai__thread { position: relative; flex: 1; min-height: 0; overflow-y: auto; }
    .suhomes-ai__empty { display: grid; place-content: center; min-height: 100%; padding: 36px 24px 88px; text-align: center; }
    .suhomes-ai__assistant-avatar { width: 48px; height: 48px; margin: 0 auto 20px; border-radius: 15px; font-size: 21px; }
    .suhomes-ai__empty h2 { margin: 0; color: var(--ai-ink); font-size: clamp(25px, 3vw, 34px); font-weight: 800; letter-spacing: -.035em; }
    .suhomes-ai__empty p { max-width: 500px; margin: 11px auto 24px; color: var(--ai-muted); font-size: 14px; }
    .suhomes-ai__suggestions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; width: min(100%, 590px); margin: 0 auto; text-align: left; }
    .suhomes-ai__suggestion { display: flex; align-items: flex-start; gap: 10px; padding: 13px; color: #4e5b70; background: #fff; border: 1px solid #e4e7ed; border-radius: 12px; font-size: 12px; font-weight: 650; line-height: 1.35; transition: .18s ease; }
    .suhomes-ai__suggestion i { color: var(--ai-brand); font-size: 16px; }
    .suhomes-ai__suggestion:hover { color: var(--ai-brand-deep); border-color: #e7baa8; box-shadow: 0 7px 18px rgba(36, 47, 70, .07); transform: translateY(-1px); }

    .suhomes-ai__messages { width: min(100%, 850px); margin: 0 auto; padding: 32px 28px 122px; }
    .suhomes-ai__message { display: flex; gap: 13px; margin-bottom: 25px; }
    .suhomes-ai__message.is-user { flex-direction: row-reverse; }
    .suhomes-ai__message-avatar { display: inline-grid; place-items: center; width: 31px; height: 31px; flex: 0 0 auto; border-radius: 9px; color: #fff; background: #39455a; font-size: 13px; }
    .suhomes-ai__message.is-user .suhomes-ai__message-avatar { background: var(--ai-brand); }
    .suhomes-ai__message-content { max-width: min(80%, 650px); color: #263248; font-size: 14px; line-height: 1.7; white-space: pre-wrap; }
    .suhomes-ai__message.is-user .suhomes-ai__message-content { padding: 11px 14px; color: #fff; background: var(--ai-brand); border-radius: 15px 4px 15px 15px; line-height: 1.55; }
    .suhomes-ai__message-meta { margin-top: 8px; color: #9aa3b2; font-size: 11px; }
    .suhomes-ai__actions { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 12px; }
    .suhomes-ai__action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 9px; color: var(--ai-brand-deep); background: #fff6f2; border: 1px solid #f3d1c4; border-radius: 7px; font-size: 11px; font-weight: 750; text-decoration: none; }
    .suhomes-ai__action:hover { color: var(--ai-brand-deep); background: #feece4; }
    .suhomes-ai__typing { display: flex; gap: 5px; align-items: center; height: 32px; }
    .suhomes-ai__typing span { width: 6px; height: 6px; background: #a3acba; border-radius: 999px; animation: suhomesAiTyping 1s ease-in-out infinite; }
    .suhomes-ai__typing span:nth-child(2) { animation-delay: .13s; }.suhomes-ai__typing span:nth-child(3) { animation-delay: .26s; }

    .suhomes-ai__composer-wrap { padding: 16px 28px 20px; background: linear-gradient(180deg, rgba(255,255,255,0), #fff 22%); }
    .suhomes-ai__composer { display: flex; align-items: flex-end; gap: 10px; width: min(100%, 850px); margin: 0 auto; padding: 8px 9px 8px 16px; background: #fff; border: 1px solid #dce1e9; border-radius: 16px; box-shadow: 0 9px 24px rgba(31, 45, 70, .08); }
    .suhomes-ai__input { display: block; width: 100%; max-height: 130px; min-height: 26px; padding: 4px 0; resize: none; color: var(--ai-ink); background: transparent; border: 0; font-size: 14px; line-height: 1.5; outline: 0; }
    .suhomes-ai__input::placeholder { color: #9ba4b2; }.suhomes-ai__send { display: inline-grid; place-items: center; width: 34px; height: 34px; flex: 0 0 auto; color: #fff; background: var(--ai-brand); border: 0; border-radius: 10px; transition: .18s ease; }.suhomes-ai__send:hover { background: var(--ai-brand-deep); }.suhomes-ai__send:disabled { cursor: wait; opacity: .55; }
    .suhomes-ai__notice { width: min(100%, 850px); margin: 7px auto 0; color: #9ca5b3; font-size: 10px; text-align: center; }
    @keyframes suhomesAiTyping { 0%, 80%, 100% { transform: translateY(0); opacity: .45; } 40% { transform: translateY(-4px); opacity: 1; } }
    [data-bs-theme="dark"] .suhomes-ai { --ai-surface: #1e2938; --ai-soft: #182332; --ai-ink: #f1f5f9; --ai-muted: #a7b1c0; --ai-line: rgba(226,232,240,.12); background: #1e2938; box-shadow: none; }
    [data-bs-theme="dark"] .suhomes-ai__sidebar { background: #172130; } [data-bs-theme="dark"] .suhomes-ai__new-chat, [data-bs-theme="dark"] .suhomes-ai__suggestion, [data-bs-theme="dark"] .suhomes-ai__composer { background: #223044; border-color: rgba(226,232,240,.14); color: #e7edf7; } [data-bs-theme="dark"] .suhomes-ai__conversation:hover, [data-bs-theme="dark"] .suhomes-ai__conversation.is-active { background: #26364d; } [data-bs-theme="dark"] .suhomes-ai__message-content { color: #e7edf7; }
    @media (max-width: 991px) { .suhomes-ai { min-height: calc(100vh - 155px); margin: 0; grid-template-columns: 1fr; border-radius: 16px; }.suhomes-ai__sidebar { display: none; }.suhomes-ai__topbar { min-height: 58px; padding: 0 17px; }.suhomes-ai__messages { padding: 24px 16px 100px; }.suhomes-ai__composer-wrap { padding: 12px 16px 16px; }.suhomes-ai__empty { padding: 25px 16px 70px; }.suhomes-ai__suggestions { grid-template-columns: 1fr; }.suhomes-ai__message-content { max-width: 85%; } }
</style>
@endpush

@section('content')
<div class="suhomes-ai" data-copilot data-history-url="{{ route('copilot.history') }}" data-reset-url="{{ route('copilot.reset') }}" data-chat-url="{{ route('copilot.chat') }}">
    <aside class="suhomes-ai__sidebar" aria-label="Historial de chats">
        <div class="suhomes-ai__brand"><span class="suhomes-ai__brand-mark"><i class="bi bi-stars"></i></span> SuHomes Copilot</div>
        <button type="button" class="suhomes-ai__new-chat" data-copilot-new><i class="bi bi-plus-lg"></i> Nuevo chat</button>
        <div class="suhomes-ai__history-label">Chats recientes</div>
        <div class="suhomes-ai__history" data-copilot-conversations><div class="suhomes-ai__history-empty">Cargando chats…</div></div>
        @if ($showCopilotCosts)
            <div class="suhomes-ai__costs" data-copilot-usage hidden>Uso mensual <strong data-copilot-usage-cost>$0.0000 USD</strong></div>
        @endif
    </aside>

    <section class="suhomes-ai__main">
        <header class="suhomes-ai__topbar">
            <div><h1>Asistente IA</h1><p>Información de SuHomes en tiempo real</p></div>
            <button type="button" class="suhomes-ai__delete" data-copilot-delete hidden><i class="bi bi-trash3"></i> Eliminar chat</button>
        </header>

        <div class="suhomes-ai__thread" data-copilot-thread>
            <div class="suhomes-ai__empty" data-copilot-empty>
                <div>
                    <div class="suhomes-ai__assistant-avatar"><i class="bi bi-stars"></i></div>
                    <h2>¿En qué te podemos ayudar?</h2>
                    <p>Consulta información de tus propiedades, cobranza, gastos, mantenimiento y expedientes.</p>
                    <div class="suhomes-ai__suggestions">
                        <button type="button" class="suhomes-ai__suggestion" data-copilot-prompt="Dame un resumen ejecutivo del sistema hoy."><i class="bi bi-graph-up-arrow"></i><span>Resumen ejecutivo del sistema</span></button>
                        <button type="button" class="suhomes-ai__suggestion" data-copilot-prompt="Que cobranza esta vencida o pendiente?"><i class="bi bi-wallet2"></i><span>Revisar cobranza pendiente</span></button>
                        <button type="button" class="suhomes-ai__suggestion" data-copilot-prompt="Que tickets de mantenimiento urgentes siguen abiertos?"><i class="bi bi-tools"></i><span>Ver tickets urgentes</span></button>
                        <button type="button" class="suhomes-ai__suggestion" data-copilot-prompt="Que documentos vencen en los proximos 30 dias?"><i class="bi bi-folder2-open"></i><span>Documentos por vencer</span></button>
                    </div>
                </div>
            </div>
            <div class="suhomes-ai__messages" data-copilot-messages hidden></div>
        </div>

        <div class="suhomes-ai__composer-wrap">
            <form class="suhomes-ai__composer" data-copilot-form>
                <textarea class="suhomes-ai__input" data-copilot-input rows="1" maxlength="2000" placeholder="Escribe tu pregunta aquí…"></textarea>
                <button type="submit" class="suhomes-ai__send" data-copilot-send aria-label="Enviar mensaje"><i class="bi bi-arrow-up"></i></button>
            </form>
            <div class="suhomes-ai__notice">SuHomes Copilot puede cometer errores. Verifica la información importante.</div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-copilot]');
    if (!root) return;

    const form = root.querySelector('[data-copilot-form]');
    const input = root.querySelector('[data-copilot-input]');
    const send = root.querySelector('[data-copilot-send]');
    const messages = root.querySelector('[data-copilot-messages]');
    const empty = root.querySelector('[data-copilot-empty]');
    const thread = root.querySelector('[data-copilot-thread]');
    const conversations = root.querySelector('[data-copilot-conversations]');
    const newChat = root.querySelector('[data-copilot-new]');
    const deleteChat = root.querySelector('[data-copilot-delete]');
    const usageCost = root.querySelector('[data-copilot-usage-cost]');
    const usage = root.querySelector('[data-copilot-usage]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let conversationId = null;
    let isLoading = false;

    const escapeHtml = (value) => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    const scrollToBottom = () => { thread.scrollTop = thread.scrollHeight; };
    const formatUsd = (value) => `$${Number(value || 0).toFixed(4)} USD`;
    const resizeInput = () => { input.style.height = 'auto'; input.style.height = `${Math.min(input.scrollHeight, 130)}px`; };

    const renderUsage = (summary) => {
        if (!usage || !usageCost || !summary) return;
        usageCost.textContent = formatUsd(summary.month?.estimated_cost_usd);
        usage.hidden = false;
    };

    const renderConversations = (items = []) => {
        conversations.innerHTML = '';
        if (!items.length) {
            conversations.innerHTML = '<div class="suhomes-ai__history-empty">Tus conversaciones guardadas aparecerán aquí.</div>';
            return;
        }
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `suhomes-ai__conversation${item.uuid === conversationId ? ' is-active' : ''}`;
            button.dataset.copilotConversation = item.uuid;
            const date = item.last_activity_at ? new Date(item.last_activity_at) : null;
            const label = date && !Number.isNaN(date.valueOf()) ? date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }) : '';
            button.innerHTML = `<i class="bi bi-chat-left-text"></i><span class="suhomes-ai__conversation-title">${escapeHtml(item.title || 'Nuevo chat')}</span><time>${escapeHtml(label)}</time>`;
            conversations.appendChild(button);
        });
    };

    const showEmpty = () => {
        conversationId = null;
        messages.innerHTML = '';
        messages.hidden = true;
        empty.hidden = false;
        deleteChat.hidden = true;
        conversations.querySelectorAll('[data-copilot-conversation]').forEach((button) => button.classList.remove('is-active'));
        setTimeout(() => input.focus(), 30);
    };

    const showThread = () => {
        empty.hidden = true;
        messages.hidden = false;
        deleteChat.hidden = !conversationId;
    };

    const addMessage = (role, content, meta = {}) => {
        showThread();
        const row = document.createElement('article');
        row.className = `suhomes-ai__message ${role === 'user' ? 'is-user' : 'is-assistant'}`;
        const avatar = role === 'user' ? '<i class="bi bi-person-fill"></i>' : '<i class="bi bi-stars"></i>';
        const actions = Array.isArray(meta.actions) ? meta.actions : [];
        const actionHtml = actions.length ? `<div class="suhomes-ai__actions">${actions.map((action) => `<a class="suhomes-ai__action" href="${escapeHtml(action.url || '#')}"><i class="bi bi-arrow-up-right"></i>${escapeHtml(action.label || 'Ir a la vista')}</a>`).join('')}</div>` : '';
        const toolCount = Number(meta.tool_call_count || 0);
        const metaHtml = role === 'assistant' && toolCount ? `<div class="suhomes-ai__message-meta">${toolCount} consulta${toolCount === 1 ? '' : 's'} al sistema</div>` : '';
        row.innerHTML = `<div class="suhomes-ai__message-avatar">${avatar}</div><div class="suhomes-ai__message-content">${escapeHtml(content)}${actionHtml}${metaHtml}</div>`;
        messages.appendChild(row);
        scrollToBottom();
    };

    const addTyping = () => {
        showThread();
        const row = document.createElement('article');
        row.className = 'suhomes-ai__message is-assistant';
        row.dataset.copilotTyping = 'true';
        row.innerHTML = '<div class="suhomes-ai__message-avatar"><i class="bi bi-stars"></i></div><div class="suhomes-ai__typing"><span></span><span></span><span></span></div>';
        messages.appendChild(row);
        scrollToBottom();
    };

    const setLoading = (value) => { isLoading = value; input.disabled = value; send.disabled = value; };
    const removeTyping = () => messages.querySelector('[data-copilot-typing]')?.remove();

    const loadHistory = async (targetConversationId = null) => {
        try {
            const url = new URL(root.dataset.historyUrl, window.location.origin);
            if (targetConversationId) url.searchParams.set('conversation_id', targetConversationId);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se pudo cargar el historial.');
            const data = await response.json();
            conversationId = data.conversation_id || null;
            renderConversations(data.conversations || []);
            renderUsage(data.usage_summary);
            messages.innerHTML = '';
            if (!Array.isArray(data.messages) || !data.messages.length) { showEmpty(); return; }
            data.messages.forEach((message) => addMessage(message.role, message.content, message.meta || {}));
            showThread();
            scrollToBottom();
        } catch (error) {
            conversations.innerHTML = '<div class="suhomes-ai__history-empty">No se pudo cargar el historial.</div>';
        }
    };

    const sendMessage = async (text) => {
        const message = text.trim();
        if (!message || isLoading) return;
        addMessage('user', message);
        input.value = '';
        resizeInput();
        setLoading(true);
        addTyping();
        try {
            const response = await fetch(root.dataset.chatUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ message, conversation_id: conversationId }) });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo consultar el asistente.');
            conversationId = data.conversation_id;
            renderConversations(data.conversations || []);
            renderUsage(data.usage_summary);
            removeTyping();
            addMessage('assistant', data.message.content, data.message.meta || {});
        } catch (error) {
            removeTyping();
            addMessage('assistant', error.message || 'Ocurrió un error al consultar el asistente.');
        } finally {
            setLoading(false);
            input.focus();
        }
    };

    const deleteConversation = async () => {
        if (!conversationId || isLoading) return;
        if (!window.confirm('¿Eliminar este chat? Esta acción no se puede deshacer.')) return;
        try {
            const response = await fetch(root.dataset.resetUrl, { method: 'DELETE', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ conversation_id: conversationId }) });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo eliminar el chat.');
            conversationId = null;
            renderConversations(data.conversations || []);
            renderUsage(data.usage_summary);
            showEmpty();
        } catch (error) { window.alert(error.message || 'No se pudo eliminar el chat.'); }
    };

    form.addEventListener('submit', (event) => { event.preventDefault(); sendMessage(input.value); });
    input.addEventListener('input', resizeInput);
    input.addEventListener('keydown', (event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); form.requestSubmit(); } });
    newChat.addEventListener('click', showEmpty);
    deleteChat.addEventListener('click', deleteConversation);
    conversations.addEventListener('click', (event) => { const button = event.target.closest('[data-copilot-conversation]'); if (button && button.dataset.copilotConversation !== conversationId) loadHistory(button.dataset.copilotConversation); });
    root.addEventListener('click', (event) => { const button = event.target.closest('[data-copilot-prompt]'); if (button) sendMessage(button.dataset.copilotPrompt || ''); });
    loadHistory();
});
</script>
@endpush
