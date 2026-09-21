<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automatically Rejected Feedback
    |--------------------------------------------------------------------------
    |
    | Add words or phrases that should cause a submission to be rejected before
    | it is sent to the sentiment-analysis API. Matching is case-insensitive and
    | uses whole words/phrases, so "spam" will not match a word such as "spammer".
    |
    | Put the normal spelling in auto_reject_feedback. Deliberate misspellings,
    | abbreviations, and character substitutions belong in auto_reject_variants.
    | Variants are explicit to avoid unsafe fuzzy matches in multilingual text.
    |
    */
    'auto_reject_feedback' => [
        // Filipino / Tagalog
        'tangina',
        'putang ina',
        'bobo',
        'tanga',
        'gago',
        'gaga',
        'ulol',
        'gagi',
        'pota',
        'pucha',
        'ninam',
        'pakyu',
        'tarantado',
        'inutil',
        'packshet',
        'torpe',

        // English
        'fuck you',
        'the fuck',
        'idiot',
        'bullshit',

        // Ilocano / locally used entries
        'ukinna',
        'ukininam',
        'okininam',
        'duldog',
        'gunggong',
        'muno',
        'lusngi',
        'lukdit',
        'langgong',
        'salbag',
        'laglag',
    ],

    'auto_reject_variants' => [
        'tangina' => ['tang ina', 'tngina', 'tangna'],
        'putang ina' => ['putangina', 'ptang ina', 'p*tang ina'],
        'bobo' => ['b0b0', 'b0bo', 'bob0'],
        'tanga' => ['tnga', 't4nga', 't@nga', 'nagtanga'],
        'gago' => ['g4go', 'gag0'],
        'gaga' => ['g4ga', 'gag4'],
        'ulol' => ['ul0l'],
        'fuck you' => ['fuck u', 'fck you', 'f*ck you'],
        'ukinna' => [
            'ukina',
            'ukin ina',
            'uki ni ina',
            'ukinnam',
            'ukinnana',
            'ukinnayo',
            'ukinnada',
            'ukinam',
            'kinnam',
            'kitnam',
        ],
    ],
];
