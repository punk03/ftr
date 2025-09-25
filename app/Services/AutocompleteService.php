<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Collection;

class AutocompleteService
{
    /**
     * Получить список названий коллективов для автозаполнения
     */
    public function getCollectiveNames(string $query = ''): Collection
    {
        $query = Group::select('collective_name')
            ->whereNotNull('collective_name')
            ->where('collective_name', '!=', '');
            
        if (!empty($query)) {
            $query->where('collective_name', 'LIKE', "%{$query}%");
        }
        
        return $query->distinct()
            ->orderBy('collective_name')
            ->limit(20)
            ->pluck('collective_name');
    }

    /**
     * Получить список руководителей для автозаполнения
     */
    public function getLeaders(string $query = ''): Collection
    {
        $query = Group::select('leaders')
            ->whereNotNull('leaders')
            ->where('leaders', '!=', '');
            
        if (!empty($query)) {
            $query->where('leaders', 'LIKE', "%{$query}%");
        }
        
        $leaders = $query->distinct()
            ->pluck('leaders')
            ->flatMap(function ($leadersString) {
                // Разбиваем строку с несколькими руководителями
                return array_map('trim', explode(',', $leadersString));
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
            
        return $leaders->take(20);
    }

    /**
     * Получить список тренеров для автозаполнения
     */
    public function getTrainers(string $query = ''): Collection
    {
        $query = Group::select('trainers')
            ->whereNotNull('trainers')
            ->where('trainers', '!=', '');
            
        if (!empty($query)) {
            $query->where('trainers', 'LIKE', "%{$query}%");
        }
        
        $trainers = $query->distinct()
            ->pluck('trainers')
            ->flatMap(function ($trainersString) {
                // Разбиваем строку с несколькими тренерами
                return array_map('trim', explode(',', $trainersString));
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
            
        return $trainers->take(20);
    }

    /**
     * Получить список принадлежностей коллективов для автозаполнения
     */
    public function getAccessories(string $query = ''): Collection
    {
        $query = Group::select('accessory')
            ->whereNotNull('accessory')
            ->where('accessory', '!=', '');
            
        if (!empty($query)) {
            $query->where('accessory', 'LIKE', "%{$query}%");
        }
        
        return $query->distinct()
            ->orderBy('accessory')
            ->limit(20)
            ->pluck('accessory');
    }

    /**
     * Получить все данные для автозаполнения
     */
    public function getAllAutocompleteData(string $query = ''): array
    {
        return [
            'collective_names' => $this->getCollectiveNames($query),
            'leaders' => $this->getLeaders($query),
            'trainers' => $this->getTrainers($query),
            'accessories' => $this->getAccessories($query),
        ];
    }
}
