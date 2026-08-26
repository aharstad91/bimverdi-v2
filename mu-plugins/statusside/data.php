<?php
/**
 * Statussiden — innholdet.
 *
 * DENNE FILA ER SANNHETEN OM HVOR LANGT VI ER. Den oppdateres via vanlige
 * commits, så git-historikken blir endringsloggen.
 *
 * Tre regler, uten unntak:
 * 1. Aldri `done` uten bevis (fil:linje eller commit) og verifisert-dato.
 * 2. Tvil = `partial`, med notat om hva som konkret mangler i verifiseringen.
 * 3. `godkjentAvTeam` settes KUN etter en menneskelig beslutning i et møte —
 *    aldri av en agent på eget initiativ. Grønn journey krever den.
 *
 * Førstegangs-audit 26.08.2026: hvert `done`-kriterium under er slått opp i
 * koden og har fil:linje. Der bare mekanismen er verifisert — ikke hele flyten
 * kjørt gjennom — står `partial` med notat om nettopp det. Baren starter derfor
 * lavere enn magefølelsen; det er meningen.
 */

if (!defined('ABSPATH')) {
    exit;
}

function bv_status_journeys() {
    return [

        [
            'id'     => 'j1',
            'nr'     => 1,
            'tittel' => 'Ny bruker kommer inn og knyttes til foretaket sitt',
            'aktor'  => 'Ny bruker (blir medlem)',
            'hvorfor' => 'Inngangsdøren. Alt annet i nettverket forutsetter at folk kommer inn og blir koblet til riktig foretak — uten den koblingen får de en halv opplevelse.',
            'steg'   => ['Registrerer seg', 'Bekrefter e-post', 'Slår opp foretak i Brønnøysund', 'Lander på Min side'],
            'kriterier' => [
                [
                    'id' => 'j1-k1',
                    'tekst' => 'E-postadressen må bekreftes før kontoen kan brukes',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-email-verification.php:45',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j1-k2',
                    'tekst' => 'Foretak slås opp mot Brønnøysund på organisasjonsnummer',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-brreg-api.php:66 — REST-rute bimverdi_brreg_get_company',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j1-k3',
                    'tekst' => 'Engangs- og fridomener avvises ved registrering',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-domain-blocklist.php:47',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j1-k4',
                    'tekst' => 'Bruker uten foretak får begrenset, men fungerende tilgang — ikke feilside',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-access-control.php:650 — bimverdi_get_account_type',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j1-k5',
                    'tekst' => 'Hele onboarding-flyten er gjennomgått ende-til-ende med en fersk bruker',
                    'status' => 'partial',
                    'notat' => 'Delene finnes hver for seg i koden, men flyten er ikke kjørt samlet. Trello #300 står fortsatt i «I arbeid».',
                ],
                [
                    'id' => 'j1-k6',
                    'tekst' => 'Fri tekstsøk etter foretak (ikke bare orgnr) ved registrering',
                    'status' => 'missing',
                    'notat' => 'Bård ba om tekstsøk i Brønnøysund-oppslaget, Trello #270. Ikke bygget.',
                ],
            ],
            'godkjentAvTeam' => null,
            'kbLenke' => 'docs/plans/2026-05-20-001-feat-onboarding-grunnmur-blocking-plan.md',
        ],

        [
            'id'     => 'j2',
            'nr'     => 2,
            'tittel' => 'Gratis foretak oppgraderer til betalende deltaker',
            'aktor'  => 'Hovedkontakt i et gratis foretak',
            'hvorfor' => 'Den eneste veien fra gratis medlem til inntekt. Uten en synlig oppgraderingsvei stopper foretakene der de landet, og BIM Verdi får ikke betalt for verdien de faktisk leverer.',
            'steg'   => ['Ser låst innhold', 'Ber om oppgradering fra Min side', 'BIM Verdi varsles', 'Admin godkjenner', 'Nivået settes'],
            'kriterier' => [
                [
                    'id' => 'j2-k1',
                    'tekst' => 'Hovedkontakt kan sende oppgraderingsforespørsel fra Min side',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-foretak-oppgradering.php:212 — bimverdi_handle_oppgradering_submission',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j2-k2',
                    'tekst' => 'Forespørselen lagres som pending på foretaket, med historikk',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-foretak-oppgradering.php:71 og :139',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j2-k3',
                    'tekst' => 'BIM Verdi varsles på e-post når noen ber om oppgradering',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-foretak-oppgradering.php:322',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j2-k4',
                    'tekst' => 'Tilleggskontakt kan også be om oppgradering',
                    'status' => 'missing',
                    'notat' => 'bimverdi_user_can_request_oppgradering krever hovedkontakt (linje 182), og bimverdi_is_hovedkontakt sammenligner mot ACF-feltet hovedkontaktperson (custom-roles.php:121). En tilleggskontakt kommer altså ikke gjennom. Plan finnes: docs/plans/2026-05-22-001.',
                ],
                [
                    'id' => 'j2-k5',
                    'tekst' => 'Admin ser pending forespørsler og setter riktig deltakernivå',
                    'status' => 'partial',
                    'notat' => 'Pending-indikator finnes i foretakslista (admin-enhancements.php:539) og nivå kan settes i quick edit, men selve godkjenningsløypa er ikke gjennomgått samlet.',
                ],
                [
                    'id' => 'j2-k6',
                    'tekst' => 'Gratis foretak møter en tydelig oppgraderings-CTA der innholdet er låst',
                    'status' => 'partial',
                    'notat' => 'Trello #279 beskriver ønsket: CTA på foretak-detalj, dashboard-banner og bedre låst-innhold-melding. Ikke verifisert hvor mye som faktisk står ute.',
                ],
            ],
            'godkjentAvTeam' => null,
            'kbLenke' => 'docs/plans/2026-04-29-001-feat-oppgraderingsvei-manuell-godkjenning-plan.md',
        ],

        [
            'id'     => 'j3',
            'nr'     => 3,
            'tittel' => 'Deltaker registrerer og vedlikeholder verktøyene sine',
            'aktor'  => 'Deltaker med foretak',
            'hvorfor' => 'Verktøykatalogen er det mest konkrete deltakerne får igjen for medlemskapet — synlighet for det de selger. Den må være enkel å fylle og enkel å rydde i.',
            'steg'   => ['Registrerer verktøy', 'Redigerer', 'Sletter', 'Ser det i katalogen'],
            'kriterier' => [
                [
                    'id' => 'j3-k1',
                    'tekst' => 'Verktøy kan registreres fra Min side',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-tool-registration.php:30 — bimverdi_register_tool',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j3-k2',
                    'tekst' => 'Verktøy kan redigeres av eget foretak',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-tool-registration.php:31 — bimverdi_edit_tool',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j3-k3',
                    'tekst' => 'Verktøy kan slettes, både utkast og publiserte',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-tool-registration.php:294 — action=delete_tool, commit 7ab134e',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j3-k4',
                    'tekst' => 'Katalogen filtrerer og paginerer server-side, ikke i nettleseren',
                    'status' => 'done',
                    'bevis' => 'themes/bimverdi-theme/archive-verktoy.php:166, commit 7ebf1e0 (7,9 MB → 302 KB)',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j3-k5',
                    'tekst' => 'AIinAEC Hub-verktøy synkroniseres ukentlig fra Notion',
                    'status' => 'done',
                    'bevis' => 'plugins/bim-verdi-core/includes/aec-ai-hub/class-aihub-cron.php:86 — wp_schedule_event weekly',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j3-k6',
                    'tekst' => 'Deltaker-verktøy og KI-verktøy fra hub-en er visuelt adskilt for besøkende',
                    'status' => 'partial',
                    'notat' => 'Splitten er levert (commit 3757f1b, Trello #321), men ikke revalidert etter at hub-synken gikk live 19.08.',
                ],
            ],
            'godkjentAvTeam' => null,
            'kbLenke' => 'docs/plans/2026-08-18-001-feat-ressurs-rig-verktoy-split-plan.md',
        ],

        [
            'id'     => 'j4',
            'nr'     => 4,
            'tittel' => 'Deltaker deler kunnskap som artikkel',
            'aktor'  => 'Prosjektdeltaker eller partner',
            'hvorfor' => 'Artiklene er det som gjør bimverdi.no til noe annet enn en medlemsliste — de gir fagligheten et sted å bo, og er samtidig grunnlaget for at folk finner nettverket via søk.',
            'steg'   => ['Skriver i Min side', 'Sender til godkjenning', 'BIM Verdi publiserer', 'Artikkelen ligger ute'],
            'kriterier' => [
                [
                    'id' => 'j4-k1',
                    'tekst' => 'Artikkel kan skrives og redigeres fra Min side med rik tekst',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-artikkel-submission.php:31 og :32, commit a11e982',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j4-k2',
                    'tekst' => 'Kun prosjektdeltaker og partner har skrivetilgang',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-access-control.php:41 — PREMIUM-tier, write_article',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j4-k3',
                    'tekst' => 'Rate limit og honeypot stopper misbruk av skjemaet',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-artikkel-submission.php:62',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j4-k4',
                    'tekst' => 'Egen artikkel kan slettes mens den venter på godkjenning',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-artikkel-submission.php:332 — action=delete_artikkel',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j4-k5',
                    'tekst' => 'Admin-godkjenningen (pending → publisert) er gjennomgått',
                    'status' => 'partial',
                    'notat' => 'Tilstandsmaskinen er beskrevet i WORKLOG, men selve godkjenningssteget er ikke verifisert i denne auditen.',
                ],
                [
                    'id' => 'j4-k6',
                    'tekst' => 'Forfatteren kan overføre redigeringsretten til en kollega',
                    'status' => 'missing',
                    'notat' => 'Bårds ønske på Trello #305 og #311. Ikke bygget.',
                ],
            ],
            'godkjentAvTeam' => null,
            'kbLenke' => 'docs/plans/2026-04-13-feat-artikler-min-side-plan.md',
        ],

        [
            'id'     => 'j5',
            'nr'     => 5,
            'tittel' => 'Aktørene diskuterer med hverandre, på tvers av innholdet',
            'aktor'  => 'Alle innloggede',
            'hvorfor' => 'Dette er selve nettverkseffekten. Uten samtale er bimverdi.no en katalog; med samtale er det et sted folk kommer tilbake til. Bygger seg opp sakte, og er samtidig fôr til søkeoptimeringen.',
            'steg'   => ['Leser innhold', 'Skriver innlegg', 'Tagger noen med @navn', 'Abonnerer', 'Får varsel', 'Kommer tilbake'],
            'kriterier' => [
                [
                    'id' => 'j5-k1',
                    'tekst' => 'Diskusjonstråd under alle arrangementer',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-still-sporsmal.php:36, commit 144ceac',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j5-k2',
                    'tekst' => 'Diskusjonstråd under alle artikler',
                    'status' => 'done',
                    'bevis' => 'themes/bimverdi-theme/single-artikkel.php:188, commit 3d3b0d0 — verifisert live på bimverdi.no/artikler/byggchat-fp/',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j5-k3',
                    'tekst' => 'Diskusjonstråd under alle verktøy, både deltakernes og AIinAEC Hub sine',
                    'status' => 'done',
                    'bevis' => 'themes/bimverdi-theme/single-verktoy.php:400, commit 3d3b0d0 — verifisert live på /verktoy/dokkio/ og /verktoy/revit-pure/',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j5-k4',
                    'tekst' => '@-mention med autocomplete gir e-postvarsel til den som nevnes',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-diskusjon-mentions.php:41 + bimverdi-diskusjon-varsler.php',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j5-k5',
                    'tekst' => 'Abonnement på tråd gir varsel om alle nye innlegg, ikke bare mentions',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-diskusjon-abonnement.php, commit 3d3b0d0',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j5-k6',
                    'tekst' => 'Avmelding virker fra postkassen, uten innlogging og uten manuell behandling',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-diskusjon-abonnement.php — HMAC-token, GET bekrefter / POST utfører, List-Unsubscribe',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j5-k7',
                    'tekst' => 'Abonnementsvarsler er sluppet løs på prod',
                    'status' => 'missing',
                    'notat' => 'Gaten er låst: BIMVERDI_DISKUSJON_ABONNEMENT_APEN står ikke i wp-config på Servebolt. Varsler går kun til andreas@aharstad.no til Bård har sett en og gitt go.',
                ],
                [
                    'id' => 'j5-k8',
                    'tekst' => 'Utlogget besøkende ser at det er aktivitet, men aldri selve innholdet',
                    'status' => 'done',
                    'bevis' => 'themes/bimverdi-theme/comments.php:71 (placeholder, aldri tekst i DOM) + REST- og feed-tetting i bimverdi-still-sporsmal.php',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j5-k9',
                    'tekst' => 'Folk bruker den faktisk — det finnes ekte samtaler i trådene',
                    'status' => 'missing',
                    'notat' => 'Bårds jobb, og den viktigste av dem alle: en tom tråd leser som en død plattform. Demoen 27.08 er første forsøk på å få liv i lagene.',
                ],
            ],
            'godkjentAvTeam' => null,
            'kbLenke' => 'docs/plans/2026-08-26-001-feat-diskusjon-artikler-verktoy-abonnement-plan.md',
        ],

        [
            'id'     => 'j6',
            'nr'     => 6,
            'tittel' => 'BIM Verdi når medlemmene på e-post',
            'aktor'  => 'Bård / BIM Verdi',
            'hvorfor' => 'Nettverket er verdiløst hvis ingen får vite hva som skjer i det. Nyhetsbrevet er kanalen Bård selv driver, uten å måtte be om hjelp.',
            'steg'   => ['Skriver nyhetsbrev', 'Forhåndsviser', 'Test-sender', 'Sender til alle', 'Mottaker kan melde seg av'],
            'kriterier' => [
                [
                    'id' => 'j6-k1',
                    'tekst' => 'Nyhetsbrev opprettes som egen innholdstype med fast mal',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-nyhetsbrev-cpt.php:48',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j6-k2',
                    'tekst' => 'Forhåndsvisning før utsending',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-nyhetsbrev-preview.php:26',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j6-k3',
                    'tekst' => 'Masseutsending går via Resend batch-API, utenom wp_mail',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-nyhetsbrev-send.php:10',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j6-k4',
                    'tekst' => 'Besøkende kan melde seg på nyhetsbrevet fra forsiden og foten',
                    'status' => 'partial',
                    'notat' => 'Skjemaet står i foten på alle sider, men koblingen fra skjema til mottakerliste er ikke verifisert i denne auditen.',
                ],
                [
                    'id' => 'j6-k5',
                    'tekst' => 'Eksisterende brukere nudges til å melde seg på',
                    'status' => 'partial',
                    'notat' => 'bimverdi-nyhetsbrev-nudge.php ligger ukommittert lokalt og er ikke deployet. Trello #343.',
                ],
                [
                    'id' => 'j6-k6',
                    'tekst' => 'Avmelding fra nyhetsbrevet virker og respekteres ved neste utsending',
                    'status' => 'partial',
                    'notat' => 'Ikke verifisert. Samme krav som for diskusjonsvarslene (GDPR art. 21) gjelder her.',
                ],
            ],
            'godkjentAvTeam' => null,
            'kbLenke' => 'docs/plans/2026-06-10-001-feat-nyhetsbrev-massesend-motor-plan.md',
        ],

        [
            'id'     => 'j7',
            'nr'     => 7,
            'tittel' => 'Medlem melder seg på et arrangement og møter opp',
            'aktor'  => 'Alle innloggede',
            'hvorfor' => 'Arrangementene er der nettverket faktisk møtes. Påmeldingen må være så enkel at terskelen forsvinner, og beskjeder om endringer må komme fram.',
            'steg'   => ['Finner arrangementet', 'Melder seg på', 'Får bekreftelse med kalenderfil', 'Får beskjed ved endring', 'Møter opp'],
            'kriterier' => [
                [
                    'id' => 'j7-k1',
                    'tekst' => 'Påmelding fra arrangementssiden',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-event-registration.php:18',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j7-k2',
                    'tekst' => 'Bekreftelsen inneholder kalenderfil (.ics)',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-ics-generator.php:168',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j7-k3',
                    'tekst' => 'Avmeldingsfrist håndteres',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-avmeldingsfrist.php',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j7-k4',
                    'tekst' => 'Avlyst arrangement varsler de påmeldte automatisk',
                    'status' => 'done',
                    'bevis' => 'mu-plugins/bimverdi-arrangement-avlyst.php:133',
                    'verifisert' => '2026-08-26',
                ],
                [
                    'id' => 'j7-k5',
                    'tekst' => 'Egne påmeldinger vises sortert på Min side',
                    'status' => 'partial',
                    'notat' => 'Lista finnes, men sorteringen er Bårds åpne ønske på Trello #329.',
                ],
                [
                    'id' => 'j7-k6',
                    'tekst' => 'Lenke til opptak kan legges på arrangementet i etterkant',
                    'status' => 'missing',
                    'notat' => 'Trello #292. Ikke bygget.',
                ],
            ],
            'godkjentAvTeam' => null,
            'kbLenke' => 'docs/plans/',
        ],

    ];
}
