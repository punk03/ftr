<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventStatisticsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function collection()
    {
        return $this->event->groups()->with([
            'participants',
            'payments',
            'nomination',
            'discipline',
            'age',
            'category',
            'user'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'Номер заявки',
            'Название коллектива',
            'Принадлежность',
            'Руководители',
            'Тренеры',
            'Номинация',
            'Дисциплина',
            'Возрастная категория',
            'Категория',
            'Название танца',
            'Количество участников',
            'Длительность',
            'Статус',
            'Стоимость участия',
            'Количество дипломов',
            'Стоимость дипломов',
            'Количество медалей',
            'Стоимость медалей',
            'Общая стоимость',
            'Статус оплаты',
            'Дата регистрации',
            'Регистратор',
        ];
    }

    public function map($group): array
    {
        $participationPayment = $group->payments->where('type', 'participation')->first();
        $diplomaPayment = $group->payments->where('type', 'diploma')->first();
        $medalPayment = $group->payments->where('type', 'medal')->first();
        
        $totalCost = $group->payments->sum('amount');
        $isPaid = $group->payments->where('status', 'paid')->count() === $group->payments->count();
        
        return [
            $group->number,
            $group->collective_name,
            $group->accessory,
            $group->leaders,
            $group->trainers,
            $group->nomination->name ?? '',
            $group->discipline->name ?? '',
            $group->age->name ?? '',
            $group->category->name ?? '',
            $group->dance_name,
            $group->participants_count,
            $group->duration,
            $this->getStatusLabel($group->status),
            $participationPayment ? $participationPayment->amount : 0,
            $group->diplomas->sum('quantity'),
            $diplomaPayment ? $diplomaPayment->amount : 0,
            $group->medals->sum('quantity'),
            $medalPayment ? $medalPayment->amount : 0,
            $totalCost,
            $isPaid ? 'Оплачено' : 'Ожидает оплаты',
            $group->created_at->format('d.m.Y H:i'),
            $group->user->name ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return "Статистика по мероприятию: {$this->event->name}";
    }

    private function getStatusLabel($status): string
    {
        return match($status) {
            0 => 'Принята',
            1 => 'На рассмотрении',
            2 => 'Отклонена',
            default => 'Неизвестно'
        };
    }
}
