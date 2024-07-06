<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

class UserController extends AppBaseController
{
    public function index(UserDataTable $userDataTable)
    {
        return $userDataTable->render('users.index');
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(CreateUserRequest $request)
    {
        $input = $request->all();
        
        $input['password'] = Hash::make($input['password']);

        User::create($input);

        Flash::success(__('messages.saved', ['model' => __('models/users.singular')]));
        return redirect(route('users.index'));
    }

    public function show($id)
    {
        $user = User::find($id);

        if (empty($user)) {
            Flash::error(__('models/users.singular').' '.__('messages.not_found'));
            return redirect(route('users.index'));
        }

        return view('users.show')->with('user', $user);
    }

    public function edit($id)
    {
        $user = User::find($id);

        if (empty($user)) {
            Flash::error(__('messages.not_found', ['model' => __('models/users.singular')]));
            return redirect(route('users.index'));
        }

        return view('users.edit')->with('user', $user);
    }

    public function update($id, UpdateUserRequest $request)
    {
        $user = User::find($id);

        if (empty($user)) {
            Flash::error(__('messages.not_found', ['model' => __('models/users.singular')]));
            return redirect(route('users.index'));
        }

        $input = $request->all();

        if (isset($input['change_password']) && $input['change_password'] && $input['password'] && $input['password_confirmation']) {
            $input['password'] = Hash::make($input['password']);
        } else {
            unset($input['password']);
        }

        $user->fill($input);
        $user->save();

        Flash::success(__('messages.updated', ['model' => __('models/users.singular')]));
        return redirect(route('users.index'));
    }

    public function destroy($id)
    {
        if ($id == 1) {
            Flash::error(__('messages.cannt_delete'));
            return redirect(route('users.index'));
        }

        $user = User::find($id);

        if (empty($user)) {
            Flash::error(__('messages.not_found', ['model' => __('models/users.singular')]));
            return redirect(route('users.index'));
        }

        $user->email = $user->email.'|'.$user->id.'-deleted';
        $user->phone = $user->phone.'|'.$user->id.'-deleted';
        $user->save();

        $user->delete();

        Flash::success(__('messages.deleted', ['model' => __('models/users.singular')]));
        return redirect(route('users.index'));
    }
}
