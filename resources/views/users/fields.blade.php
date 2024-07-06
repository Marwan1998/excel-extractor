<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('models/users.fields.name').':') !!}
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', __('models/users.fields.email').':') !!}
    {!! Form::email('email', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Phone Field -->
<div class="form-group col-sm-6">
    {!! Form::label('phone', __('models/users.fields.phone').':') !!}
    {!! Form::text('phone', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Password Field -->
@if (Route::is('users.create'))
    <!-- Password Field -->
    <div class="form-group col-sm-6">
        {!! Form::label('password', __('models/users.fields.password').':') !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
    </div>
@endif

<!-- Is Active Field -->
<div class="form-group col-sm-6 check-center">
    <div class="form-check">
        {!! Form::hidden('is_active', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('is_active', 1, isset($user->is_active)?$user->is_active:1, ['class' => 'form-check-input custom-checkbox', 'id' => 'is_active']) !!}
        {!! Form::label('is_active', __('models/users.fields.is_active').':', ['class' => 'form-check-label custom-label', 'for' => 'is_active']) !!}
    </div>
</div>

@if (Route::is('users.edit'))
    <!-- CheckBox Field -->
    <div class="form-group col-sm-12 col-lg-12">
        <div class="form-check">
            {!! Form::checkbox('change_password', 0, null,  ['class' => 'form-check-input custom-checkbox','id'=>'change_password' , 'onclick'=>'showChangePassField()']) !!}
            {!! Form::label('change_password', __('models/users.fields.change_password').':', ['class' => 'form-check-label pr-4 custom-label']) !!}
        </div>
    </div>

    <!-- password Field -->
    <div class="form-group col-sm-6 d-none" id="pass">
        {!! Form::label('password', __('auth.password').':') !!}
        {!! Form::password('password', ['class' => 'form-control','maxlength' => 255]) !!}
    </div>

    <!-- confirm_password Field -->
    <div class="form-group col-sm-6 d-none" id="passConfirm">
        {!! Form::label('password_confirmation', __('auth.confirm_password').':') !!}
        {!! Form::password('password_confirmation', ['class' => 'form-control','maxlength' => 255,]) !!}
    </div>
@endif



@push('page_css')
    <style>
        .form-check {
            display: flex;
            align-items: center;
        }

        .form-check-input.custom-checkbox {
            margin-right: 5px;
        }

        .form-check-label.custom-label:hover {
            color: #007bff;
            cursor: pointer;
        }
        .form-check-input.custom-checkbox:hover {
            color: #007bff;
            cursor: pointer;
        }
        .check-center {
            display: grid;
            justify-content: center;
        }

        .check-center .custom-form-check {
            margin: 0 auto;
        }
        
        #preview{
            max-width: 200px;
            margin :10px;
            /* display: none; */
        }
    </style>
@endpush


@push('page_scripts')
    <script>
        function showChangePassField() {
            var checkBox = document.getElementById("change_password");
            var pass = document.getElementById("pass");
            var passConfirm = document.getElementById("passConfirm");

            if (checkBox.checked == true){
                pass.classList.remove('d-none');
                passConfirm.classList.remove('d-none');
                checkBox.value = 1;
            } else {
                pass.classList.add('d-none');
                passConfirm.classList.add('d-none');
                checkBox.value = 0;
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            $('#is_active').change(function() {
                if ($(this).is(':checked')) {
                    $('input[name="is_active"]').val(1);
                } else {
                    $('input[name="is_active"]').val(0);
                }
            });
        });
    </script>
@endpush