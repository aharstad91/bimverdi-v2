# Gravity Forms Setup Guide (Gravity Forms Pro)

## 📋 Oversikt
Denne guiden viser hvordan du setter opp registreringsskjemaet med **Gravity Forms Pro** (uten User Registration Add-On). Vi bruker custom PHP-kode som allerede er implementert i `class-gravity-forms-handler.php`.

## 🎯 Forutsetninger
- ✅ Gravity Forms Pro er installert og aktivert
- ✅ BIM Verdi Core plugin er aktivert
- ✅ ACF Pro er installert

## 📝 Steg 1: Opprett Skjema i Gravity Forms

1. Gå til **Forms → New Form**
2. Navn: `Bedriftsregistrering`
3. Beskrivelse: `Registrer din bedrift som medlem i BIM Verdi`

## 🏗️ Steg 2: Legg til Felter

### Seksjon 1: Bedriftsinformasjon

Legg til **Section** felt:
- Label: `Bedriftsinformasjon`
- Description: `Grunnleggende informasjon om bedriften`

**Field ID 1** - Single Line Text:
- Label: `Organisasjonsnummer`
- Required: ✅
- Placeholder: `9 siffer`
- Max Length: 9
- Description: `Bedriftens norske organisasjonsnummer`

**Field ID 2** - Single Line Text:
- Label: `Bedriftsnavn`
- Required: ✅
- Placeholder: `Bedriftens offisielle navn`

**Field ID 3** - Paragraph Text:
- Label: `Bedriftsbeskrivelse`
- Required: ❌
- Placeholder: `Kort beskrivelse av bedriften og hva dere gjør`

**Field ID 4** - File Upload:
- Label: `Logo`
- Required: ❌
- Allowed Extensions: `jpg, jpeg, png, gif, svg`
- Maximum File Size: 2 MB
- Description: `Last opp bedriftens logo`

**Field ID 5** - Single Line Text:
- Label: `Gateadresse`
- Required: ✅

**Field ID 6** - Single Line Text:
- Label: `Postnummer`
- Required: ✅
- Max Length: 4

**Field ID 7** - Single Line Text:
- Label: `Poststed`
- Required: ✅

**Field ID 8** - Single Line Text:
- Label: `Nettside`
- Required: ❌
- Placeholder: `https://www.eksempel.no`

**Field ID 20** - Drop Down:
- Label: `Bransjekategori`
- Required: ✅
- Choices:
  - Arkitekt
  - Rådgiver
  - Entreprenør
  - Leverandør
  - Eiendomsforvalter
  - Annet

**Field ID 21** - Checkboxes:
- Label: `Kundetype (velg alle som passer)`
- Required: ✅
- Choices:
  - Offentlig sektor
  - Privat sektor
  - Boligbygg
  - Næringsbygg

### Seksjon 2: Kontaktperson

Legg til **Section** felt:
- Label: `Kontaktperson`
- Description: `Hovedkontaktperson for medlemskapet`

**Field ID 10** - Single Line Text:
- Label: `Fornavn`
- Required: ✅

**Field ID 11** - Single Line Text:
- Label: `Etternavn`
- Required: ✅

**Field ID 12** - Email:
- Label: `E-postadresse`
- Required: ✅
- Placeholder: `navn@bedrift.no`
- Description: `Brukes som brukernavn for innlogging`
- Enable Email Confirmation: ✅

**Field ID 13** - Phone:
- Label: `Telefon`
- Required: ✅
- Placeholder: `+47 123 45 678`

**Field ID 14** - Single Line Text:
- Label: `Stillingstittel`
- Required: ❌
- Placeholder: `Daglig leder, BIM-koordinator, etc.`

### Seksjon 3: Brukerkonto

Legg til **Section** felt:
- Label: `Brukerkonto`
- Description: `Opprett innloggingsdetaljer`

**Field ID 15** - Password:
- Label: `Passord`
- Required: ✅
- Password Strength: Medium
- Description: `Minimum 8 tegn, inkludert tall og bokstaver`

**Field ID 16** - Password:
- Label: `Bekreft passord`
- Required: ✅

### Seksjon 4: Samtykke og vilkår

Legg til **Section** felt:
- Label: `Samtykke og vilkår`

**Field ID 17** - Consent:
- Label: `Personvern`
- Required: ✅
- Checkbox Label: `Jeg godtar at mine opplysninger behandles i henhold til personvernerklæringen`
- Description: `Les vår <a href="/personvern" target="_blank">personvernerklæring</a>`

**Field ID 18** - Consent:
- Label: `Medlemsvilkår`
- Required: ✅
- Checkbox Label: `Jeg godtar BIM Verdis medlemsvilkår`
- Description: `Les <a href="/medlemsvilkar" target="_blank">medlemsvilkårene</a>`

**Field ID 19** - Checkbox:
- Label: `Nyhetsbrev`
- Required: ❌
- Choices:
  - `Ja, jeg ønsker å motta nyhetsbrev fra BIM Verdi`

## ⚙️ Steg 3: Konfigurer Skjema-innstillinger

### Form Settings
1. Gå til **Form Settings**
2. **Form Button Text**: `Send inn registrering`
3. **Form Title**: Skjul (uncheck "Form Title")
4. **Form Description**: Skjul (uncheck "Form Description")

### Save og opprett Form ID
1. Klikk **Save Form**
2. Legg merke til **Form ID** (f.eks. `1`)
3. Oppdater i `class-gravity-forms-handler.php`:
   ```php
   const COMPANY_REGISTRATION_FORM_ID = 1; // Endre til ditt form ID
   ```

## 📬 Steg 4: Konfigurer Notifikasjoner

Gravity Forms bruker notifikasjoner, men vi sender også custom email via PHP.

### Admin Notifikasjon
1. Gå til **Form Settings → Notifications**
2. Hover over "Admin Notification" og klikk **Edit**
3. Konfigurer:
   - **Name**: `Admin - Ny registrering`
   - **Send To**: `admin@bimverdi.no` (endre til din e-post)
   - **Subject**: `Ny medlemsregistrering: {Bedriftsnavn:2}`
   - **Message**:
     ```
     Ny bedrift registrert:
     
     Bedrift: {Bedriftsnavn:2}
     Org.nr: {Organisasjonsnummer:1}
     
     Kontaktperson: {Fornavn:10} {Etternavn:11}
     E-post: {E-postadresse:12}
     Telefon: {Telefon:13}
     
     Bransjekategori: {Bransjekategori:20}
     
     Gå til WordPress admin for å godkjenne medlemskapet:
     {admin_url}edit.php?post_type=medlemsbedrift
     ```

**Merk:** Custom velkomst-e-post til bruker sendes automatisk fra `class-gravity-forms-handler.php`

## ✅ Steg 5: Konfigurer Bekreftelsesmelding

1. Gå til **Form Settings → Confirmations**
2. Edit "Default Confirmation"
3. Type: **Text**
4. Message:
   ```html
   <div class="alert alert-success" style="background: #F0FFF4; border: 2px solid #48BB78; padding: 20px; border-radius: 8px; margin: 20px 0;">
     <h3 style="color: #2F855A; margin-top: 0;">🎉 Velkommen til BIM Verdi!</h3>
     <p>Registreringen din er mottatt og venter på godkjenning.</p>
     <p>Du vil motta en e-post når kontoen din er aktivert.</p>
     <p style="margin-bottom: 0;">
       <a href="{site_url}" class="button" style="display: inline-block; background: #FF8B5E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Tilbake til forsiden</a>
     </p>
   </div>
   ```

## 🔍 Steg 6: Test Field Mapping

For å sikre at Field IDs matcher koden:

1. Gå til skjemaet
2. Klikk på hvert felt
3. Se **Field ID** i høyre sidebar
4. Verifiser at disse matcher:
   - Field 1 = Organisasjonsnummer
   - Field 2 = Bedriftsnavn
   - Field 3 = Bedriftsbeskrivelse
   - Field 4 = Logo
   - Field 5 = Gateadresse
   - Field 6 = Postnummer
   - Field 7 = Poststed
   - Field 8 = Nettside
   - Field 10 = Fornavn
   - Field 11 = Etternavn
   - Field 12 = E-postadresse
   - Field 13 = Telefon
   - Field 14 = Stillingstittel
   - Field 15 = Passord
   - Field 16 = Bekreft passord
   - Field 20 = Bransjekategori
   - Field 21 = Kundetype

**Hvis Field IDs er forskjellige**, oppdater i `class-gravity-forms-handler.php`

## 📄 Steg 7: Opprett Registreringsside

1. Gå til **Pages → Add New**
2. Tittel: `Bli Medlem`
3. Slug: `bli-medlem`
4. Legg til **Gravity Forms** block:
   - Velg skjemaet `Bedriftsregistrering`
   - Display Form Title: ❌
   - Display Form Description: ❌
   - Ajax: ✅
5. **Publiser**

**Alternativ (shortcode):**
```
[gravityform id="1" title="false" description="false" ajax="true"]
```

## 🧪 Steg 8: Test Registreringen

### Test-data:
1. Gå til `/bli-medlem`
2. Fyll ut skjemaet:
   - Org.nr: `123456789`
   - Bedriftsnavn: `Test Bedrift AS`
   - Fornavn: `Ola`
   - Etternavn: `Nordmann`
   - E-post: `test@testbedrift.no`
   - Telefon: `12345678`
   - Passord: `Test1234!`
   - Huk av samtykke-bokser
3. Klikk **Send inn registrering**

### Verifiser at:
✅ Bekreftelsesmelding vises  
✅ Admin mottok e-post  
✅ Bruker mottok velkomst-e-post  
✅ WordPress user opprettet (Users → All Users)  
✅ Bruker har rolle "Company Owner"  
✅ Medlemsbedrift post opprettet (Medlemsbedrifter → All)  
✅ ACF-felter er fylt ut  
✅ Bransjekategori er satt  
✅ Bruker kan logge inn med e-post/passord

## 🚀 Hva skjer automatisk?

`class-gravity-forms-handler.php` gjør automatisk:

1. ✅ **Validering**:
   - Sjekker om org.nr allerede finnes
   - Sjekker om e-post allerede er i bruk
   - Verifiserer at passord matcher

2. ✅ **Opprett bruker**:
   - WordPress user med e-post som brukernavn
   - Rolle: `company_owner`
   - User meta: fornavn, etternavn, telefon, stillingstittel

3. ✅ **Opprett bedrift**:
   - Medlemsbedrift post med status `pending`
   - Alle ACF-felter fylles ut
   - Logo lastes opp til Media Library
   - Post author = opprettet bruker

4. ✅ **Link bruker til bedrift**:
   - User meta: `bim_verdi_company_id`

5. ✅ **Sett kategorier**:
   - Bransjekategori
   - Kundetype (flere valg)

6. ✅ **Send velkomst-e-post**:
   - HTML-formatert
   - BIM Verdi farger
   - Innloggingsdetaljer

## 🔧 Feilsøking

### Bruker opprettes ikke
- Sjekk at Form ID i `class-gravity-forms-handler.php` matcher
- Se WordPress debug log: `/wp-content/debug.log`
- Error lagres også i Gravity Forms entry meta

### Field mapping feil
- Verifiser Field IDs i Gravity Forms
- Oppdater Field IDs i `handle_company_registration()` metoden

### E-post sendes ikke
- Test WordPress e-post med plugin som "WP Mail SMTP"
- Sjekk spam-folder
- Se debug log for feilmeldinger

## 📌 Viktige Notater

- **Form ID**: Oppdater `COMPANY_REGISTRATION_FORM_ID` konstant hvis du har flere skjemaer
- **Field IDs**: Kan variere hvis du lager feltene i annen rekkefølge
- **Logo upload**: Håndteres automatisk og flyttes til Media Library
- **Status pending**: Nye registreringer venter på admin-godkjenning
- **Custom email**: Sendes i tillegg til Gravity Forms notifikasjoner

## 🎯 Neste Steg

Etter vellykket registrering:
1. Admin godkjenner medlemskap (endre status til `publish`)
2. Bruker kan logge inn
3. Automatisk redirect til Min Side Dashboard
4. Bruker kan fullføre bedriftsprofil

---

**🆘 Trenger du hjelp?**
- Gravity Forms dokumentasjon: https://docs.gravityforms.com/
- Se `/wp-content/debug.log` for feilmeldinger
- Test med WP_DEBUG aktivert i `wp-config.php`
