<?php

namespace App\Controller;

use App\Form\ProfilType;
use App\Repository\ProfilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PortfolioController extends AbstractController
{
    #[Route('/', name: 'app_portfolio')]
    public function index(): Response
    {
        return $this->render('portfolio/index.html.twig', [
            'controller_name' => 'PortfolioController',
        ]);
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(ProfilRepository $profilRepository): Response
    {
        $profil = $profilRepository->findOneBy([], ['id' => 'ASC']);

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'PortfolioController',
            'profil' => $profil,
            'profileForm' => $profil ? $this->createForm(ProfilType::class, $profil, [
                'action' => $this->generateUrl('app_profil_edit', ['id' => $profil->getId()]),
                'method' => 'POST',
            ])->createView() : null,
        ]);
    }
}
