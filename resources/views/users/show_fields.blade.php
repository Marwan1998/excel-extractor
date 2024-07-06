<table class="table">
    <caption></caption>
    <thead>
        <tr>
            <th colspan="2" class="table-title">@lang('models/users.user_info')</th>
        </tr>
    </thead>
    <tbody>
        <tr> <!-- Name Field -->
            <td>@lang('models/users.fields.name'):</td>
            <td>{{ $user->name }}</td>
        </tr>
        <tr> <!-- ID Field -->
            <td>@lang('models/users.fields.id'):</td>
            <td>{{ $user->id }}</td>
        </tr>
        <tr> <!-- Phone Field -->
            <td>@lang('models/users.fields.phone'):</td>
            <td>{{ $user->phone }}</td>
        </tr>
        <tr> <!-- Email Field -->
            <td>@lang('models/users.fields.email'):</td>
            <td>{{ $user->email }}</td>
        </tr>
        {{-- <tr> <!-- Role Field -->
            <td>@lang('models/users.fields.role'):</td>
            <td>{{ $roleName }}</td>
        </tr> --}}
        <tr> <!-- Is Active Field -->
            <td>@lang('models/users.fields.is_active'):</td>
            <td>{{ $user->is_active ? __('models/users.active') : __('models/users.unactive') }}</td>
        </tr>
        <tr> <!-- Email Verified At Field -->
            <td>@lang('models/users.fields.email_verified_at'):</td>
            <td>{{ $user->email_verified_at }}</td>
        </tr>
        <tr> <!-- Created At Field -->
            <td>@lang('models/users.fields.created_at'):</td>
            <td>{{ $user->created_at }}</td>
        </tr>
        <tr> <!-- Updated At Field -->
            <td>@lang('models/users.fields.updated_at'):</td>
            <td>{{ $user->updated_at }}</td>
        </tr>
    </tbody>
</table>

@push('page_css')
    <style>
        .table td, .table th {
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }
        .table tr td:first-child {
            font-weight: bold;
            width: 30%;
        }
        .table-title {
            text-align: center;
            font-size: 18px;
            background-color: #f0f0f0;
        }
        .table tbody tr:hover {
            background-color: #f0f0f0;
        }
    </style>
@endpush
