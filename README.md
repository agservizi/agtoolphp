# AGTool Finance

Gestione Finanze Personali - Applicazione Web

## Funzionalità principali
- Gestione di transazioni (entrate/uscite)
- Categorie personalizzate
- Obiettivi di risparmio
- Consulente finanziario virtuale
- Notifiche e limiti di spesa
- Statistiche e grafici

## Requisiti
- PHP >= 7.4
- MySQL/MariaDB
- Web server (Apache/Nginx)

## Installazione
1. Clona o scarica il progetto nella cartella del tuo web server.
2. Configura il database in `inc/config.php` (imposta host, utente, password, nome DB).
3. Esegui `install.php` per creare le tabelle necessarie.
4. Accedi a `index.php` dal browser.

## Struttura principale
- `index.php` — Dashboard e riepilogo
- `transactions.php` — Elenco e filtri transazioni
- `categories.php` — Gestione categorie
- `savings.php` — Obiettivi di risparmio
- `settings.php` — Preferenze utente
- `assets/js/main.js` — Logica frontend

## Personalizzazione
- Modifica le categorie in `categories.php`
- Aggiungi/gestisci obiettivi in `savings.php`
- Imposta limiti e notifiche in `settings.php`

## Supporto
Per problemi o richieste, contatta lo sviluppatore o apri una issue.

---
**AGTool Finance** © 2025
