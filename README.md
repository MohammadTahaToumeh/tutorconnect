# TutorConnect

**Nachhilfe-Plattform von Studierenden für Studierende der Hochschule Bochum.**
Studierende finden passende Tutorinnen und Tutoren für ihre Module, schreiben ihnen im Chat, buchen freie Termine und bezahlen direkt über PayPal. Nach der Sitzung können sie eine Bewertung abgeben.

> Teamprojekt an der Hochschule Bochum (4 Personen), bewertet mit **1,7**.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-003545?logo=mariadb&logoColor=white)
![PayPal](https://img.shields.io/badge/PayPal-API-003087?logo=paypal&logoColor=white)

---

## Funktionen

- **Registrierung nur mit Hochschul-E-Mail** (`@stud.hs-bochum.de`) und Bestätigung per 6-stelligem E-Mail-Code
- **Login mit Token-Authentifizierung** (Laravel Sanctum) und Rate-Limiting gegen Brute-Force
- **Tutor-Suche** nach Name, Fach und Studiengang
- **Tutor-Profile** mit Fächern, Verfügbarkeiten, Ort (online / vor Ort) und Zahlungsmethoden
- **Chat** zwischen Studierenden und Tutor:innen
- **Buchungssystem** mit Anfrage, Annahme, Ablehnung und Stornierung, inkl. Prüfung auf freie Zeitslots und Überschneidungen
- **E-Mail-Benachrichtigungen** bei neuen Anfragen und Statusänderungen
- **Bezahlung über PayPal** (Order anlegen, Zahlung erfassen, Webhook-Verifizierung, Rückerstattung)
- **Bewertungen** nach abgeschlossenen Sitzungen
- **Vergangene Sitzungen** und persönliches Dashboard

## Mein Beitrag

Ich war hauptsächlich für das **Backend und die REST-API** verantwortlich:

- Entwicklung des **Laravel-Backends** und der **REST-API**, die das Vue.js-Frontend nutzt
- **Login und Registrierung** inklusive Hochschul-E-Mail-Prüfung und Bestätigungscode
- **Suchfunktion** für Tutor:innen
- **Buchungslogik** mit Statusverwaltung und Verfügbarkeitsprüfung
- Anbindung der API an das **Vue.js-Frontend**

## Tech-Stack

| Bereich | Technologien |
|---|---|
| Backend | PHP 8.3, Laravel 13, Laravel Sanctum, Eloquent ORM |
| Frontend | Vue.js 3, Vue Router, Pinia, Axios, Vite |
| Datenbank | MariaDB / MySQL (Migrations und Seeder) |
| Zahlung | PayPal REST API (Sandbox) |
| Tests | Pest / PHPUnit |

## Architektur

```
┌──────────────────┐     REST / JSON      ┌──────────────────┐      ┌──────────┐
│  Vue.js-Frontend │  ─────────────────▶  │  Laravel-API     │ ───▶ │ MariaDB  │
│  (Vite, Pinia)   │  ◀─────────────────  │  (Sanctum-Auth)  │      └──────────┘
└──────────────────┘    Bearer-Token      └────────┬─────────┘
                                                   │
                                     ┌─────────────┴─────────────┐
                                     ▼                           ▼
                               PayPal REST API            E-Mail-Versand
```

## Projektstruktur

```
tutorconnect/
├── backend/                     # Laravel-API
│   ├── app/Http/Controllers/Api # Auth, Tutoren, Buchungen, Chat, Bewertungen, PayPal
│   ├── app/Models               # User, TutorProfile, Booking, Message, Review, ...
│   ├── app/Services             # BookingLifecycleService, PayPalService
│   ├── database/migrations      # Datenbankschema
│   ├── database/seeders         # Beispieldaten (Studiengänge, Fächer, ...)
│   └── routes/api.php           # Alle API-Endpunkte
└── frontend/                    # Vue.js-App
    └── src/
        ├── Pages/               # Login, Dashboard, Tutoren, Chat, Profil, ...
        ├── Components/          # Topbar, Bewertungs-Dialoge
        ├── router.js
        └── api.js               # Axios mit Token-Header
```

## Lokal starten

### Voraussetzungen
- PHP 8.3+ und [Composer](https://getcomposer.org/)
- Node.js 20+ und npm
- MariaDB oder MySQL

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

In der `.env` die Datenbank eintragen (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) und die Datenbank anlegen. Danach:

```bash
php artisan migrate --seed
php artisan serve
```

Die API läuft dann unter `http://127.0.0.1:8000`.

> E-Mails werden lokal standardmäßig ins Log geschrieben (`MAIL_MAILER=log`). Den Bestätigungscode findest du in `storage/logs/laravel.log`.

### 2. Frontend

```bash
cd frontend
npm install
npm run dev
```

Die App läuft dann unter `http://localhost:5173`. API-Anfragen werden über den Vite-Proxy an das Backend weitergeleitet.

### Optional: PayPal (Sandbox)

Für die Bezahlfunktion in der `.env` ergänzen:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_WEBHOOK_ID=...
FRONTEND_URL=http://localhost:5173
```

Die Zugangsdaten gibt es kostenlos im [PayPal Developer Dashboard](https://developer.paypal.com/).

## Screenshots

<!-- Hier 2–3 Screenshots einfügen, z. B.:
![Tutor-Suche](docs/tutors.png)
![Chat und Buchung](docs/chat.png)
-->

## Team

Entwickelt im Rahmen eines Studienprojekts an der Hochschule Bochum von einem vierköpfigen Team.
