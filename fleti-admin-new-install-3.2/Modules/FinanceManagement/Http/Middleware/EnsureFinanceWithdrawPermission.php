<?php

namespace Modules\FinanceManagement\Http\Middleware;

use Brian2694\Toastr\Facades\Toastr;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceWithdrawPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Gate::allows('finance_withdraw_manage')) {
            Toastr::error('Sem permissão para gerenciar saques financeiros.');

            return redirect()->route('admin.finance.withdraws.index');
        }

        return $next($request);
    }
}
