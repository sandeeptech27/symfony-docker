<?php

namespace App\Controller;

use App\Service\JsonDocumentService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    public string $apiUrl;
    public JsonDocumentService $jsonDocumentService;

    /**
     * Summary of __construct
     * 
     * @param \App\Service\JsonDocumentService $jsonDocumentService
     * @param mixed $apiUrl
     */
    public function __construct(JsonDocumentService $jsonDocumentService, $apiUrl)
    {
        $this->apiUrl = $apiUrl;
        $this->jsonDocumentService = $jsonDocumentService;
    }

    /**
     * Summary of index
     * @return JsonResponse
     */
    #[Route('/fetch')]
    public function index()
    {
        try {
            // Fetch and store documents using the ApplicationDocumentService
            $results = $this->jsonDocumentService->fetchAndStoreDocuments($this->apiUrl);

            // Separate successes and errors for response clarity
            $successes = array_filter($results, fn($result) => isset ($result['success']));
            $errors = array_filter($results, fn($result) => isset ($result['error']));

            return new JsonResponse([
                'message' => 'Document fetching completed.',
                'success_count' => count($successes),
                'error_count' => count($errors),
                'details' => $results,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred while fetching documents.',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
