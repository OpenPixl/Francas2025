# Vue "établissements par section" — regroupement, pagination et recherche

## Contexte

La vue `admin/etablissement/listetablissementsbysection.html.twig` (route `_etablissementsbysection`,
contrôleur `EtablissementController::listEtablissementsBySection`) est incluse en fragment (`render(controller(...))`)
depuis les pages de section (`section.content == 'ALL_ETABLISSEMENTS'`, voir
`templates/webapp/section/listsections.html.twig` et `include/_onesection.html.twig`).
Elle affichait initialement tous les établissements actifs d'une section sous forme de grille plate.

Trois évolutions ont été apportées à cette vue :

1. Regroupement visuel par **type d'établissement**.
2. Limitation à **8 établissements affichés par type**, avec bouton "Voir tous" vers une page dédiée si le type en compte plus.
3. Ajout du **markup HTML** d'un formulaire de recherche (texte + filtre par type), sans logique de traitement.

---

## 1. Regroupement par type d'établissement

- `EtablissementRepository::listEtablissementsBySection($idsection)` : ajout d'une jointure `e.typeEtablissement` (`t`)
  et sélection de `t.id as idTypeEtablissement, t.libelle as typeEtablissementLibelle`, tri par libellé de type puis par ville.
- `EtablissementController::listEtablissementsBySection` : regroupe le résultat plat en `$etablissementsByType`
  (clé = libellé du type, `'Autre'` si non renseigné) avant de le passer au twig.
- Le twig boucle sur `etablissementsByType` et affiche un `<h3>` par type au-dessus de chaque grille.

## 2. Limite à 8 + page dédiée par type

- Dans le twig, chaque grille utilise `etablissements|slice(0, 8)`.
- Si `etablissements|length > 8`, un bouton "Voir tous les établissements (N)" apparaît, pointant vers la nouvelle route
  `op_webapp_etablissement_bytype` (`/webapp/etablissement/type/{idtype}`), avec `idtype = etablissements[0].idTypeEtablissement`.
- Nouveau : `EtablissementRepository::listEtablissementsByType($idTypeEtablissement)` (établissements actifs d'un type donné, triés par ville).
- Nouveau : `EtablissementController::listEtablissementsByType` — rend `templates/admin/etablissement/listetablissementsbytype.html.twig`
  (page complète, `extends base.html.twig`, même grille visuelle, titre = libellé du type).

## 3. Markup du formulaire de recherche (sans logique)

Un `<form method="get" action="">` a été ajouté en haut de la vue, avec :
- un champ texte `q` (recherche libre, style repris du composant `composants/forms/inputs/input_search.html.twig`) ;
- un `<select name="typeEtablissement">` listant les types présents dans `etablissementsByType` ;
- un bouton "Rechercher".

**Ce formulaire n'est pas branché.** Il n'y a ni route de traitement, ni contrôleur, ni requête Elastica derrière —
uniquement le HTML/Tailwind, à intégrer volontairement par vos soins.

### État de la configuration Elastica (constat, non modifié)

`config/packages/fos_elastica.yaml` indexe actuellement `App\Entity\Gestapp\Articles`, **une classe qui n'existe pas**
dans le code (probablement un reliquat du scaffolding initial du bundle, cf. commit "Préparation fos/elastica -
installation du bundle"). L'entité réelle représentant les "posts" des établissements est `App\Entity\Webapp\Article`
(`title`, `intro`, `content`, `etablissement`, `createdAt`, ...), reliée à `Etablissement` (et donc à `TypeEtablissement`
via `etablissement.typeEtablissement`). Un service Elasticsearch 7.17 tourne déjà en local (`docker-compose`,
port 9200) et `friendsofsymfony/elastica-bundle` (v7.2.0) est installé. À corriger/compléter avant de brancher la
recherche sur cette entité.

---

## Fichiers créés

- `templates/admin/etablissement/listetablissementsbytype.html.twig`

## Fichiers modifiés

- `templates/admin/etablissement/listetablissementsbysection.html.twig`
- `src/Repository/Admin/EtablissementRepository.php`
- `src/Controller/Admin/EtablissementController.php`

## Vérifications effectuées

- `php bin/console lint:twig` sur les deux templates — OK.
- `php -l` sur le contrôleur et le repository — OK.
- `php bin/console debug:router` — route `op_webapp_etablissement_bytype` bien enregistrée, pas de conflit.
