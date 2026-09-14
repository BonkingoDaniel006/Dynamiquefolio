<?php

namespace App\Controller;

use App\Entity\Stack;
use App\Form\StackType;
use App\Repository\StackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stack')]
final class StackController extends AbstractController
{
    #[Route(name: 'app_stack_index', methods: ['GET'])]
    public function index(StackRepository $stackRepository): Response
    {
        return $this->render('stack/index.html.twig', [
            'stacks' => $stackRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_stack_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stack = new Stack();
        $form = $this->createForm(StackType::class, $stack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stack);
            $entityManager->flush();

            return $this->redirectToRoute('app_stack_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stack/new.html.twig', [
            'stack' => $stack,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stack_show', methods: ['GET'])]
    public function show(Stack $stack): Response
    {
        return $this->render('stack/show.html.twig', [
            'stack' => $stack,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stack_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stack $stack, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StackType::class, $stack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_stack_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stack/edit.html.twig', [
            'stack' => $stack,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stack_delete', methods: ['POST'])]
    public function delete(Request $request, Stack $stack, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stack->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($stack);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_stack_index', [], Response::HTTP_SEE_OTHER);
    }
}
