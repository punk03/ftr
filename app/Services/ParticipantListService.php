<?php

namespace App\Services;

class ParticipantListService
{
    /**
     * Очистить список участников от лишних символов и нумерации
     */
    public function cleanParticipantList(string $rawList): array
    {
        if (empty($rawList)) {
            return [];
        }

        // Разбиваем по строкам
        $lines = explode("\n", $rawList);
        
        $cleanedParticipants = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Убираем нумерацию (1., 1), 1- и т.д.)
            $line = preg_replace('/^\d+[\.\)\-\s]*/', '', $line);
            
            // Убираем лишние знаки препинания в начале и конце
            $line = trim($line, '.,;:!?');
            
            // Убираем лишние пробелы
            $line = preg_replace('/\s+/', ' ', $line);
            
            if (!empty($line)) {
                $cleanedParticipants[] = $line;
            }
        }
        
        return array_unique($cleanedParticipants);
    }

    /**
     * Подсчитать количество участников
     */
    public function countParticipants(array $participants): int
    {
        return count($participants);
    }

    /**
     * Проверить соответствие количества участников номинации
     */
    public function validateNominationCount(array $participants, int $nominationId): bool
    {
        $count = $this->countParticipants($participants);
        
        return match($nominationId) {
            1 => $count === 1, // Соло
            2 => $count === 2, // Дуэт/Пара
            3 => $count >= 3 && $count <= 7, // Малая группа
            4 => $count >= 8 && $count <= 24, // Формейшн
            5 => $count >= 25, // Продакшн
            default => true
        };
    }

    /**
     * Получить список участников как строку для сохранения
     */
    public function participantsToString(array $participants): string
    {
        return implode("\n", $participants);
    }

    /**
     * Получить список участников из строки
     */
    public function participantsFromString(string $participantsString): array
    {
        if (empty($participantsString)) {
            return [];
        }
        
        return array_filter(
            array_map('trim', explode("\n", $participantsString))
        );
    }
}
