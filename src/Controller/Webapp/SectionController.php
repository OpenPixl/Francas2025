<?php

namespace App\Controller\Webapp;

use App\Entity\Webapp\Page;
use App\Entity\Webapp\Section;
use App\Form\Webapp\SectionType;
use App\Repository\Webapp\SectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SectionController extends AbstractController
{
    /**
     * Liste toutes les sections
     */
    #[Route(path: '/webapp/section/', name: 'op_webapp_section_index', methods: ['GET'])]
    public function index(SectionRepository $sectionRepository): Response
    {
        return $this->render('webapp/section/index.html.twig', [
            'sections' => $sectionRepository->findAll(),
        ]);
    }

    /**
     * Ajoute une section
     */
    #[Route(path: '/webapp/section/new', name: 'op_webapp_section_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $section = new Section();
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($section);
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_section_index');
        }

        return $this->render('webapp/section/new.html.twig', [
            'section' => $section,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche une section selon son Id
     */
    #[Route(path: '/webapp/section/{id}', name: 'op_webapp_section_show', methods: ['GET'])]
    public function show(Section $section): Response
    {
        return $this->render('webapp/section/show.html.twig', [
            'section' => $section,
        ]);
    }

    /**
     * Edite une section selon son Id
     */
    #[Route(path: '/webapp/section/{id}/edit', name: 'op_webapp_section_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Section $section, EntityManagerInterface $entityManager): Response
    {
        $page = $section->getPage();
        $idpage = $page->getId();

        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_section_edit', [
                'id' => $section->getId()
            ]);
        }

        return $this->render('webapp/section/edit.html.twig', [
            'section' => $section,
            'idpage'=> $idpage,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/section/{id}', name: 'op_webapp_section_delete', methods: ['DELETE'])]
    public function delete(Request $request, Section $section, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$section->getId(), $request->request->get('_token'))) {
            $entityManager->remove($section);
            $entityManager->flush();
        }

        return $this->redirectToRoute('webapp_section_index');
    }

    /**
     * Liste toutes les sections de l'application liées à une page.
     */
    #[Route(path: '/listallsections/{page}', name: 'op_webapp_section_listallsections')]
    public function ListAllSections($page, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\Response
    {
        $sections = $entityManager->getRepository(Section::class)->ListAllSections($page)
        ;

        return $this->render('webapp/section/listsections.html.twig',[
            'sections' => $sections,
        ]);
    }

    #[Route(path: '/webapp/section/webapp/section/bypage/{page}', name: 'op_webapp_section_bypage', methods: ['GET'])]
    public function byPage(SectionRepository $sectionRepository, $page, EntityManagerInterface $entityManager): Response
    {
        $element = $entityManager->getRepository(Page::class)->find($page);

        return $this->render('webapp/section/bypage.html.twig', [
            'sections' => $sectionRepository->findbypage($page),
            'element' => $element,
        ]);
    }

    /**
     * Permet d'activer ou de désactiver la mise en vedette d'une section sur la page d'accueil
     */
    #[Route(path: '/webapp/section/jsstar/{id}/json', name: 'op_webapp_section_star')]
    public function jsstar(Section $section, EntityManagerInterface $em) : Response
    {
        $user = $this->getUser();
        $isstar = $section->getFavorites();
        // renvoie une erreur car l'utilisateur n'est pas connecté
        if(!$user) return $this->json([
            'code' => 403,
            'message'=> "Vous n'êtes pas connecté"
        ], 403);
        // Si la page est déja publiée, alors on dépublie
        if($isstar == true){
            $section->setfavorites(0);
            $em->flush();
            return $this->json([
                'code'      => 200,
                'message'   => "La section n'est plus publiée sur la page d'acccueil."
            ], 200);
        }
        // Si la page est déja dépubliée, alors on publie
        $section->setFavorites(1);
        $em->flush();
        return $this->json([
            'code'          => 200,
            'message'       => "La section est publiée sur la page d'acccueil."
        ], 200);
    }

    /**
     * Permet de déplacer une section dans la liste
     */
    #[Route(path: '/webapp/section/position/{id}/{level}', name: 'op_webapp_section_position_down')]
    public function Position(Section $section, $level, EntityManagerInterface $em) : Response
    {
        $user = $this->getUser();
        $id = $section->getId();
        $page = $section->getPage();

        // On récupére les positions de la section et des possiblité de la page
        // et on prépare les futures positions +1 et -1.

        //dd($previousPos, $position, $nextPos);

        if($level == 'up')
        {
            // On récupére la position de la section que l'on décrémente de 1
            $position = $section->getPosition();
            //dd($position);
            // on récupère la section supérieure
            $previousSec = $em->getRepository(Section::class)->findOneBy(['position' => $position-1]);
            //dd($previousSec);
            if($previousSec){
                // Si la précédente existe, on récupère sa position
                $previousPos = $previousSec->getPosition();
                //dd($previousPos);
                $previousSec->setPosition($section->getPosition());         // on hydrate la section précédente
                //dd($previousSec);
                $section->setPosition($previousPos);                        // on hydrate la section actuelle
                //dd($section);
                $em->flush();
            }else{
                $section->setPosition($position);
                $previousSec->setPosition($position-1);
                $em->flush();
            }

            // on récupère la liste des pages ordonnée par position
            $sections = $em->getRepository(Section::class)->findbypage($page);

            // et on retourne au format JSON
            return $this->json([
                'code'=> 200,
                'message' => "La section est montée d'un niveau",
                'liste' => $this->renderView('webapp/section/include/_liste.html.twig', [
                    'sections' => $sections
                ])
            ], 200);
        }elseif($level == 'down'){
            // On récupére la position de la section que l'on décrémnete de 1
            $position = $section->getPosition()+1;
            // on récupère la section inférieure
            $nextSec = $em->getRepository(Section::class)->findOneBy(['position' => $position]);
            if($nextSec){
                // Si la suivante existe, on récupère sa position
                $nextPos = $nextSec->getPosition();
                $nextSec->setPosition($section->getPosition());         // on hydrate la section précédente
                $section->setPosition($nextPos);                        // on hydrate la section actuelle
                $em->flush();
                // sinon aucun changement
            }
            // on récupère la liste des pages ordonnée par position
            $sections = $em->getRepository(Section::class)->findbypage($page);
            return $this->json([
                'code'=> 200,
                'message' => "La section est descendu d'un niveau",
                'liste' => $this->renderView('webapp/section/include/_liste.html.twig', [
                    'sections' => $sections
                ])
            ], 200);
        }else{
            return $this->json([
                'code'=> 200,
                'message' => "Une erreur a été détecté",
            ], 200);
        }
    }

    #[Route(path: '/webapp/section/addsection/{page}/{row}', name: 'op_webapp_section_add', methods: ['GET', 'POST'])]
    public function addSection( $page, $row, EntityManagerInterface $em) : Response
    {
        $element = $em->getRepository(Page::class)->find($page);
        $position = $row +1;

        $section = new Section;
        $section->setName('Nouvelle section');
        $section->setContent('none');
        $section->setPage($element);
        $section->setPosition($position);
        $section->setIsActiv(0);
        $em->persist($section);
        $em->flush();

        $sections = $em->getRepository(Section::class)->findbypage($page);

        return $this->json([
            'code'          => 200,
            'message'       => 'LA section à bien été ajoutée.',
            'liste'         =>  $this->renderView('webapp/section/include/_liste.html.twig', [
                'sections' => $sections
            ])
        ], 200);
    }

    #[Route(path: '/webapp/section/del/{id}', name: 'op_webapp_section_del', methods: ['POST'])]
    public function DelEvent(Section $section, EntityManagerInterface $em) : Response
    {
        // creation des éléement ncessaire à la méthode
        $user = $this->getUser();
        $page = $section->getPage();

        // Suppression de l'entité
        $em->remove($section);
        $em->flush();

        // Récupération de la liste des sectiosn
        $element = $em->getRepository(Page::class)->find($page);
        $sections = $em->getRepository(Section::class)->findbypage($page);
        return $this->json([
            'code'=> 200,
            'message' => "L'évènenemt a été supprimé",
            'liste' => $this->renderView('webapp/section/include/_liste.html.twig', [
                'sections' => $sections,
            ])
        ], 200);
    }
}
