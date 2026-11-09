<?php

namespace App\Controller;

use App\Dto\ZabbixAlert;
use App\Service\AlertRepository;
use App\Service\TelegramNotifier;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ZabbixWebhookController extends AbstractController
{
    public function __construct(
        private readonly AlertRepository $alerts,
        private readonly TelegramNotifier $telegram,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/webhook/zabbix', name: 'zabbix_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $alert = $this->parseAlert($request);
        $status = 'Sent';

        try {
            $this->telegram->send($alert);
            $this->logger->info('Alert sent: {subject}', ['subject' => $alert->subject]);
        } catch (\Throwable $e) {
            $status = 'Failed: '.$e->getMessage();
            $this->logger->error('Failed to send alert: {error}', ['error' => $e->getMessage()]);
        }

        $stored = $this->alerts->append($alert->subject, $alert->message, $status);
        $this->logger->info('Alert {id} stored as {status}', ['id' => $stored->id, 'status' => $status]);

        return new Response('OK', Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function parseAlert(Request $request): ZabbixAlert
    {
        /** @var mixed $data */
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Expected JSON body');
        }

        $subject = $data['subject'] ?? null;
        $message = $data['message'] ?? null;
        if (!is_string($subject) || $subject === '' || !is_string($message) || $message === '') {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('subject and message are required');
        }

        return new ZabbixAlert($subject, $message);
    }
}
