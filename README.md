# Eterea Gestionale

Sistema di gestione aziendale (ERP) per studio creativo.

## 🚀 Deployment Automatico

Il progetto è configurato per il deployment automatico su SiteGround tramite GitHub Actions.

### Come funziona

Ogni volta che fai push sul branch `main`, il workflow GitHub Actions carica automaticamente i file sul server SiteGround via FTP.

### Secrets configurati

Le seguenti secrets sono già configurate in GitHub:
- `FTP_SERVER` - Server FTP di SiteGround
- `FTP_USERNAME` - Username FTP
- `FTP_PASSWORD` - Password FTP

### File esclusi dal deployment

I seguenti file non vengono caricati sul server:
- File di configurazione sensibili (già presenti sul server)
- File temporanei (`assets/temp/`)
- Upload utente (`assets/uploads/clienti/`, `assets/uploads/progetti/`)
- File SQL e script di utility
- File di sistema (`.DS_Store`, `.git/`)

## 📁 Struttura del progetto

```
/
├── api/              # Endpoint API REST
├── assets/           # CSS, JS, uploads
├── config/           # Configurazioni
├── includes/         # File PHP condivisi
├── vendor/           # Librerie terze parti
└── *.php             # Pagine principali
```

## ⚙️ Configurazione

Il file `includes/config.php` contiene:
- Credenziali database
- URL dell'applicazione
- Costanti utenti e impostazioni

## 🔐 Utenti

| Utente | ID |
|--------|-----|
| Lorenzo Puccetti | ucwurog3xr8tf |
| Daniele Giuliani | ukl9ipuolsebn |
| Edmir Likaj | u3ghz4f2lnpkx |

## 📝 Note

- Versione PHP: 8.x
- Database: MySQL/MariaDB
- Framework CSS: Tailwind CSS

---

**URL:** https://gestionale.etereastudio.it
