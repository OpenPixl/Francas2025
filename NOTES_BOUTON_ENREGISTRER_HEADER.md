# Boutons "Enregistrer" / "Mettre à jour" / "Supprimer" dans le header des vues d'édition

## Contexte

Toutes les vues de création/édition de l'administration affichent, sous la navbar, une ligne d'entête
(`admin/dashboard/include/header.html.twig`) avec un titre à gauche et des boutons à droite (« Retour »,
« Créer un nouveau »). Jusqu'ici, la seule façon de sauvegarder le formulaire était le bouton de soumission
placé en bas du formulaire lui-même (`composants/buttons/button_submit.html.twig`).

Objectif : ajouter, dans cette ligne d'entête, un bouton « Enregistrer »/« Mettre à jour » qui déclenche la
sauvegarde du formulaire — alors même que ce bouton est physiquement **en dehors** de la balise `<form>` et
ne peut donc pas être un simple `type="submit"` — puis retirer le bouton devenu redondant en bas du formulaire,
sans casser les autres vues qui réutilisent les mêmes formulaires partagés.

## 1. Header : nouvelle clé `btns.save`

`admin/dashboard/include/header.html.twig` gérait déjà `btns.return`, `btns.new`, `btns.edit`, `btns.del`. Ajout
d'un bloc `btns.save` sur le même modèle, rendu avec le composant existant `button_utils.html.twig` (inchangé) :

```twig
{% if btns.save is defined and btns.save is not null %}
    {{ include('composants/buttons/button_utils.html.twig', {
        'id': btns.save.id,
        'icon': btns.save.icon,
        'href': btns.save.href,
        'text': btns.save.text,
        'array':btns.save.array
    }) }}
{% endif %}
```

Convention retenue pour chaque vue qui l'active :

```twig
'save': {
    'id' : 'btn-header-save',
    'href' : '#',
    'icon' : '<i class="fa-duotone fa-solid fa-floppy-disk"></i>',
    'text' : 'Enregistrer',   // ou 'Mettre à jour' sur les vues d'édition
    'array':null
},
```

`id` fixe (`btn-header-save`) volontairement identique partout : une seule page n'affiche jamais deux boutons
de sauvegarde à la fois, donc pas de risque de collision — et ça simplifie le JS (un seul sélecteur partagé,
voir §2).

### Vues concernées (12, toutes celles qui utilisent déjà ce header)

| Entité | Nouvelle vue | Édition |
|---|---|---|
| Établissement (admin) | `admin/etablissement/new.html.twig` | `admin/etablissement/edit.html.twig` |
| Utilisateur | `admin/user/new.html.twig` | `admin/user/edit.html.twig` |
| Page | `webapp/page/new.html.twig` | `webapp/page/edit.html.twig` |
| Ressource | `webapp/ressources/new.html.twig` | `webapp/ressources/edit.html.twig` |
| Section | — (pas de header sur `section/new.html.twig`, hors périmètre) | `webapp/section/edit.html.twig` |
| Article (admin) | `webapp/articles/newadmin.html.twig` | `webapp/articles/edit_admin.html.twig` |
| Configuration du site | — (`config/new.html.twig` n'a pas ce header) | `admin/config/edit.html.twig` |

## 2. JavaScript : `bindHeaderSaveButton()`

Nouvelle fonction partagée dans `assets/js/composants/fonctions.js` :

```js
export function bindHeaderSaveButton() {
    const saveBtn = document.getElementById('btn-header-save');
    const form = document.querySelector('#content form');

    if (!saveBtn || !form) return;

    saveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        form.requestSubmit();
    });
}
```

`#content` est le conteneur `<main>` de `admin.html.twig` : chaque page n'y a qu'un seul `<form>` (vérifié),
donc pas besoin d'ID de formulaire dédié. `requestSubmit()` (plutôt que `submit()`) déclenche la validation
HTML native et les éventuels listeners `submit` existants, exactement comme un clic sur le bouton natif du
formulaire.

Câblée en tête de chaque fonction `init*()` déjà appelée par `assets/admin.js` selon la route
(`document.body.dataset.page`) :

- `NewEditEtablissement.js`, `NewEditUser.js`, `NewEditPage.js`, `NewEditArticles.js`, `EditConfig.js`
  (fichiers déjà existants, import ajouté).
- `NewEditRessources.js`, `NewEditSection.js` (**nouveaux fichiers** — ces deux contrôleurs n'avaient
  jusqu'ici aucune organisation JS dédiée : ressources chargeait son JS en ligne dans le twig
  (`window.onload`), section n'en avait aucun. Créés sur le même modèle que les fichiers existants,
  pour l'instant réduits à l'appel de `bindHeaderSaveButton()`).

### Bug corrigé au passage dans `assets/admin.js`

Le `switch (page)` associait `initNewEditArticle()` (init CKEditor + désormais le bouton save) à
`'op_webapp_articles_new_admin'` — un nom de route qui **n'existe pas** (la vraie route est
`op_webapp_articles_newadmin`, sans le second underscore). Résultat : sur la page de création d'article admin,
`initNewEditArticle()` ne s'exécutait jamais ; seul un script `window.onload` dupliqué en ligne dans
`newadmin.html.twig` initialisait CKEditor, sans le bouton d'entête. Corrigé :
- `'op_webapp_articles_new_admin'` → `'op_webapp_articles_newadmin'` dans le `switch`.
- Script `window.onload` dupliqué retiré de `newadmin.html.twig` (redondant avec `initNewEditArticle()`,
  qui gère maintenant correctement les deux pages new/edit).

## 3. Retrait du bouton de soumission dans les formulaires

Sur les 12 vues listées au §1, le bouton `button_submit.html.twig` en bas du formulaire est devenu redondant
avec le nouveau bouton d'entête. Mais les fichiers `_form.html.twig` qui le contiennent sont **partagés** avec
d'autres vues n'ayant pas ce header (espace établissement en self-service :
`espace_etablissement/editetablissement.html.twig`, `espace_etablissement/newressourcebyetablissement.html.twig`,
`webapp/ressources/newressourcebyetablissement.html.twig`, et `webapp/section/new.html.twig`) — le supprimer
purement et simplement les aurait laissées sans aucun moyen de sauvegarder.

Solution : un paramètre optionnel `hide_submit_button` sur chaque formulaire concerné, qui encadre le bouton :

```twig
{% if hide_submit_button is not defined or not hide_submit_button %}
    <div id="bouton_form">
        {% include 'composants/buttons/button_submit.html.twig' with { ... } %}
    </div>
{% endif %}
```

Passé à `true` uniquement depuis les 12 vues du §1 (`{{ include('.../_form.html.twig', {'hide_submit_button': true, ...}) }}`).
Par défaut (paramètre absent), le bouton reste affiché — comportement inchangé pour toutes les autres vues.

### Formulaires modifiés

- `admin/etablissement/_form.html.twig`
- `admin/user/_form.html.twig`
- `webapp/page/_form.html.twig`
- `webapp/ressources/_form.html.twig`
- `webapp/section/_form.html.twig`
- `webapp/articles/_form2.html.twig`
- `admin/config/_form.html.twig` (bouton en dur, pas via `button_submit.html.twig` — seul consommateur réel :
  `admin/config/edit.html.twig` ; `admin/config/new.html.twig` inclut un chemin `admin_config/_form.html.twig`
  différent et déjà inexistant, bug préexistant hors périmètre)

## 4. Bouton "Supprimer" (`btns.del`) — rouge, avec confirmation

Demande complémentaire : ajouter un bouton « Supprimer » dans le même header, en rouge, qui ouvre la
**modale de confirmation existante** (`composants/modules/dialog.html.twig` + `showDialog()`/`hideDialog()`)
avant d'agir — jamais de suppression directe au clic.

### Portée

Uniquement les 6 vues d'**édition** d'une entité déjà persistée (rien à supprimer sur une vue de création) :
établissement, utilisateur, page, ressource, section, article (admin). **Exclu explicitement** :
`admin/config/edit.html.twig` — singleton de paramètres globaux dont la suppression risquerait de casser
l'affichage du site (dépendance dans de nombreux templates) ; décision validée avec l'utilisateur plutôt que
supposée.

### `btns.del` existait déjà dans le header (jamais utilisé)

`admin/dashboard/include/header.html.twig` gérait déjà une clé `btns.del`, mais aucune vue ne l'alimentait.
Réutilisée telle quelle, avec deux ajouts pour couvrir le besoin :
- `variant: 'danger'` → transmis à `button_utils.html.twig`, qui accepte désormais ce paramètre optionnel et
  bascule ses classes Tailwind sur un style rouge (`border-red-700 text-red-700 hover:bg-red-700
  hover:text-white`) au lieu du gris par défaut — composant inchangé pour tous les autres appels (`return`,
  `new`, `save`, `edit`), qui ne passent pas `variant` et gardent leur style habituel.
- `data: { ... }` → `button_utils.html.twig` accepte aussi un dictionnaire optionnel d'attributs `data-*`
  supplémentaires (rendus tels quels), utilisé ici pour transmettre au JS tout ce qu'il faut sans toucher au
  composant partagé pour un seul cas d'usage : `delete-url`, `delete-method` (`DELETE` ou `POST` selon la
  route réelle de chaque contrôleur), `csrf-token` (`csrf_token('delete' ~ entity.id)`, même convention que
  les anciens formulaires de suppression), `redirect-url` (voir §4bis — toujours la même destination que le
  bouton « Retour »), et `confirm-message` (texte affiché dans la modale).

### JS : `bindHeaderDeleteButton()`

Nouvelle fonction partagée dans `fonctions.js`, câblée en tête de chaque `init*()` déjà utilisé pour le
bouton save (no-op silencieux si `#btn-header-delete` est absent de la page — même principe que
`bindHeaderSaveButton()`). Au clic : ouvre `showDialog()` avec le message de confirmation ; seulement après
validation dans la modale, exécute la requête (`axios.request({ url, method, data })`, verbe HTTP et body
`_token` conformes à ce qu'attend chaque contrôleur), puis redirige (`window.location.href`) vers
`redirect-url`. En cas d'échec réseau, notification d'erreur, la page ne bouge pas.

### Bug latent évité : deux confirmations sur la même modale

`composants/modules/dialog.html.twig` expose une seule modale globale (`#validModal`) réutilisée par toutes
les actions de confirmation de la page. Or `NewEditEtablissement.js` avait déjà sa propre confirmation
(suppression du logo/bandeau) qui attachait directement un `addEventListener('click', ...)` sur
`#validModal`. Ajouter une deuxième confirmation (suppression de l'établissement) de la même façon aurait fait
tourner **les deux** actions à chaque clic sur « Valider », quelle que soit la modale ouverte (double requête,
verbe HTTP erroné sur l'une des deux, toast d'erreur trompeur).

Corrigé à la racine : `showDialog()` accepte désormais un 4ᵉ paramètre optionnel `onConfirm` et gère
elle-même **un seul** écouteur délégué sur `#validModal` (bindé une fois, `dialogConfirmBound`), qui appelle
systématiquement le callback du dernier appel à `showDialog()` (`dialogConfirmHandler`). Le flux de
suppression de média dans `NewEditEtablissement.js` a été migré vers cette même API (son
`axios.post(...).then(...)` est passé en callback de `showDialog()` au lieu d'un `addEventListener` séparé).
Rétrocompatible : les appels existants à 3 arguments (sans `onConfirm`, dans `IndexArticles.js`,
`IndexUser.js`, `ShowPage.js`, etc.) continuent de gérer leur propre écouteur sur `.submitModal` exactement
comme avant, sans changement de comportement.

### Vues concernées

| Entité | Route de suppression | Verbe | Redirection (= `return_url`, voir §4bis) |
|---|---|---|---|
| Établissement | `op_admin_etablissement_delete` | DELETE | `op_admin_etablissement_index` |
| Utilisateur | `op_admin_user_delete` | DELETE | `op_admin_user_index` |
| Page | `op_webapp_page_delete` | POST | `op_webapp_page_index` |
| Ressource | `op_webapp_ressources_delete` | POST | `op_webapp_ressources_index` |
| Section | `op_webapp_section_delete` | DELETE | `op_admin_page_show` (page parente) |
| Article (admin) | `op_webapp_articles_delete` | DELETE | `op_webapp_articles_index` |

À noter : `op_admin_user_delete` n'était jusqu'ici relié à **aucune** vue (`admin/user/_delete_form.html.twig`
existe mais n'est inclus nulle part) — le bouton d'entête est donc le premier point d'entrée UI fonctionnel
pour supprimer un utilisateur.

## 4bis. Redirection après suppression : toujours la même destination que « Retour »

Pour que l'expérience reste fluide, une fois la suppression validée, l'utilisateur ne doit pas atterrir sur
une page qui n'existe plus (celle qu'il vient de supprimer) ni sur une destination différente de celle vers
laquelle « Retour » l'aurait de toute façon ramené. Plutôt que de calculer deux fois la même URL (une fois
pour `btns.return.href`, une fois pour `btns.del.data.redirect-url` — avec le risque qu'elles divergent si
l'une des deux est modifiée sans l'autre), chaque vue calcule l'URL de retour **une seule fois** dans une
variable Twig et la réutilise aux deux endroits :

```twig
{% block header %}
    {% set return_url = path('op_webapp_page_index') %}
    {{ include('admin/dashboard/include/header.html.twig', {
        'btns' : {
            'return': {
                'href' : return_url,
                ...
            },
            'del': {
                'data': {
                    'redirect-url': return_url,
                    ...
                }
            }
        }
    }) }}
{% endblock %}
```

`bindHeaderDeleteButton()` (§4, JS) fait ensuite `window.location.href = redirectUrl` une fois la suppression
confirmée côté serveur — l'utilisateur repart donc exactement là où « Retour » l'aurait emmené, garanti par
construction plutôt que par duplication.

Cas particulier : sur `webapp/section/edit.html.twig`, `return_url` pointe vers `op_admin_page_show` (la page
parente de la section, pas un index générique) — déjà le comportement du bouton « Retour » avant l'ajout du
bouton Supprimer ; la redirection après suppression suit donc naturellement la même règle, sans code
spécifique à écrire.

Appliqué aux 6 vues concernées : `admin/etablissement/edit.html.twig`, `admin/user/edit.html.twig`,
`webapp/page/edit.html.twig`, `webapp/ressources/edit.html.twig`, `webapp/section/edit.html.twig`,
`webapp/articles/edit_admin.html.twig`.

## Fichiers créés

- `NOTES_BOUTON_ENREGISTRER_HEADER.md`
- `assets/js/admin/admin/NewEditRessources.js`
- `assets/js/admin/admin/NewEditSection.js`

## Fichiers modifiés

- `templates/admin/dashboard/include/header.html.twig`
- `templates/composants/buttons/button_utils.html.twig`
- `templates/admin/etablissement/{new,edit,_form}.html.twig`
- `templates/admin/user/{new,edit,_form}.html.twig`
- `templates/webapp/page/{new,edit,_form}.html.twig`
- `templates/webapp/ressources/{new,edit,_form}.html.twig`
- `templates/webapp/section/{edit,_form}.html.twig`
- `templates/webapp/articles/{newadmin,edit_admin,_form2}.html.twig`
- `templates/admin/config/{edit,_form}.html.twig`
- `assets/admin.js`
- `assets/js/composants/fonctions.js`
- `assets/js/composants/tailwind.js` (`showDialog()` : paramètre `onConfirm` optionnel)
- `assets/js/admin/admin/NewEditEtablissement.js`
- `assets/js/admin/admin/NewEditUser.js`
- `assets/js/admin/admin/NewEditPage.js`
- `assets/js/admin/admin/EditConfig.js`
- `assets/js/admin/webapp/NewEditArticles.js`

## Vérifications effectuées

- `yarn encore dev` : build réussi (deux fois, avant et après l'ajout du bouton Supprimer), code présent dans
  `public/build/admin.js` (vérifié par recherche des chaînes `btn-header-save`/`btn-header-delete`/
  `bindHeaderSaveButton`/`bindHeaderDeleteButton`/`requestSubmit`/`dialogConfirmHandler`).
- `php bin/console lint:twig templates/` : les 6 erreurs restantes sont préexistantes et hors périmètre
  (`vich_uploader_asset` non enregistré, fichiers non touchés par ce chantier).
- Rendu testé sur plusieurs routes (`webapp/page/new`, `webapp/articles/newadmin`,
  `webapp/articles/{id}/editAdmin`, page d'édition établissement) : bouton d'entête présent avec le bon
  `id`/`href`/classes rouges/attributs `data-*` (URL, verbe, token CSRF, redirection, message), bouton de
  formulaire disparu sur les pages concernées.
- Vérifié par grep que les vues non concernées (`webapp/section/new.html.twig`,
  `webapp/ressources/newressourcebyetablissement.html.twig`,
  `espace_etablissement/newressourcebyetablissement.html.twig`,
  `espace_etablissement/editetablissement.html.twig`) n'ont pas reçu `hide_submit_button` et conservent donc
  leur bouton de soumission habituel.
