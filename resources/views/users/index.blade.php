@extends('layouts.default')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h2 style="margin-top: 0px;">List of Users</h2>
        </div>
        <div class="col-md-6" style="text-align: right">
            <a href="/users/create" class="btn btn-primary">Add New User</a>
        </div>
    </div>

    @if (request()->updated)
    <div class="alert alert-info" style="margin-top: 15px">
        <strong>User record updated!</strong>
    </div>
    @elseif (request()->created)
    <div class="alert alert-info" style="margin-top: 15px">
        <strong>User record created!</strong>
    </div>
    @elseif (request()->deleted)
    <div class="alert alert-info" style="margin-top: 15px">
        <strong>User record deleted.</strong>
    </div>
    @endif

    <table class="table" style="margin-top: 30px">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Company</th>
                <th scope="col">Gender</th>
                <th scope="col">City</th>
                <th scope="col">Country</th>
                <th scope="col">Role</th>
                <th scope="col">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <th scope="row">{{ $user->name }}</th>
                <td scope="row"><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td>
                <td scope="row">
                    <a href="{{ route('companies.edit', $user->company) }}">{{ $user->company->name }}</a><br>
                    <small>{{ $user->department }}</small>
                </td>
                <td scope="row" style="text-transform: capitalize;">{{ $user->gender }}</td>
                <td scope="row">{{ $user->city }}</td>
                <td scope="row">{{ $user->country }}</td>
                <td scope="row">{{ $user->role }}</td>
                <td scope="row">
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-pencil"></i></a>
                    <form action="{{ route('users.destroy', $user) }}" method="POST" style="display: inline">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                        <button class="btn btn-danger btn-xs" onclick="return confirm('Are you sure you want to delete this user?')"><i class="glyphicon glyphicon-trash"></i></button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $users->links() }}
@endsection