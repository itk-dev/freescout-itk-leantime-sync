<?php

namespace Modules\ItkLeantimeSync\Service;

use App\Conversation;
use Modules\ItkLeantimeSync\Service\LeantimeHelper;

/**
 * Helper for leantime syncing..
 */
readonly class Helper
{


  /**
   * Helper constructor for Freescout.
   *
   * @return void
   */
  public function __construct(private LeantimeHelper $leantimeHelper)
  {
  }

  /**
   * @throws \Prometheus\Exception\MetricsRegistrationException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function syncStatus(Conversation $conversation, $user): void {
    $status = $conversation->getStatus();
    // @todo get correct id
    $leantimeTicketId = 55;

    switch ($status) {
      case Conversation::STATUS_ACTIVE:
      case Conversation::STATUS_PENDING:
      $this->leantimeHelper->updateTicket(
        ['status' => 4],
        $leantimeTicketId
      );
        break;
      case Conversation::STATUS_CLOSED:
      case Conversation::STATUS_SPAM:
        $this->leantimeHelper->updateTicket(
          ['status' => 0],
          $leantimeTicketId
        );
        break;
    }
  }
}
