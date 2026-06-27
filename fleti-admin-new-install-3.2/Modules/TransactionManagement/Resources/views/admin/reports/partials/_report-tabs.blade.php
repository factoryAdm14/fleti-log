<ul class="nav nav--tabs p-1 rounded bg-white" role="tablist">
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.report.earning') }}"
           class="nav-link {{ ($active ?? '') === 'earning' ? 'active' : '' }}">{{ translate('earning') }}</a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.report.expense') }}"
           class="nav-link {{ ($active ?? '') === 'expense' ? 'active' : '' }}">{{ translate('expense') }}</a>
    </li>
    <li class="nav-item" role="presentation">
        <a href="{{ route('admin.report.pix') }}"
           class="nav-link {{ ($active ?? '') === 'pix' ? 'active' : '' }}">{{ translate('pix_transactions') }}</a>
    </li>
</ul>
