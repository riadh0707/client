# Analyse — RealSetup.exe (executor Roblox « Project Real »)

Analyse statique et émulation contrôlée, réalisées sans exécution réelle du binaire.

## Identité

| | |
|---|---|
| SHA-256 | `b411b444682179bf2f4270fbc22d9a86263b659152f9611712ec40101a8f3aee` |
| SHA-1 | `bb5bc882d52a5c342ab214df7de170e7d2f4769a` |
| MD5 | `d81c13e9063abe4687060d633ff1f701` |
| Taille | 17 304 576 octets (16,5 MiB) |
| Type | PE32+ GUI x86-64, 7 sections, non packé |
| Compilateur | MSVC 14.51, horodatage 2026-08-17 18:58:14 UTC |
| Signature Authenticode | **aucune** (stage 1 et stage 2) |
| Manifeste | `requireAdministrator` |
| Version info | Real / « Real Setup » / 1.0.0.0 / RealSetup.exe |
| Build interne | `e1314aab2b692ceb0ce5fe16ecd364e9596e0dbefdea18510be32a4de1904b9a` — 2026-08-16 20:49:41 |
| Chemin de build | `C:\Users\Marko\Documents\Roblox\Scripts\GitHub-Repos\Real-Executor\Real-Setup\` |

Bibliothèques : libcurl 8.20.0, OpenSSL 3.6.2, bundle CA Mozilla, lottie-web 5.13.0, GDI+.

## Verdict

Pas de charge malveillante de type stealer/RAT/ransomware dans ce fichier. C'est un
installeur commercial réel, soigneusement écrit. En revanche il abaisse durablement
les défenses du système pour s'installer, et la partie sensible de l'executor
(`Real.dll`, `Injector`, `Spoofer.exe`) n'est **pas** dans le fichier : elle est
téléchargée après coup, dans un dossier exclu de Defender.

## Jeton dans le nom du fichier

`RealSetup_1597bf65…0b16eeb.exe` — les 64 hex ne sont **pas** un hash (le vrai SHA-256
est différent). C'est un jeton d'autorisation de téléchargement à usage unique.
Recherché dans : nom de fichier → ADS `:Zone.Identifier` → registre `HKCU\Software\Real`.
Envoyé à `POST download.projectreal.live/verify` sous la forme `{"token":"…"}` /
`{"proof":"…"}`. Sans jeton valide, l'installation refuse de démarrer
(« Complete a quick free step to get your installer »).

## Détection antivirus — `Trojan:Win32/Kepavll!rfn`

Le suffixe `!rfn` indique une détection **comportementale automatisée**, pas une
correspondance de signature. `Kepavll` est un nom générique du moteur : il ne désigne
aucune souche identifiée. Defender ne dit pas « je reconnais ce malware » mais
« ce programme se comporte comme un dropper ».

Déclencheurs heuristiques effectivement présents dans le binaire :

| Comportement | Heuristique |
|---|---|
| PE non signée extrayant une seconde PE de ses ressources | Dropper |
| `Add-MpPreference -ExclusionPath` en PowerShell caché | Auto-exclusion de l'AV |
| Tâche planifiée `RunLevel=Highest`, « without a prompt » | Persistance + contournement UAC |
| Réinitialisation de `hosts` + DNS forcé sur toutes les cartes | Détournement de résolution |
| Import massif dans `LocalMachine\Root` + purge `catroot2` | Altération de la chaîne de confiance |
| Énumération WMI des AV et des détections Defender | Reconnaissance des défenses |
| Cascade DoH / ports alternatifs / bascule TLS | Évasion de filtrage |

À retenir :

- La détection est **justifiée** — ce n'est pas un faux positif ; les comportements
  existent et sont volontaires.
- Elle ne prouve **pas** la présence d'une charge voleuse. Il n'y en a pas dans ce fichier.
- La suppression automatique par l'antivirus est le comportement correct.

## Chaîne d'exécution

1. Élévation administrateur forcée par le manifeste.
2. Preflight : OS, disque, TPM, services, DNS, proxy IE ; WMI `ROOT\SecurityCenter2`
   (liste des AV/pare-feux) et `root\Microsoft\Windows\Defender`
   (`MSFT_MpThreatDetection` — vérifie si Defender a déjà mis ses propres fichiers en
   quarantaine).
3. Exclusions Defender ajoutées et vérifiées via PowerShell caché (`Add-MpPreference`).
4. Prérequis Microsoft (VC++ 2022, .NET 8 Desktop, WebView2) — signature Authenticode
   vérifiée avant exécution.
5. Extraction de la ressource `RCDATA 101` (8,5 Mo) = `Update.exe` / RealLauncher,
   SHA-256 `b42d62f0e5275ed85cb8451973f6d3b953ab024c685cf9bb90bf10dbf48ad3b2`.
6. Tâche planifiée `Real\Launch`, `RunLevel = Highest` :
   « Starts Real with the rights it needs, without a prompt » → contournement UAC.
7. `api.projectreal.live/update/check` → manifeste signé (`X-Manifest-Signature`) →
   téléchargement de `Real.exe`, `Real.dll`, `Injector`, `Module`, `Spoofer.exe`,
   `real-mcp.exe`, `luau-lsp.exe`. Vérification SHA-256 + signature, anti-downgrade,
   anti-rejeu.
8. Enregistrement : clé Uninstall, protocole `projectreal://`, raccourcis, dossiers
   `scripts`/`workspace`/`autoexec`, clés CNG ECDSA P-256 `RealInjectorAuthKey_v1/_v2`.

## Module « Réparation » — modifications système

| Action | Sévérité |
|---|---|
| Réinitialisation du fichier `hosts` (sauvegarde `.REAL.bak`) | Critique |
| Exclusions Defender permanentes | Critique |
| Forçage DNS 1.1.1.1 / 1.0.0.1 sur toutes les cartes actives | Critique |
| `certutil -generateSSTFromWU` + import en masse dans `LocalMachine\Root` | Élevé |
| Renommage de `catroot2` (cache des catalogues de signature) | Élevé |
| Redémarrage de `mpssvc` (pare-feu), `Dnscache`, `cryptsvc`, `wuauserv`, `W32Time` | Élevé |
| `netsh winsock reset` / `int ip reset` / `int ipv6 reset` / `winhttp reset proxy` | Élevé |
| Réécriture de `SCHANNEL\Protocols`, `SchUseStrongCrypto`, `SystemDefaultTlsVersions` | Moyen |
| `regsvr32 /s` sur ole32, crypt32, wintrust, bcrypt, initpki, urlmon… | Moyen |
| `sc config w32time` + `w32tm /config /manualpeerlist` + `/resync` | Moyen |

## Réseau

Cascade de contournement en cas d'échec : IPv4 seul → sans proxy → **DoH
(1.1.1.1 / 8.8.8.8 / 9.9.9.9)** → domaine de secours `dl.projectreal.live` → ports
HTTPS alternatifs → bascule OpenSSL → WinHTTP/Schannel. Détecte la redirection DNS
(`ip-not-cloudflare (DNS redirected)`). Messages d'erreur nommant Kaspersky, Avast,
ESET, Bitdefender, 360, McAfee avec conseil de désactiver l'analyse HTTPS.

### IOC

```
projectreal.gg
api.projectreal.live/update/check
api.projectreal.live/installer/report
download.projectreal.live/verify
dl.projectreal.live
discord.gg/projectreal
```

Rapport de diagnostic : opt-in, JSON `{setup, reason, appVersion, buildStamp,
idempotencyKey}` + journal ; HMAC via `X-Real-Sig` / `X-Real-Ts` / `X-Real-KeyId: 1`
avec un secret codé en dur —
`fc2b9821d85d405d7934c65466418c07ead56a4a2283b62b0793b03ed7be1a85` (donc sans valeur
d'authentification réelle).

## Émulation contrôlée (Speakeasy / Unicorn)

Faute d'accès réseau au serveur de l'éditeur et de plateforme Windows dans le sandbox
d'analyse, l'échantillon a été **émulé au niveau instruction** : Speakeasy en Windows
10 x64 simulé, sous son nom de fichier d'origine et avec sa ligne de commande réelle.
302 appels d'API exécutés avant que le pipeline graphique ne devienne inémulable sans
implémenter COM et GDI+.

Correctifs nécessaires côté émulateur : `FlsGetValue2` absente, `GetFileAttributesExW`
bugué, `MulDiv` manquante, plus 108 API graphiques neutralisées.

### Séquence de démarrage observée

```
VirtualProtect(0x140688000, 0x100, RW <-> RO) x6     -> section .fptable
GetEnvironmentVariableW("OPENSSL_ia32cap")
WSAStartup(2.2) / WSACleanup
CoInitializeEx
GetCommandLineW
GetModuleFileNameW -> "...\RealSetup_1597bf65...0b16eeb.exe"
RegOpenKeyExW(HKCU, "...\CurrentVersion\Uninstall\Real")
SHGetFolderPathW(CSIDL_LOCAL_APPDATA)
GetFileAttributesExW
GdiplusStartup
FindResourceW(id=201) / SizeofResource / LoadResource / LockResource -> 0x140ebabc0
```

### Ce que l'émulation établit

- **`.fptable` élucidée** : le va-et-vient de protection mémoire autour de l'encodage de
  pointeurs identifie de la machinerie CRT standard, pas de l'obfuscation.
- **Recoupement statique/dynamique** : l'adresse renvoyée par `LockResource`
  (`0x140ebabc0`) correspond au décalage exact de `RCDATA 201` extrait statiquement.
- **Aucune API sensible** sur les 302 : ni `WriteProcessMemory`, ni `CreateRemoteThread`,
  ni `VirtualAllocEx`, ni `OpenProcess`, ni `AdjustTokenPrivileges`, ni `NtLoadDriver`.
- **Aucun anti-analyse** : émulé successivement en Windows 7 puis en Windows 10, le
  binaire se comporte identiquement — aucune recherche d'artefact de VM, aucun test de
  débogueur, aucune temporisation d'évitement. Un sandbox donnera donc un résultat
  représentatif.
- **Aucun appel réseau avant l'interface** : authentification, téléchargement et
  exclusions Defender partent d'un thread lancé après la boucle de messages.

### Limite

L'émulation s'arrête au chargement des images de l'interface. Poursuivre exigerait
d'implémenter les objets COM `IStream`, les bitmaps GDI+ et l'ordonnancement de threads,
sans bénéfice : la phase utile requiert d'atteindre `api.projectreal.live`. Le facteur
limitant est le réseau, pas l'émulation.

## Absent du binaire (recherche exhaustive)

Vol de mots de passe / cookies / tokens Discord / portefeuilles crypto ; accès aux
profils navigateurs ; keylogger ; captures d'écran ou webcam ; détournement de
presse-papiers ; injection de processus ; chargement de pilote ; packer ou
chiffrement ; anti-VM / anti-debug ; C2 ou shell distant ; chiffrement de fichiers ;
suppression de sauvegardes ; persistance Run/RunOnce cachée ; seconde charge cachée.

Imports sensibles limités à `OpenProcess`, `CreateProcessW`, `ShellExecuteExW`.
Ni `WriteProcessMemory`, ni `CreateRemoteThread`, ni `VirtualAllocEx`, ni `NtLoadDriver`.

L'image de fond porte des métadonnées C2PA `trainedAlgorithmicMedia` (trufo.ai) :
illustration générée par IA, purement cosmétique — explique les certificats
inattendus présents dans le binaire.

## Angle mort

`Real.dll` + `Injector` (cœur de l'executor, accès mémoire complet au processus
Roblox) et `Spoofer.exe` (usurpation d'identifiant matériel, potentiellement à base de
pilote noyau) ne sont pas dans l'échantillon. Le serveur peut servir un contenu
différent selon le jeton, la région ou la date. Leur analyse exige de dérouler
l'installation dans une VM isolée avec capture réseau, ou une détonation en sandbox.

## Détonation en sandbox — ce qui marche et ce qui ne marche pas

### ANY.RUN plan Community : inutilisable pour `RealSetup.exe`

| Contrainte | Échantillon | Verdict |
|---|---|---|
| Taille max 16 Mo | 17 304 576 o = **16,5 MiB** | refusé à l'upload |
| VM Community = Windows 7 32-bit par défaut | PE32+ x64, refus explicite < Win10 (`OS_UNSUPPORTED`) | échec immédiat |
| Timeout 60 s | prereqs .NET 8 + VC++ + WebView2 avant tout téléchargement utile ; leurs propres timeouts sont de 8 min (.NET) et 5 min (VC++) | n'atteint jamais la phase intéressante |

Pièges supplémentaires, valables sur tout sandbox :

- **Le nom du fichier doit être préservé exactement** : le jeton y est lu. Pas d'ADS
  `:Zone.Identifier` en sandbox, donc un renommage en hash donne
  `Auth token source: none` → blocage sur « This copy wasn't downloaded from projectreal.gg ».
- **Soumission publique** en offre gratuite → le jeton est exposé.
- **Accès internet du sandbox obligatoire**, sinon l'updater échoue sur le manifeste.

### Contournement : soumettre `Update.exe` (stage 2) au lieu de `RealSetup.exe`

Vérification sur le stage 2 extrait (`b42d62f0e5275ed85cb8451973f6d3b953ab024c685cf9bb90bf10dbf48ad3b2`) :

```
Auth token source   -> 0
{"token" / Proof    -> 0
quick free step     -> 0
already been used   -> 0
```

Toutes les chaînes `Verify*` du stage 2 sont des fonctions d'intégrité de téléchargement
(`VerifySha256`, `VerifySignature`, `VerifyDigest`…), pas l'endpoint d'authentification.
Ses seuls points de contact Project Real :

```
https://api.projectreal.live/update/check
https://api.projectreal.live/installer/report
```

Conséquence : **`Update.exe` récupère le manifeste et télécharge les composants sans
jeton**, et pèse 8 559 104 o = 8,16 MiB, donc sous la limite des 16 Mo. Cela supprime
les blocages de taille, de jeton et d'exposition publique du jeton.

Restent l'architecture et la durée → privilégier **tria.ge** ou **Joe Sandbox Cloud
Basic** (Windows 10 x64, durées plus longues) plutôt qu'ANY.RUN Community.

### Points à relever dans le rapport de sandbox

1. La réponse de `/update/check` — le manifeste contient les URL réelles des composants.
2. Les fichiers déposés dans `%LOCALAPPDATA%\...\Real\` : `Real.dll`, `Injector`, `Spoofer.exe`.
3. Tout `.sys` écrit ou service créé → c'est le Spoofer, seul point réellement ouvert.
4. Confirmation dynamique des exclusions Defender et de la tâche `Real\Launch`.

Aucun anti-VM ni anti-sandbox n'étant présent dans les deux étages, le binaire se
comportera normalement en environnement virtualisé.

## Recommandation

Ne pas installer sur une machine qui compte. Si l'installation a déjà eu lieu :
retirer les exclusions Defender (`Get-MpPreference | Select -Expand ExclusionPath`
puis `Remove-MpPreference`), supprimer la tâche `Real\Launch`, contrôler `hosts`
(restaurer `hosts.REAL.bak`), contrôler `Get-DnsClientServerAddress`, supprimer
`HKCU\Software\Classes\projectreal`, lancer une analyse Defender hors ligne, changer
le mot de passe du compte Roblox.
