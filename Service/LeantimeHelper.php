<?php

namespace Modules\ItkLeantimeSync\Service;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use Modules\ItkPrometheus\Service\PrometheusService;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Conversation;
use App\Thread;

/**
 * Helper for Leantime connection and creating Leantime ticket.
 */
final class LeantimeHelper
{

  /**
   * Leantime Api path.
   */
    private const API_PATH_JSONRPC = '/api/jsonrpc/';

  /**
   * Status for the ticket that is created.
   */
    private const LEANTIME_TICKET_STATUS = '3';

  /**
   * Path to the ticket (without the id)
   */
    private const LEANTIME_TICKET_PATH = '/#/tickets/showTicket/';

  /**
   * Helper constructor for Freescout leantime connection.
   *
   * @param \Modules\ItkPrometheus\Service\PrometheusService $prometheusService
   *   The prometheus service.
   */
    public function __construct(private readonly PrometheusService $prometheusService)
    {
    }

  /**
   * Use Leantime API to create the leantime ticket.
   *
   * @param Conversation $conversation
   *   A freescout conversation.
   * @param Thread $thread
   *   A freescout Thread.
   * @param String $customerName
   *   THe name of the customer.
   *
   * @return array|null
   *   Id of the created Leantime ticket or an error response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Throwable
   */
    public function sendToLeantime(
        Conversation $conversation,
        Thread $thread,
        string $customerName
    ): ?array
    {
        $conv = $conversation->getOriginal();
        $leantimeProjectKeys  = \config('itkleantimesync.leantimeProjectKeys');
        $leantimeProjectsMapped = [];
        foreach ($leantimeProjectKeys as $map) {
            $mapping = explode(',', $map);
            $leantimeProjectsMapped[$mapping[0]] = $mapping[1];
        }
        $projectId = $leantimeProjectsMapped[$conversation->getAttribute('mailbox_id')];

        $now = new DateTime();
        $interval = new DateInterval('P1W');
        $nextWeek = $now->add($interval);
        $nextWeekDate = $nextWeek->format('m/d/Y');
        $nextWeekTime = $nextWeek->format('h:i A');

        $leantimeId = $this->addTicket([
          'headline' => $conv['subject'],
          'description' => $this->createHtmlDescription($conv, $customerName, $thread),
          'status' => self::LEANTIME_TICKET_STATUS,
          'projectId' => $projectId,
          'dateToFinish' => $nextWeekDate,
          'timeToFinish' => $nextWeekTime,
        ]);

        if ($leantimeId) {
            return [
            'id' => $leantimeId,
            'url' => $this->createUrlFromId($leantimeId),
            ];
        }

        return null;
    }


  /**
   * Add ticket through a Leantime post call.
   *
   * @param array $values
   *
   * @return ResponseInterface|string|null
   *   Id of the created Leantime ticket or an error response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException|\Prometheus\Exception\MetricsRegistrationException
   */
    public function addTicket(array $values): ResponseInterface|string|null
    {
        return $this->post('leantime.rpc.tickets.addTicket', [
          'values' => $values,
        ]);
    }

  /**
   * Update a Leantime ticket.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException|\Prometheus\Exception\MetricsRegistrationException
   */
    public function updateTicket(array $params, int $id): string|ResponseInterface|null
    {
        return $this->post('leantime.rpc.Tickets.Tickets.patch', [
        'params' => $params,
        'id' => $id,
        ]);
    }

  /**
   * The post call using GuzzleHttp\Client.
   *
   * @param string $method
   *   The Leantime method to call.
   * @param array $params
   *   Required params for the method.
   *
   * @return ResponseInterface|string|null
   *   Id of the created Leantime ticket or an error response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException|\Prometheus\Exception\MetricsRegistrationException
   */
    private function post(string $method, array $params = []): ResponseInterface|string|null
    {
        $leantimeApiKey = \config('itkleantimesync.leantimeApiKey');

        $client = new Client([
          'headers' => ['Content-Type' => 'application/json'],
        ]);

        try {
            $response = $client->request(Request::METHOD_POST, $this->getLeantimeUrl() . self::API_PATH_JSONRPC, [
              'headers' => [
                'x-api-key' => $leantimeApiKey,
              ],
              'json' => [
                'timeout' => config('app.curl_timeout'),
                'connect_timeout' => config('app.curl_timeout'),
                'proxy' => config('app.proxy'),
                'jsonrpc' => '2.0',
                'method' => $method,
                'id' => uniqid(),
                'params' => $params,
              ],
            ]);
        } catch (Exception $e) {
            \Helper::logException($e);

            $counter = [
              'name_space' => 'leantime_helper',
              'name' => 'post_exception',
              'help' => 'Increases when posting to leantime fails'
            ];

            $labels = [
              'module' => 'itk_issue_create',
              'method' => 'post',
              'type' => 'exception',
              'code' => $e->getCode(),
            ];

            $this->prometheusService->incCounterBy($counter, $labels);

            return $e->getResponse();
        }

        $contents = json_decode($response->getBody()->getContents(), true);

        return $contents['result'][0];
    }

  /**
   * The Leantime URL as set in .env
   *
   * @return \Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
   */
    private function getLeantimeUrl()
    {
        return \config('itkleantimesync.leantimeUrl');
    }

  /**
   * Create a Leantime ticket URL.
   *
   * @param string $id
   *   Id of the Leantime ticket.
   *
   * @return string
   *   A full URL to the ticket in Leantime.
   */
    private function createUrlFromId(string $id) : string
    {
        return $this->getLeantimeUrl() . self::LEANTIME_TICKET_PATH . $id;
    }

  /**
   * Create HTML for Leantime ticket description.
   *
   * @param array $conv
   *   The Freescout conversation.
   * @param string $customerName
   *   The Customers name.
   * @param Thread $thread
   *   The Freescout Thread.
   *
   * @return string
   *   A rendered HTML description.
   * @throws \Throwable
   */
    private function createHtmlDescription(array $conv, string $customerName, Thread $thread): string
    {
        $freescoutPath = config('app.url');
        $freescoutUrl = $freescoutPath . '/conversation/' . $conv['id'];

        return view(
            'itkissuecreate::leantimeDescription',
            compact(
                'conv',
                'customerName',
                'thread',
                'freescoutUrl'
            )
        )->render();
    }
}
