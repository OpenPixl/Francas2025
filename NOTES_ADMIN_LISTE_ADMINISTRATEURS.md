# Page « Administrateurs » (liste des utilisateurs `typeuser = administrator`)

## Contexte

La page `admin/user/index.html.twig` (route `op_admin_user_index`) liste uniquement les
membres établissements (`UserRepository::indexEtablissementsOnly()`, filtre
`typeuser = 'etablissement'`). Besoin d'une page équivalente, mais listant uniquement les
comptes administrateurs (`typeuser = 'administrator'`), accessible depuis le dropdown
utilisateur de la navbar admin (remplace l'entrée « Votre profil », cf.
`NOTES_CORRECTIFS_2026-09.md` §5).

## Modifications

### `src/Repository/Admin/UserRepository.php`

Nouvelle méthode `indexAdministrateursOnly()`, doublon de `indexEtablissementsOnly()` avec le
filtre inversé :

```php
public function indexAdministrateursOnly()
{
    return $this->createQueryBuilder('u')
        ->where('u.typeuser LIKE :role')
        ->setParameter('role', 'administrator')
        ->getQuery()
        ->getResult()
        ;
}
```

### `src/Controller/Admin/userController.php`

Nouvelle action `listAdministrateur()`, doublon de `index()` :

- Route `GET /admin/user/administrateurs` (`op_admin_user_list_administrateur`), déclarée
  juste après `op_admin_user_index` et avant `op_admin_user_new` / `op_admin_user_show` — le
  segment statique `administrateurs` doit être testé avant le paramètre générique `{id}` de
  `show()`, sans quoi le routeur tenterait de résoudre `id = "administrateurs"`.
- Appelle `indexAdministrateursOnly()` au lieu de `indexEtablissementsOnly()`.
- **Réutilise le même template** `admin/user/index.html.twig` (pas de duplication de vue) :
  seule la donnée injectée change.

### `templates/admin/user/include/_liste.html.twig`

La colonne « Établissement » (en-tête + cellule, avec le lien « Ajout de l'établissement »
quand `user.etablissement` est vide) n'a pas de sens pour des administrateurs. Masquée par un
conditionnel sur la route courante plutôt que par un template dédié :

```twig
{% if app.request.attributes.get('_route') != 'op_admin_user_list_administrateur' %}
    <th class="text-start">Établissement</th>
{% endif %}
...
{% if app.request.attributes.get('_route') != 'op_admin_user_list_administrateur' %}
    <td>...</td>
{% endif %}
```

Le partiel reste donc commun aux deux pages (`op_admin_user_index` et
`op_admin_user_list_administrateur`).

### `templates/admin/include/navbar_admin.html.twig`

Entrée du dropdown utilisateur : « Votre profil » → **« Administrateurs »**, `href` pointant
vers `op_admin_user_list_administrateur` (cf. `NOTES_CORRECTIFS_2026-09.md` §5 pour l'origine
du câblage du dropdown).

## Déploiement

- Aucune migration (pas de changement de schéma).
- Vider le cache prod (nouvelle route — cf. `francas2025_prod_deploy_needs_cache_clear`).
- Rebuild assets non nécessaire (pas de JS touché par cette page).

## Vérifications effectuées

- `debug:router` — `op_admin_user_list_administrateur` (`GET /admin/user/administrateurs`)
  présent et testé **avant** `op_admin_user_show` (`GET /admin/user/{id}`).
- `lint:twig` sur `_liste.html.twig` et `index.html.twig` — OK.
- `php -l` sur `userController.php` et `UserRepository.php` — OK.
