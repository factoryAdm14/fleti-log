<div class="inbox_chat d-flex flex-column">
    <div class="list_filter max-h-100vh-220 overflow-y-auto pb-4">
        @forelse($customerList as $customer)
            <div class="chat_list p-3 d-flex gap-2  bg-soft-secondary customer-conversation"
                 data-channel-id="{{ $customer?->channelUsersToAdmin && $customer?->channelUsersToAdmin->count() > 0 ? $customer?->channelUsersToAdmin[0]?->channel_id : '' }}"
                 data-customer-id="{{ $customer?->id }}">
                <div class="chat_people media gap-10 w-100" id="chat_people">
                    <div class="avatar avatar-sm chat_img rounded-circle position-relative">
                        <img src="{{ onErrorImage(
                                                    $customer?->profile_image,
                                                    dynamicStorage('storage/app/public/customer/profile') . '/' . $customer?->profile_image,
                                                    dynamicAsset('public/assets/admin-module/img/user.png'),
                                                    'customer/profile/',
                                                ) }}"
                             id="" class="avatar-img rounded-circle aspect-1 h-100"
                             alt="">
                    </div>
                    <div class="chat_ib media-body title-color">
                        <h6 class="mb-1 seller active-text fw-semibold" id=""
                            data-name="{{ $customer?->full_name ?? ($customer?->first_name ? $customer?->first_name . ' ' . $customer?->last_name : 'N/A') }}"
                            data-phone="{{ $customer?->phone }}">
                            {{ $customer?->full_name ?? ($customer?->first_name ? $customer?->first_name . ' ' . $customer?->last_name : 'N/A') }}
                            <span
                                    class="fw-medium fs-10 float-end opacity-80">{{ $customer?->channelUsersToAdmin && $customer?->channelUsersToAdmin->count() > 0 && $customer?->channelUsersToAdmin[0]?->last_message ? formatCustomDate( $customer?->channelUsersToAdmin[0]?->last_message?->created_at ?? $customer?->channelUsersToAdmin[0]?->created_at) : '' }}</span>
                        </h6>
                        <div class="fs-12 opacity-50 d-block mb-2" id=""
                             data-name="Will Smith"
                             data-phone="{{ $customer?->phone }}">
                            {{ $customer?->phone }}</div>
                        <div class="d-flex justify-content-between align-items-center gap-10">
                                                    <span
                                                            class="fs-12 line--limit-1">{{ $customer?->channelUsersToAdmin && $customer?->channelUsersToAdmin->count() > 0 && $customer?->channelUsersToAdmin[0]?->last_message ? $customer?->channelUsersToAdmin[0]?->last_message?->message ?? translate('Shared file') : '' }}
                                                    </span>
                            <span
                                    class="new-msg-count {{ $customer?->channelUsersToAdmin && $customer?->channelUsersToAdmin->count() > 0 && $customer?->channelUsersToAdmin[0]?->is_unread_count ? ($customer?->channelUsersToAdmin[0]?->is_unread_count > 0 ? '' : 'd-none') : 'd-none' }}">{{ $customer?->channelUsersToAdmin && $customer?->channelUsersToAdmin->count() > 0 && $customer?->channelUsersToAdmin[0]?->is_unread_count ? $customer?->channelUsersToAdmin[0]?->is_unread_count : '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="d-flex justify-content-center mt-2" id="">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="d-flex flex-column align-items-center gap-20">
                        <img width="38"
                             src="{{ dynamicAsset('public/assets/admin-module/img/svg/user.png') }}"
                             alt="">
                        <p class="fs-12">{{ translate('no customer found') }}</p>
                    </div>
                </div>
            </div>

        @endforelse
    </div>
</div>
