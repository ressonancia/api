<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationUser>
 */
class OrganizationUserFactory extends Factory
{
    protected $model = OrganizationUser::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::withoutOrganizationCreation()->factory(),
            'role' => Organization::ROLE_MEMBER,
        ];
    }
}
