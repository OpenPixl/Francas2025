# Listes « recherche + sélection » (Tom Select) & désactivation de Turbo Drive

## Contexte

Deux chantiers liés (mêmes fichiers `assets/admin.js` / `assets/app.js`), menés ensemble le 08/09/2026
puis complétés le 09/09/2026 :

1. Remplacer les `<select>` natifs liés à une entité (choisir une page, une catégorie, un article, un
   thème…) par une liste déroulante **« recherche + sélection »** (filtrage au clavier), au style aligné
   sur les autres champs de l'admin.
2. **Désactiver Turbo Drive**, qui n'apportait qu'une navigation SPA source de bugs de rendu (le `<head>`
   / le CSS d'une page persistant sur la suivante), sans qu'aucune fonctionnalité Turbo (`<turbo-frame>`,
   `<turbo-stream>`, Mercure/Broadcast) ne soit utilisée dans le projet.

---

## 1. `initSearchSelects()` — Tom Select sur `[data-search-select]`

`assets/js/composants/fonctions.js` — nouvelle fonction exportée :

```js
export function initSearchSelects(root = document) {
    root.querySelectorAll('select[data-search-select]').forEach((select) => {
        if (select.tomselect || select.classList.contains('ts-hidden-accessible')) {
            return; // idempotent
        }

        const isRequired = select.hasAttribute('required') && !select.disabled;
        const isMultiple = select.multiple;
        const emptyOption = select.querySelector('option[value=""]');
        const placeholder = select.dataset.placeholder
            || (emptyOption ? emptyOption.textContent.trim() : '')
            || (isMultiple ? 'Sélectionner…' : 'Rechercher…');

        // Multi  : chips avec croix de retrait par item + vidage global, pas de limite.
        // Simple : bouton d'effacement uniquement si le champ n'est pas requis.
        const plugins = isMultiple
            ? ['remove_button', 'clear_button']
            : (isRequired ? [] : ['clear_button']);

        new TomSelect(select, {
            create: false,                          // pas de création de valeur libre
            allowEmptyOption: !isRequired,
            maxOptions: null,
            maxItems: isMultiple ? null : 1,
            placeholder: placeholder,
            wrapperClass: 'ts-wrapper ts-styled',
            dropdownClass: 'ts-dropdown ts-styled-dropdown',
            dropdownParent: 'body',                 // sort le menu du flux (conteneurs overflow-hidden)
            plugins: plugins,
            sortField: { field: '$order' },         // conserve l'ordre des <option> serveur
        });
    });
}
```

- **Idempotente** : les `<select>` déjà initialisés (`select.tomselect` ou classe
  `ts-hidden-accessible`) sont ignorés. Appelable sur n'importe quel formulaire.
- **Pas de création de valeur** (`create: false`) : c'est un sélecteur d'entités existantes, pas un champ
  de tags libres. Pour ce dernier cas, l'ancienne fonction `useTomSelect()` du même fichier reste
  disponible.
- **`dropdownParent: 'body'`** : les champs sont souvent dans un conteneur `overflow-hidden`
  (formulaires en colonnes) — sans ça le menu déroulant serait rogné.
- Le **mode multiple** (`<select multiple>`) est détecté automatiquement (`select.multiple`) : chips avec
  croix de retrait (`remove_button`), bouton de vidage global (`clear_button`), aucune limite d'items
  (`maxItems: null`). Ajout du 09/09/2026 pour les thèmes d'article (voir
  `NOTES_RECHERCHE_ARTICLES.md`, §13).

### Style — `.ts-styled` / `.ts-styled-dropdown`

`assets/styles/admin.css` — bloc dédié qui aligne l'apparence sur
`templates/composants/forms/input_text_horizontal.html.twig` (mêmes `border-gray-300`,
`bg-gray-100/70`, `text-sm`, `rounded`, focus `bg-white` + `sky-300`…). Les classes `.ts-styled` /
`.ts-styled-dropdown` ajoutent de la **spécificité** pour passer devant le CSS de base de Tom Select,
chargé après cette feuille.

- Le `.ts-wrapper` hérite des classes Tailwind du `<select>` d'origine (border, padding, bg) : elles
  sont **neutralisées** sur le wrapper, le style visible est porté par `.ts-control`.
- `align-items: center` sur `.ts-control` (ajouté le 09/09/2026, `admin.css` **et** `app.css`) : le
  `.ts-control` est un flex sans `align-items` par défaut, les puces et le champ de saisie n'étaient pas
  centrés verticalement.
- `app.css` importe déjà `tom-select/dist/css/tom-select.css` (front public) ; le correctif de centrage
  y est dupliqué sans les classes `.ts-styled` (non utilisées côté front pour l'instant).

## 2. Nouveaux composants de formulaire

| Composant | Pour | Remarques |
|---|---|---|
| `templates/composants/forms/input_select_horizontal.html.twig` | `<select>` simple | `search` activé par défaut (`data-search-select`) ; `search: false` pour un select natif juste stylisé. |
| `templates/composants/forms/input_selectMulti_horizontale.html.twig` | `<select multiple>` | Jumeau du précédent ; le form-type Symfony doit être un `EntityType`/`ChoiceType` avec `'multiple' => true`. Mode multi + retrait par item détectés automatiquement par `initSearchSelects()`. |

Même gabarit visuel que `input_text_horizontal.html.twig` (label à gauche `w-3/12`, champ à droite
`w-9/12`). Paramètres : `form`, `label`, `legend_width`, `col_width`, `input_width`, `search`,
`placeholder`, `onchange`.

### Formulaires migrés

- `templates/webapp/section/_form.html.twig` : les **5** listes déroulantes (page d'affichage, type de
  contenu, catégorie, article, ressource) passent de `input_text_horizontal.html.twig` à
  `input_select_horizontal.html.twig`, avec un `placeholder` explicite (« Rechercher une page », …) et
  suppression des `oninput` vides.
- `templates/webapp/articles/_form.html.twig` et `_form2.html.twig` : champ « Thèmes » via
  `input_selectMulti_horizontale.html.twig` (voir `NOTES_RECHERCHE_ARTICLES.md`, §13).

## 3. Câblage global (indépendant de la page)

`assets/admin.js` et `assets/app.js` : `initSearchSelects()` est appelée **une fois** au chargement,
dans le `onReady()` de tête, avant le `switch (page)` — donc active sur tous les formulaires d'admin et
du front sans câblage par route.

---

## 4. Turbo Drive désactivé

`assets/controllers.json` :

```json
"@symfony/ux-turbo": {
    "turbo-core": {
        "enabled": false,
        "fetch": "eager"
    },
    ...
}
```

### Pourquoi

- **Aucun** `<turbo-frame>` / `<turbo-stream>` / Broadcast dans le projet : Turbo ne servait qu'à la
  navigation SPA (remplacement du `<body>` sans rechargement).
- Cette navigation reportait le `<head>` / le CSS d'une page sur la suivante — p. ex. les styles de la
  page d'erreur Symfony restant appliqués après un retour en arrière, ou `app.css` et `admin.css`
  cohabitant lors d'une bascule front ↔ admin.
- Les scripts de page, réinitialisés à chaque `turbo:load`, se ré-attachaient en double.

### Conséquences dans le code

- `assets/admin.js` / `assets/app.js` : l'écouteur `document.addEventListener('turbo:load', …)` est
  remplacé par une fonction `onReady(callback)` (exécute au `DOMContentLoaded`, ou tout de suite si le
  DOM est déjà prêt). **Chaque navigation est désormais un chargement de page complet.**

  ```js
  function onReady(callback) {
      if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', callback);
      } else {
          callback();
      }
  }
  ```

- Le JS de page se câble sur `onReady()` — **ne pas réintroduire d'écouteur `turbo:load`** (il ne se
  déclenchera jamais).
- `config/packages/webpack_encore.yaml` : `data-turbo-track: reload` (ajouté le 08/09/2026 sur
  `script_attributes` et `link_attributes`) est désormais **sans effet** (Turbo désactivé). Laissé en
  place, inoffensif, à retirer si on confirme l'abandon de Turbo à long terme.

### Effet de bord révélé : CSRF de la page de login

Turbo maintenait un écouteur `submit` **global** persistant entre les pages, qui masquait l'absence du
contrôleur Stimulus `csrf-protection` sur le formulaire de login. Une fois Turbo désactivé, le login
renvoyait « Invalid CSRF token ». Corrigé séparément — voir
`NOTES_CORRECTIFS_2026-09.md`, §3.

---

## Fichiers créés

- `NOTES_TOMSELECT_ET_TURBO.md`
- `templates/composants/forms/input_select_horizontal.html.twig`
- `templates/composants/forms/input_selectMulti_horizontale.html.twig`

## Fichiers modifiés

- `assets/controllers.json` (turbo-core `enabled: false`)
- `assets/admin.js`, `assets/app.js` (`turbo:load` → `onReady()`, appel `initSearchSelects()`)
- `assets/js/composants/fonctions.js` (`initSearchSelects()`, gestion `<select multiple>`)
- `assets/styles/admin.css` (bloc `.ts-styled` / `.ts-styled-dropdown`, `align-items: center`)
- `assets/styles/app.css` (`.ts-wrapper .ts-control { align-items: center }`)
- `templates/webapp/section/_form.html.twig` (5 selects)
- `templates/webapp/articles/_form.html.twig`, `_form2.html.twig` (champ « Thèmes » multi)
- `config/packages/webpack_encore.yaml` (`data-turbo-track: reload` — désormais inerte)

## Déploiement

- Rebuild des assets (`yarn encore production` / pipeline habituel) obligatoire : JS et CSS modifiés.
- Rien côté base ou Elasticsearch pour ce chantier (voir `NOTES_RECHERCHE_ARTICLES.md` pour la partie
  thèmes multiples).
