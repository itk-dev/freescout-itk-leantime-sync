<?php

namespace Modules\ItkLeantimeSync\Service;

use App\Conversation;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\CustomFields\Entities\ConversationCustomField;
use Modules\CustomFields\Entities\CustomField;
use Modules\UserFields\Entities\UserField;
use Modules\UserFields\Entities\UserUserField;

/**
 * Helper for leantime syncing..
 */
readonly class Helper
{
  /**
   * Helper constructor for Freescout leantime syncing.
   *
   * @return void
   */
    public function __construct(private LeantimeHelper $leantimeHelper)
    {
    }

  /**
   *  Sync Freescout status to Leantime status.
   *
   * @throws \Prometheus\Exception\MetricsRegistrationException
   *  @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Prometheus\Exception\MetricsRegistrationException
   */
    public function syncStatus(Conversation $conversation, $user): void
    {
        $status = $conversation->getStatus();
        $leantimeTicketId = $this->getLeantimeId($conversation);
        $now = new DateTime();
        $currentDate = $now->format('d/m/Y');
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
   * @throws \Prometheus\Exception\MetricsRegistrationException
   */
    public function syncAssignee(Conversation $conversation, $user, $prev_user_id): void
    {
        $leantimeUserId = $this->getUserLeantimeId($conversation->getAttribute('user_id'));
        $leantimeTicketId = $this->getLeantimeId($conversation);
        if ($leantimeTicketId) {
            $this->leantimeHelper->updateTicket(
                [
                'editorId' => $leantimeUserId ?? '',

                ],
                $leantimeTicketId
            );
        }
    }

  /**
   * Get leantime ticket id if it is set in "Leantime issue" field.
   *
   * @param \App\Conversation $conversation
   *
   * @return mixed|null
   */
    private function getLeantimeId(Conversation $conversation)
    {
        try {
            $customField = CustomField::where('name', '=', 'Leantime issue')->firstOrFail();

            $collection = ConversationCustomField::where([
            ['custom_field_id', '=', $customField->getAttribute('id')],
            ['conversation_id', '=', $conversation->getAttribute('id')],
            ])->get()->toArray();

            return $collection[0]['value'];
        } catch (Exception $exception) {
            Log::error(__FUNCTION__. ': ' . $exception->getMessage());

            return null;
        }
    }

  /**
   * Get a leantime user id from freescout if it is set in "Leantime user id" field.
   *
   * @param $userId
   *
   * @return mixed|null
   */
    private function getUserLeantimeId($userId)
    {
        try {
            $customField = UserField::where('name', '=', 'Leantime user id')->firstOrFail();

            $collection = UserUserField::where([
            ['user_field_id', '=', $customField->getAttribute('id')],
            ['user_id', '=', $userId],
            ])->get()->toArray();

            return $collection[0]['value'];
        } catch (Exception $exception) {
            Log::error(__FUNCTION__. ': ' . $exception->getMessage());

            return null;
        }
    }
}
