<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Models\BackupSetting;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Главная страница админ-панели
     */
    public function dashboard()
    {
        $stats = [
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 'active')->count(),
            'total_groups' => Group::count(),
            'total_users' => User::count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'total_payments' => Payment::where('status', 'paid')->sum('amount'),
        ];
        
        $recent_groups = Group::with(['event', 'user'])
            ->latest()
            ->limit(10)
            ->get();
            
        return view('admin.dashboard', compact('stats', 'recent_groups'));
    }

    /**
     * Управление мероприятиями
     */
    public function events()
    {
        $events = Event::withCount('groups')->latest()->get();
        return view('admin.events', compact('events'));
    }

    /**
     * Создание/редактирование мероприятия
     */
    public function eventForm(Request $request, Event $event = null)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|string|max:191',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'diploma_price' => 'nullable|numeric|min:0',
                'medal_price' => 'nullable|numeric|min:0',
                'status' => 'required|in:active,inactive',
            ]);
            
            $data = $request->only([
                'name', 'description', 'start_date', 'end_date',
                'diploma_price', 'medal_price', 'status'
            ]);
            
            if ($event) {
                $event->update($data);
                $message = 'Мероприятие обновлено';
            } else {
                $event = Event::create($data);
                $message = 'Мероприятие создано';
            }
            
            return redirect()->route('admin.events')->with('success', $message);
        }
        
        return view('admin.event-form', compact('event'));
    }

    /**
     * Управление пользователями
     */
    public function users()
    {
        $users = User::latest()->get();
        return view('admin.users', compact('users'));
    }

    /**
     * Редактирование пользователя
     */
    public function editUser(Request $request, User $user)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|string|max:191',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'role' => 'required|in:admin,registrar,statistician',
                'city' => 'required|string|max:191',
                'phone' => 'required|string|max:191',
            ]);
            
            $user->update($request->only([
                'name', 'email', 'role', 'city', 'phone'
            ]));
            
            return redirect()->route('admin.users')->with('success', 'Пользователь обновлен');
        }
        
        return view('admin.user-form', compact('user'));
    }

    /**
     * Настройки резервного копирования
     */
    public function backupSettings(Request $request)
    {
        $settings = BackupSetting::getSettings();
        
        if ($request->isMethod('post')) {
            $request->validate([
                'enabled' => 'boolean',
                'interval_hours' => 'required|integer|min:1|max:168',
                'retention_days' => 'required|integer|min:1|max:365',
                'backup_path' => 'required|string|max:500',
            ]);
            
            $settings->update($request->only([
                'enabled', 'interval_hours', 'retention_days', 'backup_path'
            ]));
            
            return redirect()->route('admin.backup-settings')->with('success', 'Настройки обновлены');
        }
        
        return view('admin.backup-settings', compact('settings'));
    }

    /**
     * Создать резервную копию вручную
     */
    public function createBackup()
    {
        try {
            \Artisan::call('backup:create');
            $output = \Artisan::output();
            
            return redirect()->back()->with('success', 'Резервная копия создана: ' . $output);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ошибка при создании резервной копии: ' . $e->getMessage());
        }
    }

    /**
     * Все регистрации
     */
    public function registrations()
    {
        $groups = Group::with(['event', 'user', 'payments'])
            ->latest()
            ->paginate(20);
            
        return view('admin.registrations', compact('groups'));
    }

    /**
     * Статистика по мероприятиям
     */
    public function statistics()
    {
        $eventStats = Event::withCount(['groups', 'participants'])
            ->get()
            ->map(function ($event) {
                $event->total_payments = Payment::whereHas('group', function ($query) use ($event) {
                    $query->where('event_id', $event->id);
                })->where('status', 'paid')->sum('amount');
                
                return $event;
            });
            
        $nominationStats = DB::table('groups')
            ->join('nominations', 'groups.nomination_id', '=', 'nominations.id')
            ->select('nominations.name', DB::raw('COUNT(*) as count'))
            ->groupBy('nominations.id', 'nominations.name')
            ->get();
            
        $disciplineStats = DB::table('groups')
            ->join('disciplines', 'groups.discipline_id', '=', 'disciplines.id')
            ->select('disciplines.name', DB::raw('COUNT(*) as count'))
            ->groupBy('disciplines.id', 'disciplines.name')
            ->get();
            
        return view('admin.statistics', compact('eventStats', 'nominationStats', 'disciplineStats'));
    }
}
