{{-- Finance module design system — Fase 14 --}}
<style>
    .finance-ui {
        --fin-green: #15803d;
        --fin-green-bg: #f0fdf4;
        --fin-yellow: #a16207;
        --fin-yellow-bg: #fffbeb;
        --fin-red: #b91c1c;
        --fin-red-bg: #fef2f2;
        --fin-blue: #1d4ed8;
        --fin-blue-bg: #eff6ff;
        --fin-muted: #64748b;
        --fin-border: #e8edf3;
        --fin-surface: #ffffff;
        --fin-radius: 10px;
    }

    .finance-ui .fin-subnav {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-bottom: 1.25rem;
        padding: .35rem;
        background: var(--fin-surface);
        border: 1px solid var(--fin-border);
        border-radius: var(--fin-radius);
    }

    .finance-ui .fin-subnav a {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .75rem;
        font-size: .8125rem;
        font-weight: 500;
        color: var(--fin-muted);
        text-decoration: none;
        border-radius: 7px;
        transition: background .15s, color .15s;
    }

    .finance-ui .fin-subnav a:hover {
        color: var(--fin-blue);
        background: var(--fin-blue-bg);
    }

    .finance-ui .fin-subnav a.is-active {
        color: var(--fin-blue);
        background: var(--fin-blue-bg);
        font-weight: 600;
    }

    .finance-ui .fin-page-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: .75rem;
        margin-bottom: 1.25rem;
    }

    .finance-ui .fin-page-head h2 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0 0 .15rem;
        color: #0f172a;
    }

    .finance-ui .fin-page-head p {
        margin: 0;
        font-size: .8125rem;
        color: var(--fin-muted);
    }

    .finance-ui .fin-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
    }

    .finance-ui .fin-section-title {
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--fin-muted);
        margin: 0 0 .75rem;
    }

    .finance-ui .fin-panel {
        background: var(--fin-surface);
        border: 1px solid var(--fin-border);
        border-radius: var(--fin-radius);
        overflow: hidden;
    }

    .finance-ui .fin-panel-body {
        padding: 1rem 1.1rem;
    }

    .finance-ui .fin-panel-head {
        padding: .85rem 1.1rem;
        border-bottom: 1px solid var(--fin-border);
        font-size: .9375rem;
        font-weight: 600;
        color: #0f172a;
    }

    .finance-ui .fin-stat {
        background: var(--fin-surface);
        border: 1px solid var(--fin-border);
        border-radius: var(--fin-radius);
        padding: .85rem 1rem;
        height: 100%;
    }

    .finance-ui .fin-stat-label {
        font-size: .8125rem;
        color: var(--fin-muted);
        margin-bottom: .25rem;
    }

    .finance-ui .fin-stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .finance-ui .fin-stat-suffix {
        font-size: .75rem;
        color: var(--fin-muted);
        margin-top: .2rem;
    }

    .finance-ui .tone-success .fin-stat-value { color: var(--fin-green); }
    .finance-ui .tone-warning .fin-stat-value { color: var(--fin-yellow); }
    .finance-ui .tone-danger .fin-stat-value { color: var(--fin-red); }
    .finance-ui .tone-info .fin-stat-value { color: var(--fin-blue); }
    .finance-ui .tone-neutral .fin-stat-value { color: #0f172a; }

    .finance-ui .fin-badge {
        display: inline-flex;
        align-items: center;
        padding: .2rem .55rem;
        font-size: .75rem;
        font-weight: 600;
        border-radius: 999px;
        line-height: 1.3;
    }

    .finance-ui .fin-badge--success { color: var(--fin-green); background: var(--fin-green-bg); }
    .finance-ui .fin-badge--warning { color: var(--fin-yellow); background: var(--fin-yellow-bg); }
    .finance-ui .fin-badge--danger { color: var(--fin-red); background: var(--fin-red-bg); }
    .finance-ui .fin-badge--info { color: var(--fin-blue); background: var(--fin-blue-bg); }
    .finance-ui .fin-badge--neutral { color: #475569; background: #f1f5f9; }

    .finance-ui .fin-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: .25rem;
        padding: .25rem;
        background: #f8fafc;
        border: 1px solid var(--fin-border);
        border-radius: 8px;
        list-style: none;
        margin: 0;
    }

    .finance-ui .fin-tabs a {
        display: block;
        padding: .35rem .7rem;
        font-size: .8125rem;
        color: var(--fin-muted);
        text-decoration: none;
        border-radius: 6px;
    }

    .finance-ui .fin-tabs a.active {
        background: var(--fin-surface);
        color: var(--fin-blue);
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .finance-ui .fin-table {
        margin: 0;
        font-size: .875rem;
    }

    .finance-ui .fin-table thead th {
        font-size: .75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--fin-muted);
        background: #f8fafc;
        border-bottom: 1px solid var(--fin-border);
        padding: .65rem .85rem;
        white-space: nowrap;
    }

    .finance-ui .fin-table tbody td {
        padding: .75rem .85rem;
        border-bottom: 1px solid var(--fin-border);
        vertical-align: middle;
    }

    .finance-ui .fin-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .finance-ui .fin-table tbody tr:hover {
        background: #fafbfd;
    }

    .finance-ui .fin-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .8rem;
        font-size: .8125rem;
        font-weight: 500;
        border-radius: 7px;
        border: 1px solid transparent;
        text-decoration: none;
        cursor: pointer;
        line-height: 1.4;
    }

    .finance-ui .fin-btn--primary { background: var(--fin-blue); color: #fff; }
    .finance-ui .fin-btn--primary:hover { background: #1e40af; color: #fff; }
    .finance-ui .fin-btn--outline { background: #fff; color: var(--fin-blue); border-color: #bfdbfe; }
    .finance-ui .fin-btn--outline:hover { background: var(--fin-blue-bg); color: var(--fin-blue); }
    .finance-ui .fin-btn--success { background: var(--fin-green); color: #fff; }
    .finance-ui .fin-btn--danger { background: var(--fin-red); color: #fff; }
    .finance-ui .fin-btn--warning { background: #d97706; color: #fff; }
    .finance-ui .fin-btn--ghost { background: #fff; color: #475569; border-color: var(--fin-border); }
    .finance-ui .fin-btn--sm { padding: .3rem .65rem; font-size: .75rem; }

    .finance-ui .fin-form-card {
        background: var(--fin-surface);
        border: 1px solid var(--fin-border);
        border-radius: var(--fin-radius);
        padding: 1rem 1.1rem;
        height: 100%;
    }

    .finance-ui .fin-form-card h5 {
        font-size: .9375rem;
        font-weight: 600;
        margin-bottom: .85rem;
    }

    .finance-ui .fin-toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .55rem .7rem;
        border: 1px solid var(--fin-border);
        border-radius: 8px;
        margin-bottom: .5rem;
        font-size: .875rem;
    }

    .finance-ui .fin-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--fin-muted);
        font-size: .875rem;
    }

    .finance-ui .fin-money--positive { color: var(--fin-green); font-weight: 600; }
    .finance-ui .fin-money--info { color: var(--fin-blue); font-weight: 600; }
    .finance-ui .fin-money--warning { color: var(--fin-yellow); font-weight: 600; }
    .finance-ui .fin-money--danger { color: var(--fin-red); font-weight: 600; }

    .finance-ui .fin-modal .modal-content {
        border: 1px solid var(--fin-border);
        border-radius: var(--fin-radius);
        box-shadow: 0 8px 30px rgba(15, 23, 42, .08);
    }

    .finance-ui .fin-modal .modal-header {
        border-bottom: 1px solid var(--fin-border);
        padding: .85rem 1.1rem;
    }

    .finance-ui .fin-action-box {
        border: 1px solid var(--fin-border);
        border-radius: 8px;
        padding: .85rem;
        margin-bottom: .75rem;
        background: #fafbfd;
    }

    @media (max-width: 767.98px) {
        .finance-ui .fin-subnav a span.fin-subnav-label { display: none; }
        .finance-ui .fin-stat-value { font-size: 1.15rem; }
    }
</style>
