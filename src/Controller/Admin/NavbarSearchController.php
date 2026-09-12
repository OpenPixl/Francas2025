<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Search\NavbarArticleSearchType;
use App\Form\Search\NavbarEtablissementSearchType;
use App\Form\Search\NavbarUserSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Instancie, dans la zone de recherche de la navbar admin, le formulaire de
 * recherche Elastica correspondant à la route courante. Pour brancher une
 * nouvelle page, ajouter une entrée dans self::ROUTES ; le contrôleur visé
 * doit lire son champ "query" (form du même type, méthode GET) et répondre
 * en JSON ({'liste': ...}) sur requête AJAX, et exposer une route de
 * recherche instantanée (cf. "live_route") répondant en JSON ({'html': ...}).
 */
class NavbarSearchController extends AbstractController
{
    private const ROUTES = [
        'op_webapp_articles_index' => [
            'type' => NavbarArticleSearchType::class,
            'live_route' => 'op_webapp_articles_search_live',
        ],
        'op_admin_etablissement_index' => [
            'type' => NavbarEtablissementSearchType::class,
            'live_route' => 'op_admin_etablissement_search_live',
        ],
        'op_admin_user_index' => [
            'type' => NavbarUserSearchType::class,
            'live_route' => 'op_admin_user_search_live',
        ],
    ];

    public function search(string $route): Response
    {
        if (!isset(self::ROUTES[$route])) {
            return $this->render('admin/include/_navbarsearchdefault.html.twig');
        }

        $config = self::ROUTES[$route];

        $form = $this->createForm($config['type'], null, [
            'action' => $this->generateUrl($route),
            'method' => 'GET',
            'csrf_protection' => false,
        ]);

        return $this->render('admin/include/_navbarsearchform.html.twig', [
            'form' => $form->createView(),
            'live_url' => $this->generateUrl($config['live_route']),
        ]);
    }
}
