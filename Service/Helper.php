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
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Prometheus\Exception\MetricsRegistrationException
   */
    public function syncStatus(Conversation $conversation, $user): void
    {
        $status = $conversation->getStatus();
        $leantimeTicketId = $this->getLeantimeId($conversation);
        $now = new DateTime();
        $currentDate = $now->format('m/d/Y');
        $currentTime = $now->format('h:i A');

        if ($leantimeTicketId) {
            switch ($status) {
                case Conversation::STATUS_ACTIVE:
                case Conversation::STATUS_PENDING:
                    $this->leantimeHelper->updateTicket(
                        [
                        'status' => 4,
                        ],
                        $leantimeTicketId
                    );
                    break;
                case Conversation::STATUS_CLOSED:
                case Conversation::STATUS_SPAM:
                    $this->leantimeHelper->updateTicket(
                        [
                        'status' => 0,
                        'hourRemaining' => 0,
                        'editTo' => $currentDate,
                        'timeTo' => $currentTime,
                        ],
                        $leantimeTicketId
                    );
                    break;
            }
        }
    }

  /**
   *  Sync Freescout assignee to Leantime assignee.
 *
   * @param \App\Conversation $conversation
   * @param $user
   * @param $prev_user_id
   *
   * @return void
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
