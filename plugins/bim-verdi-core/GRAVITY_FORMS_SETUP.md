# Gravity Forms Setup Guide

## 📋 Innhold
Dette dokumentet inneholder instruksjoner for å sette opp registreringsskjemaet i Gravity Forms.

## 🎯 Forutsetninger
- Gravity Forms Pro er installert og aktivert
- Følgende Gravity Forms Add-ons er aktivert:
  - User Registration Add-On
  - Advanced Post Creation Add-On (valgfritt, men anbefalt)

## 📝 Steg 1: Importer Skjema

### Manuell Opprettelse
Gå til WordPress Admin → Forms → New Form og opprett et skjema med følgende struktur:

#### Seksjon 1: Bedriftsinformasjon
1. **Organisasjonsnummer** (Text, required, max 9 chars)
2. **Bedriftsnavn** (Text, required)
3. **Bedriftsbeskrivelse** (Textarea, optional)
4. **Logo** (File Upload, optional, max 2MB, jpg/png/gif/svg)
5. **Gateadresse** (Text, required)
6. **Postnummer** (Text, required, max 4 chars)
7. **Poststed** (Text, required)
8. **Nettside** (Text, optional)
9. **Bransjekategori** (Select, required)
   - Arkitekt
   - Rådgiver
   - Entreprenør
   - Leverandør
   - Eiendomsforvalter
   - Annet
10. **Kundetype** (Checkboxes, required)
    - Offentlig sektor
    - Privat sektor
    - Boligbygg
    - Næringsbygg

#### Seksjon 2: Kontaktperson
11. **Fornavn** (Text, required)
12. **Etternavn** (Text, required)
13. **E-postadresse** (Email, required)
14. **Telefon** (Phone, required)
15. **Stillingstittel** (Text, optional)

#### Seksjon 3: Brukerkonto
16. **Passord** (Password, required, medium strength)
17. **Bekreft passord** (Password, required)

#### Seksjon 4: Samtykke
18. **Personvern** (Consent, required)
19. **Medlemsvilkår** (Consent, required)
20. **Nyhetsbrev** (Checkbox, optional)

## ⚙️ Steg 2: Konfigurer User Registration

1. Gå til skjemaet → Settings → User Registration
2. Klikk "Add New"
3. Konfigurer:
   - **Username**: Map til E-postadresse (field 13)
   - **Email**: Map til E-postadresse (field 13)
   - **First Name**: Map til Fornavn (field 11)
   - **Last Name**: Map til Etternavn (field 12)
   - **Password**: Map til Passord (field 16)
   - **Role**: company_owner (velg fra dropdown)
   - **User Meta**:
     - `phone` → Telefon (field 14)
     - `stillingstittel` → Stillingstittel (field 15)

## 📬 Steg 3: Konfigurer Notifikasjoner

### Bruker-notifikasjon
1. Gå til skjemaet → Settings → Notifications
2. Rediger "Admin Notification" eller opprett ny:
   - **Name**: Bekreftelse til bruker
   - **Send To**: {E-postadresse:13}
   - **Subject**: Velkommen til BIM Verdi
   - **Message**:
     ```
     Hei {Fornavn:11},

     Takk for at du registrerte {Bedriftsnavn:2} i BIM Verdi medlemsportalen.

     Din søknad venter nå på godkjenning fra BIM Verdi.
     Du vil motta en ny e-post når kontoen din er aktivert.

     Innloggingsdetaljer:
     E-post: {E-postadresse:13}

     Med vennlig hilsen,
     BIM Verdi
     ```

### Admin-notifikasjon
1. Opprett ny notifikasjon:
   - **Name**: Varsling til admin
   - **Send To**: admin@bimverdi.no
   - **Subject**: Ny medlemsregistrering: {Bedriftsnavn:2}
   - **Message**:
     ```
     Ny bedrift registrert:

     Bedrift: {Bedriftsnavn:2}
     Org.nr: {Organisasjonsnummer:1}
     Kontakt: {Fornavn:11} {Etternavn:12}
     E-post: {E-postadresse:13}
     Telefon: {Telefon:14}

     Gå til admin for å godkjenne medlemskapet.
     ```

## 🔗 Steg 4: Koble til Medlemsbedrift CPT (Valgfritt)

### Bruk Advanced Post Creation Add-On
1. Gå til skjemaet → Settings → Advanced Post Creation
2. Klikk "Add New"
3. Konfigurer:
   - **Post Type**: medlemsbedrift
   - **Post Status**: pending
   - **Post Title**: Map til Bedriftsnavn (field 2)
   - **Post Content**: Map til Bedriftsbeskrivelse (field 3)
   - **Custom Fields** (ACF):
     - `organisasjonsnummer` → Organisasjonsnummer (field 1)
     - `bedriftsnavn` → Bedriftsnavn (field 2)
     - `beskrivelse` → Bedriftsbeskrivelse (field 3)
     - `logo` → Logo (field 4)
     - `adresse` → Gateadresse (field 5)
     - `postnummer` → Postnummer (field 6)
     - `poststed` → Poststed (field 7)
     - `nettside` → Nettside (field 8)
     - `telefon` → Telefon (field 14)
     - `medlemsstatus` → "pending" (hardcoded)

### ELLER Bruk Custom Code (class-gravity-forms-handler.php)
Pluginen inkluderer `BIM_Verdi_Gravity_Forms_Handler` som kan håndtere post-opprettelse manuelt.
Se kommentarene i filen for eksempelkode.

## 🎨 Steg 5: Embed Skjemaet

### Opprett Registreringsside
1. Gå til Pages → Add New
2. Tittel: "Bli Medlem"
3. Slug: `/bli-medlem`
4. Legg til Gravity Forms block
5. Velg "Bedriftsregistrering" skjema
6. Publiser

### Shortcode (alternativ)
```php
[gravityform id="1" title="false" description="false" ajax="true"]
```

## 🔒 Steg 6: Validering (Valgfritt)

Pluginen inkluderer validering for:
- Duplikat organisasjonsnummer
- Duplikat e-postadresse

Dette er implementert i `class-gravity-forms-handler.php`.
Fjern kommentarer fra koden for å aktivere.

## 🎯 Steg 7: Testing

1. Gå til `/bli-medlem` (eller din registreringsside)
2. Fyll ut skjemaet med testdata
3. Submit
4. Verifiser at:
   - WordPress bruker ble opprettet (Users → All Users)
   - Bruker har rolle "Company Owner"
   - Bruker mottok bekreftelse-e-post
   - Admin mottok varsling-e-post
   - (Hvis Advanced Post Creation er aktivert) Medlemsbedrift post ble opprettet

## 🚀 Neste Steg

Etter registrering kan brukeren:
1. Logge inn på `/wp-login.php`
2. Automatisk redirectes til Min Side Dashboard (`/min-side`)
3. Se sin bedriftsprofil og fullføre profilen

## 📌 Notater

- Form ID kan endres - oppdater `COMPANY_REGISTRATION_FORM_ID` i `class-gravity-forms-handler.php`
- Field IDs kan variere - sjekk i Gravity Forms og oppdater mappings
- For webhooks (Make.com, Zapier): Gå til Settings → Webhooks og legg til URL

## 🆘 Support

Se Gravity Forms dokumentasjon:
- User Registration: https://docs.gravityforms.com/category/add-ons-gravity-forms/user-registration-add-on/
- Advanced Post Creation: https://docs.gravityforms.com/category/add-ons-gravity-forms/advanced-post-creation-add-on/
