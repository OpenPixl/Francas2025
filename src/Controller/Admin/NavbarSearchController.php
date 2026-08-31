<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Search\NavbarArticleSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Instancie, dans la zone de recherche de la navbar admin, le formulaire de
 * recherche Elastica correspondant à la route courante. Pour brancher une
 * nouvelle page, ajouter une entrée dans self::ROUTES ; le contrôleur visé
 * doit lire son champ "query" (form du même type, méthode GET) et répondre
 * en JSON ({'liste': ...}) sur requête AJAX.
 */
class NavbarSearchController extends AbstractController
{
    private const ROUTES = [
        'op_webapp_articles_index' => NavbarArticleSearchType::class,
    ];

    public function search(string $route): Response
    {
        if (!isset(self::ROUTES[$route])) {
            return $this->render('admin/include/_navbarsearchdefault.html.twig');
        }

        $form = $this->createForm(self::ROUTES[$route], null, [
            'action' => $this->generateUrl($route),
            'method' => 'GET',
            'csrf_protection' => false,
        ]);

        return $this->render('admin/include/_navbarsearchform.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
