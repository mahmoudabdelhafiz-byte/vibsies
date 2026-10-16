<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
class UserFactory extends Factory {
 public function definition():array{return ['name'=>fake()->name(),'email'=>fake()->unique()->safeEmail(),'email_verified_at'=>now(),'password'=>bcrypt('password')];}
}
