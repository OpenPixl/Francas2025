# Section « un type d'établissement » (bouton vers la page dédiée du type)

## Contexte

Nouveau type de contenu de section, dans le groupe **Établissements** du champ
« Type de contenu » (`SectionType`, champ `content`) :

| Libellé (admin)              | Valeur `content`           |
|------------------------------|----------------------------|
| un établissement             | `ONE_ETABLISSEMENT`        |
| **un type d'établissement**  | **`ONE_TYPE_ETABLISSEMENT`** |
| tous les établissements      | `ALL_ETABLISSEMENTS`       |

En rendu, la section affiche un simple bouton **« Voir les {libellé du type} »**
qui pointe vers la page dédiée du type d'établissement — route existante
`op_webapp_etablissement_bytype` (`/webapp/etablissement/type/{idtype}`,
`EtablissementController::listEtablissementsByType`, template
`admin/etablissement/listetablissementsbytype.html.twig`). Aucun contrôleur ni
template nouveau, aucun fragment `render(controller(...))` : juste un `<a>`.

## Modifications

### `src/Entity/Webapp/Section.php`

- `use App\Entity\Admin\TypeEtablissement;`
- Nouvelle association `#[ORM\ManyToOne] private ?TypeEtablissement $typeEtablissement = null;`
  (sans `inversedBy` — `TypeEtablissement::$etablissements` ne pointe que vers `Etablissement`).
- Getter / setter `getTypeEtablissement()` / `setTypeEtablissement()`.

### `src/Form/Webapp/SectionType.php`

- Ajout du choix `"un type d'établissement" => 'ONE_TYPE_ETABLISSEMENT'` dans le
  groupe `Établissements` de `content`.
- `->add('typeEtablissement')` (type deviné → `EntityType`, comme `category` /
  `oneArticle` / `ressourcesCat`).

### `templates/webapp/section/_form.html.twig`

- Bloc `input_select_horizontal` « Choix du type d'établissement »
  (`form.typeEtablissement`), après « Choix de la ressource ».

### Rendu — partiel dédié inclus dans les deux gabarits

Nouveau partiel **`templates/webapp/section/include/_type_etablissement_button.html.twig`**
(param : `section`) — contient tout le markup du bouton, avec la garde
`{% if section.typeEtablissement %}` :

```twig
{% if section.typeEtablissement %}
    <div class="mt-3">
        <a href="{{ path('op_webapp_etablissement_bytype', {'idtype': section.typeEtablissement.id}) }}"
           class="inline-block rounded-md bg-[#d84519] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#c03d16]">
            Voir les {{ section.typeEtablissement.libelle }}
        </a>
    </div>
{% endif %}
```

(Classe Tailwind identique au bouton « Voir tous » de
`admin/etablissement/include/_listesearch.html.twig` — déjà dans le CSS compilé.)

`templates/webapp/section/include/_onesection.html.twig` (accueil) **et**
`templates/webapp/section/listsections.html.twig` (pages standard) : nouvelle
branche entre `ONE_ETABLISSEMENT` et `ALL_ETABLISSEMENTS` :

```twig
{% elseif section.content == 'ONE_TYPE_ETABLISSEMENT' %}
    {% include 'webapp/section/include/_type_etablissement_button.html.twig' with {'section': section} %}
```

## Migration

`migrations/Version20260910164108.php` — réécrite à la main (le `make:migration`
auto-généré embarquait tout le drift de schéma préexistant : FK manquantes,
renommages d'index sans rapport). Contenu réel :

```sql
ALTER TABLE section ADD type_etablissement_id INT DEFAULT NULL;
ALTER TABLE section ADD CONSTRAINT FK_2D737AEFA8FC9399 FOREIGN KEY (type_etablissement_id) REFERENCES type_etablissement (id);
CREATE INDEX IDX_2D737AEFA8FC9399 ON section (type_etablissement_id);
```

Colonne nullable : les sections existantes ne référencent aucun type.

## Déploiement

- `php bin/console doctrine:migrations:migrate`
- Vider le cache prod (routes/templates — cf. `francas2025_prod_deploy_needs_cache_clear`).
- Rien côté Elasticsearch. Rebuild assets non nécessaire (classes Tailwind déjà présentes).

## Vérifications effectuées

- `doctrine:migrations:migrate` — OK (3 requêtes SQL).
- `doctrine:mapping:describe Section` — association `typeEtablissement` bien mappée.
- `lint:twig templates/webapp/section/` — OK (15 fichiers, partiel inclus).
- `php -l` sur `Section.php` et `SectionType.php` — OK.
- `debug:router op_webapp_etablissement_bytype` — route présente.
