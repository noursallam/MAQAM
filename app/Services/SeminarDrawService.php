<?php

namespace App\Services;

use App\Models\Seminar;
use App\Models\SeminarDraw;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Draws a seminar's prizes, one at a time and in their order, among the
 * customers who signed up. Nobody wins twice in the same seminar.
 */
class SeminarDrawService
{
    public const NOT_STARTED = 'not_started';
    public const ALL_DRAWN = 'all_drawn';
    public const NO_ONE_LEFT = 'no_one_left';

    public function __construct(
        protected NotificationService $notifications
    ) {}

    /**
     * Draws the next prize and tells the winner.
     *
     * @throws RuntimeException with one of the constants above as its message
     */
    public function drawNext(Seminar $seminar): SeminarDraw
    {
        $draw = DB::transaction(function () use ($seminar) {
            // Locks the seminar so two clicks cannot draw the same prize twice
            $seminar = Seminar::whereKey($seminar->id)->lockForUpdate()->firstOrFail();

            if ($seminar->status() === Seminar::UPCOMING) {
                throw new RuntimeException(self::NOT_STARTED);
            }

            $draw = $seminar->draws()->whereNull('drawn_at')->first();
            if (! $draw) {
                throw new RuntimeException(self::ALL_DRAWN);
            }

            $eligible = $seminar->registrations()
                ->whereNotIn('user_id', $seminar->draws()->whereNotNull('winner_user_id')->select('winner_user_id'))
                ->whereHas('user', fn ($q) => $q->where('is_active', true))
                ->orderBy('id')
                ->pluck('user_id')
                ->all();

            if (! $eligible) {
                throw new RuntimeException(self::NO_ONE_LEFT);
            }

            // random_int uses the operating system's secure random source, so no
            // one can predict or steer who is picked
            $draw->update([
                'winner_user_id' => $eligible[random_int(0, count($eligible) - 1)],
                'drawn_at' => now(),
            ]);

            return $draw;
        });

        $this->notifyWinner($seminar, $draw);

        return $draw;
    }

    /**
     * Draws every prize left, stopping early if the attendees run out.
     *
     * @return list<SeminarDraw>
     */
    public function drawAll(Seminar $seminar): array
    {
        $drawn = [];

        while ($seminar->draws()->whereNull('drawn_at')->exists()) {
            try {
                $drawn[] = $this->drawNext($seminar);
            } catch (RuntimeException $e) {
                if ($e->getMessage() === self::NO_ONE_LEFT && $drawn) {
                    break;
                }
                throw $e;
            }
        }

        return $drawn;
    }

    private function notifyWinner(Seminar $seminar, SeminarDraw $draw): void
    {
        $this->notifications->notifyTranslated(
            [$draw->winner_user_id],
            'api.seminar.won_title',
            'api.seminar.won_body',
            ['prize' => $draw->prize, 'seminar' => $seminar->title],
            'seminar',
            ['seminar_id' => $seminar->id],
        );
    }

    /**
     * How a winner is shown to other customers: first name and the last digits of the phone.
     */
    public static function publicName(?User $user): string
    {
        if (! $user) {
            return '—';
        }

        $first = trim(strtok((string) $user->full_name, ' ') ?: '');
        $phone = preg_replace('/\D/', '', (string) $user->phone_number);
        $tail = strlen($phone) >= 3 ? '•••'.substr($phone, -3) : '';

        return trim($first.' '.$tail) ?: '—';
    }
}
