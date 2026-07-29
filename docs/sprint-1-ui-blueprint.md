# Balafon Broadcast Manager - Sprint Metier 1 UI Blueprint

Date: 2026-06-21

## 1. Objectif UX

Le sprint doit presenter un parcours client coherent:

`Medias -> Playlists -> Programmation TV`

Le design doit evoquer un centre de supervision broadcast premium:

- dense mais lisible
- sombre par defaut
- accent BALAFON rouge-orange
- data-heavy sans ressembler a un CRUD back-office

## 2. Direction visuelle

### Palette

- fond principal: `#050816`
- panneau principal: `#0b1020`
- panneau secondaire: `#121a2f`
- bordures: `rgba(255,255,255,0.08)`
- texte principal: `#f5f7fb`
- texte secondaire: `#94a3b8`
- accent primary: `#ff6b2c`
- accent secondary: `#ff9a3d`
- succes: `#10b981`
- attention: `#f59e0b`
- erreur: `#ef4444`

### Typographie

- titres: `Space Grotesk`
- texte d'interface: `Instrument Sans`
- chiffres / durees: meme famille avec tracking serre

### Signature visuelle

- side navigation fixe et sombre
- topbar translucide
- cartes a coins larges, ombres diffuses et halos subtils
- lignes temporelles avec marqueurs forts et blocs programmes colores

## 3. Architecture composants

### Shell

- `AppLayout`
- `Sidebar`
- `Topbar`
- `PageHeader`

### Feedback et lecture d'etat

- `StatCard`
- `StatusBadge`
- `AlertCard`
- `EmptyState`
- `LoadingState`
- `ConfirmDialog`

### Recherche et listing

- `SearchBar`
- `FilterPanel`
- `BroadcastCard`
- `PlaylistCard`
- `SchedulerCard`
- `TimelineCard`
- `ActivityFeed`

### Metier sprint 1

- `MediaGrid`
- `MediaListTable`
- `MediaPreviewPanel`
- `PlaylistTimeline`
- `PlaylistLibraryDrawer`
- `ScheduleTimeline`
- `ScheduleInspectorPanel`
- `ConflictBanner`

## 4. Wireframe - Page Medias

```text
+--------------------------------------------------------------------------------------+
| Sidebar | Topbar: search global | user | system status                              |
|         +--------------------------------------------------------------------------+ |
|         | PageHeader: Media Library | Add Media | Card/List toggle                 | |
|         +------------------------------+-------------------------------------------+ |
|         | FilterPanel                  | Main Canvas                               | |
|         | - search instant             | +---------------------------------------+ | |
|         | - type                       | | stats row: total / ready / archived   | | |
|         | - status                     | +---------------------------------------+ | |
|         | - duration bucket            | | cards grid or cinematic table         | | |
|         |                              | | item: title / type / duration / path  | | |
|         |                              | | quick preview / edit / delete         | | |
|         |                              | +---------------------------------------+ | |
|         +------------------------------+-------------------------------------------+ |
|         | Preview panel: poster / metadata / full file path / actions             | |
+--------------------------------------------------------------------------------------+
```

### Maquette cible

- gauche: panneau filtres compact et collant
- centre: catalogue medias en cartes cinematographiques
- droite: panneau de preview contextuel
- mode liste pour usage dense regie

## 5. Wireframe - Page Playlists

```text
+--------------------------------------------------------------------------------------+
| Sidebar | Topbar                                                                    |
|         +--------------------------------------------------------------------------+ |
|         | PageHeader: Playlists | New Playlist | Save | Publish                    | |
|         +-----------------------------+--------------------------------------------+ |
|         | Library column              | Playlist builder                           | |
|         | - playlists list            | +----------------------------------------+ | |
|         | - search                    | | header: title / status / total duration| | |
|         | - counters                  | +----------------------------------------+ | |
|         |                             | | timeline rows draggable                | | |
|         |                             | | position | asset | duration | cumulated| | |
|         |                             | | drop zone between tracks               | | |
|         |                             | +----------------------------------------+ | |
|         |                             | media drawer / add media modal            | |
|         +-----------------------------+--------------------------------------------+ |
+--------------------------------------------------------------------------------------+
```

### Maquette cible

- colonne gauche: catalogue playlists existantes
- zone centrale: editeur principal facon premiere / spotify queue
- ordre visuel tres clair avec duree totale et temps cumule
- drag and drop natif entre bibliotheque et playlist

## 6. Wireframe - Page Programmation TV

```text
+--------------------------------------------------------------------------------------+
| Sidebar | Topbar                                                                    |
|         +--------------------------------------------------------------------------+ |
|         | PageHeader: TV Scheduling | Day/Week | Duplicate Day | Duplicate Week    | |
|         +---------------------------+----------------------------------------------+ |
|         | Channels rail             | Broadcast timeline                            |
|         | - channel selector        | +-------------------------------------------+ |
|         | - date navigator          | | hour ruler                                | |
|         | - conflict summary        | +-------------------------------------------+ |
|         |                           | | channel lane                              | |
|         |                           | | [playlist block][block][conflict block]   | |
|         |                           | | drag move | resize edges                  | |
|         |                           | +-------------------------------------------+ |
|         |                           | inspector: starts_at / ends_at / status     | |
|         +---------------------------+----------------------------------------------+ |
+--------------------------------------------------------------------------------------+
```

### Maquette cible

- lecture calendrier proche Monday / Google Calendar
- timeline horizontale marquee par heures
- blocs programmes avec couleur de statut
- conflits visibles par contour rouge et badge dedie
- panneau inspecteur pour edition rapide

## 7. Regles composants

### AppLayout

- sidebar fixe desktop
- topbar collante
- zone principale scrollable
- support de densite elevee pour ecrans 1080p et plus

### StatusBadge

- `READY`, `SCHEDULED`, `ON_AIR`, `COMPLETED` en tonalites vertes / ambrees
- style compact avec capsule et point lumineux

### TimelineCard

- accepte un titre, un sous-titre, des actions et un contenu slot
- fond fonce contraste
- entete stable pour lecture planning

## 8. Parcours utilisateur cible

### Media

1. ouvrir la bibliotheque
2. filtrer par type
3. referencer un fichier local existant
4. verifier la preview et les metadonnees

### Playlist

1. creer une playlist
2. ajouter des medias depuis la bibliotheque
3. reordonner par drag and drop
4. verifier la duree totale

### Scheduling

1. choisir une chaine et une date
2. glisser une playlist sur la timeline
3. ajuster l'horaire
4. visualiser les conflits

## 9. Validation avant implementation

Le sprint sera considere visuellement coherent si:

- les trois pages utilisent le meme shell premium
- les blocs planning sont exploitables sans lire toute la ligne
- les actions principales sont accessibles sans surcharge d'ecran
- aucun ecran ne ressemble a une table CRUD Laravel standard
