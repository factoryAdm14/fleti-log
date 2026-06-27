<div
    class="tab-pane fade {{Request::is('admin/business/setup/chatting-setup/customer') ? 'show active' : ''}}"
    id="customer">
    <div class="card">
        <div class="collapsible-card-body">
            <div class="card-header flex-md-nowrap flex-wrap d-flex align-items-center justify-content-between gap-2">
                <div class="w-0 flex-grow-1">
                    <h5 class="mb-2 fs-16 text-capitalize">{{ translate('Predefined Q & A') }}</h5>
                    <div class="fs-14">
                        {{ translate('Customer will see some pre-defined messages with answer in the chatting pages') }}
                    </div>
                </div>
                <div class="card-head-group d-flex align-items-center gap-2">
                    <label class="switcher cmn_focus rounded-pill">
                        <input class="switcher_input collapsible-card-switcher update-business-setting"
                               id="customerQuestionAnswerStatus" type="checkbox" tabindex="2"
                               name="customer_question_answer_status"
                               data-name="customer_question_answer_status" data-type="{{ CHATTING_SETTINGS }}"
                               data-url="{{ route('admin.business.setup.update-business-setting') }}"
                            {{ ($settings->firstWhere('key_name', 'customer_question_answer_status')->value ?? 0) == 1 ? 'checked' : '' }}>
                        <span class="switcher_control"></span>
                    </label>
                </div>
            </div>
            <div class="card-body collapsible-card-content">
                <form action="{{ route('admin.business.setup.chatting-setup.question-answer.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="question_answer_for" value="{{ CUSTOMER }}">
                    <div class="p-lg-4 p-3 rounded bg-F6F6F6">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fs-14">{{ translate('Question') }}</label>
                                <textarea name="question" class="form-control" rows="2" maxlength="150" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-14">{{ translate('Answer') }}</label>
                                <textarea name="answer" class="form-control" rows="2" maxlength="500" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary text-capitalize">{{ translate('submit') }}</button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="border-top pt-4 mt-4">
                    <h5 class="text-capitalize mb-20">{{ translate('Question & Answer List') }}</h5>
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="table-light align-middle">
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Question') }}</th>
                                <th>{{ translate('Answer') }}</th>
                                <th>{{ translate('status') }}</th>
                                <th class="text-center">{{ translate('action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($redefinedQAs as $key => $redefinedQA)
                                <tr>
                                    <td class="sl">{{ $key + $redefinedQAs->firstItem() }}</td>
                                    <td>{{ $redefinedQA->question }}</td>
                                    <td>{{ $redefinedQA->answer }}</td>
                                    <td>
                                        <label class="switcher">
                                            <input class="switcher_input custom_status_change" type="checkbox"
                                                   id="{{ $redefinedQA->id }}"
                                                   data-url="{{ route('admin.business.setup.chatting-setup.question-answer.status') }}"
                                                {{ $redefinedQA->is_active == 1 ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-outline-primary btn-action editData" data-id="{{ $redefinedQA->id }}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button data-url="{{ route('admin.business.setup.chatting-setup.question-answer.delete', ['id' => $redefinedQA?->id]) }}"
                                                    type="button" class="btn btn-outline-danger btn-action delete-button">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3">{{ translate('no_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {!! $redefinedQAs->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
