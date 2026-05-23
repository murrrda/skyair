<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name'          => $input['first_name'] . $input['last_name'],
            'email'         => $input['email'],
            'first_name'    => $input['first_name'],
            'last_name'     => $input['last_name'],
            'password'      => $input['password'],
            'date_of_birth' => $input['date_of_birth'],
            'address'       => $input['address'],
        ]);
    }
}
