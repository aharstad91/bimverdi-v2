=== BV Bumblebee ===
Contributors: bimverdi
Tags: chat, ai, claude, anthropic, kunnskapsbase, rag, bygg
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.11.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

En chatassistent for byggenæringen som svarer på spørsmål basert på kunnskapskildene på nettstedet (WordPress-innhold, nettsider og PDF-er), med automatisk indeksering og kildehenvisning. Kun for innloggede, registrerte brukere. Drevet av Anthropic Claude.

== Beskrivelse ==

* Automatisk indeksering av kunnskapskilder (WordPress-innhold), med reindeksering når innhold lagres/publiseres/slettes.
* Chat med kildehenvisning – svarene bygger kun på indeksert innhold, og kildene rendres automatisk som klikkbare kort. Henvisninger [1], [2] lenker til riktig kilde.
* Drevet av Anthropic Claude (Messages API).
* Semantisk søk (Voyage/OpenAI), hybrid søk (RRF) og valgfri reranking (Voyage/Cohere).
* Nettsider som kunnskapskilder: innebygd crawler med valgbar dybde (1–3), domene-/sti-begrensning og robots.txt-støtte.
* PDF-uttrekk fra mediebiblioteket og fra PDF-lenker crawleren møter (ren PHP, smalot/pdfparser).
* Temagrupper som hovedkategorier: semantisk kobling av alle ressurser, primær + sekundære, merke på kildekort og admin-oversikt.
* Nattlig automatisk reindeksering (kl 02) via WP-Cron.
* Flytende chat-boble (popup): valgfri site-wide boble i hjørnet, uten kortkode.

== Flytende chat-boble (popup) ==

I stedet for (eller i tillegg til) kortkoden kan du vise chatten som en flytende boble i
hjørnet på hele nettstedet – slik support-chatter fungerer. Slå den på under
Innstillinger → BV Bumblebee → «Visning – flytende chat-boble (popup)»: velg plassering
(høyre/venstre) og om boblen også skal vises til utloggede (de får da en «Logg inn»-boks).
Boblen bruker navnet du har satt under «Navn på assistenten» – sett det til f.eks. «Bumblebee»
hvis du vil ha det navnet på boblen.

Gjenfinning kan kjøres som fulltekst (kun Anthropic-nøkkel), semantisk eller hybrid. Uten embedding-nøkkel faller søket trygt tilbake til fulltekst.

== Automatisering (nattlig reindeksering) ==

Under Innstillinger → BV Bumblebee → «Automatisering» kan du slå på nattlig reindeksering
kl 02 (nettstedets tidssone). Jobben kjører full reindeksering av WordPress-innhold og PDF-er,
oppdaterer embeddings, og (hvis temagrupper er satt opp) re-klassifiserer temagruppene. Du kan
også slå på nattlig re-crawl av nettsider-kildene (av som standard).

Merk at WP-Cron fyrer av på trafikk, ikke som en presis klokke. Har siden lite trafikk kl 02,
kjøres jobben ved første besøk etter kl 02. For presis kjøring, deaktiver WP-Cron og sett opp en
ekte systemcron. Legg i wp-config.php:

`define( 'DISABLE_WP_CRON', true );`

og legg til en cron-oppføring på serveren (kjører wp-cron.php hvert 5. minutt):

`*/5 * * * * wget -q -O - https://bimverdi.no/wp-cron.php?doing_wp_cron >/dev/null 2>&1`

== Krav ==

* WordPress 6.0+, PHP 7.4+
* MySQL 5.6+ / MariaDB 10.0.5+ (InnoDB med FULLTEXT-indeks)
* En Anthropic API-nøkkel (console.anthropic.com)
* Valgfritt: embedding-nøkkel (Voyage/OpenAI) for semantisk søk; rerank-nøkkel (Voyage/Cohere)
* For PDF-uttrekk: PHP-utvidelsene zlib og iconv (standard på de fleste servere)

== Tredjepartsbiblioteker ==

Inkluderer smalot/pdfparser (LGPL-3.0) for PDF-tekstuttrekk, i includes/vendor/pdfparser/.

== Kortkode ==

`[byggchat]`
`[byggchat height="560" placeholder="Still et spørsmål …" title="ByggChat"]`

== Tilgang ==

Kun innloggede brukere. Besøkende som ikke er innlogget ser en «Logg inn»-boks, og REST-endepunktet
avviser forespørsler uten innlogging. Snevre inn med filteret `bbee_user_can_chat`.

== Nøkler i wp-config.php ==

Tryggest er å ikke ha API-nøklene i databasen. Legg dem i wp-config.php:

`define( 'BBEE_ANTHROPIC_KEY', 'sk-ant-…' );`
`define( 'BBEE_EMBED_KEY', '…' );` (valgfritt)
`define( 'BBEE_RERANK_KEY', '…' );` (valgfritt)

Konstantene overstyrer feltene i innstillingene. Når en nøkkel er satt som konstant, vises den aldri i admin, og en eventuell gammel kopi i databasen fjernes ved neste lagring.

== Sperrede domener ==

Crawleren henter aldri innhold fra standard.no, iso.org, cen.eu, cenelec.eu og iec.ch (lisensierte standarder kan ikke gjengis uten avtale), og svar bruker aldri tekst derfra. Flere domener legges til under innstillinger, eller med filteret `bbee_blocked_domains`.

== Spørsmålslogg og tilbakemelding ==

Hvert svar har tommel opp/ned. Spørsmålene logges (maks 500) sammen med hvem som spurte (innlogget bruker, ingen IP) og vises bare for administratorer, med antall kilder og tilbakemelding. Spørsmål uten kilder viser hull i innholdet. Slå av under innstillinger, eller tøm loggen med «Tøm loggen». Spørsmålsteksten sendes til Anthropic og embedding-leverandøren: si fra om det i personvernerklæringen.

== Endringslogg ==

= 0.11.4 =
* Spørsmålsloggen viser hvem som stilte hvert spørsmål (ny kolonne «Bruker»). Gamle oppføringer vises uten bruker. Gir loggen personopplysninger: oppdater personvernerklæringen.

= 0.11.3 =
* Crawleren følger nå lenker som står i PDF-er (klikkbare lenker og adresser i teksten), med samme regler som for nettsider: dybde, «samme domene», stiprefiks og sperrede domener.

= 0.11.2 =
* Tittel og kategorier (bransjekategorier, temagrupper, deltakernivå m.fl.) indekseres nå alltid. Innhold uten brødtekst, som foretak, blir dermed søkbart.
* Nytt innstillingsfelt «Egendefinerte felt som indekseres» (én linje per type: `foretak: felt1, felt2`) og filteret `bbee_meta_fields`. Reindekser etter endring.

= 0.11.1 =
* Pluginen heter nå «BV Bumblebee» (tidligere «ByggChat ALFA pilot»). Mappenavn, innstillinger og kortkode `[byggchat]` er uendret.

= 0.11.0 =
* Tommel opp/ned på hvert svar.
* Anonym spørsmålslogg i admin (av/på, tøm).
* Sperrede domener for crawleren (standard.no, iso.org, cen.eu m.fl.), med eget felt for flere og filteret `bbee_blocked_domains`. Svar bruker heller aldri tekst fra sperrede domener.
* API-nøkler kan settes i wp-config.php (BBEE_ANTHROPIC_KEY, BBEE_EMBED_KEY, BBEE_RERANK_KEY).

= 0.10.1 =
* Ringteksten på boble-knappen endret til «Spør Bumblebee» (én tekst, sentrert øverst) og rotasjonen stoppet.

= 0.10.0 =
* Rettet: utloggede kunne ikke åpne popup-boblen (klikk ga ingen respons) – boblen åpner nå «Logg inn»-boksen som forventet.
* «Spør meg»-tekst som roterende ring rundt bie-logoen på boble-knappen.
* Popup-boblen plasseres øverst til venstre, og vises nå også til utloggede (med «Logg inn»-boks ved klikk).

= 0.9.0 =
* Bumblebee-merkevare: bie-logoen brukes i den flytende boblen og i chat-headeren, og assistenten heter Bumblebee.
* Popup kan nå plasseres i alle fire hjørner; standard er oppe til høyre.
* Engangs-oppsett ved oppgradering: setter navn Bumblebee, plassering oppe til høyre og slår på boblen (kan endres i innstillingene).

= 0.8.0 =
* Flytende chat-boble (popup): valgfri site-wide boble i hjørnet (høyre/venstre), med åpne/lukke, uten kortkode. Vises til innloggede som standard; kan også vises til utloggede med «Logg inn»-boks.

= 0.7.0 =
* Nattlig automatisk reindeksering kl 02 (WP-Cron, nettstedets tidssone), med av/på-bryter og «neste kjøring»-visning.
* Valgfri nattlig re-crawl av nettsider-kilder, og oppfølgende temagruppe-klassifisering.

= 0.6.0 =
* Temagrupper som hovedkategorier med semantisk kobling, merke på kildekort og admin-oversikt.

= 0.5.0 =
* PDF-uttrekk fra mediebibliotek og crawlede PDF-lenker (smalot/pdfparser).

= 0.4.0 =
* Webcrawler for nettsider som kunnskapskilder.

= 0.3.0 =
* Semantisk/hybrid søk og valgfri reranking; strammere systemprompt; evalueringssett.

= 0.2.0 =
* Navn: ByggChat ALFA pilot; tilgang kun for innloggede; løpende versjonsnummer.

= 0.1.0 =
* Første versjon: indeksering, fulltekst, chat via Claude, kildekort.
