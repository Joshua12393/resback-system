<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automatically Rejected Feedback
    |--------------------------------------------------------------------------
    |
    | Matching is case-insensitive and uses whole words or phrases. Put normal
    | spellings below and phonetic misspellings in auto_reject_variants. Common
    | symbol substitutions and repeated-letter tricks are normalized separately.
    |
    | Important: every term below causes a hard rejection even when a student is
    | quoting someone else. Review the high-false-positive groups carefully.
    |
    */
    'auto_reject_feedback' => [
        // Filipino / Tagalog profanity and insults
        'tangina',
        'putang ina',
        'bobo',
        'tanga',
        'gago',
        'gaga',
        'gagi',
        'ulol',
        'pota',
        'pucha',
        'ninam',
        'pakyu',
        'tarantado',
        'inutil',
        'packshet',
        'torpe',
        'pokpok',
        'boang',
        'tite',
        'etits',

        // Filipino sexual or anatomical terms — high false-positive risk
        'pekpek',
        'puke',
        'oten',
        'burat',

        // English profanity, insults, and slurs
        'fuck you',
        'the fuck',
        'idiot',
        'bullshit',
        'nigga',
        'dickhead',
        'asshole',
        'shit',
        'imbecile',
        'cunt',
        'freak',

        // English sexual or anatomical terms — high false-positive risk
        'pussy',
        'cock',
        'dick',
        'penis',
        'vagina',

        // Ilocano / locally used profanity and insults
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
        'takki',
        'garampang',
        'angdud',
        'buto',
        'salsal',
        'yutyut',
        'uki',
        'ukel ukel',
        'sang-aw',
        'sal it',
        'ninayo',
        'ninana',

        // Add locally verified terms here, for example: 'adkadawad'.
    ],

    /*
    |--------------------------------------------------------------------------
    | Phonetic, Abbreviated, and Spelling Variants
    |--------------------------------------------------------------------------
    |
    | Symbol-only spellings usually do not need to be listed here because the
    | obfuscation rules below handle them. Keep language-specific sound changes,
    | abbreviations, and omitted letters explicit to limit false positives.
    |
    */
    'auto_reject_variants' => [
        // Filipino / Tagalog
        'tangina' => [
            'tang ina', 'tngina', 'tangna', 'tangena', 'taena', 'tanginam',
            'tanginamo', 'tangina mo', 'tanginanyo', 'tangina nyo',
        ],
        'putang ina' => [
            'putangina', 'ptang ina', 'ptangina', 'potang ina', 'putang ina mo',
            'putanginamo', 'putang ina nyo',
        ],
        'bobo' => ['bubu', 'boboh', 'boboo'],
        'tanga' => ['tnga', 'tangah', 'tangaa', 'nagtanga'],
        'gago' => ['gagu', 'gagoh', 'gagow'],
        'gaga' => ['gagah'],
        'ulol' => ['olol', 'ulul', 'ululol'],
        'pota' => ['puta', 'potah', 'putah'],
        'pucha' => ['pucha ka', 'puchangina', 'puchang ina'],
        'pakyu' => ['pak yu', 'pak u', 'fak yu', 'fakyu'],
        'tarantado' => ['tarantadu', 'trantado'],
        'inutil' => ['enutil'],
        'packshet' => ['pakshet', 'pak shet', 'pakshit', 'pak shit'],
        'pokpok' => ['pok pok'],
        'pekpek' => ['pek pek'],
        'puke' => ['puki'],
        'oten' => ['utin', 'otin'],
        'burat' => ['burat ka'],
        'boang' => ['buang'],

        // English
        'fuck you' => [
            'fuck u', 'fck you', 'fck u', 'fuk you', 'fuk u', 'phuck you',
            'phuck u', 'eff you',
        ],
        'the fuck' => ['dafuq', 'dafuk', 'da fuck'],
        'idiot' => ['idi0t', 'idyot'],
        'bullshit' => ['bull shit', 'bullshet'],
        'nigga' => ['niggah', 'niga', 'niqqa'],
        'dickhead' => ['dick head', 'dikhead', 'dik hed'],
        'asshole' => ['ass hole', 'ashole', 'asswhole'],
        'shit' => ['shet', 'shyt', 'shite'],
        'imbecile' => ['imbisil', 'imbecel'],
        'cunt' => ['kunt'],
        'pussy' => ['pusi'],
        'cock' => ['kok'],
        'freak' => ['frik'],
        'dick' => ['dik'],

        // Ilocano / locally used
        'ukinna' => [
            'ukina', 'ukin ina', 'uki ni ina', 'ukinnam', 'ukinnana',
            'ukinnayo', 'ukinnada', 'ukinam', 'kinnam', 'kitnam',
        ],
        'ukininam' => ['okinninam'],
        'duldog' => ['duldug'],
        'gunggong' => ['gung gong', 'gonggong'],
        'muno' => ['munu'],
        'lusngi' => ['lusnge'],
        'lukdit' => ['lokdit'],
        'langgong' => ['langung', 'lang gong'],
        'salbag' => ['salbak'],
        'laglag' => ['lag lag'],
        'takki' => ['taki'],
        'garampang' => ['garampáng'],
        'angdud' => ['angdod'],
        'salsal' => ['sal sal'],
        'yutyut' => ['yot yot', 'yotyot', 'iyut', 'iyot'],
        'ukel ukel' => ['ukelukel', 'ukel-ukel'],
        'sang-aw' => ['sangaw', 'sang aw'],
        'sal it' => ['sal-it', 'salit'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Obfuscation Normalization
    |--------------------------------------------------------------------------
    |
    | These rules generate additional comparison forms; they do not change the
    | original feedback stored in the database. Capitalization is already ignored.
    |
    */
    'obfuscation' => [
        // Two-letter tokens are too likely to be initials, course codes, or acronyms.
        'minimum_term_length' => 3,
        'strip_inner_symbols' => true,
        'collapse_repeated_characters' => true,
        'symbol_substitutions' => [
            '@' => 'a',
            '4' => 'a',
            '8' => 'b',
            '3' => 'e',
            '1' => 'i',
            '!' => 'i',
            '|' => 'i',
            '£' => 'l',
            '0' => 'o',
            '$' => 's',
            '5' => 's',
            '7' => 't',
            '+' => 't',
        ],
    ],
];
