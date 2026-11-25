=== BV-pilot/prosjekt ===
Contributors: verdinettverk
Tags: skjema, pilot, prosjekt, BIM Verdi, medlemmer
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.51.0
License: GPL-2.0-or-later

Registrering og oppdatering av BV-pilot/prosjekt (søknadsskisse for rask avklaring) for innloggede deltakere i BIM Verdi.
Erstatter Formidable-skjema 39.

== Beskrivelse ==

* Skjemaeditor i wp-admin: legg til, endre, flytt og slett felt og seksjoner uten å røre kode (kun administrator).
* Innholdstype `bv_pilot_prosjekt` (tittel + forfatter + alle felt som post meta, meta-prefiks `_bvp_`).
* Frontend-skjema via kortkoden `[bv_pilot_prosjekt]` - krever innlogging.
* Brukeren ser sine egne registreringer, kan lagre kladd, sende inn og oppdatere i etterkant.
* Deling per registrering: privat, alle registrerte brukere, utvalgte foretak og brukere, eller publisert for alle.
* Tilleggskontakter hentes fra registrators eget foretak (hovedkontakt og tilleggskontakter) og kan lese og redigere sammen med ham.
* Seksjonene "Tillegg for administrator" og "Effektberegninger" vises kun i wp-admin.
* Filopplasting: skisse/vedlegg til løsningen (bilder + dokumenter) og bilder (kun bilder, adminfelt). Maks 20 MB per fil.
* E-postvarsel til BIM Verdi ved innsending, valgfri kvittering til brukeren.
* Admin-oversikt med status, foretak, kontakt og "Til IN?", filtrering og CSV-eksport (semikolon, UTF-8 BOM - åpner rett i norsk Excel).

== Installasjon ==

1. Last opp zip-filen under Tillegg > Legg til ny > Last opp tillegg, og aktiver.
2. Ved aktivering opprettes en kladd-side "Registrer BV-pilot/prosjekt" med kortkoden. Publiser den, eller sett `[bv_pilot_prosjekt]` inn på en egen side.
3. Gå til BV-pilot/prosjekt > Innstillinger og sett mottakeradresse(r) for varsler.
4. Sørg for at deltakerne har en brukerkonto (rollen Abonnent holder).

== Kortkode ==

`[bv_pilot_prosjekt]`
`[bv_pilot_prosjekt tittel="BV-pilot/prosjekt - rask avklaring"]`

== Status ==

Kladd = innlegg med status "utkast". Innsendt = "publisert". Innholdstypen er ikke offentlig,
så ingenting vises på nettstedet utenom skjemaet.

== Skjemaeditor ==

BV-pilot/prosjekt > Skjemaeditor. Feltoppsettet lagres i `wp_options` under `bvp_form_schema`.
Ligger ingenting der, brukes standardoppsettet fra Formidable-skjema 39.

* Felttyper: tekst, tekstområde, e-post, nettadresse, tall, dato, nedtrekksliste, radioknapper, avkryssing, filopplasting (én fil / flere bilder) og oppslag mot kunnskapskilder.
* Per felt: ledetekst, hjelpetekst, påkrevd, maks tegn, svaralternativer, betingelse ("vis bare hvis felt X = verdi") og skjuling.
* Skjulte felt forsvinner fra skjema, e-post og CSV. Lagrede svar beholdes og vises i metaboksen "Skjulte felt med lagrede svar".
* Per seksjon: tittel, hjelpetekst, om den er synlig for brukeren eller kun for administrator, og skjuling av hele seksjonen.
* Felt flyttes mellom seksjoner med nedtrekkslista «Flytt til seksjon» nederst i feltet. Feltnøkkelen og alle svar følger med.
* Rekkefølge endres med opp/ned-knapper. Sletting krever avkryssing og trer i kraft ved lagring.
* Seksjoner kan kollapses (▸/▾) med «Skjul alle / Vis alle». Dette er bare visning i editoren og påvirker verken skjemaet, lagringen eller rekkefølgen. Tilstanden huskes per nettleser.
* Lagres skjemaet et annet sted mens du har editoren åpen, varsles du i stedet for at endringene overskrives - med valget «Lagre mine endringer likevel».
* Feltnøkkelen er lagringsnøkkel og kan ikke endres. Sletter du et felt, blir svarene liggende i databasen, men vises ikke lenger.
* Forhåndsvisning, eksport/import av oppsettet som JSON, og tilbakestilling til standardoppsettet.

== Foretak ==

Pluginen bruker foretaksprofilene som allerede finnes på nettstedet. Er innholdstypen `foretak` i bruk
(som på bimverdi.no), leses den rett av - både foretaksnavn, organisasjonsnummer, hoveddomene og
koblingen mellom bruker og foretak. Hvilken brukermeta nettstedet bruker for koblingen finnes automatisk
og huskes i `bvp_link_meta_key`. Filteret `bvp_foretak_post_types` styrer hvilke innholdstyper som regnes som foretak.

I tillegg har pluginen sin egen innholdstype `bvp_foretak` (BV-pilot/prosjekt > Foretak) for foretak
som ikke finnes fra før: navn, organisasjonsnummer, e-postdomener og personene i foretaket.
Hver bruker kan kobles til ett foretak med en persontype:

* Hovedkontakt
* Tilleggskontakt
* Bruker
* Gratisbruker

Bare Hovedkontakt og Tilleggskontakt kan velges som tilleggskontakt på en registrering (filter `bvp_kontakttyper`).
Kobles en bruker til et foretak uten at persontype er valgt, blir han automatisk Tilleggskontakt hvis
e-postadressen ligger på et av foretakets domener - ellers Bruker.

Personer kobles fra foretakets egen side, eller fra brukerprofilen i wp-admin. Er registrator ikke koblet til
et foretak, viser skjemaet en nedtrekksliste over foretak med profil, og valget kobler brukeren ved lagring.

== Felttypen Oppslag (kunnskapskilder) ==

Henter kunnskapskilder direkte fra nettstedet (innholdstypen `kunnskapskilde`) og lagrer ID-ene til
de valgte kildene - ikke fritekst. Brukeren søker, får treff mens han skriver, og velger kilder i lista.

Per felt settes i skjemaeditoren:

* **Kildetyper** - standard, veiledning, forskrift, forordning, mal, forskningsrapport, casestudie,
  opplæringsmateriell, verktøydokumentasjon, nettressurs, annet. Ingen valgt = alle.
* **Temagrupper** - valgfri begrensning på taksonomien `temagruppe`.
* **Maks antall kilder** - 0 = ingen grense.

Filtrene håndheves på serversiden, så en manipulert innsending kan ikke lagre kilder utenfor utvalget.
Lesevisning, e-post og CSV viser tittel og utgiver; lesevisningen lenker til kildens eksterne lenke.

Filtre for andre nettsteder: `bvp_lookup_post_type`, `bvp_lookup_taxonomy`, `bvp_lookup_kildetyper`.

== Oversikt over alle registreringer ==

Kortkoden `[bv_alle_prosjekter]` viser alle innsendte registreringer leseren har tilgang til - med søk,
filter på foretak og sidevisning. Utloggede ser bare det som er publisert for alle.

Attributter: `tittel`, `antall` (per side, standard 25), `sok` (ja/nei), `foretak` (ja/nei).

Forskjellen på de tre kortkodene:

* `[bv_pilot_prosjekt]` - skjemaet: registrere og oppdatere egne registreringer
* `[bv_mine_prosjekter]` - personlig liste: mine, kollegers og delt med meg
* `[bv_alle_prosjekter]` - felles oversikt over alt leseren har tilgang til

== Meldinger ==

Hver registrering har et meldingsfelt nederst - både i lesevisningen og i skjemaet. Alle som har
lesetilgang kan lese dialogen; innloggede kan skrive. Meldingene lagres som kommentarer av typen
`bvp_melding` på registreringen, og holdes utenfor vanlige kommentarlister i temaet.

Ved ny melding varsles eier, tilleggskontakter og BIM Verdi (avsenderen selv varsles ikke).
Antall meldinger vises i oversiktene.

== Deling og tilgang ==

Hver registrering har et delingsnivå som registrator (eller administrator) velger i skjemaet:

* **Privat** - kun registrator, tilleggskontakter og administrator. Standard.
* **Alle registrerte brukere** - alle innloggede deltakere kan lese, også fra andre foretak.
* **Utvalgte foretak med foretaksprofil i BIM Verdi** - søk opp foretaket ved å skrive de første bokstavene i navnet
  (eller organisasjonsnummeret) og velg i trefflista. Det valgte foretaket legges til som et kort der du enten gir
  hele foretaket lesetilgang eller krysser av for enkeltpersoner blant foretakets kontakter.
* **Publisert for alle** - lesbar også uten innlogging, via lenken til skjemasiden med `?bvp_view=vis&bvp_id=<id>`.

Kladd deles aldri - deling gjelder først når registreringen er sendt inn. Bare registrator, tilleggskontakter
og administrator kan redigere; andre får lesevisning.

Administrator har to ekstra valg i delingsboksen: **overføre eierskapet** til en annen bruker (nyttig når
administrator legger inn basisinfo og noen andre skal fullføre), og velge **medredaktører fritt**, også fra
andre foretak. Vanlige brukere kan som før bare velge tilleggskontakter i sitt eget foretak.

== Mine prosjekter (min-side) ==

Ferdig modul til min-side, med tre seksjoner: egne registreringer, kollegers registreringer i samme
foretak, og det som er delt med brukeren utenfra - pluss knapp for ny registrering.

* Kortkode: `[bv_mine_prosjekter]` (attributter: tittel, delt, foretak)
* I mal: `bvp_mine_prosjekter( $args )`
* Menypunkt: `bvp_mine_prosjekter_menypunkt()` gir label, slug, url og antall
* Teller: `bvp_mine_prosjekter_antall( $user_id )`

Tilgangsreglene er de samme som ellers: kladd deles aldri, og delte registreringer følger
delingsnivået på hver enkelt registrering.

== Utvidelse ==

* Filter `bvp_sections` - legg til, fjern eller endre felt og seksjoner.
* Filter `bvp_allowed_ext` - tillatte filendelser.
* Action `bvp_after_save` ( $post_id, $values, $is_submit ).

== Endringslogg ==

= 0.51.0 =
* Retter dubletter: etter at en ny registrering var lagret, lå en kopi igjen i nettleseren og ble tilbudt som «uferdig utkast» neste gang man åpnet et tomt skjema. «Hent inn» lagde da en ny registrering med ny ID. Nå fjernes kopien når lagringen lykkes. Ligger den igjen fordi lagringen feilet, sier meldingen at man må sjekke Mine registreringer først.

= 0.50.0 =
* Prosjektkoblingen («Hvilket prosjekt hører caset til?») vises nå overalt der registreringer listes: oversikten /prosjekter/, Mine registreringer, Delt med meg, Mine prosjekter, foretaksprofilen, navnelistene for utloggede og lista i wp-admin.
* Oversikten har nytt filter «Alle prosjekter», og fritekstsøket søker også i prosjektkoblingen.

= 0.49.0 =
* VIKTIG RETTELSE: skjemaene (lagre, send inn, slett, meldinger og tilbakestilling) ble sendt via wp-admin/admin-post.php. Nettstedet sender vanlige medlemmer bort fra wp-admin til Min side, så innsendingen forsvant før den ble lagret. Bare administratorer fikk lagret. Nå sendes skjemaene til nettsiden selv.
* Gikk innloggingen ut før lagring, blir det som er skrevet nå tatt vare på, og brukeren får det tilbake etter innlogging.

= 0.48.0 =
* Foretaksprofilen viser øverst «Prosjekter og caser»: prosjektene foretaket eier, og prosjektene der noen fra foretaket er medredaktør. Leseren ser lenke bare til det han har tilgang til. Private prosjekter og kladder vises bare for dem som kan lese dem.
* Ny kortkode [bv_foretak_prosjekter] for å plassere lista direkte i temaets foretaksmal. Uten kortkoden flyttes lista øverst med et lite skript.

= 0.47.0 =
* Tellingen uten innlogging tar med kladder, og viser alle kategoriene, også de som står på null.
* «Kan leses av alle registrerte brukere» og «Vises bare for utvalgte deltakere» vises alltid, med «Ingen ennå» når lista er tom.

= 0.46.0 =
* Oversikten uten innlogging vises nå også øverst på sider med skjemaet [bv_pilot_prosjekt], som bimverdi.no/prosjekter/. Før kom bare innloggingsboksen. Ligger begge kortkodene på samme side, vises oversikten én gang.

= 0.45.0 =
* Oversikten uten innlogging viser øverst hvor mange prosjekter og caser som er registrert, fordelt på synlighet.
* Deretter kommer prosjektene som er publisert for alle, og navnene på prosjektene som krever innlogging: de som alle registrerte brukere kan lese, og de som bare er for utvalgte deltakere. Private prosjekter telles, men navnene vises ikke.

= 0.44.0 =
* Skjemaeditoren viser øverst hvilket felt som gir registreringene navn, og varsler hvis det er et langt tekstfelt.
* Bytter du tittelfelt, får alle registreringer nytt navn fra det nye feltet. Før ble bare de uten navn oppdatert.
* Adressen til prosjektsiden følger navnet. Den gamle adressen sendes videre til den nye.
* Kopier feltet tar ikke lenger med «Bruk som tittel i oversiktene».

= 0.43.0 =
* Prosjektsiden viser medredaktørene i «Registrert av», under foretaket som eier prosjektet. Hver medredaktør vises med navn og foretak, og foretaket lenker til foretaksprofilen.

= 0.42.0 =
* VIKTIG RETTELSE: eierskapet flyttet seg til den som sist lagret. Lagret en medredaktør en annens registrering, ble medredaktøren eier - og den opprinnelige eieren kunne miste den fra lista si, eller miste tilgangen helt. Nå endres eier bare med eiervelgeren.
* Engangsreparasjon: der den som registrerte (e-posten i skjemaet) ikke lenger er eier, legges vedkommende til som medredaktør, så ingen er uten tilgang. Ingen eier byttes automatisk - wp-admin viser en liste administrator kan gå gjennom.
* «Delt med meg» viser nå også kladd du er medredaktør på, med statuskolonne. Før var de usynlige for alle andre enn eieren.
* Mister du tilgangen mens du redigerer, går ikke teksten tapt: den åpnes i et nytt skjema, klar til å lagres som kladd.
* Ny innstilling (på som standard): BIM Verdi får e-post når noen starter en ny registrering, ikke bare når den sendes inn.
* «Send inn» med manglende påkrevde felt sier nå tydelig at registreringen IKKE er sendt inn, men lagret som kladd.

= 0.41.0 =
* Ny endringslogg på hver registrering: hvem endret hva, og når. Hver hendelse viser feltene som ble endret, med gammel og ny verdi.
* Den som kan redigere, kan tilbakestille en enkelt endring. Tilbakestillingen loggføres selv, så ingenting forsvinner sporløst.
* Loggen tar vare på de 300 siste hendelsene per registrering, og viser også når den ble opprettet og sendt inn.
* Nytt: @-nevning i meldingsfeltet. Skriv @ og de første bokstavene, så kommer lista over dem som har tilgang - eier, medredaktører, enkeltpersoner og kontakter i foretak som er gitt tilgang.
* De som nevnes får sin egen e-post («X nevnte deg»), og teller ikke dobbelt i det vanlige meldingsvarselet. Navnene utheves i meldingen.
* Under meldingsfeltet står det hvem som har tilgang, så man ser hvem man kan nevne.

= 0.40.0 =
* «Vis feltet bare hvis» kan nå styres av avkryssing (flere valg) og nedtrekksliste, ikke bare radioknapper.
* For avkryssing gjelder betingelsen når verdien er én av de avkryssede - f.eks. vises feltet når «ByggChat» er krysset av, uansett hva annet som er valgt.
* Virker overalt: vis/skjul mens du fyller ut, validering av påkrevde felt på serveren, lesevisning, e-post og CSV-eksport. Felt som ikke gjelder kreves ikke, vises ikke og står tomme i eksporten.
* Rettet: et påkrevd felt som var skjult av en betingelse kunne blokkere innsendingen uten synlig feilmelding.
* Skjemaeditoren viser felttypen i lista over styrende felt, og foreslår svaralternativene når du skriver verdien.

= 0.39.2 =
* «Lagre som kladd» ligger nå i rammelinja øverst i seksjonen, på linje med overskriften, i stedet for å dekke hjelpeteksten under.

= 0.39.1 =
* Dikteringen lytter nå på norsk. Nettstedet kjører med lang="en-US", og talegjenkjenningen fulgte den, så alt ble tolket som engelsk.
* Språket kan endres med filteret `bvp_diktering_sprak` (standard nb-NO). Er sidespråket norsk, brukes det.

= 0.39.0 =
* Når en bruker kobles til et foretak, skrives foretakets ID nå også til nettstedets egne nøkler: bimverdi_company_id, bim_verdi_company_id og tilknyttet_foretak - i tillegg til pluginens egen og den som eventuelt er oppdaget fra før.
* Kontotypen (bimverdi_account_type) settes til «foretak».
* Rollen løftes fra «medlem» (eller tom) til «tilleggskontakt». Hovedkontakt og tilleggskontakt står urørt.
* Fjernes koblingen, ryddes alle nøklene bort igjen.
* Nye filtre: `bvp_foretak_meta_keys`, `bvp_kontotype_meta_key`, `bvp_kontotype_verdi`, `bvp_svake_persontyper`, og ny action `bvp_bruker_koblet` ( $user_id, $foretak_id, $persontype ).

= 0.38.1 =
* Diktering: teksten kom ikke inn i feltet. Nå vises det som gjenkjennes mens du snakker, ferdige setninger settes inn der markøren står, og dikteringen starter automatisk igjen når nettleseren tar pause - helt til du trykker stopp selv.
* Knappen vises også i nettlesere uten talegjenkjenning, men deaktivert og med forklaring, i stedet for å mangle uten grunn.
* Tydeligere beskjeder: nektet mikrofon, ingen mikrofon funnet og nettverksfeil forklares hver for seg.

= 0.38.0 =
* «Lagre som kladd» står nå øverst til høyre i hver seksjon, så du kan lagre underveis i et langt skjema uten å rulle til bunnen.
* Knappen lagrer hele skjemaet, og du kommer tilbake til den samme seksjonen etterpå. Påkrevde felt stopper ikke en kladd.

= 0.37.0 =
* Ny «Snakk inn»-knapp på tekstområder og lengre tekstfelt: snakk inn svaret i stedet for å skrive. Teksten legges til der du står, og maksgrensen holdes.
* Knappen bruker nettleserens egen talegjenkjenning og vises bare der den finnes (Chrome og Edge på PC). Mangler den, er alt som før.
* Alle tekstfelt som hadde 700 tegns grense er utvidet til 1500. Andre grenser står urørt, og endringen kjøres bare én gang.

= 0.36.0 =
* «Foretak som eier prosjektet» vises nederst i seksjonen «Registrert av» på prosjektsiden, lenket til foretaksprofilen på bimverdi.no.
* Navnet hentes fra foretaksprofilen eieren er koblet til - mangler den, vises raden ikke.

= 0.35.2 =
* Flere svar i samme felt - temagrupper, standarder, verktøy, vedlegg - vises nå som punktliste med ett svar per linje, ikke som komma-rekke.
* «Les mer»-lenker som peker tilbake til siden man står på fjernes. De kommer fra temaets standardmal og førte i ring.

= 0.35.1 =
* Rettet: i blokktemaer uten egen mal for innholdstypen ble registreringen vist som et avkortet utdrag der all formatering var strippet bort, med «Les mer». Nå vises hele registreringen, og «Les mer» er borte.
* Lesevisningen er strammet opp: ledetekst til venstre og svaret til høyre i to kolonner, tynne skillelinjer, grønn seksjonsoverskrift og mindre luft. Én kolonne på mobil.
* Tomme og skjulte felt vises fortsatt ikke, og seksjoner uten svar hoppes over.

= 0.35.0 =
* Hver innsendt registrering har nå sin egen side med lesbar adresse: https://bimverdi.no/prosjekt/<navn>/. «Åpne» og «Les» i alle oversiktene går dit, og det samme gjør lenkene i e-postvarslene.
* Siden viser ID, hvem som har registrert, deling, alle svarene og meldingsfeltet. Den som kan redigere får en «Rediger registreringen»-knapp til skjemaet.
* Tilgangen er den samme som før: delingsnivået bestemmer hvem som kan lese, og utloggede som mangler tilgang sendes til innlogging og rett tilbake etterpå.
* Kladd har ingen offentlig adresse og åpnes i skjemaet som før.
* Lenkereglene skrives om automatisk ved oppdatering. Virker ikke adressene med en gang, åpne Innstillinger → Permalenker og lagre.

= 0.34.1 =
* VIKTIG RETTELSE: nedtrekkslister, radioknapper, avkryssinger, tall- og datofelt ble vist som vanlige tekstfelt i skjemaet fra og med 0.24.0. Alle felttypene virker igjen.
* Et lagret flervalgssvar i et felt som har byttet type vises nå som tekst, ikke som «Array».
* Et valgfelt uten svaralternativer slettes ikke lenger ved lagring: det beholdes som tekstfelt, og skjemaeditoren sier fra - både mens du skriver og etter lagring.

= 0.34.0 =
* Ledeteksten i skjemaet er nå grønn og større, og hjelpeteksten under står i svart.
* Hjelpeteksten kan inneholde enkel HTML, så lenker blir klikkbare: a, strong, b, em, i, br, code og small. Alt annet fjernes ved lagring.
* Lenker i hjelpeteksten åpnes i ny fane, så ingen mister et halvutfylt skjema ved å klikke. Skriver du target eller rel selv, beholdes det.
* Hjelpetekstfeltet i skjemaeditoren er nå et tekstområde med forklaring av hva som er lov.
* Nytt filter `bvp_desc_tags` for å endre hvilke tagger som tillates.

= 0.33.0 =
* Alle oversikter har fått en ID-kolonne først: #6358. Gjelder lista på /prosjekter/, «Delt med meg», Mine prosjekter og kollegalista på min side, fellesoversikten og lista i wp-admin (sorterbar der).
* ID-en vises også øverst i den enkelte registreringen, både i skjemaet og i lesevisningen, så den er lett å oppgi ved spørsmål.

= 0.32.0 =
* Valg i oppslagsfelt som ikke kan lagres forsvinner ikke lenger i det stille: skjemaet sier «Dette valget kunne ikke lagres» med tittel, felt og grunn - feil kilde, ikke publisert, slettet, eller over maksgrensen. Samme melding i wp-admin.
* Maksgrensen kutter som før de overskytende, men nå med beskjed om hvor mange feltet tar.
* Eierbytte: den nye eieren kan redigere og slette som før, og den forrige eieren blir automatisk stående som medredaktør, slik at arbeidet hans ikke blir utilgjengelig.
* Ny action `bvp_eier_byttet` ( $post_id, $ny_eier, $forrige_eier ).

= 0.31.0 =
* Utfylte skjemaer går ikke lenger tapt når innloggingen går ut: innholdet tas vare på, brukeren logger inn på nytt og får skjemaet tilbake ferdig utfylt, med beskjed om hva som skjedde.
* Samme redning ved for gammel sikkerhetsnøkkel. Ingenting lagres på registreringen uten gyldig nøkkel - mellomlagringen varer i to timer og kan bare hentes én gang.
* Nytt: skjemaet tar en lokal sikkerhetskopi i nettleseren mens du skriver. Kommer du tilbake med et uferdig utkast, kan du hente det inn eller forkaste det. Kopien slettes når registreringen er lagret. Oppslagsfelt og filopplastinger er ikke med.

= 0.30.0 =
* Oversiktene har to nye kolonner: Prosjekteier (foretaksnavnet, lenket til foretaksprofilen på bimverdi.no) og Synlighet (Alle, Reg. brukere, Utvalgte, Privat - og «Ikke delt» for kladd).
* Er registreringen delt med nettopp deg som utvalgt bruker eller foretak, står det «Utvalgte (deg)».
* Foretaket hentes fra foretaksprofilen eieren er koblet til; står ingen profil, brukes foretaksnavnet som er skrevet i skjemaet.
* Gjelder lista på /prosjekter/, «Delt med meg», Mine prosjekter på min side og fellesoversikten.

= 0.29.0 =
* Medredaktører velges nå i to steg med autofullføring: skriv de første bokstavene i foretaksnavnet, velg foretak, og velg personen i lista over de registrerte brukerne der. Den lange rullelista er borte.
* Valgte medredaktører står som egne rader med navn og foretak, og kan fjernes enkeltvis.
* «Finn i hele brukerlista i stedet» er beholdt som reserve, for brukere uten foretakskobling.
* Rettet: tomme delingslister ga ID-en 0, slik at «ingen valgt»-teksten ikke dukket opp.

= 0.28.0 =
* Tittelen i oversiktene hentes fra feltet du selv velger: kryss av «Bruk som tittel i oversiktene» på et tekstfelt i skjemaeditoren (f.eks. «Kort navn på pilot/case/prosjekt»). Bare ett felt kan ha den rollen.
* Uten avkryssing brukes «soknadstittel» hvis feltet finnes, ellers første tekstfelt som heter noe med «tittel», «kort navn» eller «navn på».
* Registreringer som står som «BV-pilot/prosjekt uten tittel» får navnet sitt automatisk ved oppdatering av pluginen og hver gang skjemaet lagres i editoren.
* Titler ryddes: linjeskift blir mellomrom, og svært lange navn kortes ned.

= 0.27.0 =
* Registreringer kan slettes: «Slett registreringen» nederst i skjemaet, med en avkryssing som må hukes av først.
* Bare den som eier registreringen, og BIM Verdi (administrator), kan slette. Medredaktører og tilleggskontakter kan fortsatt redigere, men ikke slette.
* Slettede registreringer legges i papirkurven i wp-admin, så de kan hentes tilbake. De forsvinner fra alle oversikter og kan ikke åpnes eller redigeres.
* BIM Verdi får e-postvarsel når en registrering slettes (hvis varsling er slått på i innstillingene).

= 0.26.0 =
* Stegvis søk (autofullføring) i oppslagsfeltet: lista snevres inn for hver bokstav, fra første tegn. Bokstavene du har skrevet uthevet i treffene, og treff du alt har valgt er merket.
* Statuslinja sier «Viser 20 av 143 treff - skriv mer for å snevre inn», så du ser når du må skrive mer.
* Når hele trefflista er hentet, snevres den inn i nettleseren uten nye søk mot serveren. Foreldede svar forkastes hvis du skriver videre mens søket er underveis.
* Rettet: søkefeltets hjelpetekst ble overskrevet med «Søk i kunnskapskildene ...» selv om feltet søkte i en annen kilde, og oppsettet kunne bli kjørt to ganger slik at hvert søk ble sendt dobbelt.

= 0.25.0 =
* «Hvem eier registreringen?» er delt i to steg: først foretaket, så personen. Foretaket finnes ved å skrive de første bokstavene i navnet eller organisasjonsnummeret - treffene kommer etter hvert som du skriver.
* Personlista fylles med de registrerte brukerne i det valgte foretaket, med persontype. Tømmer du foretaksfeltet, kan du velge blant alle registrerte brukere som før.
* Dagens eier står alltid i lista, også om vedkommende mangler kobling til foretaket.

= 0.24.0 =
* Valgte treff i oppslagsfelt kan merkes: skriv merkegruppene i skjemaeditoren («Status: Pålagt, Anbefalt» på egen linje), og hvert valgt treff får en nedtrekksliste per gruppe. Merkene vises i lesevisning, e-post og CSV-eksport.
* Rettet: «Hva skal det søkes i?», meta-filteret og ID-ene ble forkastet ved lagring i skjemaeditoren, så et felt som skulle søke i verktøy falt tilbake til kunnskapskilder.
* Svar som bare er lagret som ID-er virker uendret.

= 0.23.0 =
* Hvert felt og hver seksjon har nå et fast ID-nummer som vises i skjemaeditoren (#42 på felt, S7 på seksjoner). ID-en er en unik referanse som ikke endres om ledetekst, nøkkel eller plassering endres. En kopi får sin egen ID.
* Oppslagsfeltet kan nå søke i andre innholdstyper enn kunnskapskilder - velg kilde i editoren, f.eks. "Verktøy og digitale tjenester" (/verktoy/).
* Oppslagsfeltet kan begrenses på kategorier (alle taksonomier kilden har, f.eks. temagruppe og verktøykategori) og på et valgfritt felt (meta-nøkkel + verdi, f.eks. type = Programvare).
* Treffene lenker til siden på bimverdi.no også for verktøy, og den eksterne nettsiden tas vare på i dataene.

= 0.22.0 =
* Nytt: "Kopier feltet" i skjemaeditoren. Kopien legges rett under originalen med alle innstillinger (felttype, påkrevd, hjelpetekst, svaralternativer, oppslagsfiltre), ledeteksten blir "Kopi av ...", og kopien får sin egen lagringsnøkkel når du lagrer.

= 0.21.1 =
* Lenkene til standarder og veiledninger i oppslagsfeltet går nå til kildesiden på bimverdi.no (f.eks. /kunnskapskilder/ns-34512022-bygningsdelstabell/), ikke til den opprinnelige kilden.
* Valgte kilder er klikkbare også mens skjemaet fylles ut, og åpnes i ny fane.
* CSV-eksport og e-post tar med bimverdi-lenken til hver kilde.

= 0.21.0 =
* Meldingsfelt på hver registrering: dialog mellom alle som har tilgang, med e-postvarsel til eier, kontakter og BIM Verdi.
* "Åpne"/"Les" åpner registreringen i ny fane, og oversiktene viser antall meldinger.

= 0.20.0 =
* Ny kortkode [bv_alle_prosjekter]: felles oversikt over alle innsendte registreringer leseren har tilgang til, med søk, foretaksfilter og sidevisning.

= 0.19.1 =
* Skjemaeditoren varsler nå når en seksjon ikke har synlige felt - slike seksjoner vises ikke i skjemaet.

= 0.19.0 =
* Felt kan flyttes fra én seksjon til en annen i skjemaeditoren.
* Et felt som er merket for sletting stopper ikke lenger lagringen selv om ledeteksten er tom.

= 0.18.0 =
* Administrator kan overføre eierskapet til en registrering til en annen bruker.
* Administrator kan velge medredaktører fra alle foretak, ikke bare eierens eget.

= 0.17.0 =
* Rettet: «Lagre skjema» kunne stoppe uten melding (og flytting av seksjoner gikk tapt) fordi en ledetekst manglet i en lukket feltrad. Editoren åpner nå raden, peker på feltet og forklarer hva som mangler.
* Nytt: kollaps av seksjoner med «Skjul alle / Vis alle», husket per nettleser.
* Nytt: varsel ved samtidig redigering - lagring fra en annen fane overskrives ikke lenger i stillhet.

= 0.16.0 =
* Ny felttype "Oppslag (kunnskapskilder)": søk i kunnskapsbasen, filtrert på kildetype og temagruppe, lagrer kilde-ID-er.
* Nytt AJAX-endepunkt bvp_kilde_sok (innlogget + nonce), med filtrene hentet fra feltdefinisjonen på serversiden.

= 0.15.0 =
* Ny modul "Mine prosjekter" til min-side: kortkode, malfunksjon og menypunkt, med egne registreringer, kollegers registreringer i foretaket og delt med meg.
* Ny innstilling: adresse til skjemasiden.

= 0.14.1 =
* Foretakssøket går nå mot databasen (WP_Query) i stedet for å laste hele foretakslista, og tåler mange hundre foretak. Søker på navn og organisasjonsnummer, bare publiserte foretak.

= 0.14.0 =
* Leser foretakskoblingen som allerede finnes på nettstedet (innholdstypen `foretak`) - brukere som er registrert der, regnes nå som koblet uten at noe må legges inn på nytt.
* Finner selv hvilken brukermeta nettstedet bruker for koblingen, og hvilken persontype brukeren har.
* Organisasjonsnummer og hoveddomene leses også fra ACF-feltene på nettstedets foretaksprofil.
* Foretakssøket og kontaktlistene dekker både nettstedets foretak og pluginens egne.

= 0.13.3 =
* "Logg inn"-knappen sender brukeren rett til utfylling av skjemaet.
* Den store krav-boksen vises nå bare for utloggede. Innloggede uten foretakskobling får i stedet en kort melding i skjemaet, med lenke til foretaksvalget.

= 0.13.2 =
* Feltet "Foretaksnavn som (foreløpig) står som søker" heter nå "Foretaksnavn som registrerer" - også i allerede lagrede skjemaoppsett, med mindre teksten er endret manuelt.
* Foretaksnavnet forhåndsutfylles fra foretaksprofilen brukeren er koblet til, både på nye og tomme eksisterende registreringer.
* Hjelpeteksten peker til en kontaktlenke (standard https://bimverdi.no/tilbakemelding/) i stedet for e-post. E-post brukes bare hvis lenken står tom.

= 0.13.1 =
* Tydelig krav-boks i stedet for "du må være innlogget": brukerprofil hos BIM Verdi med kobling til et foretak i Foretaksregisteret/BRREG, med stegvis veiledning for dem som mangler det ene eller begge.
* Innloggede uten foretakskobling får samme veiledning over skjemaet og oversikten.
* Nye innstillinger: kontakt-e-post for deltakere og lenke til "Slik blir du deltaker".

= 0.13.0 =
* Delingsnivået heter nå "Utvalgte foretak med foretaksprofil i BIM Verdi".
* Foretak velges med søk/autofullfør (navn eller org.nr) i stedet for en lang rulleliste.
* Valgt foretak vises som et kort med foretakets kontaktpersoner, der enkeltpersoner kan krysses av.
* Nye AJAX-endepunkter bvp_foretak_sok og bvp_foretak_kontakter (innlogget + nonce).

= 0.12.0 =
* Ny innholdstype Foretak med org.nr, e-postdomener og personer med persontype (Hovedkontakt, Tilleggskontakt, Bruker, Gratisbruker).
* Feltet Tilleggskontakter lister nå bare hovedkontakt og tilleggskontakter i registrators eget foretak - ikke alle brukere på nettstedet.
* Er registrator ikke koblet til et foretak, kan han velge foretak i skjemaet; valget kobler brukeren.
* Automatisk tilleggskontakt ved treff på foretakets e-postdomene, og foretaksfelt på brukerprofilen i wp-admin.
* Delingsnivået "utvalgte foretak" bruker foretaksprofilene, med fallback til gamle foretaksnavn.

= 0.11.2 =
* "Skjul seksjonen" ved siden av "Slett seksjonen" i seksjonshodet. En skjult seksjon forsvinner fra skjemaet, wp-admin, e-posten og CSV-eksporten, men beholder lagrede svar.

= 0.11.1 =
* "Skjul feltet" og "Slett feltet" ligger nå side om side nederst på hvert spørsmål, med forklaring på forskjellen.

= 0.11.0 =
* Skjul enkeltfelt fra skjemaeditoren uten å miste lagrede svar.
* Deling per registrering med fire nivåer, tilleggskontakter, "Delt med meg"-liste og lesevisning.

= 0.10.0 =
* Skjemaeditor i wp-admin med forhåndsvisning, JSON-eksport/import og tilbakestilling.
* Nye felttyper: tall, dato og nedtrekksliste.
* Feltoppsettet flyttet fra kode til `bvp_form_schema` i wp_options (BVP_Schema).

= 0.9.0 =
* Pilotversjon under navnet BV-pilot/prosjekt. Innholdstype `bv_pilot_prosjekt`, kortkode `[bv_pilot_prosjekt]`, prefiks `bvp_`.
* Bygget fra Formidable-skjema 39 "Reg. av søknadsskisse - rask avklaring".
