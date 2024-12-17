<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

class JsonDocumentService
{
    private HttpClientInterface $httpClient;
    private Filesystem $filesystem;
    private LoggerInterface $logger;
    private string $storagePath;
    public function __construct(
        HttpClientInterface $httpClient,
        Filesystem $filesystem,
        LoggerInterface $logger,
        string $storagePath
    ) {
        $this->httpClient = $httpClient;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->storagePath = rtrim($storagePath, '/');
    }

    /**
     * Fetch And Store Documents
     * 
     * @param string $apiUrl
     * @throws \Exception
     * @return array
     */
    public function fetchAndStoreDocuments(string $apiUrl): array
    {
        $results = [];

        try {
            // Fetch data from the API
            $response = $this->httpClient->request('GET', $apiUrl);

            if (200 !== $response->getStatusCode()) {
                throw new \Exception('Failed to fetch data from the API. HTTP status code: ' . $response->getStatusCode());
            }

            $documents = $response->toArray();

            // Validate the response
            if (!is_array($documents)) {
                throw new \Exception('Invalid response format from the API.');
            }

            // Process each document
            foreach ($documents as $document) {
                $results[] = $this->processDocument($document);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error occurred while fetching documents: ' . $e->getMessage());
            $results[] = ['error' => $e->getMessage()];
        }

        return $results;
    }

    /**
     * Process Document
     * 
     * @param array $document
     * @return array
     */
    private function processDocument(array $document): array
    {
        // Check required fields
        if (isset($document['certificate'], $document['doc_no'], $document['description'])) {
            $decodedFile = base64_decode($document['certificate']);
            if (false === $decodedFile) {
                $this->logger->error('Failed to decode certificate for doc_no: ' . $document['doc_no']);
                return ['error' => 'Failed to decode certificate', 'doc_no' => $document['doc_no']];
            }

            // Generate file name based on description and doc_no
            $fileName = sprintf('%s_%s.pdf', $document['description'], $document['doc_no']);
            $filePath = $this->storagePath . '/' . $fileName;

            try {
                // Save the file
                $this->filesystem->dumpFile($filePath, $decodedFile);
                return ['success' => true, 'file' => $filePath];
            } catch (\Exception $e) {
                $this->logger->error('Failed to store file: ' . $e->getMessage());
                return ['error' => 'Failed to store file', 'doc_no' => $document['doc_no']];
            }
        } else {
            // Log invalid document format
            $this->logger->error('Invalid document format: ' . json_encode($document));
            return ['error' => 'Invalid document format', 'document' => $document];
        }
    }
}
