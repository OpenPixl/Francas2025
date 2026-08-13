# Bouton "Enregistrer" / "Mettre à jour" dans le header des vues d'édition

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

## Fichiers créés

- `NOTES_BOUTON_ENREGISTRER_HEADER.md`
- `assets/js/admin/admin/NewEditRessources.js`
- `assets/js/admin/admin/NewEditSection.js`

## Fichiers modifiés

- `templates/admin/dashboard/include/header.html.twig`
- `templates/admin/etablissement/{new,edit,_form}.html.twig`
- `templates/admin/user/{new,edit,_form}.html.twig`
- `templates/webapp/page/{new,edit,_form}.html.twig`
- `templates/webapp/ressources/{new,edit,_form}.html.twig`
- `templates/webapp/section/{edit,_form}.html.twig`
- `templates/webapp/articles/{newadmin,edit_admin,_form2}.html.twig`
- `templates/admin/config/{edit,_form}.html.twig`
- `assets/admin.js`
- `assets/js/composants/fonctions.js`
- `assets/js/admin/admin/NewEditEtablissement.js`
- `assets/js/admin/admin/NewEditUser.js`
- `assets/js/admin/admin/NewEditPage.js`
- `assets/js/admin/admin/EditConfig.js`
- `assets/js/admin/webapp/NewEditArticles.js`

## Vérifications effectuées

- `yarn encore dev` : build réussi, code présent dans `public/build/admin.js` (vérifié par recherche des
  chaînes `btn-header-save`/`bindHeaderSaveButton`/`requestSubmit`).
- `php bin/console lint:twig templates/` : les 6 erreurs restantes sont préexistantes et hors périmètre
  (`vich_uploader_asset` non enregistré, fichiers non touchés par ce chantier).
- Rendu testé sur plusieurs routes (`webapp/page/new`, `webapp/articles/newadmin`, page d'édition
  établissement) : bouton d'entête présent avec le bon `id`/`href`, bouton de formulaire disparu sur les
  pages concernées.
- Vérifié par grep que les vues non concernées (`webapp/section/new.html.twig`,
  `webapp/ressources/newressourcebyetablissement.html.twig`,
  `espace_etablissement/newressourcebyetablissement.html.twig`,
  `espace_etablissement/editetablissement.html.twig`) n'ont pas reçu `hide_submit_button` et conservent donc
  leur bouton de soumission habituel.
