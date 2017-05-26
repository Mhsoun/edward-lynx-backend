<?php

namespace App\Http\Controllers;

use Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::users()
                    ->orderBy('name', 'ASC')
                    ->paginate(10);
        return view('users.index', ['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $companies = User::companies()
                        ->orderBy('name', 'ASC')
                        ->get();
        return view('users.create', [
            'companies' => $companies,
            'user'      => new User
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'firstname'         => 'required|max:127',
            'lastname'          => 'required|max:127',
            'email'             => 'required|email|unique:users,email',
            'gender'            => 'in:male,female',
            'company'           => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('parentId', null);
                }),
            ],
            'department'        => 'max:255',
            'role'              => 'max:255',
            'country'           => 'max:255',
            'city'              => 'max:255',
            'password'          => 'required|same:repeat_password',
            'repeat_password'   => 'required|same:password'
        ]);

        $user = new User;
        $user->fill($request->only(['email', 'gender', 'department', 'role', 'country', 'city', 'info']));
        $user->parentId = $request->company;
        $user->name = $request->firstname . ' ' . $request->lastname;
        $user->password = Hash::make($request->password);
        $user->accessLevel = 3;
        $user->save();

        return redirect(route('users.index'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $companies = User::companies()
                        ->orderBy('name', 'ASC')
                        ->get();
        return view('users.edit', [
            'companies' => $companies,
            'user'      => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Illuminate\Http\Request  $request
     * @param  App\Models\User          $user
     * @return Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->validate($request, [
            'firstname'         => 'required|max:127',
            'lastname'          => 'required|max:127',
            'email'             => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'gender'            => 'in:male,female',
            'company'           => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('parentId', null);
                }),
            ],
            'department'        => 'max:255',
            'role'              => 'max:255',
            'country'           => 'max:255',
            'city'              => 'max:255',
            'password'          => 'required_with:repeat_password|same:repeat_password',
            'repeat_password'   => 'required_with:password|same:password'
        ]);

        $user->fill($request->only('email', 'gender', 'department', 'role', 'country', 'city', 'info'));
        $user->parentId = $request->company;
        $user->name = $request->firstname . ' ' . $request->lastname;

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return redirect(route('users.index', ['updated' => $user->id]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  App\Models\User  $user
     * @return Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect(route('users.index', ['deleted' => $user->id]));
    }
}
