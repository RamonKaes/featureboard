<?php

declare(strict_types=1);

return [
    'confirmed' => ':Attribute und die Bestätigung stimmen nicht überein.',
    'current_password' => 'Das Passwort ist nicht korrekt.',
    'email' => ':Attribute muss eine gültige E-Mail-Adresse sein.',
    'enum' => ':Attribute ist ungültig.',
    'integer' => ':Attribute muss eine ganze Zahl sein.',
    'lowercase' => ':Attribute darf nur Kleinbuchstaben enthalten.',
    'max' => [
        'file' => ':Attribute darf höchstens :max Kilobyte groß sein.',
        'string' => ':Attribute darf höchstens :max Zeichen lang sein.',
    ],
    'min' => [
        'file' => ':Attribute muss mindestens :min Kilobyte groß sein.',
        'string' => ':Attribute muss mindestens :min Zeichen lang sein.',
    ],
    'password' => [
        'letters' => ':Attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':Attribute muss Groß- und Kleinbuchstaben enthalten.',
        'numbers' => ':Attribute muss mindestens eine Zahl enthalten.',
        'symbols' => ':Attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => ':Attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes.',
    ],
    'required' => 'Bitte gib :Attribute an.',
    'string' => ':Attribute muss ein Text sein.',
    'unique' => ':Attribute ist bereits vergeben.',

    'attributes' => [
        'current_password' => 'Aktuelles Passwort',
        'description' => 'Beschreibung',
        'email' => 'E-Mail',
        'except' => 'Ausnahme',
        'name' => 'Name',
        'password' => 'Passwort',
        'status' => 'Status',
        'title' => 'Titel',
    ],
];
