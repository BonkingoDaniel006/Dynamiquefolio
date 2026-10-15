<?php

namespace App\Controller;

use App\Entity\Competences;
use App\Form\CompetencesType;
use App\Form\ProfilType;
use App\Repository\CompetencesRepository;
use App\Repository\ProfilRepository;
use App\Repository\ParcoursRepository;
use App\Repository\ProjetRepository;
use App\Repository\StackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PortfolioController extends AbstractController
{
    #[Route('/', name: 'app_portfolio')]
    public function index(
        ProfilRepository $profilRepository,
        CompetencesRepository $competencesRepository,
        ParcoursRepository $parcoursRepository,
        StackRepository $stackRepository,
        ProjetRepository $projetRepository,
    ): Response {
        $profil = $profilRepository->findOneBy([], ['id' => 'ASC']);
        $competences = $competencesRepository->findBy(
            ['id_profil' => $profil?->getId()],
            ['id' => 'ASC']
        );
        $parcours = $parcoursRepository->findBy([], ['debut' => 'DESC']);
        $stacksById = [];

        foreach ($stackRepository->findAll() as $stack) {
            $stacksById[$stack->getId()] = $stack;
        }

        return $this->render('portfolio/index.html.twig', [
            'controller_name' => 'PortfolioController',
            'profil' => $profil,
            'competences' => $competences,
            'parcours' => $parcours,
            'stacksById' => $stacksById,
            'projets' => $projetRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function legalNotice(): Response
    {
        return $this->render('portfolio/mentions-legales.html.twig');
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        ProfilRepository $profilRepository,
        CompetencesRepository $competencesRepository,
        ProjetRepository $projetRepository,
    ): Response
    {
        $profil = $profilRepository->findOneBy([], ['id' => 'ASC']);
        $competences = $competencesRepository->findAll();
        $competenceForm = null;
        $competenceForms = [];

        if ($profil) {
            $competence = (new Competences())->setIdProfil($profil->getId());
            $competenceForm = $this->createForm(CompetencesType::class, $competence, [
                'action' => $this->generateUrl('app_competences_new'),
                'method' => 'POST',
            ])->createView();

        }

        foreach ($competences as $existingCompetence) {
            $competenceForms[$existingCompetence->getId()] = $this->createForm(CompetencesType::class, $existingCompetence, [
                'action' => $this->generateUrl('app_competences_edit', ['id' => $existingCompetence->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'PortfolioController',
            'profil' => $profil,
            'competences'=> $competences,
            'projets' => $projetRepository->findBy([], ['createdAt' => 'DESC']),
            'competenceForm' => $competenceForm,
            'competenceForms' => $competenceForms,
            'profileForm' => $profil ? $this->createForm(ProfilType::class, $profil, [
                'action' => $this->generateUrl('app_profil_edit', ['id' => $profil->getId()]),
                'method' => 'POST',
            ])->createView() : null,
        ]);
    }
}
