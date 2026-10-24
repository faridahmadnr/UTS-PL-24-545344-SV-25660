<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo user and sample books.
     */
    public function run(): void
    {
        // 1. Akun Demo Administrator untuk evaluasi dan pengujian login
        User::firstOrCreate(
            ['email' => 'admin@kepl.local'],
            [
                'name'     => 'Farid Admin',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Data Master Buku Awal
        Book::firstOrCreate(
            ['title' => 'Clean Code: A Handbook of Agile Software Craftsmanship'],
            [
                'author'      => 'Robert C. Martin',
                'year'        => 2008,
                'description' => 'Standar industri panduan penulisan kode bersih, refactoring, dan pengujian perangkat lunak.',
            ]
        );

        Book::firstOrCreate(
            ['title' => 'The Pragmatic Programmer: Your Journey To Mastery'],
            [
                'author'      => 'Andrew Hunt & David Thomas',
                'year'        => 1999,
                'description' => 'Prinsip arsitektur dan rekayasa evolusi perangkat lunak modern.',
            ]
        );
    }
}
