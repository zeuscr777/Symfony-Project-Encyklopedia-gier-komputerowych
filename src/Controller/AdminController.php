<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoriesRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\GamesRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\TutorialsRepository;

use App\Entity\Categories;
use App\Entity\Games;
use App\Entity\Tutorials;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    public function view_category(CategoriesRepository $categoriesRepository)
    {
        $data = $categoriesRepository->findAll();

        return $this->render('admin/category.html.twig', compact('data'));
    }

    public function add_category(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = new Categories();

        $data->setCategoryName($request->request->get('category'));

        $entityManager->persist($data);
        $entityManager->flush();

        $this->addFlash('success', 'Kategoria dodana poprawnie!');

        return $this->redirectToRoute('view_category');
    }

    public function delete_category(int $id, CategoriesRepository $categoriesRepository, EntityManagerInterface $entityManager): Response
    {
        $data = $categoriesRepository->find($id);

        if ($data) {
            $entityManager->remove($data);
            $entityManager->flush();

            $this->addFlash('success', 'Kategoria została usunięta!');
        } else {
            $this->addFlash('error', 'Nie można znaleźć kategorii o podanym ID!');
        }

        return $this->redirectToRoute('view_category', [], 303);
    }

    public function view_addGame(CategoriesRepository $categoriesRepository): Response
    {
        $category = $categoriesRepository->findAll();
        return $this->render('admin/addGame.html.twig', compact('category'));
    }

    public function addGame(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $game = new Games();

        $game->setTitle($request->request->get('title'));
        $game->setDescription($request->request->get('description'));
        $game->setYear($request->request->get('year'));
        $game->setMark($request->request->get('mark'));
        $game->setCategory($request->request->get('category'));

        /** @var UploadedFile $image */
        $image = $request->files->get('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();

        $image->move(
            $this->getParameter('game_images_directory'),
            $imageName
        );

        $game->setImage($imageName);

        $entityManager->persist($game);
        $entityManager->flush();

        $this->addFlash('message', 'Gra dodana poprawnie!');

        return $this->redirectToRoute('view_addGame');
    }

    public function view_showGames(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $gamesQuery = $entityManager->getRepository(Games::class)
            ->createQueryBuilder('g')
            ->getQuery();

        $games = $paginator->paginate(
            $gamesQuery,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('admin/showGames.html.twig', compact('games'));
    }

    public function delete_game(int $id, GamesRepository $gamesRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $game = $gamesRepository->find($id);

        if ($game) {
            $entityManager->remove($game);
            $entityManager->flush();

            $this->addFlash('message', 'Gra została usunięta!');
        } else {
            $this->addFlash('error', 'Nie można znaleźć gry o podanym ID!');
        }

        return $this->redirectToRoute('view_showGames');
    }

    public function update_game(int $id, GamesRepository $gamesRepository, CategoriesRepository $categoriesRepository): Response
    {
        $game = $gamesRepository->find($id);

        if (!$game) {
            $this->addFlash('error', 'Nie można znaleźć gry o podanym ID!');
            return $this->redirectToRoute('view_showGames');
        }

        $categories = $categoriesRepository->findAll();

        return $this->render('admin/updateGame.html.twig', [
            'game' => $game,
            'categories' => $categories,
        ]);
    }

    public function update_game_confirm(Request $request, int $id, GamesRepository $gamesRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $game = $gamesRepository->find($id);

        $game->setTitle($request->request->get('title'));
        $game->setDescription($request->request->get('description'));
        $game->setYear($request->request->get('year'));
        $game->setMark($request->request->get('mark'));
        $game->setCategory($request->request->get('category'));

        /** @var UploadedFile $image */
        $image = $request->files->get('image');

        if ($image) {
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move('game', $imageName);
            $game->setImage($imageName);
        }

        $entityManager->persist($game);
        $entityManager->flush();

        $this->addFlash('message', 'Gra zaktualizowana poprawnie!');

        return $this->redirectToRoute('view_showGames');
    }

    public function view_addTutorial(GamesRepository $gamesRepository): Response
    {
        $games = $gamesRepository->findAll();

        return $this->render('admin/addTutorial.html.twig', [
            'games' => $games
        ]);
    }

    private $entityManager;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function add_tutorial(Request $request): RedirectResponse
    {
        $tutorialRepository = $this->entityManager->getRepository(Tutorials::class);
        $gameTitle = $request->request->get('title');
        $content = $request->request->get('editor1');

        $game = $tutorialRepository->findOneBy(['title' => $gameTitle]);

        if (!$game) {
            $game = new Tutorials();
            $game->setTitle($gameTitle);
            $message = 'Poradnik do gry dodany poprawnie!';
        } else {
            $message = 'Poradnik do gry zaktualizowany poprawnie!';
        }

        $content = str_replace(PHP_EOL, '<br>', $content);
        $content = preg_replace('/(<br\s*\/?>\s*){2,}/', '<br>', $content);

        $game->setContent($content);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $this->addFlash('success', $message);

        return new RedirectResponse($this->urlGenerator->generate('view_addTutorial'));
    }

    public function view_showTutorials(Request $request, TutorialsRepository $tutorialsRepository, PaginatorInterface $paginator): Response
    {
        $tutorialsQuery = $tutorialsRepository->getTutorialsWithGames();

        $tutorials = $paginator->paginate(
            $tutorialsQuery,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('admin/showTutorials.html.twig', compact('tutorials'));
    }

    public function delete_tutorial(TutorialsRepository $tutorialsRepository, $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $tutorial = $tutorialsRepository->find($id);
        $em->remove($tutorial);
        $em->flush();

        $this->addFlash('message', 'Gra została usunięta!');
        return $this->redirectToRoute('delete_tutorial', ['id' => $id]);
    }

    public function update_tutorial(TutorialsRepository $tutorialsRepository, $id): Response
    {
        $tutorial = $tutorialsRepository->find($id);
        return $this->render('admin/updateTutorial.html.twig', ['game' => $tutorial]);
    }

    public function update_tutorial_confirm(Request $request, TutorialsRepository $tutorialsRepository, $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $tutorial = $tutorialsRepository->find($id);
        $tutorial->setContent(str_replace(PHP_EOL, '<br>', $request->get('editor1')));

        $em->persist($tutorial);
        $em->flush();

        $this->addFlash('message', 'Poradnik do gry zaktualizowany poprawnie!');
        return $this->redirectToRoute('update_tutorial_confirm', ['id' => $id]);
    }

    public function generate_pdf(TutorialsRepository $tutorialsRepository, Pdf $pdf, $id): Response
    {
        $tutorial = $tutorialsRepository->find($id);

        $html = $this->renderView('admin/pdf_view.html.twig', [
            'tutorial' => $tutorial,
        ]);

        $filename = 'tutorial.pdf';

        return new Response(
            $pdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }
}
