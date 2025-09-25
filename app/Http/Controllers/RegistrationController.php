<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Diploma;
use App\Models\Medal;
use App\Services\ParticipantListService;
use App\Services\PricingService;
use App\Services\AutocompleteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    protected ParticipantListService $participantService;
    protected PricingService $pricingService;
    protected AutocompleteService $autocompleteService;

    public function __construct(
        ParticipantListService $participantService,
        PricingService $pricingService,
        AutocompleteService $autocompleteService
    ) {
        $this->participantService = $participantService;
        $this->pricingService = $pricingService;
        $this->autocompleteService = $autocompleteService;
        
        $this->middleware('auth');
        $this->middleware('role:admin,registrar');
    }

    /**
     * Показать форму регистрации
     */
    public function create()
    {
        $events = Event::where('status', 'active')->get();
        $autocompleteData = $this->autocompleteService->getAllAutocompleteData();
        
        return view('registration.create', compact('events', 'autocompleteData'));
    }

    /**
     * Сохранить регистрацию
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'collective_name' => 'required|string|max:191',
            'accessory' => 'nullable|string|max:191',
            'leaders' => 'required|string|max:191',
            'trainers' => 'nullable|string|max:191',
            'participants' => 'required|string',
            'nomination_id' => 'required|exists:nominations,id',
            'discipline_id' => 'required|exists:disciplines,id',
            'age_id' => 'required|exists:ages,id',
            'category_id' => 'nullable|exists:categories,id',
            'dance_name' => 'required|string|max:191',
            'duration' => 'required|string',
            'diploma_quantity' => 'nullable|integer|min:0',
            'diploma_participants' => 'nullable|string',
            'medal_quantity' => 'nullable|integer|min:0',
            'medal_participants' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            // Обрабатываем список участников
            $participants = $this->participantService->cleanParticipantList($request->participants);
            $participantCount = $this->participantService->countParticipants($participants);
            
            // Проверяем соответствие номинации
            if (!$this->participantService->validateNominationCount($participants, $request->nomination_id)) {
                return back()->withErrors(['participants' => 'Количество участников не соответствует выбранной номинации']);
            }
            
            // Создаем группу
            $group = Group::create([
                'collective_name' => $request->collective_name,
                'accessory' => $request->accessory,
                'leaders' => $request->leaders,
                'trainers' => $request->trainers,
                'user_id' => Auth::id(),
                'event_id' => $request->event_id,
                'discipline_id' => $request->discipline_id,
                'nomination_id' => $request->nomination_id,
                'age_id' => $request->age_id,
                'category_id' => $request->category_id,
                'dance_name' => $request->dance_name,
                'duration' => $request->duration,
                'participants_count' => $participantCount,
                'status' => 1, // На рассмотрении
                'number' => $this->getNextGroupNumber($request->event_id),
            ]);
            
            // Создаем участников
            foreach ($participants as $participantName) {
                $group->participants()->create([
                    'name' => $participantName,
                ]);
            }
            
            // Рассчитываем стоимость
            $costs = $this->pricingService->calculateTotalCost(
                $request->event_id,
                $request->nomination_id,
                $participantCount,
                $request->diploma_quantity ?? 0,
                $request->medal_quantity ?? 0
            );
            
            // Создаем записи об оплатах
            if ($costs['participation'] > 0) {
                Payment::create([
                    'group_id' => $group->id,
                    'type' => 'participation',
                    'amount' => $costs['participation'],
                    'quantity' => $participantCount,
                    'status' => 'pending',
                ]);
            }
            
            // Создаем дипломы
            if ($request->diploma_quantity > 0) {
                Diploma::create([
                    'group_id' => $group->id,
                    'quantity' => $request->diploma_quantity,
                    'price_per_diploma' => $costs['diplomas'] / $request->diploma_quantity,
                    'total_amount' => $costs['diplomas'],
                    'participants_list' => $request->diploma_participants,
                ]);
                
                Payment::create([
                    'group_id' => $group->id,
                    'type' => 'diploma',
                    'amount' => $costs['diplomas'],
                    'quantity' => $request->diploma_quantity,
                    'status' => 'pending',
                ]);
            }
            
            // Создаем медали
            if ($request->medal_quantity > 0) {
                Medal::create([
                    'group_id' => $group->id,
                    'quantity' => $request->medal_quantity,
                    'price_per_medal' => $costs['medals'] / $request->medal_quantity,
                    'total_amount' => $costs['medals'],
                    'participants_list' => $request->medal_participants,
                ]);
                
                Payment::create([
                    'group_id' => $group->id,
                    'type' => 'medal',
                    'amount' => $costs['medals'],
                    'quantity' => $request->medal_quantity,
                    'status' => 'pending',
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('registrations.show', $group->id)
                ->with('success', 'Регистрация успешно создана!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors(['error' => 'Произошла ошибка при создании регистрации: ' . $e->getMessage()]);
        }
    }

    /**
     * Показать регистрацию
     */
    public function show(Group $group)
    {
        $group->load(['participants', 'payments', 'diplomas', 'medals']);
        
        return view('registration.show', compact('group'));
    }

    /**
     * Получить данные для автозаполнения
     */
    public function autocomplete(Request $request)
    {
        $query = $request->get('query', '');
        
        return response()->json(
            $this->autocompleteService->getAllAutocompleteData($query)
        );
    }

    /**
     * Рассчитать стоимость
     */
    public function calculateCost(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'nomination_id' => 'required|exists:nominations,id',
            'participant_count' => 'required|integer|min:1',
            'diploma_quantity' => 'nullable|integer|min:0',
            'medal_quantity' => 'nullable|integer|min:0',
        ]);
        
        $costs = $this->pricingService->calculateTotalCost(
            $request->event_id,
            $request->nomination_id,
            $request->participant_count,
            $request->diploma_quantity ?? 0,
            $request->medal_quantity ?? 0
        );
        
        return response()->json($costs);
    }

    /**
     * Получить следующий номер группы для мероприятия
     */
    private function getNextGroupNumber(int $eventId): int
    {
        $lastNumber = Group::where('event_id', $eventId)
            ->max('number');
            
        return ($lastNumber ?? 0) + 1;
    }
}
