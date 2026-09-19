{{--@push('update_password_modal')--}}
    @php
    $url = get_update_password_link();
 @endphp
    @component('zerooonez-dashboard::admin-components.admin-modal', [
        'form_id' => 'update_password_form',
        'form_action' => $url,
        'id' => 'update_password_form_modal',
        'title' => __('admin.update_password'),
        'action' => 'edit',
        'call_function' => 'updatePassword',
        'reset_on_close' => true,
        'scroll' => true,
    ])
        @slot('body')
            <div class="row">
                <div class="col-md-6 col-lg-'12' col-12 p-2">
                    <div>
                        <label for="Name" class="form-label">{{__('inputs.password')}}</label>
                        <input type="password" class="form-control" id="update_password_password" placeholder="" name="password" >
                    </div>
                </div>

            </div>
        @endslot
    @endcomponent
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if(window.show_password_modal===1){
                var modalEl = document.getElementById('update_password_form_modal');
                if (modalEl) {
                    var myModal = new bootstrap.Modal(modalEl);
                    myModal.show();
                }
            }
        });
    </script>
{{--@endpush--}}
