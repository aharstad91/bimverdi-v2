# BV lenkevakt 1.0.2

Ukentlig kontroll av de eksterne lenkene på kunnskapskildene på bimverdi.no.

## Hva den gjør

Én gang i uka går pluginen gjennom alle publiserte kunnskapskilder, henter den eksterne lenken fra kilden og sjekker hva serveren svarer.

| Svar | Hva BV lenkevakt gjør |
|---|---|
| 200-299 | Ingenting. Status «OK». |
| 301 / 308 (permanent flyttet) | Skriver den nye adressen inn i lenkefeltet, og tar vare på den gamle i `_lenkevakt_forrige_url`. |
| 302 / 303 / 307 (midlertidig) | Lar lenken stå. Noteres som «Midlertidig omdirigert». |
| 404 / 410, eller dødt domene | Teller som brutt. Etter **2 kjøringer på rad** settes kunnskapskilden til **utkast**. Andre innholdstyper blir aldri avpublisert. |
| 401 / 402 / 403 / 429 | «Trenger blikk». Settes **aldri** til kladd — dette er typisk betalingsmur eller robotsperre, som Standard Norge og byggforsk.no. |
| 5xx og tidsavbrudd | «Trenger blikk». Antas midlertidig. |
| Tomt lenkefelt | «Mangler lenke». Rapporteres, men endrer ingenting. |

## Bare kunnskapskilder kan avpubliseres

Overvåkingen setter **kun kunnskapskilder** til utkast. Innlegg, sider, arrangementer, verktøy og alt annet du huker av blir kontrollert og rapportert, men aldri tatt ned — en brutt lenke i en artikkel er én lenke som må rettes, ikke grunn til å avpublisere teksten.

Sperren ligger i koden, ikke i innstillingene, så den kan ikke slås av ved et uhell. Trenger dere å utvide den bevisst, finnes filteret:

```php
add_filter( 'bv_lenkevakt_posttyper_som_kan_kladdes', function ( $typer ) {
    $typer[] = 'verktoy';
    return $typer;
} );
```

### Opprydding fra 1.0.0

Kjørte 1.0.0 med flere innholdstyper avhuket, kan den ha satt innlegg, sider eller arrangementer til utkast. Første gang du åpner wp-admin etter oppgradering til 1.0.1, publiseres alt slikt automatisk igjen, og du får et varsel som sier hvor mange og av hvilken type. Oppryddingen ser bare på poster som bærer overvåkingens eget merke (`_lenkevakt_draftet`) — altså poster som var publisert da den tok dem ned. Utkast du selv har laget røres ikke.

Vil du kjøre den manuelt: `wp bimverdi lenkevakt reparer`.

Kilder som BV lenkevakt selv har satt til kladd blir sjekket videre. Svarer lenken igjen, står det i rapporten at kilden kan publiseres på nytt — det gjøres av et menneske, ikke automatisk.

## Installering

1. Last opp zip-fila under **Tillegg → Legg til → Last opp tillegg**.
2. Aktiver. Loggtabellen opprettes, og første kjøring planlegges mandag kl. 06:00.
3. Gå til **Kunnskapskilder → BV lenkevakt → Innstillinger** og sjekk at riktig lenkefelt er valgt.
4. Trykk **Kjør kontroll nå** for en første runde.

## Hvor sidene ligger

Normalt havner de som undersider av **Kunnskapskilder**. Er innholdstypen registrert med `show_in_menu` satt til en annen meny — eller til `false` — finnes ikke den forelderen, og da legger pluginen seg som **egen toppnivåmeny** med lenke-ikonet i stedet. Den sjekker de virkelige menyglobalene på `admin_menu`-prioritet 100, så den finner seg selv uansett oppsett.

Uansett hvor menyen ender opp, ligger det snarveier til **Rapport** og **Innstillinger** rett på tilleggets rad i **Tillegg**-lista.

Første gang bør du kjøre med **tørrkjøring** påslått. Da rapporterer BV lenkevakt alt den ville gjort, uten å endre noe. Se over lista, slå av tørrkjøring og kjør på ordentlig.

## Lenkefeltet

BV lenkevakt kjenner ikke feltnavnene i temaet på forhånd. Innstillingssiden viser alle metanøkler på kunnskapskildene som inneholder en http-adresse, med antall kilder per nøkkel — huk av den riktige.

Lar du alt stå uhuket, finner BV lenkevakt selv alle synlige metafelt med en http-adresse på hver enkelt kilde. Det virker, men er tregere og kan treffe felt du ikke mente. Velg feltet eksplisitt når du vet hva det heter.

## Kjøring i bolker

En full runde deles i bolker på 25 kilder med ett minutts mellomrom, så 249 kilder tar rundt ti minutter og legger ikke merkbar last på Servebolt. Køen ligger i `bimverdi_lenkevakt_ko` og overlever at en cron-kjøring blir avbrutt.

WP-Cron trigges av trafikk. Er nettstedet stille på et natt-tidspunkt, kan kjøringen bli forsinket. Vil dere ha den presis, sett opp et ekte cron-kall mot `wp-cron.php` i Servebolt og sett `DISABLE_WP_CRON` til true.

## WP-CLI

```
wp bimverdi lenkevakt kjor          # full runde nå, synkront
wp bimverdi lenkevakt kjor --torr   # samme, men uten å endre noe
wp bimverdi lenkevakt neste         # når går neste ukentlige kjøring?
wp bimverdi lenkevakt reparer       # publiser igjen alt utenom kunnskapskilder som er satt til utkast
```

## Kroker for temaet

```php
do_action( 'bv_lenkevakt_url_oppdatert', $post_id, $meta_nokkel, $gammel_url, $ny_url );
do_action( 'bv_lenkevakt_satt_til_kladd', $post_id, $resultat );
do_action( 'bv_lenkevakt_runde_ferdig', $kjoring );
apply_filters( 'bv_lenkevakt_forespoersel_argumenter', $argumenter, $url, $metode );
```

Filteret er stedet å legge inn egne headere for verter som er kresne på roboter.

## Data pluginen legger igjen

Per kunnskapskilde: `_lenkevakt_status`, `_lenkevakt_http_kode`, `_lenkevakt_sist_sjekket`, `_lenkevakt_feil_paa_rad`, `_lenkevakt_melding`, `_lenkevakt_sjekket_url`, `_lenkevakt_foreslatt_url`, `_lenkevakt_forrige_url`, `_lenkevakt_draftet`.

Historikk i tabellen `wp_bimverdi_lenkevakt_logg`, som ryddes for rader eldre enn ett år etter hver ukentlige runde.

Avinstallering fjerner metafelt, innstillinger og loggtabell. Kilder som er satt til kladd forblir kladd.

## Kjente begrensninger

- **Myke 404-er** fanges ikke. Et nettsted som svarer 200 med «Siden finnes ikke» ser OK ut for BV lenkevakt.
- **Innhold som er byttet ut** oppdages ikke. Lenken kan svare 200 selv om dokumentet bak er et helt annet.
- Vertsnavn kontaktes maks én gang per sekund, men BV lenkevakt leser ikke `robots.txt`.
