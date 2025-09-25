<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPrice;

class PricingService
{
    /**
     * Рассчитать стоимость участия
     */
    public function calculateParticipationCost(int $eventId, int $nominationId, int $participantCount): float
    {
        $eventPrice = EventPrice::where('event_id', $eventId)
            ->where('nomination_id', $nominationId)
            ->first();
            
        if (!$eventPrice) {
            return 0;
        }
        
        return $eventPrice->price * $participantCount;
    }

    /**
     * Рассчитать стоимость дипломов
     */
    public function calculateDiplomaCost(int $eventId, int $quantity): float
    {
        $event = Event::find($eventId);
        
        if (!$event || !$event->diploma_price) {
            return 0;
        }
        
        return $event->diploma_price * $quantity;
    }

    /**
     * Рассчитать стоимость медалей
     */
    public function calculateMedalCost(int $eventId, int $quantity): float
    {
        $event = Event::find($eventId);
        
        if (!$event || !$event->medal_price) {
            return 0;
        }
        
        return $event->medal_price * $quantity;
    }

    /**
     * Рассчитать общую стоимость
     */
    public function calculateTotalCost(
        int $eventId,
        int $nominationId,
        int $participantCount,
        int $diplomaQuantity = 0,
        int $medalQuantity = 0
    ): array {
        $participationCost = $this->calculateParticipationCost($eventId, $nominationId, $participantCount);
        $diplomaCost = $this->calculateDiplomaCost($eventId, $diplomaQuantity);
        $medalCost = $this->calculateMedalCost($eventId, $medalQuantity);
        
        return [
            'participation' => $participationCost,
            'diplomas' => $diplomaCost,
            'medals' => $medalCost,
            'total' => $participationCost + $diplomaCost + $medalCost,
        ];
    }

    /**
     * Получить цены для мероприятия
     */
    public function getEventPrices(int $eventId): array
    {
        $event = Event::find($eventId);
        
        if (!$event) {
            return [];
        }
        
        $nominationPrices = EventPrice::where('event_id', $eventId)
            ->with('nomination')
            ->get()
            ->keyBy('nomination_id');
            
        return [
            'nomination_prices' => $nominationPrices,
            'diploma_price' => $event->diploma_price ?? 0,
            'medal_price' => $event->medal_price ?? 0,
        ];
    }
}
