<?php

declare(strict_types=1);

return [
    'auth_global' => [
        'email' => 'E-Mail',
        'password' => 'Heslo',
        'password_confirm' => 'Potvrdenie hesla',
    ],

    'login' => [
        'title' => 'Prihlásenie',
        'sign_in_text' => 'Prihlásenie do účtu',
        'button' => 'Prihlásenie',
        'forgot_password' => 'Zabudli ste heslo?',
        'passkey_button' => 'Prihlásiť sa prístupovým kľúčom',
    ],

    'password_reset' => [
        'title' => 'Obnova hesla',
        'note' => 'Obnoviť zabudnuté heslo',
        'button' => 'Obnoviť heslo',
        'back_to_login' => 'Späť na prihlásenie',
    ],

    'forgot_password' => [
        'title' => 'Obnova hesla',
        'note' => 'Odoslať e-mail na obnovu hesla',
        'button' => 'Odoslať link na obnovu hesla',
        'back_to_login' => 'Späť na prihlásenie',
    ],

    'activation_form' => [
        'title' => 'Aktivácia účtu',
        'note' => 'Odoslať aktivačný link na e-mail',
        'button' => 'Odoslať aktivačný link',
    ],

    'activations' => [
        'sent' => 'Poslali sme Vám aktivačný link.',
        'activated' => 'Váš účet bol aktitovaný.',
        'invalid_request' => 'Požiadavka bola zamietnutá.',
        'disabled' => "Aktivácia nebola povolená.",
    ],

    'passwords' => [
        'reset' => 'Vaše heslo bolo obnovené.',
        'sent' => 'Poslali sme Vám link na obnovu hesla.',
        'invalid_password' => 'Heslo musí obsahovať najmenej šesť znakov a musí sa zhodovať s potvrdením.',
        'invalid_token' => 'Token na obnovu hesla je neplatný.',
        'invalid_user' => 'Nepodarilo sa nájsť používateľa s touto e-mailovou adresou.',
    ],

    'profile_dropdown' => [
        'profile' => 'Profil',
        'password' => 'Heslo',
        'two_factor' => 'Dvojfaktorové overenie',
        'passkeys' => 'Prístupové kľúče',
        'logout' => 'Odhlásiť sa',
    ],

    'profile' => [
        'title' => 'Upraviť profil',
        'columns' => [
            'first_name' => 'Meno',
            'last_name' => 'Priezvisko',
            'email' => 'E-mail',
            'language' => 'Jazyk',
            'current_password' => 'Súčasné heslo',
        ],
        'select_an_option' => 'Vyberte možnosť',
        'saved' => 'Váš profil bol uložený.',
    ],

    'password_change' => [
        'title' => 'Zmeniť heslo',
        'columns' => [
            'current_password' => 'Súčasné heslo',
            'password' => 'Nové heslo',
            'password_repeat' => 'Potvrdenie nového hesla',
        ],
        'saved' => 'Vaše heslo bolo zmenené. Ostatné prihlásenia boli odhlásené.',
    ],

    'two_factor' => [
        'title' => 'Dvojfaktorové overenie',
        'status_on' => 'Zapnuté',
        'status_off' => 'Vypnuté',
        'enable' => 'Zapnúť dvojfaktorové overenie',
        'enable_description' => 'Po zadaní hesla zadáte aj kód z overovacej aplikácie.',
        'scan_qr' => 'Naskenujte QR kód overovacou aplikáciou a zadajte kód, ktorý zobrazí.',
        'secret_label' => 'Alebo zadajte tento kľúč ručne:',
        'code' => 'Kód',
        'confirm' => 'Potvrdiť',
        'recovery_codes_title' => 'Záchranné kódy',
        'recovery_codes_description' => 'Uložte si tieto jednorazové kódy bezpečne. Znovu sa už nezobrazia.',
        'copy' => 'Kopírovať',
        'copied' => 'Skopírované',
        'download' => 'Stiahnuť',
        'done' => 'Hotovo',
        'current_password' => 'Súčasné heslo',
        'regenerate' => 'Vygenerovať nové záchranné kódy',
        'disable' => 'Vypnúť dvojfaktorové overenie',
        'required_notice' => 'Dvojfaktorové overenie je povinné. Kód vypnete, len kým máte prístupový kľúč.',
        'enrolment_required' => 'Dvojfaktorové overenie je pre váš účet povinné. Zapnite ho, aby ste mohli pokračovať.',
        'challenge_required' => 'Potvrďte prihlásenie kódom dvojfaktorového overenia.',
        'migration_missing' => 'Chýba migrácia: zverejnite migrácie admin-auth a spustite php artisan migrate.',
        'already_enabled' => 'Dvojfaktorové overenie je už zapnuté.',
        'not_enabled' => 'Dvojfaktorové overenie nie je zapnuté.',
        'cannot_disable_required' => 'Dvojfaktorové overenie je povinné. Pred vypnutím kódu pridajte prístupový kľúč.',
        'invalid_code' => 'Kód je neplatný.',
        'invalid_recovery_code' => 'Záchranný kód je neplatný.',
        'enabled' => 'Dvojfaktorové overenie bolo zapnuté.',
        'disabled' => 'Dvojfaktorové overenie bolo vypnuté.',
        'recovery_codes_regenerated' => 'Boli vygenerované nové záchranné kódy.',
        'status_not_set_up' => 'Nenastavené',
        'active_via' => 'Aktívne: :factors',
        'factors' => [
            'totp' => [
                'name' => 'Overovacia aplikácia',
            ],
            'passkey' => [
                'name' => 'Prístupové kľúče',
                'count' => '{1} :count prístupový kľúč|[2,4] :count prístupové kľúče|[5,*] :count prístupových kľúčov',
                'none' => 'Žiadne',
                'manage' => 'Spravovať prístupové kľúče',
                'add' => 'Pridať prístupový kľúč',
            ],
        ],
        'challenge' => [
            'title' => 'Dvojfaktorové overenie',
            'code_text' => 'Zadajte kód z overovacej aplikácie.',
            'recovery_code_text' => 'Zadajte jeden zo svojich záchranných kódov.',
            'code' => 'Kód',
            'recovery_code' => 'Záchranný kód',
            'button' => 'Overiť',
            'use_recovery_code' => 'Použiť záchranný kód',
            'use_code' => 'Použiť overovací kód',
            'passkey_text' => 'Potvrďte prihlásenie prístupovým kľúčom.',
            'use_passkey' => 'Použiť prístupový kľúč',
            'or' => 'alebo',
        ],
    ],

    'passkeys' => [
        'title' => 'Prístupové kľúče',
        'description' => 'Prihlasujte sa odtlačkom prsta, tvárou alebo PIN-om, alebo ich použite ako druhý faktor.',
        'empty' => 'Zatiaľ nemáte žiadne prístupové kľúče.',
        'add' => 'Pridať prístupový kľúč',
        'name' => 'Názov',
        'default_name' => ':browser na :os',
        'current_password' => 'Aktuálne heslo',
        'rename' => 'Premenovať',
        'remove' => 'Odstrániť',
        'remove_confirm' => 'Na odstránenie prístupového kľúča zadajte heslo.',
        'added_at' => 'Pridaný',
        'last_used' => 'naposledy použitý',
        'never_used' => 'nikdy',
        'unsupported' => 'Prístupové kľúče tu nefungujú: potrebujú aktuálny prehliadač a pripojenie cez HTTPS.',
        'migration_missing' => 'Prístupové kľúče potrebujú migráciu: publikujte migrácie admin-auth, potom migrate.',
        'invalid' => 'Prístupový kľúč sa nepodarilo overiť. Skúste to znova.',
        'cannot_remove_last' => 'Dvojfaktorové overenie je pre váš účet povinné a toto je váš posledný druhý faktor.',
        'added' => 'Prístupový kľúč bol pridaný.',
        'renamed' => 'Prístupový kľúč bol premenovaný.',
        'removed' => 'Prístupový kľúč bol odstránený.',
    ],

    'security_reset' => [
        'reset_btn' => 'Zrušiť dvojfaktorové overenie',
        'confirm_title' => 'Zrušiť dvojfaktorové overenie?',
        'confirm_text' => 'Odstráni aplikáciu na overovanie, záchranné kódy a prístupové kľúče administrátora.',
        'current_password' => 'Vaše heslo',
        'confirm_btn' => 'Zrušiť',
        'own_account' => 'Svoj účet meníte na vlastných stránkach dvojfaktorového overenia a kľúčov.',
        'nothing_to_reset' => 'Tento administrátor nemá dvojfaktorové overenie, niet čo zrušiť.',
        'done' => 'Dvojfaktorové overenie administrátora bolo zrušené.',
        'notification' => [
            'subject' => 'Vaše dvojfaktorové overenie bolo zrušené',
            'line' => ':name zrušil(a) vaše dvojfaktorové overenie :time.',
            'enrol' => 'Môžete sa prihlásiť heslom a znova si nastaviť dvojfaktorové overenie.',
            'unexpected' => 'Ak ste o to nežiadali, kontaktujte :name alebo iného administrátora.',
        ],
    ],
];
