<?php

namespace App\Tool\BetAI\Controller;

use App\Tool\BetAI\Entity\BetAISetting;
use App\Tool\BetAI\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tool/bet-ai/settings', name: 'app_bet_ai_settings_')]
class BetAISettingsController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        GeminiService $geminiService,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            $model = $request->request->get('gemini_model');
            if ($model) {
                $setting = $entityManager->getRepository(BetAISetting::class)->findOneBy(['key' => 'gemini_model']);
                if (!$setting) {
                    $setting = new BetAISetting('gemini_model');
                    $entityManager->persist($setting);
                }
                $setting->value = $model;
                $entityManager->flush();

                $this->addFlash('success', 'Einstellungen wurden gespeichert.');
                return $this->redirectToRoute('app_bet_ai_settings_index');
            }
        }

        $models = $geminiService->listModels();
        $selectedModel = $geminiService->getSelectedModel();

        return $this->render('tool/bet_ai/settings/index.html.twig', [
            'models' => $models,
            'selectedModel' => $selectedModel,
        ]);
    }
}
