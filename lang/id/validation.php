<?php

/*
| Pesan validasi bahasa Indonesia untuk aturan yang dipakai Rahina (auth starter kit, Tanya Wariga, Profil).
| Aturan lain jatuh ke bahasa Inggris (fallback_locale).
*/

return [
    'accepted' => ':attribute wajib disetujui.',
    'after' => ':attribute harus setelah :date.',
    'before' => ':attribute harus sebelum :date.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date_format' => ':attribute harus berformat :format.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'in' => ':attribute yang dipilih tidak valid.',
    'lowercase' => ':attribute harus huruf kecil.',
    'max' => [
        'array' => ':attribute maksimal :max item.',
        'string' => ':attribute maksimal :max karakter.',
        'numeric' => ':attribute maksimal :max.',
    ],
    'min' => [
        'array' => ':attribute minimal :min item.',
        'string' => ':attribute minimal :min karakter.',
        'numeric' => ':attribute minimal :min.',
    ],
    'password' => [
        'letters' => ':attribute harus memuat minimal satu huruf.',
        'mixed' => ':attribute harus memuat huruf besar dan huruf kecil.',
        'numbers' => ':attribute harus memuat minimal satu angka.',
        'symbols' => ':attribute harus memuat minimal satu simbol.',
        'uncompromised' => ':attribute ini pernah bocor di internet. Pilih :attribute lain.',
    ],
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah dipakai.',

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'question' => 'pertanyaan',
        'birth_date' => 'tanggal lahir',
        'consent_daily' => 'persetujuan ringkasan pagi',
        'consent_birthdate' => 'persetujuan tanggal lahir',
        'category' => 'kategori',
    ],
];
