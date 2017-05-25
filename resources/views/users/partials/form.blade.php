<?php
$name_parts = explode(' ', $user->name);
$name_count = count($name_parts);
if ($name_count == 1) {
    $firstname = $user->name;
    $lastname = '';
} else {
    $firstname = implode(' ', array_slice($name_parts, 0, $name_count - 1));
    $lastname = $name_parts[$name_count - 1];
}
?>

{{ csrf_field() }}

<div class="row">
    <div class="col-md-6 form-group">
        <label for="firstname">First Name</label>
        <input type="text" name="firstname" id="firstname" class="form-control" value="{{ old('firstname', $firstname) }}" maxlength="85" required autofocus>
    </div>
    <div class="col-md-6 form-group">
        <label for="lastname">Last Name</label>
        <input type="text" name="lastname" id="lastname" class="form-control" value="{{ old('lastname', $lastname) }}" maxlength="85" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
    </div>
    <div class="col-md-6 form-group">
        <label for="gender-x">Gender</label><br>
        <label class="radio-inline">
            <input type="radio" name="gender" value="male" @if(old('gender', $user->gender) == 'male') checked @endif>
            Male
        </label>
        <label class="radio-inline">
            <input type="radio" name="gender" value="female" @if(old('gender', $user->gender) == 'female') checked @endif>
            Female
        </label>
    </div>
</div>

<div class="row">
    <div class="col-md-4 form-group">
        <label for="company">Company</label>
        <select name="company" id="company" class="form-control">
            @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (Input::old('company', $user->parentId) == $company->id ? 'selected' : '') }}>{{ $company->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label for="department">Department</label>
        <input type="text" name="department" id="department" class="form-control" value="{{ old('department', $user->department) }}">
    </div>
    <div class="col-md-4 form-group">
        <label for="role">Role</label>
        <input type="text" name="role" id="role" class="form-control" value="{{ old('role', $user->role) }}">
    </div>
</div>

<div class="row">
    <div class="col-md-4 form-group">
        <label for="country">Country</label>
        <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $user->country) }}">
    </div>
    <div class="col-md-4 form-group">
        <label for="city">City</label>
        <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $user->city) }}">
    </div>
    <div class="col-md-4">&nbsp;</div>
</div>

<div class="row">
    <div class="col-md-12 form-group">
        <label for="info">Info</label>
        <textarea name="info" id="info" class="form-control" rows="5">{{ old('info', $user->info) }}</textarea>
    </div>
</div>

@if ($user->exists)
<div class="row">
    <div class="col-md-6 form-group">
        <label for="password1">New Password</label>
        <input type="password" name="password" id="password1" class="form-control" autocomplete="off">
    </div>
    <div class="col-md-6 form-group">
        <label for="password2">Repeat Password</label>
        <input type="password" name="repeat_password" id="password2" class="form-control" autocomplete="off">
    </div>
</div>
@else
<div class="row">
    <div class="col-md-6 form-group">
        <label for="password1">Password</label>
        <input type="password" name="password" id="password1" class="form-control" autocomplete="off" required>
    </div>
    <div class="col-md-6 form-group">
        <label for="password2">Repeat Password</label>
        <input type="password" name="repeat_password" id="password2" class="form-control" autocomplete="off" required>
    </div>
</div>
@endif