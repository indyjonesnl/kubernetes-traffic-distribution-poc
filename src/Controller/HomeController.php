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
    #[Route('/healthz', methods: [Request::METHOD_GET])]
    public function healthz(): Response
    {
        return new Response('Ok');
    }

    #[Route('/', methods: [Request::METHOD_GET])]
    public function home(HttpClientInterface $hostnameClient): Response
    {
        $responses = [];
        for ($index = 0; $index < 6; $index++) {
            // send all requests concurrently (they won't block until response content is read)
            $responses[$index] = $hostnameClient->request(Request::METHOD_GET, '/api/hostname');
        }

        $results = [
            'webserver' => gethostname(),
        ];

        // iterate through the responses and read their content
        foreach ($responses as $index => $response) {
            // process response data somehow ...
            try {
                $results['microservice-' . ($index+1)] = $response->toArray()['hostname'];
            } catch(Throwable $t) {
                var_dump($t);
            }
        }

        return $this->json($results);
    }
}
