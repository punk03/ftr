<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Group;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EventStatisticsExport;

class StatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,statistician');
    }

    /**
     * Главная страница статистики
     */
    public function dashboard()
    {
        $stats = [
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 'active')->count(),
            'total_groups' => Group::count(),
            'total_participants' => DB::table('participants')->count(),
            'total_payments' => Payment::where('status', 'paid')->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->sum('amount'),
        ];
        
        // Статистика по месяцам
        $monthlyStats = DB::table('groups')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as groups_count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
            
        return view('statistics.dashboard', compact('stats', 'monthlyStats'));
    }

    /**
     * Статистика по мероприятию
     */
    public function eventStatistics(Event $event)
    {
        $groups = Group::where('event_id', $event->id)
            ->with(['participants', 'payments'])
            ->get();
            
        $stats = [
            'total_groups' => $groups->count(),
            'total_participants' => $groups->sum('participants_count'),
            'total_payments' => Payment::whereHas('group', function ($query) use ($event) {
                $query->where('event_id', $event->id);
            })->where('status', 'paid')->sum('amount'),
        ];
        
        // Статистика по номинациям
        $nominationStats = DB::table('groups')
            ->join('nominations', 'groups.nomination_id', '=', 'nominations.id')
            ->where('groups.event_id', $event->id)
            ->select('nominations.name', DB::raw('COUNT(*) as count'))
            ->groupBy('nominations.id', 'nominations.name')
            ->get();
            
        // Статистика по дисциплинам
        $disciplineStats = DB::table('groups')
            ->join('disciplines', 'groups.discipline_id', '=', 'disciplines.id')
            ->where('groups.event_id', $event->id)
            ->select('disciplines.name', DB::raw('COUNT(*) as count'))
            ->groupBy('disciplines.id', 'disciplines.name')
            ->get();
            
        // Статистика по возрастным категориям
        $ageStats = DB::table('groups')
            ->join('ages', 'groups.age_id', '=', 'ages.id')
            ->where('groups.event_id', $event->id)
            ->select('ages.name', DB::raw('COUNT(*) as count'))
            ->groupBy('ages.id', 'ages.name')
            ->get();
            
        return view('statistics.event', compact('event', 'stats', 'nominationStats', 'disciplineStats', 'ageStats'));
    }

    /**
     * Экспорт статистики в Excel
     */
    public function exportEventStatistics(Event $event)
    {
        return Excel::download(new EventStatisticsExport($event), "statistics_event_{$event->id}.xlsx");
    }

    /**
     * Финансовая отчетность
     */
    public function financialReport(Request $request)
    {
        $query = Payment::where('status', 'paid')
            ->with(['group.event']);
            
        if ($request->filled('event_id')) {
            $query->whereHas('group', function ($q) use ($request) {
                $q->where('event_id', $request->event_id);
            });
        }
        
        if ($request->filled('date_from')) {
            $query->where('paid_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('paid_at', '<=', $request->date_to);
        }
        
        $payments = $query->latest('paid_at')->get();
        
        $summary = [
            'total_amount' => $payments->sum('amount'),
            'participation_amount' => $payments->where('type', 'participation')->sum('amount'),
            'diploma_amount' => $payments->where('type', 'diploma')->sum('amount'),
            'medal_amount' => $payments->where('type', 'medal')->sum('amount'),
        ];
        
        $events = Event::all();
        
        return view('statistics.financial', compact('payments', 'summary', 'events'));
    }

    /**
     * Отчет по номинациям
     */
    public function nominationReport(Request $request)
    {
        $query = DB::table('groups')
            ->join('nominations', 'groups.nomination_id', '=', 'nominations.id')
            ->join('events', 'groups.event_id', '=', 'events.id')
            ->select(
                'events.name as event_name',
                'nominations.name as nomination_name',
                DB::raw('COUNT(*) as groups_count'),
                DB::raw('SUM(groups.participants_count) as participants_count')
            );
            
        if ($request->filled('event_id')) {
            $query->where('groups.event_id', $request->event_id);
        }
        
        $report = $query->groupBy('events.id', 'events.name', 'nominations.id', 'nominations.name')
            ->orderBy('events.name')
            ->orderBy('nominations.name')
            ->get();
            
        $events = Event::all();
        
        return view('statistics.nomination', compact('report', 'events'));
    }

    /**
     * Отчет по дисциплинам
     */
    public function disciplineReport(Request $request)
    {
        $query = DB::table('groups')
            ->join('disciplines', 'groups.discipline_id', '=', 'disciplines.id')
            ->join('events', 'groups.event_id', '=', 'events.id')
            ->select(
                'events.name as event_name',
                'disciplines.name as discipline_name',
                DB::raw('COUNT(*) as groups_count'),
                DB::raw('SUM(groups.participants_count) as participants_count')
            );
            
        if ($request->filled('event_id')) {
            $query->where('groups.event_id', $request->event_id);
        }
        
        $report = $query->groupBy('events.id', 'events.name', 'disciplines.id', 'disciplines.name')
            ->orderBy('events.name')
            ->orderBy('disciplines.name')
            ->get();
            
        $events = Event::all();
        
        return view('statistics.discipline', compact('report', 'events'));
    }
}
