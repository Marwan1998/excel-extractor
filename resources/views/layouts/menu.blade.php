{{-- <li class="nav-item">
    <a href="{{ route('users.index') }}"
    class="nav-link {{ Request::is('users*') ? 'active' : '' }}">
    <i class="fa fa-users nav-icon"></i>
    <p>@lang('models/users.plural')</p>
    </a>
</li> --}}
<li class="nav-item">
    <a href="{{ route('extractorInterfaces.create') }}"
       class="nav-link {{ Request::is('extractorInterfaces*') ? 'active' : '' }}">
        <p>@lang('models/extractorInterfaces.plural')</p>
    </a>
</li>

