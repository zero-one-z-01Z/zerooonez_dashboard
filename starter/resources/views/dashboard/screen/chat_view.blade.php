
@extends('dashboard.layout.admin-main-layout')

@push('head')

    @component('dashboard.layout.parts.admin-include-head', [
    'chat'=>true,
    'support_chat'=>isset($support_chat)
])
    @endcomponent


@endpush
@section('content')



    <div class="content-wrapper" style="overflow: hidden">
        <!-- Content -->
        <div class="main-content">
            <div class="page-content" style=" margin-top: 35px; ">
                <div class="container-fluid">

                    <div class="chat-wrapper d-lg-flex gap-1 mx-n4 mt-n4 p-1">

                        <div class="user-chat w-100 overflow-hidden"  id="chat-container">
                            <div class="chat-content d-lg-flex">
                                <!-- start chat conversation section -->
                                <div class="w-100 overflow-hidden position-relative">
                                    <!-- conversation user -->
                                    <div class="position-relative">

                                        <div class="chat-conversation p-8 p-lg-4 " id="chat-conversation" style="height: {{isset($support_chat)?'85vh':'90vh'}}"
                                             data-simplebar >
                                            <ul class="list-unstyled chat-conversation-list"
                                                id="users-conversation">
                                                @foreach($chat->messages as $message)
                                                    <li class="chat-list @if($message->sender==$type) left @else right @endif">
                                                        <div class="conversation-list">
                                                            @if($message->sender=='user')
                                                                <div class="chat-avatar m-2">
                                                                    <img src="{{$image}}" alt="">
                                                                </div>
                                                            @endif
                                                            <div class="user-chat-content">
                                                                <div class="ctext-wrap">
                                                                    <div class="ctext-wrap-content">
                                                                        @if($message->type=='text')
                                                                            <p class="mb-0 ctext-content">{{$message->message}}
                                                                            </p>
                                                                        @elseif($message->type=='image')
                                                                            <img
                                                                                src="{{$message->message}}"
                                                                                alt="Example Image"
                                                                                onclick="window.open(this.src)"
                                                                                style="height: 200px; width: auto; max-width: 400px; object-fit: contain;">
                                                                        @elseif($message->type=='audio')
                                                                            <audio controls >
                                                                                <source src="{{$message->message}}" type="audio/mpeg">
                                                                                Your browser does not support the audio element.
                                                                            </audio>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="conversation-name"><small
                                                                        class="text-muted time">{{formatCreatedAt($message->created_at)}}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach

                                            </ul>

                                        </div>
                                        @isset($support_chat)
                                            <div class="chat-input-section p-3 p-lg-4" style="padding: 0.5rem!important">
                                                <!-- PREVIEW AREA ABOVE INPUT -->
                                                <div id="previewArea" class="mb-2"></div>

                                                <form method="POST" action="" id="chatinput-form" enctype="multipart/form-data">
                                                    <input type="hidden" name="ticket_id" value="{{$chat->id}}">
                                                    <div class="row g-0 align-items-center">
                                                        <div class="col">
                                                            <input
                                                                type="text"
                                                                name="message"
                                                                class="form-control chat-input bg-light border-light"
                                                                id="chat-input"
                                                                placeholder="Type a message"
                                                                autocomplete="off">
                                                        </div>
                                                        <div class="col-auto d-flex align-items-center">
                                                            <!-- Upload Button -->
                                                            <div class="chat-input-links me-2 ms-2">
                                                                <label for="fileInput" class="btn btn-light mb-0">
                                                                    <i class="icon-base ti tabler-paperclip"></i>
                                                                </label>
                                                                <input
                                                                    type="file"
                                                                    id="fileInput"
                                                                    name="attachment"
                                                                    class="d-none"
                                                                    accept="image/*">
                                                            </div>

                                                            <!-- Send Button -->
                                                            <div class="chat-input-links">
                                                                <button type="submit" class="btn btn-success chat-send waves-effect waves-light align-content-center">
                                                                    <i class="icon-base ti tabler-send"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        @endisset


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')

    @component('dashboard.layout.parts.admin-include-scripts', [
    'chat'=>true,
    'support_chat'=>isset($support_chat),
    'chat_id'=>$chat->id,
    'image'=>$image,
        ])
    @endcomponent

    @php
        $dashboardChatConfig = [
            'support' => isset($support_chat),
            'chatId' => $chat->id,
            'userImage' => $image,
            'storeUrl' => route('admin.tickets.store_message'),
        ];
    @endphp
    <script type="application/json" id="dashboard-chat-config">@json($dashboardChatConfig)</script>
    @dashboardVite('resources/js/back/pages/chat.js')

@endpush
