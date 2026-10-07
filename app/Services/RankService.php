<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Rank;

/**
 * Ranks follow lifetime earned points, so spending the balance never costs a customer their rank.
 */
class RankService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    /**
     * Promote the customer when their lifetime points reach a higher rank. Returns the new rank, if any.
     */
    public function promoteIfEarned(Customer $customer): ?Rank
    {
        $earned = Rank::where('is_active', true)
            ->where('min_points', '<=', (int) $customer->total_points_earned)
            ->orderByDesc('min_points')
            ->first();

        $current = $customer->rank_id ? Rank::find($customer->rank_id) : null;

        // Promotion only: a rank an admin granted by hand is never taken away here
        if (! $earned || ($current && $current->min_points >= $earned->min_points)) {
            return null;
        }

        $customer->forceFill(['rank_id' => $earned->id])->save();
        $customer->setRelation('rank', $earned);

        if ($current) {
            $this->notifications->notifyTranslated(
                [$customer->user_id],
                'api.rank.upgraded_title',
                'api.rank.upgraded_body',
                ['rank' => fn (string $locale) => $locale === 'en' ? $earned->name_en : $earned->name_ar],
                'rank_upgrade',
            );
        }

        return $earned;
    }
}
