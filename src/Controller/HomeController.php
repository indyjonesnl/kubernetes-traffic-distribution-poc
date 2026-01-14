<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class HomeController extends AbstractController
{
    private const int NUM_CALLS = 6;

    public function __construct(
        private readonly HttpClientInterface $hostnameClient,
    )
    {
    }

    #[Route('/healthz', methods: [Request::METHOD_GET])]
    public function healthz(): Response
    {
        return new Response('Ok');
    }

    #[Route('/', methods: [Request::METHOD_GET])]
    public function home(): Response
    {
        $results = $this->callMicroservices(self::NUM_CALLS, false);

        return $this->json($results);
    }

    private function callMicroservices(int $amount, bool $useNodeName): array
    {
        $responses = [];
        for ($index = 0; $index < $amount; $index++) {
            // send all requests concurrently (they won't block until response content is read)
            $responses[$index] = $this->hostnameClient->request(Request::METHOD_GET, $useNodeName ? '/api/node_name' : '/api/hostname');
        }

        $results = [
            'webserver' => $useNodeName ? getenv('NODE_NAME') : gethostname(),
        ];

        // iterate through the responses and read their content
        foreach ($responses as $index => $response) {
            // process response data somehow ...
            try {
                $results['microservice-' . ($index+1)] = $response->toArray()[$useNodeName ? 'node_name' : 'hostname'];
            } catch(Throwable $t) {
                var_dump($t);
            }
        }

        return $results;
    }

    #[Route('/node_name', methods: [Request::METHOD_GET])]
    public function nodeName(): Response
    {
        $results = $this->callMicroservices(self::NUM_CALLS, true);

        return $this->json($results);
    }
}
