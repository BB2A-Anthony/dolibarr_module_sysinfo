# Module Dolibarr « sysinfo »

Ce module reçoit les informations systèmes de l'agent Windows et permet de les **interroger depuis Dolibarr** (API REST + fiche tiers).

## Endpoints

| Méthode | URL | Rôle |
|---|---|---|
| `POST` | `/api/index.php/sysinfo/machine` | L'agent y envoie son rapport (authentifié par `DOLIBARR_API_KEY`) |
| `GET` | `/api/index.php/sysinfo/machine` | Dernier rapport de **chaque machine** connue |
| `GET` | `/api/index.php/sysinfo/machine/{guid}` | Dernier rapport d'**une machine** par identifiant unique |
| `GET` | `/api/index.php/sysinfo/config` | Versions et URLs de mise à jour des agents (auto-update) |

## Installation

1. Copier le dossier `sysinfo` dans `htdocs/custom/` de votre instance Dolibarr.
2. Créer la table en exécutant `sql/llx_sysinfo_reports.sql` (via phpMyAdmin ou l'outil SQL de Dolibarr).
3. Activer le module : **Accueil > Configuration > Modules/Boxes > Modules externes** (activer « SYSINFO »).
4. Vérifier que l'API est active (**Configuration > API**, module REST) et qu'un utilisateur possède une **clé API** (fiche utilisateur > « Interface API »).

## Interroger depuis Dolibarr

Dernier rapport d'une machine :

```bash
curl -H "DOLIBARR_API_KEY: <cle>" \
  https://votre-dolibarr/api/index.php/sysinfo/machine/TIERS-001
```

Tous les derniers rapports :

```bash
curl -H "DOLIBARR_API_KEY: <cle>" \
  https://votre-dolibarr/api/index.php/sysinfo/machine
```

Exemple de réponse :

```json
{
    "guid": "TIERS-001",
    "thirdparty_id": 42,
    "hostname": "PC-ATLAS",
    "os": "Windows 10 Pro",
    "date_creation": 1710000000,
    "report": { "memory": {"total_bytes": 17179869184, "...": "..."} }
}
```

Ces endpoints sont également visibles et testables dans l'**API Explorer** de Dolibarr (`/api/index.php/explorer`).

### Fiche tiers

Un hook affiche automatiquement un bloc « Informations systeme (agent Windows) » sur la fiche du tiers : date du dernier rapport, hostname, OS, mémoire, CPU. La correspondance se fait entre `guid` de l'agent et le **code client** du tiers.

## Stockage

**Une seule ligne par machine** dans la table `llx_sysinfo_reports` : à chaque envoi (30 s après le démarrage puis toutes les 4 h), le module met à jour l'enregistrement existant de la machine (`guid`) — `fk_soc`, hostname, OS, rapport JSON et date — ou le crée s'il s'agit du premier rapport. La colonne `guid` est `UNIQUE` pour garantir l'unicité.

## Mise à jour automatique des agents

Le descripteur du module (`core/modules/modSysinfo.class.php`) définit pour chaque agent sa dernière version publiée et l'URL de son dossier de mise à jour GitHub : `agentWindowsVersion`/`agentWindowsUpdateUrl`, `agentLinuxVersion`/`agentLinuxUpdateUrl`, `agentAndroidVersion`/`agentAndroidUpdateUrl`. L'URL pointe vers le dossier `update/` de la release GitHub correspondante (`https://github.com/BB2A-Anthony/dolibarr_sysinfo_agent_<os>/releases/download/<version>/update/`).

Ces valeurs sont exposées aux agents via `GET /api/index.php/sysinfo/config` :

```json
{
  "windows": {"version": "1.0.0", "update_url": "https://github.com/.../update/"},
  "linux":   {"version": "1.0.0", "update_url": "https://github.com/.../update/"},
  "android": {"version": "1.0.0", "update_url": "https://github.com/.../update/"}
}
```

Chaque agent compare cette version à la sienne après chaque envoi de rapport, télécharge son package dans le dossier `update/` de la release et l'applique (redémarrage automatique sur Linux, installateur silencieux sur Windows, confirmation utilisateur sur Android). Le comportement est désactivable côté agent (`"auto_update": false`) et surchargeable côté serveur via les constantes `SYSINFO_AGENT_<OS>_VERSION` / `SYSINFO_AGENT_<OS>_UPDATE_URL`.

Pour publier une mise à jour : créer la release GitHub de l'agent avec le dossier `update/` contenant le package, puis incrémenter la version dans le descripteur du module.

## Utilisateur technique

À l'activation du module, un utilisateur technique `sysinfoagent` est créé (mot de passe aléatoire jamais stocké, clé API générée automatiquement) et ajouté au groupe Dolibarr **Technical users** (créé s'il n'existe pas). C'est la clé API de cet utilisateur qui est remise à l'agent lors de l'enrôlement.
